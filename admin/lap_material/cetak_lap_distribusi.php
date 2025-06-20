<?php
session_start();
include("../../config/koneksi_mysql.php");

// --- Bagian PHP untuk mengambil data (TIDAK ADA PERUBAHAN) ---
$tanggal_mulai = $_GET['start'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['end'] ?? date('Y-m-t');
$id_proyek_filter = $_GET['proyek'] ?? '';
$id_material_filter = $_GET['material'] ?? '';
// ... (sisa kode query dinamis Anda yang sudah benar ada di sini) ...
$sql_parts = [
    "select" => "SELECT DISTINCT d.id_distribusi, d.tanggal_distribusi, d.keterangan_umum, u.nama_lengkap AS nama_pj, CONCAT(pr.nama_perumahan, ' - Kavling ', p.kavling) AS nama_proyek_lengkap",
    "from"   => "FROM distribusi_material d",
    "join"   => "LEFT JOIN master_user u ON d.id_user_pj = u.id_user LEFT JOIN master_proyek p ON d.id_proyek = p.id_proyek LEFT JOIN master_perumahan pr ON p.id_perumahan = pr.id_perumahan",
    "where"  => "WHERE d.tanggal_distribusi BETWEEN ? AND ?",
    "order"  => "ORDER BY d.tanggal_distribusi ASC"
];
$params = [$tanggal_mulai, $tanggal_selesai];
$param_types = "ss";
if (!empty($id_proyek_filter)) {
    $sql_parts['where'] .= " AND d.id_proyek = ?";
    $params[] = $id_proyek_filter;
    $param_types .= "i";
}
if (!empty($id_material_filter)) {
    $sql_parts['join'] .= " JOIN detail_distribusi dd ON d.id_distribusi = dd.id_distribusi";
    $sql_parts['where'] .= " AND dd.id_material = ?";
    $params[] = $id_material_filter;
    $param_types .= "i";
}
$sql = implode(" ", $sql_parts);
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');

// --- Logika untuk memproses logo (TIDAK ADA PERUBAHAN) ---
$path_logo = '../assets/img/logo/LOGO PT.jpg';
$tipe_logo = pathinfo($path_logo, PATHINFO_EXTENSION);
$data_logo = file_get_contents($path_logo);
$logo_base64 = 'data:image/' . $tipe_logo . ';base64,' . base64_encode($data_logo);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Distribusi - <?= date('d M Y') ?></title>
    <style>
        /* Menggunakan CSS yang sama persis dengan Laporan Pembelian */
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; }
        .container { width: 95%; margin: auto; }
        .kop-surat table { width: 100%; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat td { border: 0; vertical-align: middle; }
        .kop-surat .logo { width: 80px; }
        .kop-surat .text-kop { text-align: center; }
        .kop-surat h4 { font-size: 16pt; font-weight: bold; margin: 0; }
        .kop-surat p { font-size: 10pt; margin: 2px 0 0 0; }
        .report-title { text-align: center; font-size: 14pt; font-weight: bold; text-decoration: underline; margin: 20px 0 5px 0; }
        .report-period { text-align: center; font-size: 11pt; margin-bottom: 20px; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { border: 1px solid black; padding: 6px; }
        .report-table th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .signature-section { margin-top: 40px; page-break-inside: avoid; width: 30%; float: right; text-align: center; }
        .clearfix::after { content: ""; clear: both; display: table; }

        /* DIUBAH: Pengaturan kertas menjadi A4 Portrait */
        @page {
            size: A4 portrait; 
            margin: 20mm 15mm 20mm 15mm; 
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="kop-surat">
            <table>
                <tr>
                    <td style="width: 20%;">
                        <img src="<?= $logo_base64 ?>" alt="Logo" class="logo">
                    </td>
                    <td style="width: 80%;" class="text-kop">
                        <h4>PT. HASTA BANGUN NUSANTARA</h4>
                        <p>Jalan Cokroaminoto 63414 Ponorogo Jawa Timur</p>
                        <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
                    </td>
                </tr>
            </table>
        </header>

        <main>
            <p class="report-title">LAPORAN DISTRIBUSI MATERIAL</p>
            <p class="report-period">
                Periode: <?= date('d F Y', strtotime($tanggal_mulai)) ?> s/d <?= date('d F Y', strtotime($tanggal_selesai)) ?>
            </p>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 15%;">ID Distribusi</th>
                        <th style="width: 15%;">Tanggal</th>
                        <th style="width: 25%;">Proyek Tujuan</th>
                        <th style="width: 20%;">Didistribusikan Oleh</th>
                        <th style="width: 20%;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $nomor = 1;
                    if ($result && mysqli_num_rows($result) > 0):
                        while ($row = mysqli_fetch_assoc($result)):
                            $tahun_distribusi = date('Y', strtotime($row['tanggal_distribusi']));
                            $formatted_id = 'DIST' . $row['id_distribusi'] . $tahun_distribusi;
                    ?>
                        <tr>
                            <td class="text-center"><?= $nomor++ ?></td>
                            <td><?= htmlspecialchars($formatted_id) ?></td>
                            <td class="text-center"><?= date("d-m-Y", strtotime($row['tanggal_distribusi'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_proyek_lengkap']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pj']) ?></td>
                            <td><?= htmlspecialchars($row['keterangan_umum']) ?></td>
                        </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 20px;">Tidak ada data untuk filter yang dipilih.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="signature-section">
                <p>Ponorogo, <?= strftime('%d %B %Y') ?></p>
                <p>Mengetahui,</p>
                <br><br><br><br>
                <p class="fw-bold" style="text-decoration: underline;">( Ir.Purwo Hermanto )</p>
                <p>Direktur</p>
            </div>
            <div class="clearfix"></div>
        </main>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>