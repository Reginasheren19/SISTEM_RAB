<?php
session_start();
include("../config/koneksi_mysql.php");

// =========================================================================
// Pastikan nama session ini sesuai dengan file login.php Anda
$logged_in_user_id = $_SESSION['id_user'] ?? 0;
$user_role = strtolower($_SESSION['role'] ?? 'guest');

// Jika tidak ada session, kembali ke halaman login
if ($logged_in_user_id === 0) {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}
// =========================================================================

// Ambil semua parameter filter dari URL (jika ada)
$status_filter = $_GET['status'] ?? 'semua';
$proyek_filter = $_GET['proyek'] ?? 'semua';
$mandor_filter = $_GET['mandor'] ?? 'semua';
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';


// Ambil data untuk dropdown filter
$proyek_list = mysqli_query($koneksi, "SELECT id_proyek, CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek FROM master_proyek mpr LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan ORDER BY nama_proyek ASC");
$mandor_list = mysqli_query($koneksi, "SELECT id_mandor, nama_mandor FROM master_mandor ORDER BY nama_mandor ASC");

// Bangun query utama
$sql = "SELECT 
            pu.id_pengajuan_upah, 
            pu.tanggal_pengajuan, 
            pu.total_pengajuan, 
            pu.status_pengajuan,
            CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek,
            mm.nama_mandor
        FROM pengajuan_upah pu
        LEFT JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah
        LEFT JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
        LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
        WHERE 1=1";

// Tambahkan filter ke query
if ($status_filter !== 'semua') {
    $sql .= " AND pu.status_pengajuan = '" . mysqli_real_escape_string($koneksi, $status_filter) . "'";
}
if ($proyek_filter !== 'semua') {
    $sql .= " AND ru.id_proyek = " . (int)$proyek_filter;
}
if ($mandor_filter !== 'semua') {
    $sql .= " AND mpr.id_mandor = " . (int)$mandor_filter;
}
if (!empty($tanggal_mulai)) {
    $sql .= " AND pu.tanggal_pengajuan >= '" . mysqli_real_escape_string($koneksi, $tanggal_mulai) . "'";
}
if (!empty($tanggal_selesai)) {
    $sql .= " AND pu.tanggal_pengajuan <= '" . mysqli_real_escape_string($koneksi, $tanggal_selesai) . "'";
}

$sql .= " ORDER BY pu.id_pengajuan_upah DESC";

$result_laporan = mysqli_query($koneksi, $sql);

// Fungsi untuk membuat badge status
function getStatusBadge($status) {
    $status_lower = strtolower($status);
    $badge_class = 'bg-secondary';
    $text_class = '';
    switch ($status_lower) {
        case 'diajukan': $badge_class = 'bg-warning'; $text_class = 'text-dark'; break;
        case 'disetujui': $badge_class = 'bg-success'; break;
        case 'ditolak': $badge_class = 'bg-danger'; break;
        case 'dibayar': $badge_class = 'bg-primary'; break;
    }
    return "<span class='badge {$badge_class} {$text_class}'>" . ucwords($status) . "</span>";
}

