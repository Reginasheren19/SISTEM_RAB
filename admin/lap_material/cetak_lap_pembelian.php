<?php
session_start();
include("../../config/koneksi_mysql.php");

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
$path_logo = '../assets/img/logo/LOGO PT.jpg';
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
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; }
        .container { width: 100%; }
        .kop-surat table { width: 100%; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat td { border: 0; vertical-align: middle; }
        .kop-surat .logo { width: 80px; }
        .kop-surat .text-kop { text-align: center; }
        .kop-surat h4 { font-size: 16pt; font-weight: bold; margin: 0; }
        .kop-surat p { font-size: 10pt; margin: 2px 0 0 0; }
        .report-title { text-align: center; font-size: 14pt; font-weight: bold; text-decoration: underline; margin: 20px 0 5px 0; }
        .report-period { text-align: center; font-size: 11pt; margin-bottom: 20px; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { border: 1px solid black; padding: 5px; }
        .report-table th { background-color: #e9ecef; text-align: center; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .signature-section { margin-top: 40px; page-break-inside: avoid; width: 30%; float: right; text-align: center; }
        .clearfix::after { content: ""; clear: both; display: table; }
        @page { size: A4 portrait; margin: 20mm 15mm 20mm 15mm; }
    </style>
</head>
<body>
    <div class="container">
        <header class="kop-surat">
            <table>
                <tr>
                    <td style="width: 20%;"><img src="<?= $logo_base64 ?>" alt="Logo" class="logo"></td>
                    <td style="width: 80%;" class="text-kop">
                        <h4>PT. HASTA BANGUN NUSANTARA</h4>
                        <h2 class ="tagline">General Contractor & Developer</h2>
                                <p>Jalan Cakraninggrat, Kauman, Kabupaten Ponorogo, Jawa Timur 63414</p>
                                <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
                    </td>
                </tr>
            </table>
        </header>

        <main>
            <p class="report-title">LAPORAN RINCIAN PEMBELIAN</p>
            <p class="report-period">
                Periode: <?= date('d F Y', strtotime($tanggal_mulai)) ?> s/d <?= date('d F Y', strtotime($tanggal_selesai)) ?>
            </p>

            <table class="report-table">
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