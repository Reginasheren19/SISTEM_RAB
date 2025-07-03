<?php
session_start();
include("../config/koneksi_mysql.php");

// =========================================================================
$logged_in_user_id = $_SESSION['id_user'] ?? 0;
$user_role = strtolower($_SESSION['role'] ?? 'guest');

// Proteksi Halaman: Hanya Admin yang bisa akses
if ($user_role !== 'admin') {
    die("Akses ditolak. Halaman ini khusus untuk Administrator.");
}
// =========================================================================

// Fungsi bantu untuk menjalankan query KPI dengan aman
function get_kpi_value($koneksi, $sql) {
    $result = mysqli_query($koneksi, $sql);
    if ($result) { return mysqli_fetch_assoc($result)['total'] ?? 0; }
    error_log("Dashboard Admin Query Failed: " . mysqli_error($koneksi));
    return 0;
}

// --- 1. PENGAMBILAN DATA UNTUK KARTU KPI ---
$total_menunggu_bayar_rp = get_kpi_value($koneksi, "SELECT SUM(total_pengajuan) as total FROM pengajuan_upah WHERE status_pengajuan = 'Disetujui'");
$total_menunggu_bayar_count = get_kpi_value($koneksi, "SELECT COUNT(id_pengajuan_upah) as total FROM pengajuan_upah WHERE status_pengajuan = 'Disetujui'");
$bulan_ini = date('m');
$tahun_ini = date('Y');
$total_dibayar_bulan_ini_rp = get_kpi_value($koneksi, "SELECT SUM(total_pengajuan) as total FROM pengajuan_upah WHERE status_pengajuan = 'Dibayar' AND MONTH(tanggal_pengajuan) = '$bulan_ini' AND YEAR(tanggal_pengajuan) = '$tahun_ini'");
$total_transaksi_bulan_ini = get_kpi_value($koneksi, "SELECT COUNT(id_pengajuan_upah) as total FROM pengajuan_upah WHERE status_pengajuan = 'Dibayar' AND MONTH(tanggal_pengajuan) = '$bulan_ini' AND YEAR(tanggal_pengajuan) = '$tahun_ini'");

// --- 2. PENGAMBILAN DATA UNTUK TABEL AKSI UTAMA ---
$pengajuan_siap_bayar = [];
$q_siap_bayar = mysqli_query($koneksi, "SELECT pu.id_pengajuan_upah, CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) as nama_proyek, pu.tanggal_pengajuan, pu.total_pengajuan FROM pengajuan_upah pu JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan WHERE pu.status_pengajuan = 'Disetujui' ORDER BY pu.tanggal_pengajuan ASC");
if ($q_siap_bayar) { while($row = mysqli_fetch_assoc($q_siap_bayar)) { $pengajuan_siap_bayar[] = $row; } }

