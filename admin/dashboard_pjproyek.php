<?php
session_start();
include("../config/koneksi_mysql.php");

// =========================================================================
$logged_in_user_id = $_SESSION['id_user'] ?? 0;
$user_role = strtolower($_SESSION['role'] ?? 'guest');

if ($logged_in_user_id === 0) {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}
// =========================================================================

// --- [PERBAIKAN] LOGIKA FILTER YANG LEBIH AMAN ---
$pj_proyek_filter_proyek = ''; // Untuk filter di tabel master_proyek
$pj_proyek_filter_join = '';   // Untuk filter di query yang sudah di-JOIN

if ($user_role === 'pj proyek') {
    $safe_user_id = (int) $logged_in_user_id;
    $pj_proyek_filter_proyek = " WHERE id_user_pj = $safe_user_id";
    $pj_proyek_filter_join = " AND mpr.id_user_pj = $safe_user_id";
}

// Fungsi bantu untuk membuat query KPI lebih aman
function get_kpi_value($koneksi, $sql) {
    $result = mysqli_query($koneksi, $sql);
    if ($result) {
        return mysqli_fetch_assoc($result)['total'] ?? 0;
    }
    // Jika query gagal, ini akan membantu debugging di log server
    error_log("Dashboard Query Failed: " . mysqli_error($koneksi));
    return 0;
}

// --- 1. PENGAMBILAN DATA UNTUK KARTU KPI (DENGAN QUERY BARU) ---
$total_proyek = get_kpi_value($koneksi, "SELECT COUNT(id_proyek) as total FROM master_proyek" . $pj_proyek_filter_proyek);
$total_diajukan = get_kpi_value($koneksi, "SELECT COUNT(pu.id_pengajuan_upah) as total FROM pengajuan_upah pu LEFT JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah LEFT JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek WHERE pu.status_pengajuan = 'diajukan'" . $pj_proyek_filter_join);
$total_ditolak = get_kpi_value($koneksi, "SELECT COUNT(pu.id_pengajuan_upah) as total FROM pengajuan_upah pu LEFT JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah LEFT JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek WHERE pu.status_pengajuan = 'ditolak'" . $pj_proyek_filter_join);
$total_dibayar_rp = get_kpi_value($koneksi, "SELECT SUM(pu.total_pengajuan) as total FROM pengajuan_upah pu LEFT JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah LEFT JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek WHERE pu.status_pengajuan = 'dibayar'" . $pj_proyek_filter_join);


// --- 2. PENGAMBILAN DATA UNTUK GRAFIK & TABEL (DENGAN QUERY BARU) ---
$where_clause_untuk_join = ltrim($pj_proyek_filter_join, ' AND'); // Menghilangkan ' AND' jika ada

// Data untuk Tabel Pengajuan Terbaru
$pengajuan_terbaru = [];
$sql_terbaru = "SELECT pu.id_pengajuan_upah, pu.total_pengajuan, pu.status_pengajuan, CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek FROM pengajuan_upah pu LEFT JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah LEFT JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan" . ($where_clause_untuk_join ? " WHERE " . $where_clause_untuk_join : "") . " ORDER BY pu.id_pengajuan_upah DESC LIMIT 5";
$result_terbaru = mysqli_query($koneksi, $sql_terbaru);
if($result_terbaru) { while($row = mysqli_fetch_assoc($result_terbaru)) { $pengajuan_terbaru[] = $row; } }

// Data untuk Donut Chart Status
$chart_status_data = [];
$sql_status_chart = "SELECT status_pengajuan, COUNT(id_pengajuan_upah) as jumlah FROM pengajuan_upah pu LEFT JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah LEFT JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek" . ($where_clause_untuk_join ? " WHERE " . $where_clause_untuk_join : "") . " GROUP BY status_pengajuan";
$result_status_chart = mysqli_query($koneksi, $sql_status_chart);
if ($result_status_chart) { while ($row = mysqli_fetch_assoc($result_status_chart)) { $chart_status_data[$row['status_pengajuan']] = $row['jumlah']; } }

