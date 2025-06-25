<?php
session_start();
include("../config/koneksi_mysql.php");

// Proteksi dan Validasi
if (!isset($_SESSION['id_user'])) {
    die("Akses ditolak.");
}
$proyek_id = isset($_GET['proyek_id']) ? (int)$_GET['proyek_id'] : 0;
if ($proyek_id === 0) {
    die("ID Proyek tidak valid.");
}

// Fungsi
function toRoman($num) {
    $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $result = '';
    foreach ($map as $roman => $value) { while ($num >= $value) { $result .= $roman; $num -= $value; } }
    return $result;
}

// 1. Data Proyek
$info_sql = "
    SELECT 
        mpe.nama_perumahan, mpr.kavling, mm.nama_mandor, u.nama_lengkap as pj_proyek,
        ru.id_rab_upah, ru.total_rab_upah, ru.tanggal_mulai, ru.tanggal_selesai
    FROM master_proyek mpr
    LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
    LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
    LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
    LEFT JOIN rab_upah ru ON mpr.id_proyek = ru.id_proyek
    WHERE mpr.id_proyek = $proyek_id
";
$proyek_info_result = mysqli_query($koneksi, $info_sql);
if(!$proyek_info_result || mysqli_num_rows($proyek_info_result) == 0){ die("Proyek tidak ditemukan atau belum memiliki RAB."); }
$proyek_info = mysqli_fetch_assoc($proyek_info_result);
$id_rab_upah = $proyek_info['id_rab_upah'];

// 2. Data Termin
$termins = [];
if ($id_rab_upah) {
    $termins_sql = "SELECT pu.id_pengajuan_upah, pu.tanggal_pengajuan FROM pengajuan_upah pu WHERE pu.id_rab_upah = $id_rab_upah ORDER BY pu.tanggal_pengajuan, pu.id_pengajuan_upah";
    $termins_result = mysqli_query($koneksi, $termins_sql);
    if($termins_result){ while($row = mysqli_fetch_assoc($termins_result)){ $termins[] = $row; } }
}

// 3. Data Progres
$report_data = [];
$detail_sql = "
    SELECT 
        dr.id_detail_rab_upah, k.nama_kategori, mp.uraian_pekerjaan, dr.sub_total AS nilai_anggaran,
        dpu.id_pengajuan_upah, dpu.progress_pekerjaan, pu.status_pengajuan
    FROM detail_rab_upah dr
    INNER JOIN rab_upah ru ON dr.id_rab_upah = ru.id_rab_upah
    LEFT JOIN master_pekerjaan mp ON dr.id_pekerjaan = mp.id_pekerjaan
    LEFT JOIN master_kategori k ON dr.id_kategori = k.id_kategori
    LEFT JOIN detail_pengajuan_upah dpu ON dr.id_detail_rab_upah = dpu.id_detail_rab_upah
    LEFT JOIN pengajuan_upah pu ON dpu.id_pengajuan_upah = pu.id_pengajuan_upah
    WHERE ru.id_proyek = $proyek_id
    ORDER BY k.id_kategori, dr.id_detail_rab_upah, pu.tanggal_pengajuan
";
$result_detail = mysqli_query($koneksi, $detail_sql);
if (!$result_detail) { die("Gagal mengambil data detail progres: " . mysqli_error($koneksi)); }

// Proses Data & Kalkulasi Total
$grand_total_anggaran = 0;
$grand_total_dibayar = 0;
$kategori_data = [];
if ($result_detail) {
    while ($row = mysqli_fetch_assoc($result_detail)) {
        $id_item = $row['id_detail_rab_upah'];
        $kategori = $row['nama_kategori'] ?? 'Tanpa Kategori';

        if (!isset($report_data[$id_item])) {
            $report_data[$id_item] = [
                'kategori' => $kategori, 'uraian' => $row['uraian_pekerjaan'] ?? 'N/A',
                'anggaran' => (float)($row['nilai_anggaran'] ?? 0), 'progres_per_termin' => [],
                'total_dibayar' => 0, 'total_progress_dibayar' => 0
            ];
        }
        if (!isset($kategori_data[$kategori])) {
             $kategori_data[$kategori] = ['anggaran' => 0, 'dibayar' => 0, 'items' => []];
        }

        if ($row['id_pengajuan_upah']) {
            $report_data[$id_item]['progres_per_termin'][$row['id_pengajuan_upah']] = (float)$row['progress_pekerjaan'];
            if (strtolower($row['status_pengajuan']) === 'dibayar') {
                $nilai_dibayar_item = ((float)$row['nilai_anggaran'] * (float)$row['progress_pekerjaan']) / 100;
                $report_data[$id_item]['total_dibayar'] += $nilai_dibayar_item;
                $report_data[$id_item]['total_progress_dibayar'] += (float)$row['progress_pekerjaan'];
            }
        }
    }
    // Kalkulasi total setelah semua data diproses
    foreach($report_data as $item) {
        $grand_total_anggaran += $item['anggaran'];
        $grand_total_dibayar += $item['total_dibayar'];
        $kategori_data[$item['kategori']]['anggaran'] += $item['anggaran'];
        $kategori_data[$item['kategori']]['dibayar'] += $item['total_dibayar'];
    }
}

