<?php
session_start();
include("../config/koneksi_mysql.php");

// Pastikan ID Pengajuan ada dan valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Akses tidak valid.");
}
$id_pengajuan_upah = (int)$_GET['id'];

// Set locale ke Indonesia agar nama bulan menjadi Bahasa Indonesia
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');

// Query info utama untuk memisahkan nama perumahan dan kavling
$sql_info = "SELECT 
                pu.tanggal_pengajuan, pu.total_pengajuan, pu.status_pengajuan,
                mpe.nama_perumahan,
                mpr.kavling,
                mpe.lokasi,
                mm.nama_mandor,
                u.nama_lengkap AS pj_proyek,
                ru.total_rab_upah,
                tr.tanggal_mulai, -- Ditambahkan untuk durasi
                tr.tanggal_selesai -- Ditambahkan untuk durasi
            FROM pengajuan_upah pu
            LEFT JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah
            LEFT JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
            LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
            LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
            LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
            LEFT JOIN rab_upah tr ON pu.id_rab_upah = tr.id_rab_upah -- Join ulang untuk tanggal
            WHERE pu.id_pengajuan_upah = $id_pengajuan_upah";
$result_info = mysqli_query($koneksi, $sql_info);
if (!$result_info || mysqli_num_rows($result_info) == 0) {
    die("Data pengajuan tidak ditemukan.");
}
$pengajuan_info = mysqli_fetch_assoc($result_info);

// Query untuk mengambil detail pekerjaan
$sql_detail = "SELECT k.nama_kategori, mp.uraian_pekerjaan, dp.progress_pekerjaan, dp.nilai_upah_diajukan
               FROM detail_pengajuan_upah dp
               LEFT JOIN detail_rab_upah dr ON dp.id_detail_rab_upah = dr.id_detail_rab_upah
               LEFT JOIN master_pekerjaan mp ON dr.id_pekerjaan = mp.id_pekerjaan
               LEFT JOIN master_kategori k ON dr.id_kategori = k.id_kategori
               WHERE dp.id_pengajuan_upah = $id_pengajuan_upah
               ORDER BY k.id_kategori, mp.id_pekerjaan";
$result_detail = mysqli_query($koneksi, $sql_detail);

// Query untuk menghitung termin
$id_rab_upah_q = mysqli_query($koneksi, "SELECT id_rab_upah FROM pengajuan_upah WHERE id_pengajuan_upah=$id_pengajuan_upah");
$id_rab_upah = mysqli_fetch_assoc($id_rab_upah_q)['id_rab_upah'];
$sql_termin = "SELECT COUNT(id_pengajuan_upah) AS termin_ke FROM pengajuan_upah WHERE id_rab_upah = $id_rab_upah AND id_pengajuan_upah <= $id_pengajuan_upah";
$termin_ke = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_termin))['termin_ke'];

// Query untuk mengambil nama Direktur
$sql_direktur = "SELECT nama_lengkap FROM master_user WHERE role = 'direktur' LIMIT 1";
$result_direktur = mysqli_query($koneksi, $sql_direktur);
$nama_direktur = "....................."; 
if ($result_direktur && mysqli_num_rows($result_direktur) > 0) {
    $direktur_info = mysqli_fetch_assoc($result_direktur);
    $nama_direktur = $direktur_info['nama_lengkap'];
}

$nama_komisaris = "Hastut Pantjarini, SE";

