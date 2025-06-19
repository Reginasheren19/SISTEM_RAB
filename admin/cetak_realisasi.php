<?php
session_start();
include("../config/koneksi_mysql.php");

// Pastikan ada session aktif
if (!isset($_SESSION['id_user'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// Ambil parameter filter
$proyek_filter = $_GET['proyek'] ?? 'semua';
$mandor_filter = $_GET['mandor'] ?? 'semua';
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

// Bangun query utama dengan filter dinamis
$sql = "SELECT 
            mpr.id_proyek,
            CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek,
            ru.total_rab_upah,
            (SELECT SUM(pu.total_pengajuan) 
             FROM pengajuan_upah pu 
             WHERE pu.id_rab_upah = ru.id_rab_upah AND pu.status_pengajuan = 'dibayar'
             " . (!empty($tanggal_mulai) ? "AND pu.tanggal_pengajuan >= '" . mysqli_real_escape_string($koneksi, $tanggal_mulai) . "'" : "") . "
             " . (!empty($tanggal_selesai) ? "AND pu.tanggal_pengajuan <= '" . mysqli_real_escape_string($koneksi, $tanggal_selesai) . "'" : "") . "
            ) AS total_terbayar
        FROM master_proyek mpr
        LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        LEFT JOIN rab_upah ru ON mpr.id_proyek = ru.id_proyek";

$where_conditions = [];
if (strtolower($_SESSION['role']) === 'pj proyek') {
    $where_conditions[] = "mpr.id_user_pj = " . (int)$_SESSION['id_user'];
}
if ($proyek_filter !== 'semua') {
    $where_conditions[] = "mpr.id_proyek = " . (int)$proyek_filter;
}
if ($mandor_filter !== 'semua') {
    $where_conditions[] = "mpr.id_mandor = " . (int)$mandor_filter;
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " GROUP BY mpr.id_proyek, nama_proyek, ru.total_rab_upah ORDER BY mpe.nama_perumahan, mpr.kavling ASC";

$result_laporan = mysqli_query($koneksi, $sql);
if (!$result_laporan) {
    die("Query Gagal: " . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Realisasi Anggaran Upah</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <style>
        body { font-family: 'Times New Roman', Times, serif; background-color: #fff; color: #000; }
        .container { width: 100%; padding: 20px; }
        .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
        .kop-surat h3, .kop-surat p { margin: 0; }
        .report-title { text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 18px;}
        .table th, .table td { padding: 0.5rem; vertical-align: middle; border: 1px solid #333 !important; }
        .table th { background-color: #e9ecef !important; text-align: center; }
        .text-end { text-align: right; }
        @media print {
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="kop-surat">
            <h3>PT. HASTA BANGUN NUSANTARA</h3>
            <p>Jalan Cokroaminoto 63414 Ponorogo Jawa Timur</p>
        </div>
        <h5 class="report-title">LAPORAN REALISASI ANGGARAN UPAH</h5>
        <p><strong>Periode:</strong> <?= !empty($tanggal_mulai) ? date("d M Y", strtotime($tanggal_mulai)) : 'Awal' ?> s/d <?= !empty($tanggal_selesai) ? date("d M Y", strtotime($tanggal_selesai)) : 'Akhir' ?></p>
        
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th>Nama Proyek</th>
                    <th style="width: 20%;" class="text-end">Anggaran (RAB)</th>
                    <th style="width: 20%;" class="text-end">Realisasi (Terbayar)</th>
                    <th style="width: 20%;" class="text-end">Sisa Anggaran</th>
                    <th style="width: 10%;" class="text-center">Realisasi (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $grand_total_rab = 0;
                $grand_total_terbayar = 0;
                if ($result_laporan && mysqli_num_rows($result_laporan) > 0):
                    while($row = mysqli_fetch_assoc($result_laporan)): 
                        $total_rab = (float)($row['total_rab_upah'] ?? 0);
                        $total_terbayar = (float)($row['total_terbayar'] ?? 0);
                        $sisa_anggaran = $total_rab - $total_terbayar;
                        $realisasi_persen = ($total_rab > 0) ? ($total_terbayar / $total_rab) * 100 : 0;
                        
                        $grand_total_rab += $total_rab;
                        $grand_total_terbayar += $total_terbayar;
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_proyek']) ?></td>
                    <td class="text-end">Rp <?= number_format($total_rab, 0, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($total_terbayar, 0, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($sisa_anggaran, 0, ',', '.') ?></td>
                    <td class="text-center"><?= number_format($realisasi_persen, 2) ?>%</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="2" class="text-end">TOTAL KESELURUHAN</td>
                    <td class="text-end">Rp <?= number_format($grand_total_rab, 0, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($grand_total_terbayar, 0, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($grand_total_rab - $grand_total_terbayar, 0, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
             <?php else: ?>
                <tr><td colspan="6" class="text-center">Tidak ada data untuk ditampilkan.</td></tr>
            <?php endif; ?>
        </table>
    </div>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
