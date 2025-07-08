<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Validasi dan ambil filter dari URL
$id_proyek_filter = $_GET['proyek'] ?? null;
if (empty($id_proyek_filter) || !is_numeric($id_proyek_filter)) {
    die("Akses tidak valid. Silakan cetak dari halaman Laporan RAB vs Realisasi dengan memilih satu proyek spesifik.");
}
$tanggal_mulai = $_GET['start'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['end'] ?? date('Y-m-t');
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');

// 2. Query untuk Info Header & Ringkasan
$header_data = [];
$sql_header = "
    SELECT 
        r.id_rab_material, p.kavling, p.type_proyek, per.nama_perumahan,
        r.tanggal_mulai_mt, r.tanggal_selesai_mt, m.nama_mandor,
        r.total_rab_material as total_anggaran
    FROM rab_material r
    JOIN master_proyek p ON r.id_proyek = p.id_proyek
    JOIN master_perumahan per ON p.id_perumahan = per.id_perumahan
    LEFT JOIN master_mandor m ON p.id_mandor = m.id_mandor
    WHERE r.id_proyek = ?
";
$stmt_header = $koneksi->prepare($sql_header);
$stmt_header->bind_param("i", $id_proyek_filter);
$stmt_header->execute();
$result_header = $stmt_header->get_result();
$header_data = $result_header->fetch_assoc();
$stmt_header->close();

if (!$header_data) {
    die("Data RAB untuk proyek ini tidak ditemukan.");
}

// 3. Hitung Total Realisasi & Rinciannya
$total_realisasi = 0;
$detail_realisasi = [];
$sql_distribusi = "SELECT dd.id_material, m.nama_material, s.nama_satuan, SUM(dd.jumlah_distribusi) AS total_kuantitas FROM detail_distribusi dd JOIN distribusi_material dm ON dd.id_distribusi = dm.id_distribusi JOIN master_material m ON dd.id_material = m.id_material LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan WHERE dm.id_proyek = ? AND dm.tanggal_distribusi BETWEEN ? AND ? GROUP BY dd.id_material, m.nama_material, s.nama_satuan";
$stmt_distribusi = $koneksi->prepare($sql_distribusi);
$stmt_distribusi->bind_param("iss", $id_proyek_filter, $tanggal_mulai, $tanggal_selesai);
$stmt_distribusi->execute();
$result_distribusi = $stmt_distribusi->get_result();
while($item = $result_distribusi->fetch_assoc()) {
    $stmt_harga = $koneksi->prepare("SELECT (SUM(sub_total_pp) / NULLIF(SUM(quantity), 0)) AS harga_rata_rata FROM detail_pencatatan_pembelian WHERE id_material = ? AND quantity > 0");
    $stmt_harga->bind_param("i", $item['id_material']);
    $stmt_harga->execute();
    $harga_rata_rata = (float)($stmt_harga->get_result()->fetch_assoc()['harga_rata_rata'] ?? 0);
    $stmt_harga->close();
    $nilai_item = $item['total_kuantitas'] * $harga_rata_rata;
    $total_realisasi += $nilai_item;
    $detail_realisasi[] = ['nama_material' => $item['nama_material'], 'satuan' => $item['nama_satuan'], 'volume' => $item['total_kuantitas'], 'nilai' => $nilai_item];
}
$sisa_anggaran = $header_data['total_anggaran'] - $total_realisasi;

// Logika untuk logo
$path_logo = 'assets/img/logo/LOGO PT.jpg';
$logo_base64 = '';
if (file_exists($path_logo)) { $logo_base64 = 'data:image/' . pathinfo($path_logo, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($path_logo)); }

// Siapkan data untuk tabel ringkasan
$laporan_data = [[
    'nama_proyek' => htmlspecialchars($header_data['nama_perumahan']) . ' - Kavling: ' . htmlspecialchars($header_data['kavling']) . ' (Tipe: ' . htmlspecialchars($header_data['type_proyek']) . ')',
    'anggaran' => $header_data['total_anggaran'],
    'realisasi' => $total_realisasi,
    'selisih' => $sisa_anggaran
]];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Detail Realisasi Anggaran</title>
    <style>
        body{font-family:'Tahoma', sans-serif; font-size: 11px; color: #333;}
        .container{width: 100%; margin: auto;}
        .kop-surat{display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 15px;}
        .kop-surat img{width: 90px;}
        .kop-surat .kop-text{text-align: center; flex-grow: 1;}
        .kop-surat h3{font-size: 20px; font-weight: bold; margin: 0;}
        .kop-surat h2{font-size: 16px; font-weight: bold; margin: 0;}
        .kop-surat p{font-size: 12px; margin: 0;}
        .report-title{text-align: center; margin-bottom: 20px; font-weight: bold; text-decoration: underline; font-size: 14px;}
        .info-section{margin-bottom: 15px; font-size: 12px;}
        .info-section table{width: 100%; border: none;}
        .info-section td{padding: 2px 5px; border: none;}
        .info-section .label{font-weight: bold; width: 120px;}
        .kpi-section{display: flex; justify-content: space-between; margin-bottom: 20px; text-align: center;}
        .kpi-card{border: 1px solid #ccc; padding: 10px; width: 32%; box-sizing: border-box; border-radius: 5px;}
        .kpi-card .title{font-size: 11px; color: #555; margin-bottom: 5px; text-transform: uppercase;}
        .kpi-card .value{font-size: 16px; font-weight: bold;}
        .detail-table{width: 100%; border-collapse: collapse; margin-top: 15px;}
        .detail-table th, .detail-table td{border: 1px solid black; padding: 6px;}
        .detail-table th{background-color: #e9ecef !important; text-align: center;}
        .text-end{text-align: right!important;}
        .fw-bold{font-weight: bold!important;}
.signature-section {
    margin-top: 40px; 
    width: 30%; 
    float: right; 
    text-align: center;
}
.signature-section p {
    margin-bottom: 2px; /* Jarak antar baris dibuat rapat */
}
.signature-section .name {
    margin-top: 60px; /* Jarak untuk ruang tanda tangan diberikan di sini */
    text-decoration: underline; 
    font-weight: bold;
}        @media print{ body{-webkit-print-color-adjust: exact;} @page{size: A4 portrait; margin: 15mm;} }
    </style>
</head>
<body>
<div class="container">
    <div class="kop-surat">
        <img src="<?= $logo_base64 ?>" alt="Logo Perusahaan">
        <div class="kop-text">
            <h3>PT. HASTA BANGUN NUSANTARA</h3>
            <h2>General Contractor & Developer</h2>
            <p>Jalan Cakraninggrat, Kauman, Kabupaten Ponorogo, Jawa Timur 63414<br>
            Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
        </div>
    </div>
    <p class="report-title">LAPORAN DETAIL REALISASI ANGGARAN</p>

    <div class="info-section">
        <table>
            <tr>
                <td class="label">ID RAB</td><td>: <?= 'RABM' . date('Y', strtotime($header_data['tanggal_mulai_mt'])) . $header_data['id_rab_material'] ?></td>
                <td class="label">Tanggal Mulai</td><td>: <?= date('d F Y', strtotime($header_data['tanggal_mulai_mt'])) ?></td>
            </tr>
            <tr>
                <td class="label">Nama Perumahan</td><td>: <?= htmlspecialchars($header_data['nama_perumahan']) ?></td>
                <td class="label">Tanggal Selesai</td><td>: <?= date('d F Y', strtotime($header_data['tanggal_selesai_mt'])) ?></td>
            </tr>
            <tr>
                <td class="label">Kavling / Blok</td><td>: <?= htmlspecialchars($header_data['kavling']) ?> (Tipe: <?= htmlspecialchars($header_data['type_proyek']) ?>)</td>
                <td class="label">Mandor</td><td>: <?= htmlspecialchars($header_data['nama_mandor']) ?></td>
            </tr>
        </table>
    </div>

    <div class="kpi-section">
        <div class="kpi-card"><div class="title">TOTAL NILAI ANGGARAN</div><div class="value">Rp <?= number_format($header_data['total_anggaran'], 0, ',', '.') ?></div></div>
        <div class="kpi-card"><div class="title">TOTAL REALISASI</div><div class="value">Rp <?= number_format($total_realisasi, 0, ',', '.') ?></div></div>
        <div class="kpi-card"><div class="title">SISA ANGGARAN</div><div class="value">Rp <?= number_format($sisa_anggaran, 0, ',', '.') ?></div></div>
    </div>

    <h4 style="margin-top: 20px;">Ringkasan Anggaran</h4>
    <table class="detail-table">
        <thead>
            <tr><th>Nama Proyek</th><th class="text-center">Anggaran (RAB)</th><th class="text-center">Realisasi (Terpakai)</th><th class="text-center">Selisih</th></tr>
        </thead>
        <tbody>
            <?php foreach ($laporan_data as $data): ?>
            <tr>
                <td><?= $data['nama_proyek'] ?></td>
                <td class="text-center">Rp <?= number_format($data['anggaran'], 0, ',', '.') ?></td>
                <td class="text-center">Rp <?= number_format($data['realisasi'], 0, ',', '.') ?></td>
                <td class="text-center fw-bold <?= ($data['selisih'] >= 0) ? 'text-success' : 'text-danger' ?>">
                    Rp <?= number_format(abs($data['selisih']), 0, ',', '.') ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 style="font-size: 13px;">Rincian Realisasi Biaya per Material</h4>
    <table class="detail-table">
        <thead>
            <tr><th>No.</th><th>Nama Material</th><th class="text-center">Volume Terpakai</th><th class="text-center">Nilai Realisasi (Rp)</th></tr>
        </thead>
        <tbody>
            <?php $nomor_detail = 1; $total_nilai_detail = 0; foreach($detail_realisasi as $detail): ?>
            <tr>
                <td class="text-center"><?= $nomor_detail++ ?></td>
                <td><?= htmlspecialchars($detail['nama_material']) ?></td>
                <td class="text-center"><?= number_format($detail['volume'], 2, ',', '.') ?> <?= htmlspecialchars($detail['satuan']) ?></td>
                <td class="text-center">Rp <?= number_format($detail['nilai'], 0, ',', '.') ?></td>
            </tr>
            <?php $total_nilai_detail += $detail['nilai']; endforeach; ?>
        </tbody>
        <tfoot>
            <tr><th colspan="3" class="text-end fw-bold">Total Realisasi</th><th class="text-center fw-bold">Rp <?= number_format($total_nilai_detail, 0, ',', '.') ?></th></tr>
        </tfoot>
    </table>

<div class="signature-section">
        <p>Ponorogo, <?= strftime('%d %B %Y') ?></p>
        <p>Mengetahui,</p>
        
        <br><br> <p class="name">( Ir.Purwo Hermanto )</p>
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
