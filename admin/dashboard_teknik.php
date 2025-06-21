<?php
session_start();
include("../config/koneksi_mysql.php");

// =========================================================================
$logged_in_user_id = $_SESSION['id_user'] ?? 0;
$user_role = strtolower($_SESSION['role'] ?? 'guest');

// Proteksi Halaman: Sesuaikan dengan nama role Divisi Teknik Anda
if ($user_role !== 'divisi teknik' && $user_role !== 'admin') { // Contoh: izinkan admin juga
    die("Akses ditolak. Halaman ini khusus untuk Divisi Teknik.");
}
// =========================================================================

// --- 1. PENGAMBILAN DATA UNTUK KARTU KPI ---
function get_kpi_value($koneksi, $sql) {
    $result = mysqli_query($koneksi, $sql);
    if ($result) { return mysqli_fetch_assoc($result)['total'] ?? 0; }
    return 0;
}

// Proyek dengan RAB: Menghitung jumlah unik proyek di tabel rab_upah
$proyek_dengan_rab = get_kpi_value($koneksi, "SELECT COUNT(DISTINCT id_proyek) as total FROM rab_upah");

// Proyek Menunggu RAB: Menghitung proyek di master_proyek yang ID-nya tidak ada di rab_upah
$proyek_tanpa_rab = get_kpi_value($koneksi, "SELECT COUNT(mpr.id_proyek) as total FROM master_proyek mpr LEFT JOIN rab_upah ru ON mpr.id_proyek = ru.id_proyek WHERE ru.id_rab_upah IS NULL");

// Total Nilai RAB: Jumlah total dari semua RAB yang dibuat
$total_nilai_rab = get_kpi_value($koneksi, "SELECT SUM(total_rab_upah) as total FROM rab_upah");

// [PERBAIKAN] RAB Dibuat Bulan Ini: Menggunakan 'tanggal_mulai' sebagai acuan tanggal dibuat
$bulan_ini = date('m');
$tahun_ini = date('Y');
$rab_bulan_ini = get_kpi_value($koneksi, "SELECT COUNT(id_rab_upah) as total FROM rab_upah WHERE MONTH(tanggal_mulai) = '$bulan_ini' AND YEAR(tanggal_mulai) = '$tahun_ini'");


// --- 2. PENGAMBILAN DATA UNTUK GRAFIK & TABEL ---

// Ambil daftar perumahan untuk filter dropdown
$daftar_perumahan = [];
$q_daftar_perumahan = mysqli_query($koneksi, "SELECT id_perumahan, nama_perumahan FROM master_perumahan ORDER BY nama_perumahan ASC");
if ($q_daftar_perumahan) { while($row = mysqli_fetch_assoc($q_daftar_perumahan)) { $daftar_perumahan[] = $row; } }

// Tentukan filter yang aktif
$perumahan_id_terpilih = $_GET['perumahan_id'] ?? 'semua';

// Bangun query dinamis berdasarkan filter untuk Grafik Analisis Anggaran
$sql_perbandingan = "SELECT CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) as nama_proyek, ru.total_rab_upah as total_rab FROM rab_upah ru JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan";
if ($perumahan_id_terpilih !== 'semua' && is_numeric($perumahan_id_terpilih)) {
    $sql_perbandingan .= " WHERE mpr.id_perumahan = " . (int)$perumahan_id_terpilih;
}
$sql_perbandingan .= " ORDER BY nama_proyek ASC";
$q_perbandingan = mysqli_query($koneksi, $sql_perbandingan);

// Siapkan data untuk JavaScript Chart
$labels_proyek_perbandingan = [];
$data_rab_perbandingan = [];
if ($q_perbandingan) {
    while($row = mysqli_fetch_assoc($q_perbandingan)) {
        $labels_proyek_perbandingan[] = $row['nama_proyek'];
        $data_rab_perbandingan[] = (int)$row['total_rab'];
    }
}

