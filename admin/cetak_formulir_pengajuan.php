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
$info_sql = "SELECT pu.tanggal_pengajuan, pu.id_rab_upah, mpe.nama_perumahan, mpr.kavling, mm.nama_mandor, u.nama_lengkap AS pj_proyek FROM pengajuan_upah pu JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user WHERE pu.id_pengajuan_upah = $id_pengajuan_upah";
$info_result = mysqli_query($koneksi, $info_sql);
if (!$info_result || mysqli_num_rows($info_result) == 0) { die("Data pengajuan tidak ditemukan."); }
$info = mysqli_fetch_assoc($info_result);
$id_rab_upah = $info['id_rab_upah'];

// 2. Ambil Detail Pekerjaan untuk Pengajuan Ini, lengkap dengan data keuangan
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

// 3. Ambil nama Direktur & Komisaris untuk TTD
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
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #fff; color: #000; font-size: 9px;}
        .container { width: 98%; margin: auto; }
        .kop-surat { text-align: center; margin-bottom: 15px; }
        .kop-surat h3 { font-size: 18px; font-weight: bold; margin: 0; }
        .kop-surat p { font-size: 12px; margin: 2px 0; }
        .report-title { text-align: center; margin-bottom: 15px; font-weight: bold; text-decoration: underline; font-size: 14px;}
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid black; padding: 4px; vertical-align: middle; }
        table.report th { background-color: #e9ecef !important; text-align: center; }
        .category-row td { background-color: #f8f9fa; font-weight: bold; }
        .text-end { text-align: right; } .text-center { text-align: center; }
        .signature-section { margin-top: 30px; width: 100%; }
        .signature-box { text-align: center; width: 33.33%; float: left; }
        .signature-box .name { margin-top: 50px; font-weight: bold; text-decoration: underline; }
        @media print { .no-print { display: none; } @page { size: A4 landscape; margin: 10mm; } }
    </style>
</head>
<body>
    <div class="container my-3">
        <button class="no-print" onclick="window.print()" style="margin-bottom:15px; padding: 8px 12px;">Cetak Formulir</button>
        <div class="kop-surat">
            <h3>PENGAJUAN OPNAME VOLUME PEKERJAAN</h3>
            <p>PROYEK: <?= strtoupper(htmlspecialchars($info['nama_perumahan'])) ?> - KAVLING <?= strtoupper(htmlspecialchars($info['kavling'])) ?> | TANGGAL: <?= strtoupper(strftime('%d %B %Y', strtotime($info['tanggal_pengajuan']))) ?></p>
        </div>
        
        <table class="report">
            <thead>
                <tr>
                    <th style="width: 2%;">No</th>
                    <th style="width: 28%;">Keterangan</th>
                    <th style="width: 12%;">Kontrak (Rp)</th>
                    <th style="width: 12%;">Pencairan (Rp)</th>
                    <th style="width: 12%;">Sisa (Rp)</th>
                    <th style="width: 12%;">Pengajuan (Rp)</th>
                    <th style="width: 10%;">Progress (%)</th>
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
        <div style="clear: both;"></div>
        <div class="signature-section">
            <div class="signature-box"><p>Diajukan oleh,</p><div class="name"><?= htmlspecialchars($info['pj_proyek']) ?></div><p>PJ Proyek</p></div>
            <div class="signature-box"><p>Mengetahui,</p><div class="name"><?= htmlspecialchars($nama_komisaris) ?></div><p>Komisaris</p></div>
            <div class="signature-box"><p>Disetujui oleh,</p><div class="name">Ir. <?= htmlspecialchars($nama_direktur) ?></div><p>Direktur</p></div>
        </div>
    </div>
    <script> window.onload = function() { window.print(); } </script>
</body>
</html>
