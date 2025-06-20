<?php
session_start();
include("../../config/koneksi_mysql.php");

// --- Bagian PHP untuk mengambil data (TIDAK ADA PERUBAHAN) ---
$tanggal_mulai = $_GET['start'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['end'] ?? date('Y-m-t');
$id_material_filter = $_GET['material'] ?? '';

$sql_parts = [
    "select"    => "SELECT DISTINCT p.id_pembelian, p.tanggal_pembelian, p.keterangan_pembelian, p.total_biaya",
    "from"      => "FROM pencatatan_pembelian p",
    "join"      => "",
    "where"     => "WHERE p.tanggal_pembelian BETWEEN ? AND ?",
    "order"     => "ORDER BY p.tanggal_pembelian ASC"
];
$params = [$tanggal_mulai, $tanggal_selesai];
$param_types = "ss";

if (!empty($id_material_filter)) {
    $sql_parts['join'] = "JOIN detail_pencatatan_pembelian dp ON p.id_pembelian = dp.id_pembelian";
    $sql_parts['where'] .= " AND dp.id_material = ?";
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
    <title>Laporan Pembelian - <?= date('d M Y') ?></title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt; /* Ukuran font standar untuk dokumen */
        }
        .container {
            width: 100%;
        }
        .kop-surat table {
            width: 100%;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .kop-surat td {
            border: 0;
            vertical-align: middle;
        }
        .kop-surat .logo { width: 80px; }
        .kop-surat .text-kop { text-align: center; }
        .kop-surat h4 { font-size: 16pt; font-weight: bold; margin: 0; }
        .kop-surat p { font-size: 10pt; margin: 2px 0 0 0; }
        .report-title { text-align: center; font-size: 14pt; font-weight: bold; text-decoration: underline; margin: 20px 0 5px 0; }
        .report-period { text-align: center; font-size: 11pt; margin-bottom: 20px; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { border: 1px solid black; padding: 6px; }
        .report-table th { background-color: #e9ecef; text-align: center; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .signature-section { margin-top: 40px; page-break-inside: avoid; width: 30%; float: right; text-align: center; }
        .clearfix::after { content: ""; clear: both; display: table; }

        /* PENGATURAN KERTAS DAN MARGIN SAAT PRINT */
        @page {
            size: A4 portrait; /* Mengatur kertas menjadi A4 Portrait */
            margin: 20mm 15mm 20mm 15mm; /* Atas, Kanan, Bawah, Kiri */
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
            <p class="report-title">LAPORAN PENCATATAN PEMBELIAN</p>
            <p class="report-period">
                Periode: <?= date('d F Y', strtotime($tanggal_mulai)) ?> s/d <?= date('d F Y', strtotime($tanggal_selesai)) ?>
            </p>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 15%;">ID Pembelian</th>
                        <th style="width: 15%;">Tanggal</th>
                        <th style="width: 45%;">Keterangan</th>
                        <th style="width: 20%;" class="text-end">Total Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result && mysqli_num_rows($result) > 0):
                        $nomor = 1;
                        $grand_total = 0;
                        while ($row = mysqli_fetch_assoc($result)):
                            $grand_total += $row['total_biaya'];

                            // Logika pemformatan ID
                            $tahun_pembelian = date('Y', strtotime($row['tanggal_pembelian']));
                            $formatted_id = 'PB' . $row['id_pembelian'] . $tahun_pembelian;
                    ?>
                        <tr>
                            <td class="text-center"><?= $nomor++ ?></td>
                            <td><?= htmlspecialchars($formatted_id) ?></td>
                            <td class="text-center"><?= date("d-m-Y", strtotime($row['tanggal_pembelian'])) ?></td>
                            <td><?= htmlspecialchars($row['keterangan_pembelian']) ?></td>
                            <td class="text-end">Rp <?= number_format($row['total_biaya'] ?? 0, 0, ',', '.') ?></td>
                        </tr>
                    <?php 
                        endwhile; 
                    
                    // BAGIAN YANG HILANG ADA DI SINI:
                    else:
                    ?>
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada data untuk filter yang dipilih.</td>
                        </tr>
                    <?php 
                    endif; // <-- DAN PENUTUP 'ENDIF' INI YANG PALING PENTING
                    ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end">GRAND TOTAL</td>
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