// --- 3. PENGAMBILAN DATA UNTUK GRAFIK ALUR DANA ---
$alur_dana_data = [ 'Diajukan' => 0, 'Disetujui' => 0, 'Dibayar' => 0 ];
$q_alur_dana = mysqli_query($koneksi, "SELECT status_pengajuan, SUM(total_pengajuan) as total_nilai FROM pengajuan_upah GROUP BY status_pengajuan");
if ($q_alur_dana) {
    while($row = mysqli_fetch_assoc($q_alur_dana)) {
        $status = ucwords($row['status_pengajuan']);
        if (isset($alur_dana_data[$status])) {
            $alur_dana_data[$status] = (int)$row['total_nilai'];
        }
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
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Dashboard Administrator</h3>
                            <h6 class="op-7 mb-2">Pusat Kontrol Keuangan dan Manajemen Sistem</h6>
                        </div>
                    </div>

                    <!-- KARTU STATISTIK KEUANGAN -->
                    <div class="row">
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-warning bubble-shadow-small"><i class="fas fa-wallet"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Menunggu Dibayar</p><h4 class="card-title">Rp <?= number_format($total_menunggu_bayar_rp, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-info bubble-shadow-small"><i class="fas fa-file-invoice"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Jumlah Tagihan</p><h4 class="card-title"><?= $total_menunggu_bayar_count ?></h4></div></div></div></div></div></div>
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-success bubble-shadow-small"><i class="fas fa-money-bill-wave"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Dibayar Bulan Ini</p><h4 class="card-title">Rp <?= number_format($total_dibayar_bulan_ini_rp, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                        <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-primary bubble-shadow-small"><i class="fas fa-check-double"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Transaksi Bulan Ini</p><h4 class="card-title"><?= $total_transaksi_bulan_ini ?></h4></div></div></div></div></div></div>
                    </div>

                    <!-- TABEL PEKERJAAN UTAMA ADMIN -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header"><div class="card-title"><i class="fas fa-tasks me-2"></i>Daftar Pengajuan Siap Dibayar</div></div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead><tr><th class="text-center">ID</th><th>Proyek</th><th class="text-center">Tanggal Pengajuan</th><th class="text-center">Total Pengajuan</th><th class="text-center">Aksi</th></tr></thead>
                                            <tbody>
                                            <?php if (empty($pengajuan_siap_bayar)): ?>
                                                <tr><td colspan="5" class="text-center text-muted py-4">Luar biasa! Tidak ada pengajuan yang menunggu pembayaran.</td></tr>
                                            <?php else: foreach ($pengajuan_siap_bayar as $pengajuan): ?>
                                                <tr>
                                                    <td class="text-center"><?= $pengajuan['id_pengajuan_upah'] ?></td>
                                                    <td><?= htmlspecialchars($pengajuan['nama_proyek']) ?></td>
                                                    <td class="text-center"><?= date('d M Y', strtotime($pengajuan['tanggal_pengajuan'])) ?></td>
                                                    <td class="text-center fw-bold">Rp <?= number_format($pengajuan['total_pengajuan'], 0, ',', '.') ?></td>
                                                    <td class="text-center"><a href="pengajuan_upah.php" class="btn btn-success btn-sm"><i class="fas fa-money-check-alt me-1"></i> Proses Pembayaran</a></td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GRAFIK & AKSES CEPAT -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header"><div class="card-title">Visualisasi Alur Dana Pengajuan</div></div>
                                <div class="card-body"><div class="chart-container" style="height: 300px"><canvas id="alurDanaChart"></canvas></div></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="card">
                                <div class="card-header"><div class="card-title">Akses Cepat</div></div>
                                <div class="card-body">
                                    <!-- [DIUBAH] Akses Cepat yang lebih lengkap -->
                                    <div class="d-grid gap-2">
                                        <a href="master_perumahan.php" class="btn btn-secondary btn-sm"><i></i> Master Perumahan</a>
                                        <a href="master_mandor.php" class="btn btn-secondary btn-sm"><i></i> Master Mandor</a>
                                        <a href="master_proyek.php" class="btn btn-secondary btn-sm"><i></i> Master Proyek</a>
                                        <a href="master_kategori.php" class="btn btn-secondary btn-sm"><i></i> Master Kategori</a>
                                        <a href="master_satuan.php" class="btn btn-secondary btn-sm"><i></i> Master Satuan</a>
                                        <a href="master_pekerjaan.php" class="btn btn-secondary btn-sm"><i></i> Master Pekerjaan</a>
                                        <a href="master_user.php" class="btn btn-secondary btn-sm"><i></i> Master User</a>
                                    </div>
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
        // Data untuk Grafik Alur Dana
        const alurDanaLabels = <?= json_encode(array_keys($alur_dana_data)) ?>;
        const alurDanaValues = <?= json_encode(array_values($alur_dana_data)) ?>;
        
        // Inisialisasi Grafik
        const ctxAlurDana = document.getElementById('alurDanaChart').getContext('2d');
        new Chart(ctxAlurDana, {
            type: 'bar',
            data: {
                labels: alurDanaLabels,
                datasets: [{
                    label: "Total Nilai (Rp)",
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745'],
                    data: alurDanaValues,
                }],
            },
            options: {
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
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