function toRoman($num) {
    $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $result = '';
    foreach ($map as $roman => $value) {
        while ($num >= $value) {
            $result .= $roman;
            $num -= $value;
        }
    }
    return $result;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Pengajuan Upah #<?= htmlspecialchars($id_pengajuan_upah) ?></title>
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
        .table th, .table td { padding: 0.4rem; vertical-align: middle; border: 1px solid #333 !important; }
        .table th { background-color: #e9ecef !important; }
        .info-section .table { border: none !important; }
        .info-section .table td { border: none !important; padding: 2px 0; font-size: 14px; }
        .info-section td:nth-child(1) { width: 140px; font-weight: bold;}
        .signature-section { margin-top: 50px; width: 100%; }
        .signature-box { text-align: center; width: 33.33%; float: left; }
        .signature-box .name { margin-top: 60px; font-weight: bold; text-decoration: underline; }
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <div class="kop-surat">
            <img src="assets/img/logo/LOGO PT.jpg" alt="Logo Perusahaan" onerror="this.style.display='none'">
            <div class="kop-text">
                <h3>PT. HASTA BANGUN NUSANTARA</h3>
                <p>Jalan Cokroaminoto 63414 Ponorogo Jawa Timur</p>
                <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
            </div>
        </div>
        
        <h5 class="report-title">FORMULIR PENGAJUAN UPAH</h5>

        <div class="info-section mb-4">
            <div class="row">
                <div class="col-7">
                    <table class="table table-sm">
                        <tr><td>No. Pengajuan</td><td>: <?= htmlspecialchars($id_pengajuan_upah) ?> / Termin Pengajuan ke-<?= $termin_ke ?></td></tr>
                        <tr><td>Nama Perumahan</td><td>: <?= htmlspecialchars($pengajuan_info['nama_perumahan']) ?></td></tr>
                        <tr><td>Kavling / Blok</td><td>: <?= htmlspecialchars($pengajuan_info['kavling']) ?></td></tr>
                        <tr><td>PJ Proyek</td><td>: <?= htmlspecialchars($pengajuan_info['pj_proyek']) ?></td></tr>
                    </table>
                </div>
                <div class="col-5">
                    <table class="table table-sm">
                        <tr><td>Tanggal</td><td>: <?= date("d F Y", strtotime($pengajuan_info['tanggal_pengajuan'])) ?></td></tr>
                        <tr><td>Mandor</td><td>: <?= htmlspecialchars($pengajuan_info['nama_mandor']) ?></td></tr>
                        <tr><td>Total Anggaran</td><td>: Rp <?= number_format($pengajuan_info['total_rab_upah'], 0, ',', '.') ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        
        <h6>Rincian Pekerjaan:</h6>
        <table class="table">
            <thead class="text-center">
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th>Uraian Pekerjaan</th>
                    <th style="width: 15%;">Progress</th>
                    <th style="width: 25%;">Nilai Diajukan (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_keseluruhan = 0;
                if ($result_detail && mysqli_num_rows($result_detail) > 0):
                    $prevKategori = null;
                    $noKategori = 0;
                    $noPekerjaan = 1;
                    while ($row = mysqli_fetch_assoc($result_detail)):
                        if ($prevKategori !== $row['nama_kategori']) {
                            $noKategori++;
                            echo "<tr class='fw-bold' style='background-color: #f8f9fa;'><td class='text-center'>" . toRoman($noKategori) . "</td><td colspan='3'>" . htmlspecialchars($row['nama_kategori']) . "</td></tr>";
                            $prevKategori = $row['nama_kategori'];
                            $noPekerjaan = 1; 
                        }
                        $total_keseluruhan += $row['nilai_upah_diajukan'];
                ?>
                    <tr>
                        <td class="text-center"><?= $noPekerjaan++ ?></td>
                        <td><?= htmlspecialchars($row['uraian_pekerjaan']) ?></td>
                        <td class="text-center"><?= number_format($row['progress_pekerjaan'], 2) ?>%</td>
                        <td class="text-end"><?= number_format($row['nilai_upah_diajukan'], 0, ',', '.') ?></td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr><td colspan="4" class="text-center">Tidak ada rincian pekerjaan.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">TOTAL PENGAJUAN</td>
                    <td class="text-end">Rp <?= number_format($total_keseluruhan, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="clearfix"></div>

        <div class="row mt-5">
            <div class="col-12 text-end">
                <p>Ponorogo, <?= strftime('%d %B %Y') ?></p>
            </div>
        </div>

        <div class="signature-section row">
            <div class="signature-box col-4">
                <p>Diajukan oleh,</p>
                <div class="name"><?= htmlspecialchars($pengajuan_info['pj_proyek']) ?></div>
                <p>PJ Proyek</p>
            </div>
            <div class="signature-box col-4">
                <p>Mengetahui,</p>
                <div class="name"><?= htmlspecialchars($nama_komisaris) ?></div>
                <p>Komisaris</p>
            </div>
            <div class="signature-box col-4">
                <p>Disetujui oleh,</p>
                <div class="name">Ir. <?= htmlspecialchars($nama_direktur) ?></div>
                <p>Direktur Utama</p>
            </div>
        </div>
        
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
