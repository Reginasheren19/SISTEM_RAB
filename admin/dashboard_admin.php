<?php
session_start();
include("../config/koneksi_mysql.php");

// Proteksi Halaman: Pastikan hanya direktur yang bisa mengakses
$user_role = strtolower($_SESSION['role'] ?? 'guest');
if ($user_role !== 'direktur') {
    // Arahkan ke dashboard lain atau halaman error jika bukan direktur
    // Misalnya, header("Location: dashboard_umum.php");
    die("Akses ditolak. Halaman ini khusus untuk Direktur.");
}
// =========================================================================

// --- 1. PENGAMBILAN DATA UNTUK KARTU KPI ---

// Jumlah Proyek Aktif (asumsi ada kolom status di master_proyek)
$q_proyek_aktif = mysqli_query($koneksi, "SELECT COUNT(id_proyek) as total FROM master_proyek");
$proyek_aktif = mysqli_fetch_assoc($q_proyek_aktif)['total'] ?? 0;

// Total Anggaran (RAB) dari semua proyek
$q_total_rab = mysqli_query($koneksi, "SELECT SUM(total_rab_upah) as total FROM rab_upah");
$total_rab = mysqli_fetch_assoc($q_total_rab)['total'] ?? 0;

// Total Realisasi (yang sudah dibayar)
$q_total_realisasi = mysqli_query($koneksi, "SELECT SUM(total_pengajuan) as total FROM pengajuan_upah WHERE status_pengajuan = 'dibayar'");
$total_realisasi = mysqli_fetch_assoc($q_total_realisasi)['total'] ?? 0;

// Pengajuan perlu persetujuan
$q_perlu_setuju = mysqli_query($koneksi, "SELECT COUNT(id_pengajuan_upah) as total FROM pengajuan_upah WHERE status_pengajuan = 'diajukan'");
$perlu_setuju = mysqli_fetch_assoc($q_perlu_setuju)['total'] ?? 0;

// --- 2. PENGAMBILAN DATA UNTUK GRAFIK ---

// Data untuk Grafik Realisasi per Bulan (6 bulan terakhir)
$realisasi_per_bulan = [];
$labels_bulan = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('m', strtotime("-$i month"));
    $tahun = date('Y', strtotime("-$i month"));
    $labels_bulan[] = date('M Y', strtotime("-$i month"));
    
    $q_realisasi_bulan = mysqli_query($koneksi, "SELECT SUM(total_pengajuan) as total FROM pengajuan_upah WHERE status_pengajuan = 'dibayar' AND MONTH(tanggal_pengajuan) = '$bulan' AND YEAR(tanggal_pengajuan) = '$tahun'");
    $realisasi_per_bulan[] = (int)(mysqli_fetch_assoc($q_realisasi_bulan)['total'] ?? 0);
}

// Data untuk Grafik Status Pengajuan
$q_status_pie = mysqli_query($koneksi, "SELECT status_pengajuan, COUNT(*) as jumlah FROM pengajuan_upah GROUP BY status_pengajuan");
$data_status_pie = [];
while ($row = mysqli_fetch_assoc($q_status_pie)) {
    $data_status_pie[ucwords($row['status_pengajuan'])] = $row['jumlah'];
}

// --- 3. PENGAMBILAN DATA UNTUK TABEL AKSI ---

// Proyek Kritis (Realisasi > 85%)
$proyek_kritis = [];
$q_proyek_kritis = mysqli_query($koneksi, "
    SELECT 
        p.nama_proyek, 
        p.total_rab, 
        COALESCE(SUM(p.total_dibayar), 0) as total_realisasi,
        (COALESCE(SUM(p.total_dibayar), 0) / p.total_rab) * 100 as persentase
    FROM (
        SELECT 
            CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) as nama_proyek,
            ru.total_rab_upah as total_rab,
            pu.total_pengajuan as total_dibayar
        FROM rab_upah ru
        JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
        JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        LEFT JOIN pengajuan_upah pu ON ru.id_rab_upah = pu.id_rab_upah AND pu.status_pengajuan = 'dibayar'
    ) as p
    WHERE p.total_rab > 0
    GROUP BY p.nama_proyek, p.total_rab
    HAVING persentase > 85
    ORDER BY persentase DESC
");
if ($q_proyek_kritis) {
    while($row = mysqli_fetch_assoc($q_proyek_kritis)) {
        $proyek_kritis[] = $row;
    }
}


