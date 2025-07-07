<?php
session_start();
include("../config/koneksi_mysql.php");

// Pastikan user sudah login
$logged_in_user_id = $_SESSION['id_user'] ?? 0;
if ($logged_in_user_id === 0) {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

// Ambil parameter filter dari URL (PASTIKAN dari name="perumahan")
$perumahan_filter = $_GET['perumahan'] ?? 'semua';
$mandor_filter = $_GET['mandor'] ?? 'semua';
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

// Ambil data untuk dropdown filter
$perumahan_list = mysqli_query($koneksi, "SELECT id_perumahan, nama_perumahan FROM master_perumahan ORDER BY nama_perumahan ASC");
$mandor_list = mysqli_query($koneksi, "SELECT id_mandor, nama_mandor FROM master_mandor ORDER BY nama_mandor ASC");

// Bangun query utama dengan filter dinamis
$sql = "SELECT 
            mpr.id_proyek,
            CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek,
            ru.total_rab_upah,
            (SELECT SUM(dpu.nilai_upah_diajukan) 
             FROM detail_pengajuan_upah dpu
             JOIN pengajuan_upah pu ON dpu.id_pengajuan_upah = pu.id_pengajuan_upah
             WHERE pu.id_rab_upah = ru.id_rab_upah AND pu.status_pengajuan = 'dibayar'
             " . (!empty($tanggal_mulai) ? "AND pu.tanggal_pengajuan >= '" . mysqli_real_escape_string($koneksi, $tanggal_mulai) . "'" : "") . "
             " . (!empty($tanggal_selesai) ? "AND pu.tanggal_pengajuan <= '" . mysqli_real_escape_string($koneksi, $tanggal_selesai) . "'" : "") . "
            ) AS total_terbayar
        FROM master_proyek mpr
        LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        INNER JOIN rab_upah ru ON mpr.id_proyek = ru.id_proyek";

$where_conditions = [];
if ($perumahan_filter !== 'semua') {
    $where_conditions[] = "mpr.id_perumahan = " . (int)$perumahan_filter;
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

// Bangun query string untuk link download, tambahkan 'laporan'
$download_query_string = http_build_query([
    'laporan' => 'realisasi_anggaran',
    'perumahan' => $perumahan_filter,
    'mandor' => $mandor_filter,
    'tanggal_mulai' => $tanggal_mulai,
    'tanggal_selesai' => $tanggal_selesai,
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Dashboard - Kaiadmin</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/LOGO PT.jpg" type="image/x-icon" />
    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: [ "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons" ],
                urls: ["assets/css/fonts.min.css"],
            },
        });
    </script>
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-panel">
        <div class="main-header">
            <!-- ...header kode... (dipendekkan agar ringkas) -->
        </div>
        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Laporan Realisasi Anggaran Upah</h3>
                    <ul class="breadcrumbs mb-3">
                        <li class="nav-home"><a href="dashboard.php"><i class="icon-home"></i></a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a href="#">Laporan</a></li>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Filter Laporan</h4></div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="filter-perumahan" class="form-label">Perumahan</label>
                                    <select id="filter-perumahan" name="perumahan" class="form-select">
                                        <option value="semua">Semua Perumahan</option>
                                        <?php if($perumahan_list) mysqli_data_seek($perumahan_list, 0); ?>
                                        <?php while($p = mysqli_fetch_assoc($perumahan_list)): ?>
                                            <option value="<?= $p['id_perumahan'] ?>" <?= ($perumahan_filter == $p['id_perumahan']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['nama_perumahan']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filter-mandor" class="form-label">Mandor</label>
                                    <select id="filter-mandor" name="mandor" class="form-select">
                                        <option value="semua">Semua Mandor</option>
                                        <?php if($mandor_list) mysqli_data_seek($mandor_list, 0); ?>
                                        <?php while($m = mysqli_fetch_assoc($mandor_list)): ?>
                                            <option value="<?= $m['id_mandor'] ?>" <?= ($mandor_filter == $m['id_mandor']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['nama_mandor']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="filter-tanggal-mulai" class="form-label">Dari Tanggal</label>
                                    <input type="date" id="filter-tanggal-mulai" name="tanggal_mulai" class="form-control" value="<?= htmlspecialchars($tanggal_mulai) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="filter-tanggal-selesai" class="form-label">Sampai Tanggal</label>
                                    <input type="date" id="filter-tanggal-selesai" name="tanggal_selesai" class="form-control" value="<?= htmlspecialchars($tanggal_selesai) ?>">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Terapkan Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">Perbandingan Anggaran vs Realisasi per Proyek</h4>
                            <a href="cetak_lap_upah.php?<?= $download_query_string ?>" target="_blank" class="btn btn-success btn-round ms-auto">
                                <i class="fas fa-print"></i> Cetak Ringkasan
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="report-table" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Proyek</th>
                                        <th class="text-end">Total Anggaran (RAB)</th>
                                        <th class="text-end">Total Terbayar</th>
                                        <th class="text-end">Sisa Anggaran</th>
                                        <th class="text-center" style="width: 20%;">Realisasi (%)</th>
                                        <th class="text-center" style="width: 15%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_laporan && mysqli_num_rows($result_laporan) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($result_laporan)):
                                            $total_rab = (float)($row['total_rab_upah'] ?? 0);
                                            $total_terbayar = (float)($row['total_terbayar'] ?? 0);
                                            $sisa_anggaran = $total_rab - $total_terbayar;
                                            $realisasi_persen = ($total_rab > 0) ? ($total_terbayar / $total_rab) * 100 : 0;
                                            $progress_color = 'bg-success';
                                            if ($realisasi_persen > 80) $progress_color = 'bg-warning';
                                            if ($realisasi_persen >= 100) $progress_color = 'bg-danger';
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nama_proyek']) ?></td>
                                            <td class="text-end fw-bold">Rp <?= number_format($total_rab, 0, ',', '.') ?></td>
                                            <td class="text-end">Rp <?= number_format($total_terbayar, 0, ',', '.') ?></td>
                                            <td class="text-end fw-bold text-success">Rp <?= number_format($sisa_anggaran, 0, ',', '.') ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar <?= $progress_color ?>" role="progressbar" style="width: <?= number_format($realisasi_persen, 2) ?>%;" aria-valuenow="<?= $realisasi_persen ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?= number_format($realisasi_persen, 2) ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a href="lap_detail_proyek.php?proyek_id=<?= $row['id_proyek'] ?>" class="btn btn-info btn-sm" title="Lihat Dashboard Proyek"><i class="fas fa-chart-line"></i></a>
                                                <a href="cetak_detail_progres.php?proyek_id=<?= $row['id_proyek'] ?>" target="_blank" class="btn btn-success btn-sm" title="Cetak Detail Progres">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center text-muted">Tidak ada data proyek untuk ditampilkan. Coba ubah filter Anda.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<!--   Core JS Files   -->
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<!-- jQuery Scrollbar -->
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<!-- Chart JS -->
<script src="assets/js/plugin/chart.js/chart.min.js"></script>
<!-- jQuery Sparkline -->
<script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>
<!-- Chart Circle -->
<script src="assets/js/plugin/chart-circle/circles.min.js"></script>
<!-- Datatables -->
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<!-- Bootstrap Notify -->
<script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
<!-- jQuery Vector Maps -->
<script src="assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
<script src="assets/js/plugin/jsvectormap/world.js"></script>
<!-- Sweet Alert -->
<script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<!-- Kaiadmin JS -->
<script src="assets/js/kaiadmin.min.js"></script>
<script src="assets/js/setting-demo.js"></script>
<script src="assets/js/demo.js"></script>
<script>
    $(document).ready(function() {
        $('#report-table').DataTable({
            "pageLength": 10,
        });
    });
</script>
</body>
</html>
