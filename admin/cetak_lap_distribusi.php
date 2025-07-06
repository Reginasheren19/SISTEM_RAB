<?php
session_start();
include("../config/koneksi_mysql.php");

// Ambil parameter filter dari URL
$tanggal_mulai = $_GET['start'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['end'] ?? date('Y-m-t');
$id_proyek_filter = $_GET['proyek'] ?? '';
$id_material_filter = $_GET['material'] ?? '';
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');

// Query utama laporan
$sql_parts = [
    "select" => "
        SELECT 
            d.id_distribusi, d.tanggal_distribusi, d.keterangan_umum, 
            u.nama_lengkap AS nama_pj,
            CONCAT(pr.nama_perumahan, ' - Kavling ', p.kavling) AS nama_proyek_lengkap,
            m.nama_material,
            s.nama_satuan,
            dd.jumlah_distribusi
    ",
    "from"   => "FROM detail_distribusi dd",
    "join"   => "
        JOIN distribusi_material d ON dd.id_distribusi = d.id_distribusi
        JOIN master_material m ON dd.id_material = m.id_material
        LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan
        LEFT JOIN master_user u ON d.id_user_pj = u.id_user 
        LEFT JOIN master_proyek p ON d.id_proyek = p.id_proyek 
        LEFT JOIN master_perumahan pr ON p.id_perumahan = pr.id_perumahan
    ",
    "where"  => "WHERE d.tanggal_distribusi BETWEEN ? AND ?",
    "order"  => "ORDER BY d.id_distribusi DESC, d.tanggal_distribusi DESC"
];
$params = [$tanggal_mulai, $tanggal_selesai];
$param_types = "ss";

if (!empty($id_proyek_filter)) {
    $sql_parts['where'] .= " AND d.id_proyek = ?";
    $params[] = $id_proyek_filter;
    $param_types .= "i";
}
if (!empty($id_material_filter)) {
    $sql_parts['where'] .= " AND dd.id_material = ?";
    $params[] = $id_material_filter;
    $param_types .= "i";
}

$sql = implode(" ", $sql_parts);
$stmt = mysqli_prepare($koneksi, $sql);
if($stmt === false) { die("Query Gagal Disiapkan: " . mysqli_error($koneksi)); }
mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Siapkan data dan hitung rowspan
$data_laporan = [];
$rowspan_counts = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data_laporan[] = $row;
        if (!isset($rowspan_counts[$row['id_distribusi']])) {
            $rowspan_counts[$row['id_distribusi']] = 0;
        }
        $rowspan_counts[$row['id_distribusi']]++;
    }
}

// Logika untuk logo
$path_logo = 'assets/img/logo/LOGO PT.jpg';
$logo_base64 = '';
if (file_exists($path_logo)) {
    $tipe_logo = pathinfo($path_logo, PATHINFO_EXTENSION);
    $data_logo = file_get_contents($path_logo);
    $logo_base64 = 'data:image/' . $tipe_logo . ';base64,' . base64_encode($data_logo);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Distribusi - <?= date('d M Y') ?></title>
    <style>
        body{font-family:'Tahoma',sans-serif;font-size:12px;color:#000;margin:0;padding:0}.container{width:100%;margin:auto}.kop-surat{display:flex;align-items:center;border-bottom:3px double #000;padding-bottom:15px;margin-bottom:20px}.kop-surat img{width:100px;height:auto;margin-left:40px}.kop-surat .kop-text{text-align:center;flex-grow:1}.kop-surat h3{font-size:22px;font-weight:bold;margin:0}.kop-surat h2{font-size:18px;font-weight:bold;margin:0}.kop-surat p{font-size:14px;margin:0}.report-title{text-align:center;margin-bottom:5px;font-weight:bold;text-decoration:underline;font-size:16px}.report-period{text-align:center;font-size:11pt;margin-bottom:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid black;padding:6px;text-align:left;vertical-align:middle}th{background-color:#e9ecef!important;text-align:center}.signature-section{margin-top:40px;width:30%;float:right;text-align:center}.signature-section p{margin-bottom:2px}.signature-section .name{font-weight:bold;text-decoration:underline}.clearfix::after{content:"";clear:both;display:table}.tagline{font-style:italic}.text-end{text-align:right!important}.text-center{text-align:center!important}.fw-bold{font-weight:bold!important}.text-success{color:green!important}.text-danger{color:red!important}@media print{body{-webkit-print-color-adjust:exact}.no-print{display:none}@page{size:A4 portrait;margin:10mm}}
    </style>
</head>
<body>
<div class="container my-4">
    <div class="kop-surat">
        <img src="<?= $logo_base64 ?>" alt="Logo Perusahaan" onerror="this.style.display='none'">
        <div class="kop-text">
            <h3>PT. HASTA BANGUN NUSANTARA</h3>
            <h2 class="tagline">General Contractor & Developer</h2>
            <p>Jalan Cakraninggrat, Kauman, Kabupaten Ponorogo, Jawa Timur 63414</p>
            <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
        </div>
    </div>

    <p class="report-title">LAPORAN DISTRIBUSI MATERIAL</p>
    <p class="report-period">Periode: <?= date('d F Y', strtotime($tanggal_mulai)) ?> s/d <?= date('d F Y', strtotime($tanggal_selesai)) ?></p>

    <table class="table-bordered">
        <thead>
            <tr>
                <th>No</th>
                <th>ID Distribusi</th>
                <th>Tanggal</th>
                <th>Proyek Tujuan</th>
                <th>Material</th>
                <th class="text-end">Jumlah</th>
                <th>Oleh</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (!empty($data_laporan)):
                $nomor = 1;
                $last_id = null;
                foreach ($data_laporan as $row):
                    $tahun_distribusi = date('Y', strtotime($row['tanggal_distribusi']));
                    $formatted_id = 'DIST' . $row['id_distribusi'] . $tahun_distribusi;
            ?>
            <tr>
                <?php if ($row['id_distribusi'] != $last_id): 
                    $rowspan = $rowspan_counts[$row['id_distribusi']];
                ?>
                    <td class="text-center" rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= $nomor++ ?></td>
                    <td rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= htmlspecialchars($formatted_id) ?></td>
                    <td class="text-center" rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= date("d-m-Y", strtotime($row['tanggal_distribusi'])) ?></td>
                    <td rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= htmlspecialchars($row['nama_proyek_lengkap']) ?></td>
                <?php endif; ?>
                
                <td><?= htmlspecialchars($row['nama_material']) ?></td>
                <td class="text-end"><?= number_format($row['jumlah_distribusi'], 2, ',', '.') ?> <?= htmlspecialchars($row['nama_satuan']) ?></td>
                
                <?php if ($row['id_distribusi'] != $last_id): ?>
                    <td rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= htmlspecialchars($row['nama_pj']) ?></td>
                    <td rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= htmlspecialchars($row['keterangan_umum']) ?></td>
                <?php endif; ?>
            </tr>
            <?php 
                    $last_id = $row['id_distribusi'];
                endforeach; 
            else:
            ?>
            <tr><td colspan="8" class="text-center" style="padding: 20px;">Tidak ada data untuk filter yang dipilih.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="signature-section">
        <p>Ponorogo, <?= strftime('%d %B %Y') ?></p>
        <p>Mengetahui,</p>
        <br><br><br>
        <p class="name">( Ir.Purwo Hermanto )</p>
        <span>Direktur</span>
    </div>
    <div class="clearfix"></div>

</div>
<script>
    window.onload = function() { window.print(); }
</script>
</body>
</html>