// Bangun query string untuk link download
$download_query_string = http_build_query([
    'status' => $status_filter,
    'proyek' => $proyek_filter,
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
                <!-- Logo Header -->
                <div class="main-header-logo">
                    <div class="logo-header" data-background-color="dark">
                        <a href="dashboard.php" class="logo">
                            <img src="assets/img/logo/LOGO PT.jpg" alt="Logo PT" class="navbar-brand" height="30" />
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                            <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                        </div>
                        <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
                    </div>
                </div>
                <!-- End Logo Header -->
                <!-- Navbar Header -->
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="../uploads/user_photos/<?= !empty($_SESSION['profile_pic']) ? htmlspecialchars($_SESSION['profile_pic']) : 'default.jpg' ?>" alt="Foto Profil" class="avatar-img rounded-circle" onerror="this.onerror=null; this.src='assets/img/profile.jpg';">
                                    </div>
                                    <span class="profile-username">
                                        <span class="op-7">Selamat Datang,</span>
                                        <span class="fw-bold"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest') ?></span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box">
                                                <div class="avatar-lg">
                                                    <img src="../uploads/user_photos/<?= !empty($_SESSION['profile_pic']) ? htmlspecialchars($_SESSION['profile_pic']) : 'default.jpg' ?>" alt="Foto Profil" class="avatar-img rounded" onerror="this.onerror=null; this.src='assets/img/profile.jpg';">
                                                </div>
                                                <div class="u-text">
                                                    <h4><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest') ?></h4>
                                                    <p class="text-muted"><?= htmlspecialchars($_SESSION['username'] ?? 'guest') ?></p>
                                                    <a href="profile.php" class="btn btn-xs btn-secondary btn-sm">Lihat Profil</a>
                                                </div>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="profile.php">Pengaturan Akun</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="../logout.php">Logout</a>
                                        </li>
                                    </div>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- End Navbar -->
            </div>

            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Laporan Pengajuan Upah</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="dashboard.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Laporan</a></li>
                        </ul>
                    </div>

                    <!-- Filter Section -->
<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <i class="fas fa-filter me-2"></i> Filter Laporan
        </h4>
    </div>
    <div class="card-body">
        <form id="filter-form" method="GET" action="">
<div class="row g-3 align-items-end">
    <div class="col-md-3">
        <label for="filter-proyek" class="form-label">Proyek</label>
        <select id="filter-proyek" name="proyek" class="form-select">
            <option value="semua">Semua Proyek</option>
            <?php if($proyek_list) mysqli_data_seek($proyek_list, 0); ?>
            <?php while($p = mysqli_fetch_assoc($proyek_list)): ?>
                <option value="<?= $p['id_proyek'] ?>" <?= ($proyek_filter == $p['id_proyek']) ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_proyek']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="col-md-3">
        <label for="filter-mandor" class="form-label">Mandor</label>
        <select id="filter-mandor" name="mandor" class="form-select">
            <option value="semua">Semua Mandor</option>
            <?php if($mandor_list) mysqli_data_seek($mandor_list, 0); ?>
            <?php while($m = mysqli_fetch_assoc($mandor_list)): ?>
                <option value="<?= $m['id_mandor'] ?>" <?= ($mandor_filter == $m['id_mandor']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_mandor']) ?></option>
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
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Terapkan</button>
    </div>
</div>

            <hr class="my-4">
            
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <div class="btn-group">
                        <button type="submit" name="status" value="semua" class="btn btn-outline-primary <?= $status_filter == 'semua' ? 'active' : '' ?>">Semua</button>
                        <button type="submit" name="status" value="diajukan" class="btn btn-outline-primary <?= $status_filter == 'diajukan' ? 'active' : '' ?>">Diajukan</button>
                        <button type="submit" name="status" value="disetujui" class="btn btn-outline-primary <?= $status_filter == 'disetujui' ? 'active' : '' ?>">Disetujui</button>
                        <button type="submit" name="status" value="ditolak" class="btn btn-outline-primary <?= $status_filter == 'ditolak' ? 'active' : '' ?>">Ditolak</button>
                        <button type="submit" name="status" value="dibayar" class="btn btn-outline-primary <?= $status_filter == 'dibayar' ? 'active' : '' ?>">Dibayar</button>
                    </div>
                </div>
                <div>
                    <a href="cetak_lap_upah.php?laporan=pengajuan_upah&<?= $download_query_string ?>" target="_blank" class="btn btn-success">
                        <i class="fas fa-download me-1"></i> Unduh Laporan (PDF)
                    </a>
                </div>
            </div>

        </form>
    </div>
</div>
                    
                    <!-- Tabel Laporan -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">
                <i class="fas fa-table me-2"></i> Hasil Laporan
            </h4>
        </div>
    </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="report-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Proyek</th>
                                            <th>Mandor</th>
                                            <th class="text-center">Tanggal Pengajuan</th>
                                            <th class="text-end">Total Pengajuan</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result_laporan && mysqli_num_rows($result_laporan) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($result_laporan)): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['id_pengajuan_upah']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_proyek']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_mandor']) ?></td>
                                                <td class="text-center"><?= date("d M Y", strtotime($row['tanggal_pengajuan'])) ?></td>
                                                <td class="text-end">Rp <?= number_format($row['total_pengajuan'], 0, ',', '.') ?></td>
                                                <td class="text-center"><?= getStatusBadge($row['status_pengajuan']) ?></td>
                                                <td class="text-center">
                                                    <a href="cetak_formulir_pengajuan.php?id=<?= $row['id_pengajuan_upah'] ?>" target="_blank" class="btn btn-success btn-sm" title="Cetak Laporan">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">Tidak ada data yang cocok dengan filter yang dipilih.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <div class="copyright">
                        2024, made with <i class="fa fa-heart heart text-danger"></i> by <a href="#">Your Company</a>
                    </div>
                </div>
            </footer>
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

    <!-- Kaiadmin DEMO methods, don't include it in your project! -->
    <script src="assets/js/setting-demo.js"></script>
    <script src="assets/js/demo.js"></script>
    <script>
        $(document).ready(function() {
            $('#report-table').DataTable();

            // [BARU] Logika untuk membuat tombol status memfilter form
            $('#status-buttons .filter-btn').on('click', function() {
                var status = $(this).data('status');
                $('#status-input').val(status);
                $('#filter-form').submit();
            });
        });
    </script>
</body>
</html>
