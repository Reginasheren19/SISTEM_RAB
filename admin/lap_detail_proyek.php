<?php
session_start();
include("../config/koneksi_mysql.php");

// Proteksi & Validasi
if (!isset($_SESSION['id_user'])) {
    die("Akses ditolak.");
}
$proyek_id = isset($_GET['proyek_id']) ? (int)$_GET['proyek_id'] : 0;
if ($proyek_id === 0) {
    die("ID Proyek tidak valid.");
}

// 1. Ambil Info Utama Proyek
$info_sql = "SELECT CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek, mm.nama_mandor, u.nama_lengkap as pj_proyek FROM master_proyek mpr LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user WHERE mpr.id_proyek = $proyek_id";
$proyek_info_result = mysqli_query($koneksi, $info_sql);
$proyek_info = mysqli_fetch_assoc($proyek_info_result);
if (!$proyek_info) {
    die("Proyek tidak ditemukan.");
}

// 2. Ambil data keuangan untuk grafik
$rab_upah_info = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id_rab_upah, total_rab_upah FROM rab_upah WHERE id_proyek = $proyek_id"));
$id_rab_upah = $rab_upah_info['id_rab_upah'] ?? 0;
$total_rab = (float)($rab_upah_info['total_rab_upah'] ?? 0);

$total_realisasi = 0;
if ($id_rab_upah > 0) {
    $realisasi_result = mysqli_query($koneksi, "SELECT SUM(total_pengajuan) as total FROM pengajuan_upah WHERE id_rab_upah = $id_rab_upah AND status_pengajuan = 'dibayar'");
    $total_realisasi = (float)(mysqli_fetch_assoc($realisasi_result)['total'] ?? 0);
}
$sisa_anggaran = $total_rab - $total_realisasi;

// 3. Ambil riwayat pengajuan (termin)
$pengajuan_history = [];
if ($id_rab_upah > 0) {
    $history_sql = "SELECT id_pengajuan_upah, tanggal_pengajuan, total_pengajuan, status_pengajuan FROM pengajuan_upah WHERE id_rab_upah = $id_rab_upah ORDER BY tanggal_pengajuan DESC";
    $history_result = mysqli_query($koneksi, $history_sql);
    if($history_result){ while($row = mysqli_fetch_assoc($history_result)){ $pengajuan_history[] = $row; } }
}
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
        <!-- Sidebar -->
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo">
                <div class="logo-header" data-background-color="dark">
                    <a href="dashboard.php" class="logo">
                        <img src="assets/img/logo/LOGO PT.jpg" alt="Logo PT" class="navbar-brand" height="30" />
                    </a>
                    <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
                </div>
            </div>
            <div class="sidebar-wrapper scrollbar scrollbar-inner">
                <div class="sidebar-content">
                    <ul class="nav nav-secondary">
              <li class="nav-item">
                <a href="dashboard.php">
                  <i class="fas fa-home"></i>
                  <p>Dashboard</p>
                </a>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Transaksi RAB Upah</h4>
              </li>
              <li class="nav-item">
                <a href="transaksi_rab_upah.php">
                  <i class="fas fa-calculator"></i>
                  <p>Rancang RAB Upah</p>
                </a>
              </li>
                            <li class="nav-item">
                <a href="pengajuan_upah.php">
                  <i class="fas fa-hand-holding-usd"></i>
                  <p>Pengajuah Upah</p>
                </a>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Laporan</h4>
              </li>
                            <li class="nav-item">
                <a href="lap_pengajuan_upah.php">
                  <i class="fas fa-file"></i>
                  <p>Pengajuan Upah</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="lap_realisasi_anggaran.php">
                  <i class="fas fa-file"></i>
                  <p>Realisasi Anggaran</p>
                </a>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Mastering Data</h4>
              </li>
<li class="nav-item">
  <a href="master_perumahan.php">
    <i class="fas fa-database"></i>
    <p>Master Perumahan</p>
  </a>