// Data untuk Tabel 5 RAB Terbaru
$rab_terbaru = [];
$q_rab_terbaru = mysqli_query($koneksi, "SELECT ru.id_rab_upah, CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) as nama_proyek, ru.total_rab_upah, ru.tanggal_mulai, ru.tanggal_selesai FROM rab_upah ru JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan ORDER BY ru.id_rab_upah DESC LIMIT 5");
if ($q_rab_terbaru) { while($row = mysqli_fetch_assoc($q_rab_terbaru)) { $rab_terbaru[] = $row; } }

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
                            <h3 class="fw-bold mb-3">Dashboard Divisi Teknik</h3>
                            <h6 class="op-7 mb-2">Manajemen Rencana Anggaran Biaya (RAB) Proyek</h6>
                        </div>
                    </div>

                    <!-- KARTU STATISTIK -->
                    <div class="row">
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-success bubble-shadow-small"><i class="fas fa-check-circle"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Proyek dengan RAB</p><h4 class="card-title"><?= $proyek_dengan_rab ?></h4></div></div></div></div></div></div>
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-danger bubble-shadow-small"><i class="fas fa-exclamation-circle"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Menunggu RAB</p><h4 class="card-title"><?= $proyek_tanpa_rab ?></h4></div></div></div></div></div></div>
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-info bubble-shadow-small"><i class="fas fa-file-signature"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Total Nilai RAB</p><h4 class="card-title" style="font-size: 1.1rem;">Rp <?= number_format($total_nilai_rab, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-primary bubble-shadow-small"><i class="fas fa-calendar-check"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">RAB Bulan Ini</p><h4 class="card-title"><?= $rab_bulan_ini ?></h4></div></div></div></div></div></div>
                    </div>

                    
                    <!-- 5 RAB TERAKHIR DIBUAT & AKSES CEPAT -->
                    <div class="row">
                        <div class="col-md-9">
                             <div class="card">
                                <div class="card-header"><div class="card-title">5 RAB Terakhir Dibuat</div></div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                             <thead><tr><th class="text-center">ID RAB</th><th class="text-center">Proyek</th><th class="text-center">Total Anggaran</th><th class="text-center">Tanggal Mulai</th><th class="text-center">Tanggal Selesai</th></tr></thead>
                                            <tbody>
                                            <?php if (empty($rab_terbaru)): ?>
                                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada RAB yang dibuat.</td></tr>
                                            <?php else: foreach ($rab_terbaru as $rab): ?>
                                                <tr><td><?= 'RABU' . date('y', strtotime($rab['tanggal_mulai'])) . date('m', strtotime($rab['tanggal_mulai'])) . $rab['id_rab_upah'] ?></td><td><?= htmlspecialchars($rab['nama_proyek']) ?></td><td class="text-end">Rp <?= number_format($rab['total_rab_upah'], 0, ',', '.') ?></td><td><?= date('d M Y', strtotime($rab['tanggal_mulai'])) ?></td><td><?= date('d M Y', strtotime($rab['tanggal_selesai'])) ?></td></tr>
                                            <?php endforeach; endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                             <div class="card">
                                <div class="card-header"><div class="card-title">Akses Cepat</div></div>
                                <div class="card-body">
                                    <!-- [DIUBAH] Akses Cepat yang lebih lengkap -->
                                    <div class="d-grid gap-2">
                                        <a href="transaksi_rab_upah.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Buat RAB Baru</a>
                                        <hr class="my-1">
                                        <a href="master_pekerjaan.php" class="btn btn-secondary btn-sm"><i></i> Master Pekerjaan</a>
                                        <a href="master_kategori.php" class="btn btn-secondary btn-sm"><i></i> Master Kategori</a>
                                        <a href="master_satuan.php" class="btn btn-secondary btn-sm"><i></i> Master Satuan</a>
                                        <a href="master_perumahan.php" class="btn btn-secondary btn-sm"><i></i> Master Perumahan</a>
                                        <a href="master_proyek.php" class="btn btn-secondary btn-sm"><i></i> Master Proyek</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GRAFIK ANALISIS ANGGARAN DENGAN FILTER -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header"><h4 class="card-title">Analisis Anggaran Proyek</h4></div>
                                <div class="card-body">
                                    <form method="GET" action="dashboard_teknik.php">
                                        <div class="row gx-2 mb-4">
                                            <div class="col-md-5">
                                                <label for="perumahanFilter" class="form-label">Filter berdasarkan Perumahan:</label>
                                                <select class="form-select" id="perumahanFilter" name="perumahan_id">
                                                    <option value="semua">-- Tampilkan Semua Proyek --</option>
                                                    <?php foreach($daftar_perumahan as $perumahan): ?>
                                                        <option value="<?= $perumahan['id_perumahan'] ?>" <?= ($perumahan['id_perumahan'] == $perumahan_id_terpilih) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($perumahan['nama_perumahan']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                                            </div>
                                        </div>
                                    </form>
                                    <div class="chart-container" style="height: 350px">
                                        <canvas id="rabPerProyekChart"></canvas>
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
        // Data untuk Grafik Analisis Anggaran
        const labelsProyek = <?= json_encode($labels_proyek_perbandingan) ?>;
        const dataRabProyek = <?= json_encode($data_rab_perbandingan) ?>;
        
        // Inisialisasi Grafik
        const ctxRab = document.getElementById('rabPerProyekChart').getContext('2d');
        new Chart(ctxRab, {
            type: 'bar',
            data: {
                labels: labelsProyek,
                datasets: [{
                    label: "Total Anggaran (RAB)",
                    backgroundColor: '#177dff',
                    borderColor: '#177dff',
                    data: dataRabProyek,
                }],
            },
            options: {
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                         callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); }
                        }
                    }
                },
            }
        });
    </script>
</body>
</html>