$progress_keseluruhan = ($grand_total_anggaran > 0) ? ($grand_total_dibayar / $grand_total_anggaran) * 100 : 0;
$sql_direktur = "SELECT nama_lengkap FROM master_user WHERE role = 'direktur' LIMIT 1";
$nama_direktur = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_direktur))['nama_lengkap'] ?? '.....................';
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Detail Progres Proyek</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background-color: #fff; color: #000; font-size: 11px; }
        .container { max-width: 800px; margin: auto; }
.kop-surat {
    display: flex;
    align-items: center;
    border-bottom: 3px double #000;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.kop-surat img {
    width: 100px;
    height: auto;
    margin-left: 40px; /* Geser logo ke kanan */
}

.kop-surat .kop-text {
    text-align: center;
    flex-grow: 1;
}

.kop-surat h3 {
    font-size: 22px;
    font-weight: bold;
    margin: 0;
}

.kop-surat h2 {
    font-size: 18px;
    font-weight: bold;
    margin: 0;
}

.kop-surat p {
    font-size: 14px;
    margin: 0;
}

        .report-title { text-align: center; margin-bottom: 25px; font-weight: bold; text-decoration: underline; font-size: 16px;}
/* Untuk membagi dua kolom kiri-kanan */
.info-section {
    display: flex;
    justify-content: space-between;
    margin-bottom: 25px;
}

.info-section .col-left, .info-section .col-right {
    width: 48%;  /* Menyusun kolom kiri dan kanan dengan lebar yang hampir sama */
}

/* Mengatur tabel untuk informasi agar lebih rapi */
.info-section .table {
    width: 100%;
    margin-bottom: 0;
}

.info-section .table td {
    border: none !important;
    padding: 1px 0;
    font-size: 12px;
    vertical-align: top;
}

.info-section td:nth-child(1) {
    width: 140px;
    font-weight: bold;
}

