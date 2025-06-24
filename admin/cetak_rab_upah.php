<?php
session_start();
include("../config/koneksi_mysql.php");

// Proteksi halaman dan validasi input
if (!isset($_SESSION['id_user'])) {
    die("Akses ditolak.");
}
if (!isset($_GET['id_rab_upah'])) {
    die("ID RAB Upah tidak valid.");
}
$id_rab_upah = (int)$_GET['id_rab_upah'];

// 1. Query untuk mengambil info header yang lengkap
$sql_rab_info = "
    SELECT 
        ru.id_rab_upah, ru.id_proyek, ru.tanggal_mulai, ru.tanggal_selesai, ru.total_rab_upah,
        mpe.nama_perumahan, mpr.kavling, mm.nama_mandor, u.nama_lengkap AS pj_proyek
    FROM rab_upah ru
    JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
    LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
    LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
    LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
    WHERE ru.id_rab_upah = $id_rab_upah
";
$rab_result = mysqli_query($koneksi, $sql_rab_info);
if (!$rab_result || mysqli_num_rows($rab_result) == 0) {
    die("Data RAB Upah tidak ditemukan.");
}
$rab_info = mysqli_fetch_assoc($rab_result);

// Logika untuk memformat ID RAB
$tahun = date('Y', strtotime($rab_info['tanggal_mulai']));
$bulan = date('m', strtotime($rab_info['tanggal_mulai']));
$formatted_id_rab = 'RABP' . substr($tahun, -2) . $bulan . $rab_info['id_proyek'] . $rab_info['id_rab_upah'];

// 2. Query untuk mengambil detail pekerjaan
$sql_detail = "
    SELECT 
        d.id_detail_rab_upah, k.nama_kategori, mp.uraian_pekerjaan, d.volume, d.satuan, d.harga_satuan, d.sub_total
    FROM detail_rab_upah d 
    LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan 
    LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori 
    WHERE d.id_rab_upah = $id_rab_upah
    ORDER BY k.id_kategori, mp.uraian_pekerjaan
";
$detail_result = mysqli_query($koneksi, $sql_detail);
if (!$detail_result) {
    die("Gagal mengambil detail pekerjaan: " . mysqli_error($koneksi));
}

// 3. Ambil nama Direktur & PJ Proyek untuk TTD
$sql_direktur = "SELECT nama_lengkap FROM master_user WHERE role = 'direktur' LIMIT 1";
$nama_direktur = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_direktur))['nama_lengkap'] ?? '.....................';
$pj_proyek = $rab_info['pj_proyek'];
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');