// Data untuk Tabel Progres Proyek
$proyek_progres = [];
$sql_proyek_progres = "SELECT CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) as nama_proyek, ru.total_rab_upah as total_rab, COALESCE((SELECT SUM(pu.total_pengajuan) FROM pengajuan_upah pu WHERE pu.id_rab_upah = ru.id_rab_upah AND pu.status_pengajuan = 'dibayar'), 0) as total_realisasi FROM rab_upah ru JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan" . ($where_clause_untuk_join ? " WHERE " . $where_clause_untuk_join : "") . " GROUP BY ru.id_rab_upah ORDER BY nama_proyek ASC";
$result_proyek_progres = mysqli_query($koneksi, $sql_proyek_progres);
if ($result_proyek_progres) { while($row = mysqli_fetch_assoc($result_proyek_progres)) { $proyek_progres[] = $row; } }
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
                            <li class="nav-item">
                <a href="lap_rekapitulasi_proyek.php">
                  <i class="fas fa-file"></i>
                  <p>Rekapitulasi Proyek</p>
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
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Dashboard PJ Proyek</h3>
                            <h6 class="op-7 mb-2">Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Pengguna') ?>!</h6>
                        </div>
                    </div>

                    <!-- Kartu Statistik -->
                    <div class="row">
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-primary bubble-shadow-small"><i class="fas fa-building"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Proyek Saya</p><h4 class="card-title"><?= $total_proyek ?></h4></div></div></div></div></div></div>
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-warning bubble-shadow-small"><i class="fas fa-hourglass-half"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Menunggu Persetujuan</p><h4 class="card-title"><?= $total_diajukan ?></h4></div></div></div></div></div></div>
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-danger bubble-shadow-small"><i class="fas fa-times-circle"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Pengajuan Ditolak</p><h4 class="card-title"><?= $total_ditolak ?></h4></div></div></div></div></div></div>
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-success bubble-shadow-small"><i class="fas fa-money-check-alt"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Dana Telah Cair</p><h4 class="card-title">Rp <?= number_format($total_dibayar_rp, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                    </div>
                    
                    <!-- [DIUBAH] Status Chart dan Tabel Pengajuan -->
                    <div class="row">
                        <div class="col-md-5">
                             <div class="card">
                                <div class="card-header"><div class="card-title">Status Pengajuan Saya</div></div>
                                <div class="card-body"><div class="chart-container" style="min-height: 300px"><canvas id="statusDonutChart"></canvas></div></div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="card card-round">
                                <div class="card-header"><div class="card-title">5 Pengajuan Terakhir Saya</div></div>
                                <div class="card-body"><div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead><tr><th>ID</th><th>Proyek</th><th>Total</th><th class="text-center">Status</th></tr></thead>
                                        <tbody>
                                        <?php if(empty($pengajuan_terbaru)): ?>
                                            <tr><td colspan="4" class="text-center text-muted">Belum ada data pengajuan.</td></tr>
                                        <?php else: foreach($pengajuan_terbaru as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['id_pengajuan_upah']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_proyek']) ?></td>
                                                <td>Rp <?= number_format($row['total_pengajuan'], 0, ',', '.') ?></td>
                                                <td class="text-center"><span class="badge bg-<?= strtolower($row['status_pengajuan']) == 'diajukan' ? 'warning text-dark' : (strtolower($row['status_pengajuan']) == 'disetujui' ? 'success' : (strtolower($row['status_pengajuan']) == 'ditolak' ? 'danger' : 'primary')) ?>"><?= ucwords($row['status_pengajuan']) ?></span></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div></div>
                            </div>
                        </div>
                    </div>

                                        <!-- [DIUBAH] Monitoring Progres Proyek -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header"><div class="card-title">Monitoring Progres Proyek Saya</div></div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped mt-3">
                                            <thead><tr><th>Nama Proyek</th><th class="text-end">Anggaran</th><th class="text-end">Realisasi</th><th style="width: 30%;">Progres Pembayaran</th></tr></thead>
                                            <tbody>
                                            <?php if (empty($proyek_progres)): ?>
                                                <tr><td colspan="4" class="text-center text-muted py-3">Belum ada proyek dengan RAB.</td></tr>
                                            <?php else: foreach ($proyek_progres as $p): 
                                                $total_rab_p = (float) $p['total_rab'];
                                                $total_realisasi_p = (float) $p['total_realisasi'];
                                                $persentase = ($total_rab_p > 0) ? ($total_realisasi_p / $total_rab_p) * 100 : 0;
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($p['nama_proyek']) ?></td>
                                                    <td class="text-end">Rp <?= number_format($total_rab_p, 0, ',', '.') ?></td>
                                                    <td class="text-end">Rp <?= number_format($total_realisasi_p, 0, ',', '.') ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 22px;">
                                                            <div class="progress-bar" role="progressbar" style="width: <?= $persentase ?>%;" aria-valuenow="<?= $persentase ?>" aria-valuemin="0" aria-valuemax="100"><?= number_format($persentase, 1) ?>%</div>
                                                        </div>
                                                    </td>
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

        <footer class="footer">
          <div class="container-fluid d-flex justify-content-between">
            <nav class="pull-left">
              <ul class="nav">
            </nav>
            <div class="copyright">
              made with <i class="fa fa-heart heart text-danger"></i> PT. Hasta Bangun Nusantara
            </div>
            <div>
              2025
            </div>
          </div>
        </footer>
      </div>

      <!-- Custom template | don't include it in your project! -->
      <div class="custom-template">
        <div class="title">Settings</div>
        <div class="custom-content">
          <div class="switcher">
            <div class="switch-block">
              <h4>Logo Header</h4>
              <div class="btnSwitch">
                <button
                  type="button"
                  class="selected changeLogoHeaderColor"
                  data-color="dark"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="blue"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="purple"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="light-blue"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="green"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="orange"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="red"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="white"
                ></button>
                <br />
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="dark2"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="blue2"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="purple2"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="light-blue2"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="green2"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="orange2"
                ></button>
                <button
                  type="button"
                  class="changeLogoHeaderColor"
                  data-color="red2"
                ></button>
              </div>
            </div>
            <div class="switch-block">
              <h4>Navbar Header</h4>
              <div class="btnSwitch">
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="dark"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="blue"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="purple"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="light-blue"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="green"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="orange"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="red"
                ></button>
                <button
                  type="button"
                  class="selected changeTopBarColor"
                  data-color="white"
                ></button>
                <br />
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="dark2"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="blue2"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="purple2"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="light-blue2"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="green2"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="orange2"
                ></button>
                <button
                  type="button"
                  class="changeTopBarColor"
                  data-color="red2"
                ></button>
              </div>
            </div>
            <div class="switch-block">
              <h4>Sidebar</h4>
              <div class="btnSwitch">
                <button
                  type="button"
                  class="changeSideBarColor"
                  data-color="white"
                ></button>
                <button
                  type="button"
                  class="selected changeSideBarColor"
                  data-color="dark"
                ></button>
                <button
                  type="button"
                  class="changeSideBarColor"
                  data-color="dark2"
                ></button>
              </div>
            </div>
          </div>
        </div>
        <div class="custom-toggle">
          <i class="icon-settings"></i>
        </div>
      </div>
      <!-- End Custom template -->
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
      $("#lineChart").sparkline([102, 109, 120, 99, 110, 105, 115], {
        type: "line",
        height: "70",
        width: "100%",
        lineWidth: "2",
        lineColor: "#177dff",
        fillColor: "rgba(23, 125, 255, 0.14)",
      });

      $("#lineChart2").sparkline([99, 125, 122, 105, 110, 124, 115], {
        type: "line",
        height: "70",
        width: "100%",
        lineWidth: "2",
        lineColor: "#f3545d",
        fillColor: "rgba(243, 84, 93, .14)",
      });

      $("#lineChart3").sparkline([105, 103, 123, 100, 95, 105, 115], {
        type: "line",
        height: "70",
        width: "100%",
        lineWidth: "2",
        lineColor: "#ffa534",
        fillColor: "rgba(255, 165, 52, .14)",
      });
    </script>
        <!-- [DIUBAH] Skrip untuk menampilkan chart -->
    <script>
        // Data dari PHP untuk Donut Chart
        const statusLabels = <?= json_encode(array_keys($chart_status_data)); ?>;
        const statusCounts = <?= json_encode(array_values($chart_status_data)); ?>;
        const statusColors = ['#ffc107', '#28a745', '#dc3545', '#0d6efd', '#6c757d']; // Warna default untuk status tak dikenal

        // Inisialisasi Donut Chart
        if (statusLabels.length > 0) {
            var ctxDonut = document.getElementById('statusDonutChart').getContext('2d');
            new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: statusLabels.map(label => label.charAt(0).toUpperCase() + label.slice(1)), // Membuat huruf awal besar
                    datasets: [{ data: statusCounts, backgroundColor: statusColors }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    </script>
  </body>
</html>
