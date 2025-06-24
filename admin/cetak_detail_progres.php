<?php
session_start();
include("../config/koneksi_mysql.php");

if (!isset($_SESSION['id_user'])) {
    die("Akses ditolak.");
}

$proyek_id = isset($_GET['proyek_id']) ? (int)$_GET['proyek_id'] : 0;
if ($proyek_id === 0) {
    die("ID Proyek tidak valid.");
}

// Fungsi konversi ke angka romawi
function toRoman($num) {
    $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $result = '';
    foreach ($map as $roman => $value) { while ($num >= $value) { $result .= $roman; $num -= $value; } }
    return $result;
}

// 1. Ambil Info Utama Proyek
$info_sql = "
    SELECT 
        mpe.nama_perumahan,
        mpr.kavling,
        mm.nama_mandor,
        u.nama_lengkap as pj_proyek,
        ru.id_rab_upah,
        ru.total_rab_upah,
        ru.tanggal_mulai,
        ru.tanggal_selesai
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

// 2. Ambil semua termin pengajuan untuk proyek ini
$termins = [];
$termins_sql = "SELECT pu.id_pengajuan_upah, pu.tanggal_pengajuan FROM pengajuan_upah pu WHERE pu.id_rab_upah = $id_rab_upah ORDER BY pu.tanggal_pengajuan, pu.id_pengajuan_upah";
$termins_result = mysqli_query($koneksi, $termins_sql);
if($termins_result){ while($row = mysqli_fetch_assoc($termins_result)){ $termins[] = $row; } }

// 3. Ambil semua data progres
$report_data = [];
$detail_sql = "
    SELECT 
        dr.id_detail_rab_upah,
        k.nama_kategori,
        mp.uraian_pekerjaan,
        dr.sub_total AS nilai_anggaran,
        dpu.id_pengajuan_upah,
        dpu.progress_pekerjaan,
        pu.status_pengajuan
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

if (!$result_detail) {
    die("Gagal mengambil data detail progres: " . mysqli_error($koneksi));
}

// Proses data mentah menjadi struktur yang mudah ditampilkan
if ($result_detail) {
    while ($row = mysqli_fetch_assoc($result_detail)) {
        $id = $row['id_detail_rab_upah'];
        if (!isset($report_data[$id])) {
            $report_data[$id] = [
                'kategori' => $row['nama_kategori'] ?? 'Tanpa Kategori',
                'uraian' => $row['uraian_pekerjaan'] ?? 'Pekerjaan Tidak Ditemukan',
                'anggaran' => (float)($row['nilai_anggaran'] ?? 0),
                'progres_per_termin' => [],
                'total_dibayar' => 0,
                'total_progress_dibayar' => 0
            ];
        }
        if ($row['id_pengajuan_upah']) {
            $report_data[$id]['progres_per_termin'][$row['id_pengajuan_upah']] = (float)$row['progress_pekerjaan'];
            if (strtolower($row['status_pengajuan']) === 'dibayar') {
                 $report_data[$id]['total_progress_dibayar'] += (float)$row['progress_pekerjaan'];
                 $report_data[$id]['total_dibayar'] += ($report_data[$id]['anggaran'] * (float)$row['progress_pekerjaan']) / 100;
            }
        }
    }
}