function toRoman($num) {
    $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $result = '';
    foreach ($map as $roman => $value) { while ($num >= $value) { $result .= $roman; $num -= $value; } }
    return $result;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak RAB Upah #<?= $id_rab_upah ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <style>
        body { font-family: 'Times New Roman', Times, serif; background-color: #fff; color: #000; font-size: 11px;}
        .container { max-width: 800px; margin: auto; }
        .kop-surat { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
        .kop-surat img { width: 100px; height: auto; margin-right: 20px; }
        .kop-surat .kop-text { text-align: center; flex-grow: 1; }
        .kop-surat h3 { font-size: 22px; font-weight: bold; margin: 0; }
        .kop-surat p { font-size: 14px; margin: 0; }
        .report-title { text-align: center; margin-bottom: 20px; font-weight: bold; text-decoration: underline; font-size: 16px;}
        .info-section .table { border: none !important; margin-bottom: 0; }
        .info-section .table td { border: none !important; padding: 1px 0; font-size: 12px; vertical-align: top; }
        .info-section td:nth-child(1) { width: 140px; font-weight: bold;}
        table.report { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.report th, table.report td { border: 1px solid black; padding: 4px; vertical-align: middle; }
        table.report th { background-color: #e9ecef !important; text-align: center; }
        .category-row td { background-color: #f8f9fa; font-weight: bold; }
        .text-end { text-align: right; } .text-center { text-align: center; }
        .signature-section { margin-top: 30px; width: 100%; }
        .signature-box { text-align: center; width: 33.33%; float: left; }
        .signature-box .name { margin-top: 50px; font-weight: bold; text-decoration: underline; }
        .clearfix { clear: both; }

        @media print { 
            .no-print { display: none; } 
            @page { size: A4 portrait; margin: 10mm; } 
        }
    </style>
</head>
<body>
    <div class="container my-3">
        <button class="no-print" onclick="window.print()" style="margin-bottom:15px; padding: 8px 12px;">Cetak</button>
        <div class="kop-surat">
            <img src="assets/img/logo/LOGO PT.jpg" alt="Logo Perusahaan" onerror="this.style.display='none'">
            <div class="kop-text">
                <h3>PT. HASTA BANGUN NUSANTARA</h3>
                <p>Jalan Cokroaminoto 63414 Ponorogo Jawa Timur</p>
                <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
            </div>
        </div>
        <h5 class="report-title">RENCANA ANGGARAN BIAYA (RAB) UPAH</h5>

        <div class="info-section mb-4">
            <div class="row">
                <div class="col-7"><table class="table table-sm">
                    <tr><td>ID RAB</td><td>: <?= htmlspecialchars($formatted_id_rab) ?></td></tr>
                    <tr><td>Nama Perumahan</td><td>: <?= htmlspecialchars($rab_info['nama_proyek']) ?></td></tr>
                    <tr><td>PJ Proyek</td><td>: <?= htmlspecialchars($rab_info['pj_proyek']) ?></td></tr>
                </table></div>
                <div class="col-5"><table class="table table-sm">
                    <tr><td>Tanggal Mulai</td><td>: <?= date("d F Y", strtotime($rab_info['tanggal_mulai'])) ?></td></tr>
                    <tr><td>Tanggal Selesai</td><td>: <?= date("d F Y", strtotime($rab_info['tanggal_selesai'])) ?></td></tr>
                    <tr><td>Mandor</td><td>: <?= htmlspecialchars($rab_info['nama_mandor']) ?></td></tr>
                </table></div>
            </div>
        </div>
        
        <table class="report">
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">No</th>
                    <th>Uraian Pekerjaan</th>
                    <th class="text-center">Volume</th>
                    <th class="text-center">Satuan</th>
                    <th class="text-end">Harga Satuan (Rp)</th>
                    <th class="text-end">Sub-Total (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($detail_result) > 0): mysqli_data_seek($detail_result, 0); $prevKategori = null; $noKategori = 0; $noPekerjaan = 1; while ($row = mysqli_fetch_assoc($detail_result)): if ($prevKategori !== $row['nama_kategori']): $noKategori++; echo "<tr class='category-row'><td class='text-center'>" . toRoman($noKategori) . "</td><td colspan='5'>" . htmlspecialchars($row['nama_kategori']) . "</td></tr>"; $prevKategori = $row['nama_kategori']; $noPekerjaan = 1; endif; ?>
                <tr>
                    <td class="text-center"><?= $noPekerjaan++ ?></td>
                    <td><span class="ms-3"><?= htmlspecialchars($row['uraian_pekerjaan']) ?></span></td>
                    <td class="text-center"><?= htmlspecialchars($row['volume']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['satuan']) ?></td>
                    <td class="text-end"><?= number_format($row['harga_satuan'], 0, ',', '.') ?></td>
                    <td class="text-end fw-bold"><?= number_format($row['sub_total'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; else: echo "<tr><td colspan='6' class='text-center text-muted'>Belum ada detail pekerjaan.</td></tr>"; endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-light fw-bolder">
                    <td colspan="5" class="text-end">TOTAL RAB UPAH</td>
                    <td class="text-end">Rp <?= number_format($rab_info['total_rab_upah'], 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <div style="clear: both;"></div>
        <div class="signature-section">
            <div style="width: 33.33%; float: right; text-align: center;">
                <p>Ponorogo, <?= strftime('%d %B %Y') ?></p>
            </div>
            <div class="clearfix" style="margin-bottom: 20px;"></div>
            <div class="signature-box" style="width: 50%; float: left;"><p>Dibuat oleh,</p><div class="name"><?= htmlspecialchars($pj_proyek) ?></div><p>Divisi Teknik</p></div>
            <div class="signature-box" style="width: 50%; float: left;"><p>Disetujui oleh,</p><div class="name"><?= htmlspecialchars($nama_direktur) ?></div><p>Direktur</p></div>
        </div>
    </div>
    <script> window.onload = function() { window.print(); } </script>
</body>
</html>
