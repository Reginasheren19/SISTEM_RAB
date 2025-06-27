<?php
session_start();
include("../config/koneksi_mysql.php");

// Proteksi & Validasi Input
if (!isset($_SESSION['id_user'])) { die("Akses ditolak."); }
$id_pengajuan_upah = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_pengajuan_upah === 0) { die("ID Pengajuan tidak valid."); }

// Fungsi konversi ke angka romawi
function toRoman($num) {
    $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $result = '';
    foreach ($map as $roman => $value) { while ($num >= $value) { $result .= $roman; $num -= $value; } }
    return $result;
}

// 1. Ambil Info Utama Pengajuan & Proyek
$info_sql = "
    SELECT 
        pu.tanggal_pengajuan, pu.total_pengajuan, pu.id_rab_upah,
        mpe.nama_perumahan, mpr.kavling, mm.nama_mandor, u.nama_lengkap AS pj_proyek,
        ru.total_rab_upah, ru.tanggal_mulai, ru.tanggal_selesai
    FROM pengajuan_upah pu
    JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah
    JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
    LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
    LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
    LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
    WHERE pu.id_pengajuan_upah = $id_pengajuan_upah
";
$info_result = mysqli_query($koneksi, $info_sql);
if (!$info_result || mysqli_num_rows($info_result) == 0) { die("Data pengajuan tidak ditemukan."); }
$info = mysqli_fetch_assoc($info_result);
$id_rab_upah = $info['id_rab_upah'];

// 2. Hitung ini termin ke berapa
$sql_termin = "SELECT COUNT(id_pengajuan_upah) AS termin_ke FROM pengajuan_upah WHERE id_rab_upah = $id_rab_upah AND id_pengajuan_upah <= $id_pengajuan_upah";
$termin_ke = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_termin))['termin_ke'];

// 3. Ambil Detail Pekerjaan untuk Pengajuan Ini
$detail_sql = "
    SELECT 
        k.nama_kategori, 
        mp.uraian_pekerjaan, 
        dr.sub_total AS nilai_kontrak_item,
        dpu.nilai_upah_diajukan AS nilai_pengajuan_ini,
        (SELECT COALESCE(SUM(prev_dpu.nilai_upah_diajukan), 0) 
         FROM detail_pengajuan_upah prev_dpu
         JOIN pengajuan_upah prev_pu ON prev_dpu.id_pengajuan_upah = prev_pu.id_pengajuan_upah
         WHERE prev_dpu.id_detail_rab_upah = dr.id_detail_rab_upah AND prev_pu.id_pengajuan_upah < $id_pengajuan_upah AND prev_pu.status_pengajuan = 'dibayar'
        ) AS pencairan_lalu
    FROM detail_pengajuan_upah dpu
    JOIN detail_rab_upah dr ON dpu.id_detail_rab_upah = dr.id_detail_rab_upah
    LEFT JOIN master_pekerjaan mp ON dr.id_pekerjaan = mp.id_pekerjaan
    LEFT JOIN master_kategori k ON dr.id_kategori = k.id_kategori
    WHERE dpu.id_pengajuan_upah = $id_pengajuan_upah
    ORDER BY k.id_kategori, mp.id_pekerjaan
";
$detail_result = mysqli_query($koneksi, $detail_sql);
if (!$detail_result) { die("Gagal mengambil detail pekerjaan: " . mysqli_error($koneksi)); }