.info-section td:nth-child(2) {
    width: 15px;
    text-align: center;
}

        /* [PERUBAHAN] CSS untuk Kartu Statistik yang Rapi */
        .summary-container { display: flex; justify-content: space-between; gap: 15px; margin-bottom: 25px; text-align: center; }
        .summary-box { flex: 1; border: 1px solid #ccc; padding: 10px; border-radius: 4px; }
        .summary-label { font-size: 10px; font-weight: bold; color: #666; display: block; margin-bottom: 5px; text-transform: uppercase; }
        .summary-value { font-size: 16px; font-weight: bold; color: #000; display: block; }
        .summary-value-percent { font-size: 20px; font-weight: bold; color: #0d6efd; display: block; }
        .mini-progress { height: 5px; background-color: #e9ecef; border-radius: 5px; margin-top: 8px; }
        .mini-progress-bar { height: 100%; background-color: #0d6efd; border-radius: 5px; }

        table.report { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.report th, table.report td { border: 1px solid black; padding: 4px; vertical-align: middle; }
        table.report th { background-color: #e9ecef !important; text-align: center; }
        .category-row td { background-color: #f8f9fa; font-weight: bold; }
        .text-end { text-align: right; } .text-center { text-align: center; }
        .signature-section { margin-top: 50px; width: 100%; }
        .signature-box { text-align: center; width: 33.33%; float: left; }
        .signature-box .name { margin-top: 60px; font-weight: bold; text-decoration: underline; }
        .clearfix { clear: both; }
                .tagline {
    font-style: italic;
}

        @media print { 
            .no-print { display: none; } 
            @page { 
                size: A4 portrait; 
                margin: 10mm; 
            } 
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <button class="no-print" onclick="window.print()" style="position:fixed; top:10px; right:10px; z-index:100; padding: 8px 12px;">Cetak</button>
        <!-- [DIUBAH] Menggunakan Format Kop Surat & Header dari get_pengajuan_upah.php -->
<div class="kop-surat">
    <img src="assets/img/logo/LOGO PT.jpg" alt="Logo Perusahaan" onerror="this.style.display='none'">
    <div class="kop-text">
        <h3>PT. HASTA BANGUN NUSANTARA</h3>
        <h2 class ="tagline">General Contractor & Developer</h2>
                <p>Jalan Cakraninggrat, Kauman, Kabupaten Ponorogo, Jawa Timur 63414</p>
                <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
    </div>
</div>
        <h5 class="report-title">LAPORAN DETAIL PROGRES PROYEK</h5>
        
<div class="info-section">
    <!-- Kolom Kiri -->
    <div class="col-left">
        <table class="table table-borderless table-sm">
            <tr>
                <td><strong>ID RAB</strong></td>
                <td>:</td>
                <td><?= 'RABU' . date('ym', strtotime($proyek_info['tanggal_mulai'])) . str_pad($proyek_info['id_rab_upah'], 2, '0', STR_PAD_LEFT) ?></td>
            </tr>
            <tr>
                <td><strong>Nama Perumahan</strong></td>
                <td>:</td>
                <td><?= htmlspecialchars($proyek_info['nama_perumahan']) ?></td>
            </tr>
            <tr>
                <td><strong>Kavling / Blok</strong></td>
                <td>:</td>
                <td><?= htmlspecialchars($proyek_info['kavling']) ?></td>
            </tr>
        </table>
    </div>

    <!-- Kolom Kanan -->
    <div class="col-right">
        <table class="table table-borderless table-sm">
            <tr>
                <td><strong>Tanggal Mulai</strong></td>
                <td>:</td>
                <td><?= date("d F Y", strtotime($proyek_info['tanggal_mulai'])) ?></td>
            </tr>
            <tr>
                <td><strong>Tanggal Selesai</strong></td>
                <td>:</td>
                <td><?= htmlspecialchars($proyek_info['tanggal_selesai'] ? date("d F Y", strtotime($proyek_info['tanggal_selesai'])) : '-') ?></td>
            </tr>
            <tr>
                <td><strong>Mandor</strong></td>
                <td>:</td>
                <td><?= htmlspecialchars($proyek_info['nama_mandor']) ?></td>
            </tr>
        </table>
    </div>
</div>


        <div class="summary-container">
            <div class="summary-box">
                <span class="summary-label">Progress Keseluruhan</span>
                <span class="summary-value-percent"><?= number_format($progress_keseluruhan, 2) ?>%</span>
                <div class="mini-progress"><div class="mini-progress-bar" style="width: <?= number_format($progress_keseluruhan, 2) ?>%;"></div></div>
            </div>
            <div class="summary-box">
                <span class="summary-label">Total Nilai Anggaran</span>
                <span class="summary-value">Rp <?= number_format($grand_total_anggaran, 0, ',', '.') ?></span>
            </div>
            <div class="summary-box">
                <span class="summary-label">Total Dibayarkan</span>
                <span class="summary-value">Rp <?= number_format($grand_total_dibayar, 0, ',', '.') ?></span>
            </div>
            <div class="summary-box">
                <span class="summary-label">Sisa Anggaran</span>
                <span class="summary-value">Rp <?= number_format($grand_total_anggaran - $grand_total_dibayar, 0, ',', '.') ?></span>
            </div>
        </div>

        <table class="report">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 3%;">No</th>
                    <th rowspan="2" style="width: 25%;">Uraian Pekerjaan</th>
                    <th rowspan="2" class="text-end">Anggaran (Rp)</th>
                    <?php if(!empty($termins)): ?><th colspan="<?= count($termins) ?>">Progres per Termin (%)</th><?php endif; ?>
                    <th rowspan="2" style="width: 7%;">Total<br>Progress<br>Dibayar (%)</th>
                    <th rowspan="2" class="text-end" style="width: 10%;">Total<br>Dibayar (Rp)</th>
                    <th rowspan="2" class="text-end" style="width: 10%;">Sisa<br>Anggaran (Rp)</th>
                </tr>
                <tr>
                    <?php foreach($termins as $index => $termin): ?>
                        <th class="text-center">T-<?= $index + 1 ?><br><small>(<?= date('d/m/y', strtotime($termin['tanggal_pengajuan'])) ?>)</small></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($report_data)): 
                    $current_kategori = "";
                    foreach($kategori_data as $kategori => $data_kat):
                        $no_urut_item = 1;
                        // Baris Kategori
                        echo "<tr class='category-row'><td colspan='2'><strong>" . htmlspecialchars($kategori) . "</strong></td>";
                        echo "<td class='text-end'><strong>" . number_format($data_kat['anggaran'], 0, ',', '.') . "</strong></td>";
                        echo "<td colspan='" . count($termins) . "'></td>";
                        $progress_kategori = ($data_kat['anggaran'] > 0) ? ($data_kat['dibayar'] / $data_kat['anggaran']) * 100 : 0;
                        echo "<td class='text-center'><strong>". number_format($progress_kategori, 2) ."%</strong></td>";
                        echo "<td class='text-end'><strong>" . number_format($data_kat['dibayar'], 0, ',', '.') . "</strong></td>";
                        echo "<td class='text-end'><strong>" . number_format($data_kat['anggaran'] - $data_kat['dibayar'], 0, ',', '.') . "</strong></td></tr>";
                        
                        // Item Pekerjaan dalam kategori ini
                        foreach($report_data as $item):
                            if ($item['kategori'] === $kategori):
                ?>
                                <tr>
                                    <td class="text-center"><?= $no_urut_item++ ?></td>
                                    <td><?= htmlspecialchars($item['uraian']) ?></td>
                                    <td class="text-end"><?= number_format($item['anggaran'], 0, ',', '.') ?></td>
                                    <?php foreach($termins as $termin): ?>
                                        <td class="text-center">
                                            <?= isset($item['progres_per_termin'][$termin['id_pengajuan_upah']]) ? number_format($item['progres_per_termin'][$termin['id_pengajuan_upah']], 2) . '%' : '-' ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="text-center"><?= number_format($item['total_progress_dibayar'], 2) ?>%</td>
                                    <td class="text-end"><?= number_format($item['total_dibayar'], 0, ',', '.') ?></td>
                                    <td class="text-end"><?= number_format($item['anggaran'] - $item['total_dibayar'], 0, ',', '.') ?></td>
                                </tr>
                <?php       endif;
                        endforeach;
                    endforeach; 
                ?>
                    <tr style="font-weight: bold; background-color: #e0e0e0;">
                        <td colspan="2" class="text-center">TOTAL KESELURUHAN</td>
                        <td class="text-end"><?= number_format($grand_total_anggaran, 0, ',', '.') ?></td>
                        <td colspan="<?= count($termins) + 1 ?>"></td>
                        <td class="text-end"><?= number_format($grand_total_dibayar, 0, ',', '.') ?></td>
                        <td class="text-end"><?= number_format($grand_total_anggaran - $grand_total_dibayar, 0, ',', '.') ?></td>
                    </tr>
                <?php else: ?>
                    <tr><td colspan="<?= (6 + count($termins)) ?>" class="text-center">Belum ada detail pekerjaan untuk proyek ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="clearfix"></div>
        <div class="signature-section">
            <div style="width: 33.33%; float: right; text-align: center;"><p>Ponorogo, <?= strftime('%d %B %Y') ?></p></div>
            <div class="clearfix" style="margin-bottom: 20px;"></div>
            <div class="signature-box"><p>Diajukan oleh,</p><div class="name"><?= htmlspecialchars($proyek_info['pj_proyek']) ?></div><p>PJ Proyek</p></div>
            <div class="signature-box"><p>Mengetahui,</p><div class="name"><?= htmlspecialchars($proyek_info['nama_mandor']) ?></div><p>Mandor</p></div>
            <div class="signature-box"><p>Disetujui oleh,</p><div class="name"><?= htmlspecialchars($nama_direktur) ?></div><p>Direktur Utama</p></div>
        </div>
    </div>
        <script> window.onload = function() { window.print(); } </script>

</body>
</html>