<?php
session_start();
include("../config/koneksi_mysql.php");

// Ambil parameter filter dari URL
$tanggal_mulai = $_GET['start'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['end'] ?? date('Y-m-t');
$id_material_filter = $_GET['material'] ?? '';

// Query utama disamakan dengan laporan_pembelian.php, dengan tambahan filter harga > 0
$sql_parts = [
    "select"  => "SELECT p.tanggal_pembelian, p.id_pembelian, p.keterangan_pembelian, m.nama_material, s.nama_satuan, dp.quantity, dp.harga_satuan_pp, dp.sub_total_pp",
    "from"    => "FROM detail_pencatatan_pembelian dp",
    "join"    => "JOIN pencatatan_pembelian p ON dp.id_pembelian = p.id_pembelian
                  JOIN master_material m ON dp.id_material = m.id_material
                  LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan",
    // --- [DITAMBAHKAN] Filter item pengganti dengan harga 0 ---
    "where"   => "WHERE p.tanggal_pembelian BETWEEN ? AND ? AND dp.harga_satuan_pp > 0",
    "order"   => "ORDER BY p.tanggal_pembelian, p.id_pembelian, m.nama_material"
];
$params = [$tanggal_mulai, $tanggal_selesai];
$param_types = "ss";

if (!empty($id_material_filter)) {
    $sql_parts['where'] .= " AND dp.id_material = ?";
    $params[] = $id_material_filter;
    $param_types .= "i";
}

$sql = implode(" ", $sql_parts);
$stmt = mysqli_prepare($koneksi, $sql);
if ($stmt === false) { die("Query Gagal Disiapkan. Error: " . mysqli_error($koneksi)); }
mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');

// Logika untuk memproses logo
$path_logo = 'assets/img/logo/LOGO PT.jpg';
$tipe_logo = pathinfo($path_logo, PATHINFO_EXTENSION);
$data_logo = file_get_contents($path_logo);
$logo_base64 = 'data:image/' . $tipe_logo . ';base64,' . base64_encode($data_logo);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembelian - <?= date('d M Y') ?></title>
    <style>
        body{font-family:'Tahoma',sans-serif;font-size:12px;color:#000;margin:0;padding:0}.container{max-width:800px;margin:auto}.kop-surat{display:flex;align-items:center;border-bottom:3px double #000;padding-bottom:15px;margin-bottom:20px}.kop-surat img{width:100px;height:auto;margin-left:40px}.kop-surat .kop-text{text-align:center;flex-grow:1}.kop-surat h3{font-size:22px;font-weight:bold;margin:0}.kop-surat h2{font-size:18px;font-weight:bold;margin:0}.kop-surat p{font-size:14px;margin:0}.report-title{text-align:center;margin-bottom:5px;font-weight:bold;text-decoration:underline;font-size:16px}.report-period{text-align:center;font-size:11pt;margin-bottom:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid black;padding:6px;text-align:left;vertical-align:middle}th{background-color:#e9ecef!important;text-align:center}.signature-section{margin-top:40px;width:30%;float:right;text-align:center}
        
        /* [DIUBAH] Jarak spasi/enter di bagian tanda tangan diperbaiki di sini */
        .signature-section p{margin-bottom: 2px;}

        .signature-section .name{font-weight:bold;text-decoration:underline}.clearfix::after{content:"";clear:both;display:table}.tagline{font-style:italic}.text-end{text-align:right!important}.text-center{text-align:center!important}.fw-bold{font-weight:bold!important}.text-success{color:green!important}.text-danger{color:red!important}@media print{body{-webkit-print-color-adjust:exact}.no-print{display:none}@page{size:A4 portrait;margin:10mm}}
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
            <p class="report-title">LAPORAN RINCIAN PEMBELIAN</p>
            <p class="report-period">
                Periode: <?= date('d F Y', strtotime($tanggal_mulai)) ?> s/d <?= date('d F Y', strtotime($tanggal_selesai)) ?>
            </p>

            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 10%;">Tanggal</th>
                        <th style="width: 10%;">ID Beli</th>
                        <th style="width: 20%;">Keterangan</th>
                        <th>Material</th>
                        <th style="width: 10%;" class="text-end">Kuantitas</th>
                        <th style="width: 12%;" class="text-end">Harga Satuan</th>
                        <th style="width: 13%;" class="text-end">Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result && mysqli_num_rows($result) > 0):
                        $nomor = 1;
                        $grand_total = 0;
                        while ($row = mysqli_fetch_assoc($result)):
                            $grand_total += $row['sub_total_pp'];
                            $tahun_pembelian = date('Y', strtotime($row['tanggal_pembelian']));
                            $formatted_id = 'PB' . $row['id_pembelian'] . $tahun_pembelian;
                    ?>
                    <tr>
                        <td class="text-center"><?= $nomor++ ?></td>
                        <td class="text-center"><?= date("d-m-Y", strtotime($row['tanggal_pembelian'])) ?></td>
                        <td><?= htmlspecialchars($formatted_id) ?></td>
                        <td><?= htmlspecialchars($row['keterangan_pembelian']) ?></td>
                        <td><?= htmlspecialchars($row['nama_material']) ?></td>
                        <td class="text-end"><?= number_format($row['quantity'], 2, ',', '.') ?> <?= htmlspecialchars($row['nama_satuan']) ?></td>
                        <td class="text-end">Rp <?= number_format($row['harga_satuan_pp'] ?? 0, 0, ',', '.') ?></td>
                        <td class="text-end">Rp <?= number_format($row['sub_total_pp'] ?? 0, 0, ',', '.') ?></td>
                    </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr>
                        <td colspan="8" class="text-center">Tidak ada data untuk filter yang dipilih.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="7" class="text-end">GRAND TOTAL</td>
                        <td class="text-end">Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>

    <div class="signature-section">
        <p>Ponorogo, <?= strftime('%d %B %Y') ?></p>
        <p>Mengetahui,</p>
        <br><br><br> <p class="name">( Ir.Purwo Hermanto )</p>
        <span>Direktur</span>
    </div>
    <div class="clearfix"></div>

</div>

<script>
    window.onload = function() {
        window.print();
    }
</script>
</body>
</html>