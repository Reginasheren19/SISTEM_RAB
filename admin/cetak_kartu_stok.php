<?php
session_start();
include("../config/koneksi_mysql.php");

$tanggal_mulai = $_GET['start'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['end'] ?? date('Y-m-t');
$id_material_filter = $_GET['material'] ?? null;

if (!$id_material_filter) {
    die("Material tidak dipilih.");
}

// Ambil nama material & satuan
$stmt_info = $koneksi->prepare("SELECT m.nama_material, s.nama_satuan FROM master_material m LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan WHERE m.id_material = ?");
$stmt_info->bind_param("i", $id_material_filter);
$stmt_info->execute();
$material_data = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();

$nama_material = $material_data['nama_material'] ?? '-';
$satuan_material = $material_data['nama_satuan'] ?? '';

// Ambil transaksi
$stmt_trx = $koneksi->prepare("SELECT waktu, uraian, masuk, keluar, keterangan FROM (
    SELECT 
        lpm.tanggal_penerimaan AS waktu,
        CONCAT('Penerimaan dari Pembelian: ', p.keterangan_pembelian) AS uraian,
        lpm.jumlah_diterima AS masuk,
        0 AS keluar,
        lpm.catatan AS keterangan
    FROM log_penerimaan_material lpm
    JOIN pencatatan_pembelian p ON lpm.id_pembelian = p.id_pembelian
    WHERE lpm.id_material = ? AND lpm.tanggal_penerimaan BETWEEN ? AND ?
    UNION ALL
    SELECT 
        dm.created_at AS waktu,
        CONCAT('Distribusi ke: ', per.nama_perumahan, ' - ', mp.kavling) AS uraian,
        0 AS masuk,
        dd.jumlah_distribusi AS keluar,
        dm.keterangan_umum AS keterangan
    FROM detail_distribusi dd
    JOIN distribusi_material dm ON dd.id_distribusi = dm.id_distribusi
    JOIN master_proyek mp ON dm.id_proyek = mp.id_proyek
    JOIN master_perumahan per ON mp.id_perumahan = per.id_perumahan
    WHERE dd.id_material = ? AND dm.tanggal_distribusi BETWEEN ? AND ?
) AS transaksi
ORDER BY waktu ASC");
$stmt_trx->bind_param("isssis", $id_material_filter, $tanggal_mulai, $tanggal_selesai, $id_material_filter, $tanggal_mulai, $tanggal_selesai);
$stmt_trx->execute();
$result_trx = $stmt_trx->get_result();
$transaksi = $result_trx->fetch_all(MYSQLI_ASSOC);
$stmt_trx->close();

// Hitung saldo awal
$stmt_saldo = $koneksi->prepare("SELECT (COALESCE(SUM(masuk), 0) - COALESCE(SUM(keluar), 0)) as saldo_awal FROM (
    (SELECT SUM(jumlah_diterima) as masuk, 0 as keluar FROM log_penerimaan_material WHERE id_material = ? AND tanggal_penerimaan < ?)
    UNION ALL
    (SELECT 0 as masuk, SUM(dd.jumlah_distribusi) as keluar FROM detail_distribusi dd JOIN distribusi_material dm ON dd.id_distribusi = dm.id_distribusi WHERE dd.id_material = ? AND dm.tanggal_distribusi < ?)
) as history");
$stmt_saldo->bind_param("isis", $id_material_filter, $tanggal_mulai, $id_material_filter, $tanggal_mulai);
$stmt_saldo->execute();
$saldo_awal = (float)($stmt_saldo->get_result()->fetch_assoc()['saldo_awal'] ?? 0);
$stmt_saldo->close();

// Hitung posisi terakhir
$saldo_akhir = $saldo_awal;
foreach ($transaksi as $trx) {
    $saldo_akhir += $trx['masuk'] - $trx['keluar'];
}

// Logo
$path_logo = 'assets/img/logo/LOGO PT.jpg';
$tipe_logo = pathinfo($path_logo, PATHINFO_EXTENSION);
$data_logo = file_get_contents($path_logo);
$logo_base64 = 'data:image/' . $tipe_logo . ';base64,' . base64_encode($data_logo);

function formatAngka($angka) {
    return rtrim(rtrim(number_format($angka, 2, ',', '.'), '0'), ',');
}

setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Stok - <?= htmlspecialchars($nama_material) ?></title>
    <style>
        body { font-family: 'Tahoma', sans-serif; font-size: 12px; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        .kop-surat { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
        .kop-surat img { width: 100px; height: auto; margin-left: 40px; }
        .kop-text { text-align: center; flex-grow: 1; }
        .kop-text h3 { font-size: 22px; font-weight: bold; margin: 0; }
        .kop-text h2 { font-size: 18px; font-weight: bold; margin: 0; }
        .kop-text p { font-size: 14px; margin: 0; }
        .report-title { text-align: center; margin-bottom: 5px; font-weight: bold; text-decoration: underline; font-size: 16px; }
        .report-period { text-align: center; font-size: 11pt; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid black; padding: 6px; text-align: left; vertical-align: middle; }
        th { background-color: #e9ecef !important; text-align: center; }
        .signature-section { margin-top: 40px; width: 30%; float: right; text-align: center; }
        .signature-section p { margin-bottom: 2px; }
        .signature-section .name { font-weight: bold; text-decoration: underline; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .tagline { font-style: italic; }
        .text-end { text-align: right !important; }
        .text-center { text-align: center !important; }
        .fw-bold { font-weight: bold !important; }
        .text-success { color: green !important; }
        .text-danger { color: red !important; }
        @media print { body {-webkit-print-color-adjust: exact;} .no-print { display: none } @page { size: A4 portrait; margin: 10mm; } }

        .info-card {
            border: 1px solid #999;
            background-color: #f9f9f9;
            padding: 10px 15px;
            margin: 15px 0 20px;
            border-left: 5px solid #007bff;
        }
        .info-card p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="kop-surat">
        <img src="<?= $logo_base64 ?>" alt="Logo Perusahaan" onerror="this.style.display='none'">
        <div class="kop-text">
            <h3>PT. HASTA BANGUN NUSANTARA</h3>
            <h2 class="tagline">General Contractor & Developer</h2>
            <p>Jalan Cakraninggrat, Kauman, Ponorogo, Jawa Timur 63414</p>
            <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
        </div>
    </div>

    <p class="report-title">KARTU STOK MATERIAL</p>
    <p class="report-period">
        Periode: <?= date('d F Y', strtotime($tanggal_mulai)) ?> s/d <?= date('d F Y', strtotime($tanggal_selesai)) ?>
    </p>

    <div class="info-card">
        <p><strong>Material:</strong> <?= htmlspecialchars($nama_material) ?> <?= $satuan_material ? "($satuan_material)" : '' ?></p>
        <p><strong>Posisi Terakhir:</strong> <?= formatAngka($saldo_akhir) ?> <?= $satuan_material ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 14%;">Tanggal</th>
                <th>Uraian</th>
                <th style="width: 10%;">Masuk</th>
                <th style="width: 10%;">Keluar</th>
                <th style="width: 10%;">Sisa</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $saldo = $saldo_awal;
        if (!empty($transaksi)):
            foreach ($transaksi as $trx):
                $saldo += $trx['masuk'] - $trx['keluar'];
        ?>
            <tr>
                <td class="text-center"><?= date('d-m-Y H:i', strtotime($trx['waktu'])) ?></td>
                <td><?= htmlspecialchars($trx['uraian']) ?></td>
                <td class="text-end text-success">
                    <?= ($trx['masuk'] > 0) ? '+' . formatAngka($trx['masuk']) : '-' ?>
                </td>
                <td class="text-end text-danger">
                    <?= ($trx['keluar'] > 0) ? '-' . formatAngka($trx['keluar']) : '-' ?>
                </td>
                <td class="text-end fw-bold">
                    <?= formatAngka($saldo) ?>
                </td>
                <td><?= htmlspecialchars($trx['keterangan']) ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr>
                <td colspan="6" class="text-center text-muted"><i>Tidak ada riwayat transaksi.</i></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="signature-section">
        <p>Ponorogo, <?= strftime('%d %B %Y') ?></p>
        <p>Mengetahui,</p><br><br><br>
        <p class="name">( Ir.Purwo Hermanto )</p>
        <span>Direktur</span>
    </div>
    <div class="clearfix"></div>
</div>

<script>window.onload = function() { window.print(); }</script>
</body>
</html>
