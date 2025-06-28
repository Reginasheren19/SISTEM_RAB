<?php
session_start();
include("../../config/koneksi_mysql.php");

// BAGIAN 1: Mengambil Filter dari URL
$id_perumahan_filter = $_GET['perumahan'] ?? null;
$id_proyek_filter = $_GET['proyek'] ?? 'semua';
$tanggal_mulai = $_GET['start'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['end'] ?? date('Y-m-t');
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');

// BAGIAN 2: LOGIKA PENGAMBILAN DATA
$laporan_data = [];

$sql_proyek_rab = "
    SELECT 
        p.id_proyek,
        CONCAT(per.nama_perumahan, ' - Kavling: ', p.kavling, ' (Tipe: ', p.type_proyek, ')') AS nama_proyek_lengkap,
        rab.total_rab_material AS total_anggaran
    FROM master_proyek p
    JOIN master_perumahan per ON p.id_perumahan = per.id_perumahan
    INNER JOIN rab_material rab ON p.id_proyek = rab.id_proyek
";
$where_conditions = [];
$params = [];
$param_types = '';
if (!empty($id_perumahan_filter)) {
    $where_conditions[] = "p.id_perumahan = ?";
    $params[] = $id_perumahan_filter;
    $param_types .= 'i';
}
if ($id_proyek_filter != 'semua' && is_numeric($id_proyek_filter)) {
    $where_conditions[] = "p.id_proyek = ?";
    $params[] = $id_proyek_filter;
    $param_types .= 'i';
}
if (!empty($where_conditions)) {
    $sql_proyek_rab .= " WHERE " . implode(' AND ', $where_conditions);
}
$sql_proyek_rab .= " ORDER BY per.nama_perumahan, p.kavling";

$stmt_proyek_rab = $koneksi->prepare($sql_proyek_rab);
if ($stmt_proyek_rab) {
    if (!empty($params)) {
        $stmt_proyek_rab->bind_param($param_types, ...$params);
    }
    $stmt_proyek_rab->execute();
    $result_proyek_rab = $stmt_proyek_rab->get_result();
    while ($proyek = $result_proyek_rab->fetch_assoc()) {
        $current_proyek_id = $proyek['id_proyek'];
        $total_realisasi_proyek = 0;
        $sql_distribusi = "SELECT dd.id_material, SUM(dd.jumlah_distribusi) AS total_kuantitas FROM detail_distribusi dd JOIN distribusi_material dm ON dd.id_distribusi = dm.id_distribusi WHERE dm.id_proyek = ? AND dm.tanggal_distribusi BETWEEN ? AND ? GROUP BY dd.id_material";
        $stmt_distribusi = $koneksi->prepare($sql_distribusi);
        $stmt_distribusi->bind_param("iss", $current_proyek_id, $tanggal_mulai, $tanggal_selesai);
        $stmt_distribusi->execute();
        $result_distribusi = $stmt_distribusi->get_result();
        while($item_distribusi = $result_distribusi->fetch_assoc()){
            $id_material = $item_distribusi['id_material'];
            $kuantitas_terpakai = (float)$item_distribusi['total_kuantitas'];
            $stmt_harga = $koneksi->prepare("SELECT SUM(sub_total_pp) / NULLIF(SUM(quantity), 0) AS harga_rata_rata FROM detail_pencatatan_pembelian WHERE id_material = ? AND quantity > 0 AND harga_satuan_pp > 0");
            $stmt_harga->bind_param("i", $id_material);
            $stmt_harga->execute();
            $harga_rata_rata = (float)($stmt_harga->get_result()->fetch_assoc()['harga_rata_rata'] ?? 0);
            $stmt_harga->close();
            $total_realisasi_proyek += $kuantitas_terpakai * $harga_rata_rata;
        }
        $stmt_distribusi->close();
        $laporan_data[] = ['nama_proyek' => $proyek['nama_proyek_lengkap'], 'anggaran' => $proyek['total_anggaran'], 'realisasi' => $total_realisasi_proyek, 'selisih' => $proyek['total_anggaran'] - $total_realisasi_proyek];
    }
    $stmt_proyek_rab->close();
}

// Logika untuk logo
$path_logo = '../assets/img/logo/LOGO PT.jpg';
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
    <title>Laporan Realisasi Anggaran - <?= date('d M Y') ?></title>
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

    <p class="report-title">LAPORAN REALISASI ANGGARAN MATERIAL</p>
    <p class="report-period">Periode Distribusi: <?= date('d F Y', strtotime($tanggal_mulai)) ?> s/d <?= date('d F Y', strtotime($tanggal_selesai)) ?></p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Proyek</th>
                <th>Anggaran (RAB)</th>
                <th>Realisasi (Terpakai)</th>
                <th>Selisih</th>
                <th>Persentase</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($laporan_data)): $nomor = 1; foreach ($laporan_data as $data): ?>
            <tr>
                <td class="text-center"><?= $nomor++ ?></td>
                <td><?= htmlspecialchars($data['nama_proyek']) ?></td>
                <td class="text-end">Rp <?= number_format($data['anggaran'], 2, ',', '.') ?></td>
                <td class="text-end">Rp <?= number_format($data['realisasi'], 2, ',', '.') ?></td>
                <td class="text-end fw-bold <?= ($data['selisih'] >= 0) ? 'text-success' : 'text-danger' ?>">
                    Rp <?= number_format(abs($data['selisih']), 2, ',', '.') ?>
                </td>
                <td class="text-center fw-bold <?= ($data['realisasi'] > $data['anggaran']) ? 'text-danger' : 'text-success' ?>">
                    <?php 
                    $persentase = ($data['anggaran'] > 0) ? ($data['realisasi'] / $data['anggaran']) * 100 : 0;
                    echo number_format($persentase, 1) . '%';
                    ?>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center"><i>Tidak ada data untuk filter yang dipilih.</i></td></tr>
            <?php endif; ?>
        </tbody>
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