// Pengajuan terbaru perlu persetujuan
$pengajuan_terbaru = [];
$q_pengajuan_terbaru = mysqli_query($koneksi, "SELECT pu.id_pengajuan_upah, CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) as nama_proyek, pu.tanggal_pengajuan, pu.total_pengajuan FROM pengajuan_upah pu JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan WHERE pu.status_pengajuan = 'diajukan' ORDER BY pu.tanggal_pengajuan DESC LIMIT 5");
if ($q_pengajuan_terbaru) {
    while($row = mysqli_fetch_assoc($q_pengajuan_terbaru)) {
        $pengajuan_terbaru[] = $row;
    }
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
                            <h3 class="fw-bold mb-3">Dashboard Direktur</h3>
                            <h6 class="op-7 mb-2">Ringkasan Umum Kinerja Proyek dan Keuangan</h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-primary card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5"><div class="icon-big text-center"><i class="flaticon-agenda-1"></i></div></div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers"><p class="card-category">Proyek Aktif</p><h4 class="card-title"><?= $proyek_aktif ?></h4></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-info card-round">
                                <div class="card-body"><div class="row"><div class="col-5"><div class="icon-big text-center"><i class="flaticon-coins"></i></div></div><div class="col-7 col-stats"><div class="numbers"><p class="card-category">Total Anggaran</p><h4 class="card-title">Rp <?= number_format($total_rab, 0, ',', '.') ?></h4></div></div></div></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-success card-round">
                                <div class="card-body"><div class="row"><div class="col-5"><div class="icon-big text-center"><i class="flaticon-analytics"></i></div></div><div class="col-7 col-stats"><div class="numbers"><p class="card-category">Total Realisasi</p><h4 class="card-title">Rp <?= number_format($total_realisasi, 0, ',', '.') ?></h4></div></div></div></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-danger card-round">
                                <div class="card-body"><div class="row"><div class="col-5"><div class="icon-big text-center"><i class="flaticon-envelope-1"></i></div></div><div class="col-7 col-stats"><div class="numbers"><p class="card-category">Perlu Disetujui</p><h4 class="card-title"><?= $perlu_setuju ?></h4></div></div></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header"><div class="card-title">Realisasi Anggaran 6 Bulan Terakhir</div></div>
                                <div class="card-body"><div class="chart-container"><canvas id="realisasiBulananChart"></canvas></div></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header"><div class="card-title">Komposisi Status Pengajuan</div></div>
                                <div class="card-body"><div class="chart-container"><canvas id="statusPieChart"></canvas></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header"><h4 class="card-title text-warning"><i class="fas fa-exclamation-triangle"></i> Proyek Kritis (Anggaran > 85%)</h4></div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped mt-3">
                                            <thead><tr><th>Nama Proyek</th><th class="text-end">Anggaran</th><th class="text-end">Realisasi</th><th class="text-center">%</th></tr></thead>
                                            <tbody>
                                                <?php if (empty($proyek_kritis)): ?>
                                                    <tr><td colspan="4" class="text-center text-muted">Tidak ada proyek dalam kondisi kritis.</td></tr>
                                                <?php else: foreach ($proyek_kritis as $pk): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($pk['nama_proyek']) ?></td>
                                                        <td class="text-end">Rp <?= number_format($pk['total_rab'], 0, ',', '.') ?></td>
                                                        <td class="text-end">Rp <?= number_format($pk['total_realisasi'], 0, ',', '.') ?></td>
                                                        <td class="text-center"><span class="badge bg-danger"><?= number_format($pk['persentase'], 2) ?>%</span></td>
                                                    </tr>
                                                <?php endforeach; endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header"><h4 class="card-title">Menunggu Persetujuan Anda</h4></div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped mt-3">
                                        <thead><tr><th>Proyek</th><th>Tanggal</th><th class="text-end">Total</th><th>Aksi</th></tr></thead>
                                            <tbody>
                                            <?php if (empty($pengajuan_terbaru)): ?>
                                                    <tr><td colspan="4" class="text-center text-muted">Tidak ada pengajuan baru.</td></tr>
                                                <?php else: foreach ($pengajuan_terbaru as $pt): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($pt['nama_proyek']) ?></td>
                                                        <td><?= date('d M Y', strtotime($pt['tanggal_pengajuan'])) ?></td>
                                                        <td class="text-end">Rp <?= number_format($pt['total_pengajuan'], 0, ',', '.') ?></td>
                                                        <td><a href="pengajuan_upah.php" class="btn btn-primary btn-sm">Lihat</a></td>
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
    <script>
        // Data dari PHP untuk JavaScript
        const labelsBulan = <?= json_encode($labels_bulan) ?>;
        const dataRealisasiBulanan = <?= json_encode($realisasi_per_bulan) ?>;
        const dataStatusPie = <?= json_encode(array_values($data_status_pie)) ?>;
        const labelsStatusPie = <?= json_encode(array_keys($data_status_pie)) ?>;
        
        // Grafik Realisasi Bulanan
        var ctxRealisasi = document.getElementById('realisasiBulananChart').getContext('2d');
        var realisasiBulananChart = new Chart(ctxRealisasi, {
            type: 'bar',
            data: {
                labels: labelsBulan,
                datasets: [{
                    label: "Total Realisasi",
                    backgroundColor: '#1d7af3',
                    borderColor: '#1d7af3',
                    data: dataRealisasiBulanan,
                }],
            },
            options: {
                responsive: true, 
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true, callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); } } }],
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return 'Rp ' + tooltipItem.yLabel.toLocaleString('id-ID');
                        }
                    }
                }
            }
        });

        // Grafik Status Pengajuan
        var ctxStatus = document.getElementById('statusPieChart').getContext('2d');
        var statusPieChart = new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: dataStatusPie,
                    backgroundColor : ['#ffc107', '#28a745', '#dc3545', '#1d7af3'], // Sesuaikan warna dengan status Anda
                }],
                labels: labelsStatusPie
            },
            options: {
                responsive: true, 
                maintainAspectRatio: false,
                legend: { position: 'bottom' },
            }
        });
    </script>
</body>
</html>