// 4. Ambil nama Direktur & Komisaris untuk TTD
$sql_direktur = "SELECT nama_lengkap FROM master_user WHERE role = 'direktur' LIMIT 1";
$nama_direktur = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_direktur))['nama_lengkap'] ?? '.....................';
$nama_komisaris = "Hastut Pantjarini, SE"; 
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Formulir Pengajuan #<?= $id_pengajuan_upah ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <style>
        body { font-family: 'Tahoma', sans-serif; background-color: #fff; color: #000; font-size: 11px; }
        .container { max-width: 800px; margin: auto; }
        .kop-surat { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
.kop-surat img { width: 100px; height: auto; margin-left: 40px; } /* Geser logo ke kanan */
        .kop-surat .kop-text { text-align: center; flex-grow: 1; }
        .kop-surat h3 { font-size: 22px; font-weight: bold; margin: 0; }
                .kop-surat h2 { font-size: 18px; font-weight: bold; margin: 0; }
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
        .tagline {
    font-style: italic;
}


        @media print { 
            .no-print { display: none; } 
            @page { 
                size: A4 portrait; 
                margin: 10mm; 
            } 
        }
    </style>
</head>
<body>
    <div class="container my-3">
        <button class="no-print" onclick="window.print()" style="margin-bottom:15px; padding: 8px 12px;">Cetak Formulir</button>
        
        <!-- [DIUBAH] Menggunakan Format Kop Surat & Header dari get_pengajuan_upah.php -->
<div class="kop-surat">
    <img src="assets/img/logo/LOGO PT.jpg" alt="Logo Perusahaan" onerror="this.style.display='none'">
    <div class="kop-text">
        <h3>PT. HASTA BANGUN NUSANTARA</h3>
        <h2 class ="tagline">General Contractor & Developer</h2>
                <p>Jalan Cakraninggrat, Kauman, Kabupaten Ponorogo, Jawa Timur 63414</p>
                <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
    </div>
</div>
        <h5 class="report-title">PENGAJUAN OPNAME VOLUME PEKERJAAN</h5>

        <div class="info-section mb-4">
            <div class="row">
                <div class="col-7">
                    <table class="table table-sm">
                        <tr><td>ID Pengajuan</td><td>: PU<?= htmlspecialchars($id_pengajuan_upah) ?>/ Termin Pengajuan ke-<?= $termin_ke ?></td></tr>
                        <tr><td>Nama Perumahan</td><td>: <?= htmlspecialchars($info['nama_perumahan']) ?></td></tr>
                        <tr><td>Kavling / Blok</td><td>: <?= htmlspecialchars($info['kavling']) ?></td></tr>
                    </table>
                </div>
                <div class="col-5">
                    <table class="table table-sm">
                        <tr><td>Tanggal Pengajuan</td><td>: <?= date("d F Y", strtotime($info['tanggal_pengajuan'])) ?></td></tr>
                        <tr><td>Mandor</td><td>: <?= htmlspecialchars($info['nama_mandor']) ?></td></tr>
                        <tr><td>PJ Proyek</td><td>: <?= htmlspecialchars($info['pj_proyek']) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        
        <table class="report">
            <thead>
                <tr>
                    <th style="width: 3%;">No</th>
                    <th style="width: 32%;">Keterangan</th>
                    <th style="width: 13%;">Kontrak (Rp)</th>
                    <th style="width: 13%;">Pencairan (Rp)</th>
                    <th style="width: 13%;">Sisa (Rp)</th>
                    <th style="width: 13%;">Pengajuan (Rp)</th>
                    <th style="width: 11%;">Progress (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($detail_result) > 0):
                    mysqli_data_seek($detail_result, 0);
                    $prevKategori = null; $noKategori = 0; $noPekerjaan = 1;
                    $total_kontrak = 0; $total_pencairan_lalu = 0; $total_sisa = 0; $total_pengajuan_ini = 0;
                    while($row = mysqli_fetch_assoc($detail_result)):
                        if ($prevKategori !== $row['nama_kategori']) {
                            $noKategori++;
                            echo "<tr class='category-row'><td class='text-center'>" . toRoman($noKategori) . "</td><td colspan='6'>" . htmlspecialchars($row['nama_kategori']) . "</td></tr>";
                            $prevKategori = $row['nama_kategori']; $noPekerjaan = 1;
                        }
                        $kontrak_item = (float) $row['nilai_kontrak_item'];
                        $pencairan_lalu_item = (float) $row['pencairan_lalu'];
                        $pengajuan_ini_item = (float) $row['nilai_pengajuan_ini'];
                        $sisa_item = $kontrak_item - $pencairan_lalu_item;
                        $progress_item = ($kontrak_item > 0) ? (($pencairan_lalu_item + $pengajuan_ini_item) / $kontrak_item) * 100 : 0;
                        
                        $total_kontrak += $kontrak_item;
                        $total_pencairan_lalu += $pencairan_lalu_item;
                        $total_sisa += $sisa_item;
                        $total_pengajuan_ini += $pengajuan_ini_item;
                ?>
                    <tr>
                        <td class="text-center"><?= $noPekerjaan++ ?></td>
                        <td><?= htmlspecialchars($row['uraian_pekerjaan']) ?></td>
                        <td class="text-end"><?= number_format($kontrak_item, 0, ',', '.') ?></td>
                        <td class="text-end"><?= number_format($pencairan_lalu_item, 0, ',', '.') ?></td>
                        <td class="text-end"><?= number_format($sisa_item, 0, ',', '.') ?></td>
                        <td class="text-end"><?= number_format($pengajuan_ini_item, 0, ',', '.') ?></td>
                        <td class="text-center"><?= number_format($progress_item, 2) ?>%</td>
                    </tr>
                <?php endwhile; ?>
                    <tr style="font-weight: bold; background-color: #e9ecef;">
                        <td colspan="2" class="text-center">TOTAL</td>
                        <td class="text-end"><?= number_format($total_kontrak, 0, ',', '.') ?></td>
                        <td class="text-end"><?= number_format($total_pencairan_lalu, 0, ',', '.') ?></td>
                        <td class="text-end"><?= number_format($total_sisa, 0, ',', '.') ?></td>
                        <td class="text-end"><?= number_format($total_pengajuan_ini, 0, ',', '.') ?></td>
                        <td></td>
                    </tr>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">Tidak ada rincian pekerjaan untuk pengajuan ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Tambahkan tempat tanggal -->
<div class="date-box">
    <p class="text-end" style="margin-top: 30px;">Ponorogo, <?= date("d F Y") ?></p>
</div>
        <div style="clear: both;"></div>
        <div class="signature-section">
            <div class="signature-box"><p>Diajukan oleh,</p><div class="name"><?= htmlspecialchars($info['pj_proyek']) ?></div><p>PJ Proyek</p></div>
            <div class="signature-box"><p>Mengetahui,</p><div class="name"><?= htmlspecialchars($nama_komisaris) ?></div><p>Komisaris</p></div>
            <div class="signature-box"><p>Disetujui oleh,</p><div class="name"><?= htmlspecialchars($nama_direktur) ?></div><p>Direktur</p></div>
        </div>
    </div>
    <script> window.onload = function() { window.print(); } </script>
</body>
</html>