</li>
<li class="nav-item">
  <a href="master_proyek.php">
    <i class="fas fa-database"></i>
    <p>Master Proyek</p>
  </a>
</li>
<li class="nav-item">
  <a href="master_mandor.php">
    <i class="fas fa-database"></i>
    <p>Master Mandor</p>
  </a>
</li>
<li class="nav-item">
  <a href="master_kategori.php">
    <i class="fas fa-database"></i>
    <p>Master Kategori</p>
  </a>
</li>
<li class="nav-item">
  <a href="master_satuan.php">
    <i class="fas fa-database"></i>
    <p>Master Satuan</p>
  </a>
</li>
<li class="nav-item">
  <a href="#" class="disabled">
    <i class="fas fa-database"></i>
    <p>Master Pekerjaan</p>
  </a>
</li>
<li class="nav-item">
  <a href="master_user.php">
    <i class="fas fa-database"></i>
    <p>Master User</p>
  </a>
</li>

            </ul>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->

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
                        <h3 class="fw-bold mb-3">Dashboard Proyek</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="dashboard.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="lap_realisasi_anggaran.php">Monitoring Anggaran</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a>Detail Proyek</a></li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header"><h4 class="card-title"><?= htmlspecialchars($proyek_info['nama_proyek']) ?></h4></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="chart-container" style="height: 300px">
                                                <canvas id="proyekChart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
<h5>Informasi Proyek</h5>
<div class="row">
    <div class="col-md-6">
        <dl>
            <dt>PJ Proyek</dt>
            <dd><?= htmlspecialchars($proyek_info['pj_proyek']) ?></dd>
        </dl>
    </div>
    <div class="col-md-6">
        <dl>
            <dt>Mandor</dt>
            <dd><?= htmlspecialchars($proyek_info['nama_mandor']) ?></dd>
        </dl>
    </div>
</div>

                                            <h5>Ringkasan Keuangan</h5>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between"><span>Total Anggaran (RAB)</span> <strong class="text-primary">Rp <?= number_format($total_rab,0,',','.') ?></strong></li>
                                                <li class="list-group-item d-flex justify-content-between"><span>Total Realisasi</span> <strong class="text-success">Rp <?= number_format($total_realisasi,0,',','.') ?></strong></li>
                                                <li class="list-group-item d-flex justify-content-between"><span>Sisa Anggaran</span> <strong class="text-danger">Rp <?= number_format($sisa_anggaran,0,',','.') ?></strong></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header"><h4 class="card-title">Riwayat Pengajuan Termin</h4></div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead><tr><th class="text-center">ID Pengajuan</th><th class="text-center">Tanggal</th><th class="text-end">Total Diajukan</th><th class="text-center">Status</th></tr></thead>
                                            <tbody>
                                            <?php if (empty($pengajuan_history)): ?>
                                                <tr><td colspan="4" class="text-center text-muted">Belum ada riwayat pengajuan.</td></tr>
                                            <?php else: foreach($pengajuan_history as $hist): ?>
                                                <tr>
                                                    <td class="text-center">PU<?= $hist['id_pengajuan_upah'] ?></td>
                                                    <td class="text-center"><?= date('d M Y', strtotime($hist['tanggal_pengajuan'])) ?></td>
                                                    <td class="text-end">Rp <?= number_format($hist['total_pengajuan'],0,',','.') ?></td>
                                                    <td class="text-center"><span class="badge bg-info"><?= ucwords($hist['status_pengajuan']) ?></span></td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Core JS Files -->
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
        const ctx = document.getElementById('proyekChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Anggaran', 'Realisasi', 'Sisa'],
                datasets: [{
                    label: 'Status Keuangan Proyek (Rp)',
                    data: [<?= $total_rab ?>, <?= $total_realisasi ?>, <?= $sisa_anggaran ?>],
                    backgroundColor: ['#1d7af3', '#28a745', '#dc3545'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); } } } }
            }
        });
    </script>
</body>
</html>