// [BARU] Ambil nama Direktur untuk TTD
$sql_direktur = "SELECT nama_lengkap FROM master_user WHERE role = 'direktur' LIMIT 1";
$result_direktur = mysqli_query($koneksi, $sql_direktur);
$nama_direktur = "....................."; 
if ($result_direktur && mysqli_num_rows($result_direktur) > 0) {
    $direktur_info = mysqli_fetch_assoc($result_direktur);
    $nama_direktur = $direktur_info['nama_lengkap'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Detail Progres Proyek</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <style>
        body { font-family: 'Times New Roman', Times, serif; background-color: #fff; color: #000; }
        .container { max-width: 800px; margin: auto; }
        .kop-surat { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
        .kop-surat img { width: 100px; height: auto; margin-right: 20px; }
        .kop-surat .kop-text { text-align: center; flex-grow: 1; }
        .kop-surat h3, .kop-surat p { margin: 0; }
        .kop-surat h3 { font-size: 24px; font-weight: bold; }
        .kop-surat p { font-size: 14px; }
        .report-title { text-align: center; margin-bottom: 20px; font-weight: bold; text-decoration: underline; font-size: 18px;}
        .info-section .table { border: none !important; }
        .info-section .table td { border: none !important; padding: 2px 0; font-size: 12px; }
        .info-section td:first-child { width: 140px; font-weight: bold;}
        table.report { width: 100%; border-collapse: collapse; page-break-inside: auto; font-size: 10px; }
        table.report tr { page-break-inside: avoid; page-break-after: auto; }
        table.report th, table.report td { border: 1px solid black; padding: 4px; word-wrap: break-word; }
        table.report th { background-color: #e9ecef; text-align: center; vertical-align: middle; }
        .category-row td { background-color: #f8f9fa; font-weight: bold; }
        .text-end { text-align: right; } .text-center { text-align: center; }
        /* [BARU] CSS untuk Tanda Tangan */
        .signature-section { margin-top: 50px; width: 100%; }
        .signature-box { text-align: center; width: 33.33%; float: left; }
        .signature-box .name { margin-top: 60px; font-weight: bold; text-decoration: underline; }
        .clearfix { clear: both; }

        @media print { 
            .no-print { display: none; } 
            @page { 
                size: A4 portrait;
                margin: 15mm; 
            } 
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <button class="no-print" onclick="window.print()" style="margin-bottom:15px; padding: 8px 12px;">Cetak Laporan</button>
        <div class="kop-surat">
            <img src="assets/img/logo/LOGO PT.jpg" alt="Logo Perusahaan" onerror="this.style.display='none'">
            <div class="kop-text">
                <h3>PT. HASTA BANGUN NUSANTARA</h3>
                <p>Jalan Cakraninggrat, Kauman, Kabupaten Ponorogo, Jawa Timur 63414</p>
                <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
            </div>
        </div>
        <h5 class="report-title">LAPORAN DETAIL PROGRES PROYEK</h5>
        
        <div class="info-section mb-4">
            <div class="row">
                <div class="col-7">
                    <table class="table table-sm">
                        <tr><td>ID RAB</td><td>: <?= 'RABU' . date('ym', strtotime($proyek_info['tanggal_mulai'])) . $proyek_info['id_rab_upah'] ?></td></tr>
                        <tr><td>Nama Perumahan</td><td>: <?= htmlspecialchars($proyek_info['nama_perumahan']) ?></td></tr>
                        <tr><td>Kavling / Blok</td><td>: <?= htmlspecialchars($proyek_info['kavling']) ?></td></tr>
                    </table>
                </div>
                <div class="col-5">
                    <table class="table table-sm">
                        <tr><td>Tanggal Mulai</td><td>: <?= date("d F Y", strtotime($proyek_info['tanggal_mulai'])) ?></td></tr>
                        <tr><td>Tanggal Selesai</td><td>: <?= date("d F Y", strtotime($proyek_info['tanggal_selesai'])) ?></td></tr>
                        <tr><td>Mandor</td><td>: <?= htmlspecialchars($proyek_info['nama_mandor']) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <table class="report">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 3%;">No</th>
                    <th rowspan="2" style="width: 25%;">Uraian Pekerjaan</th>
                    <th rowspan="2" class="text-end">Nilai Anggaran (Rp)</th>
                    <?php if(!empty($termins)): ?>
                    <th colspan="<?= count($termins) ?>">Progres per Termin (%)</th>
                    <?php endif; ?>
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
                    $grand_total_anggaran = 0; $grand_total_dibayar = 0; $prev_kategori = null; $no_kategori = 0;
                    foreach($report_data as $item):
                        if ($prev_kategori !== $item['kategori']) {
                            $no_kategori++;
                            echo "<tr class='category-row'><td class='text-center'>" . toRoman($no_kategori) . "</td><td colspan='" . (6 + count($termins)) . "'>" . htmlspecialchars($item['kategori']) . "</td></tr>";
                            $prev_kategori = $item['kategori'];
                            $no = 1;
                        }
                        $grand_total_anggaran += $item['anggaran'];
                        $grand_total_dibayar += $item['total_dibayar'];
                ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
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
                <?php endforeach; ?>
                    <tr style="font-weight: bold; background-color: #e9ecef;">
                        <td colspan="2" class="text-center">TOTAL KESELURUHAN</td>
                        <td class="text-end"><?= number_format($grand_total_anggaran, 0, ',', '.') ?></td>
                        <td colspan="<?= count($termins) + 1 ?>"></td>
                        <td class="text-end"><?= number_format($grand_total_dibayar, 0, ',', '.') ?></td>
                        <td class="text-end"><?= number_format($grand_total_anggaran - $grand_total_dibayar, 0, ',', '.') ?></td>
                    </tr>
                <?php else: ?>
                    <tr><td colspan="<?= (7 + count($termins)) ?>" class="text-center">Belum ada detail pekerjaan atau pengajuan untuk proyek ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="clearfix"></div>

        <!-- [DIUBAH] Tanda Tangan yang Lebih Rapi -->
        <div class="signature-section">
            <div style="width: 33.33%; float: right; text-align: center;">
                <p>Ponorogo, <?= strftime('%d %B %Y') ?></p>
            </div>
            <div class="clearfix" style="margin-bottom: 20px;"></div>

            <div class="signature-box" style="width: 33.33%; float: left;">
                <p>Diajukan oleh,</p>
                <div class="name"><?= htmlspecialchars($proyek_info['pj_proyek']) ?></div>
                <p>PJ Proyek</p>
            </div>
            <div class="signature-box" style="width: 33.33%; float: left;">
                <p>Mengetahui,</p>
                <div class="name"><?= htmlspecialchars($proyek_info['nama_mandor']) ?></div>
                <p>Mandor</p>
            </div>
            <div class="signature-box" style="width: 33.33%; float: left;">
                <p>Disetujui oleh,</p>
                <div class="name">Ir. <?= htmlspecialchars($nama_direktur) ?></div>
                <p>Direktur Utama</p>
            </div>
        </div>
    </div>
    <script>
        window.onload = function() { window.print(); }
    </script>
</body>
</html>
