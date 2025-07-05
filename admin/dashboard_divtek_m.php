<?php
session_start();
include("../config/koneksi_mysql.php");

// Proteksi Halaman
if (strtolower($_SESSION['role'] ?? '') !== 'divisi teknik') {
    die("Akses ditolak. Halaman ini khusus untuk Divisi Teknik.");
}

// Inisialisasi variabel
$kpi_total_rab_dibuat = 0;
$kpi_total_nilai_rab = 0;
$kpi_proyek_tanpa_rab = 0;
$proyek_tanpa_rab_list = [];
$pie_chart_data = [];
$rab_terakhir_list = [];

// --- KPI 1 & 2 ---
$q_rab_summary = mysqli_query($koneksi, "SELECT COUNT(id_rab_material) as total_rab, SUM(total_rab_material) as total_nilai FROM rab_material");
if ($q_rab_summary) {
    $rab_summary = mysqli_fetch_assoc($q_rab_summary);
    $kpi_total_rab_dibuat = $rab_summary['total_rab'] ?? 0;
    $kpi_total_nilai_rab = $rab_summary['total_nilai'] ?? 0;
}

// --- KPI 3 & Widget "Proyek Menunggu RAB" ---
$sql_proyek_tanpa_rab = "SELECT p.id_proyek, CONCAT(per.nama_perumahan, ' - Kavling: ', p.kavling) AS nama_proyek_lengkap FROM master_proyek p JOIN master_perumahan per ON p.id_perumahan = per.id_perumahan LEFT JOIN rab_material r ON p.id_proyek = r.id_proyek WHERE r.id_rab_material IS NULL ORDER BY p.id_proyek DESC LIMIT 5";
$result_proyek_tanpa_rab = mysqli_query($koneksi, $sql_proyek_tanpa_rab);
if ($result_proyek_tanpa_rab) {
    $proyek_tanpa_rab_list = mysqli_fetch_all($result_proyek_tanpa_rab, MYSQLI_ASSOC);
    $kpi_proyek_tanpa_rab = count($proyek_tanpa_rab_list);
}

// --- Data untuk Grafik Lingkaran ---
$sql_pie_chart = "SELECT mk.nama_kategori, SUM(drm.sub_total) AS total_anggaran_kategori FROM detail_rab_material drm JOIN master_kategori mk ON drm.id_kategori = mk.id_kategori GROUP BY mk.nama_kategori HAVING SUM(drm.sub_total) > 0 ORDER BY total_anggaran_kategori DESC";
$result_pie_chart = mysqli_query($koneksi, $sql_pie_chart);
if($result_pie_chart) {
    $pie_chart_data = mysqli_fetch_all($result_pie_chart, MYSQLI_ASSOC);
}

// --- Data untuk Widget "5 RAB Terakhir Dibuat" ---
$sql_rab_terakhir = "SELECT r.id_rab_material, r.tanggal_mulai_mt, CONCAT(per.nama_perumahan, ' - Kavling: ', p.kavling) AS nama_proyek FROM rab_material r JOIN master_proyek p ON r.id_proyek = p.id_proyek JOIN master_perumahan per ON p.id_perumahan = per.id_perumahan ORDER BY r.id_rab_material DESC LIMIT 5";
$result_rab_terakhir = mysqli_query($koneksi, $sql_rab_terakhir);
if($result_rab_terakhir) {
    $rab_terakhir_list = mysqli_fetch_all($result_rab_terakhir, MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Dashboard</title>
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
        <?php include 'sidebar_m.php'; ?>


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
                        <h6 class="op-7 mb-2">Ringkasan Perencanaan & Pengelolaan Data Master</h6>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6 col-md-4"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-success bubble-shadow-small"><i class="fas fa-check-circle"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">RAB Dibuat</p><h4 class="card-title"><?= $kpi_total_rab_dibuat ?></h4></div></div></div></div></div></div>
                    <div class="col-sm-6 col-md-4"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-info bubble-shadow-small"><i class="fas fa-file-invoice-dollar"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Total Nilai Anggaran</p><h4 class="card-title">Rp <?= number_format($kpi_total_nilai_rab, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                    <div class="col-sm-6 col-md-4"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-warning bubble-shadow-small"><i class="fas fa-exclamation-triangle"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Proyek Menunggu RAB</p><h4 class="card-title"><?= $kpi_proyek_tanpa_rab ?></h4></div></div></div></div></div></div>
                </div>

<div class="row">
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Proyek Baru Menunggu Anggaran (Top 5)</h4></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if (empty($proyek_tanpa_rab_list)): ?>
                                        <li class="list-group-item text-center text-muted">Semua proyek sudah memiliki RAB. Kerja bagus!</li>
                                    <?php else: foreach ($proyek_tanpa_rab_list as $proyek): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><?= htmlspecialchars($proyek['nama_proyek_lengkap']) ?></span>
                                            <a href="transaksi_rab_material.php?action=add&id_proyek=<?= $proyek['id_proyek'] ?>" class="btn btn-primary btn-sm">Buat RAB</a>
                                        </li>
                                    <?php endforeach; endif; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><h4 class="card-title">5 RAB Terakhir Dibuat</h4></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if (empty($rab_terakhir_list)): ?>
                                        <li class="list-group-item text-center text-muted">Belum ada RAB yang dibuat.</li>
                                    <?php else: foreach($rab_terakhir_list as $rab): ?>
                                        <li class="list-group-item">
                                            <a href="detail_rab_material.php?id_rab_material=<?= $rab['id_rab_material'] ?>" class="text-decoration-none" title="Lihat detail RAB">
                                                <strong><?= htmlspecialchars($rab['nama_proyek']) ?></strong>
                                                <small class="d-block text-muted">Dibuat pada: <?= date('d M Y', strtotime($rab['tanggal_mulai_mt'])) ?></small>
                                            </a>
                                        </li>
                                    <?php endforeach; endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Alokasi Anggaran per Kategori</h4></div>
                            <div class="card-body">
                                <div class="chart-container" style="min-height: 350px;"><canvas id="pieChart"></canvas></div>
                            </div>
                        </div>
                    </div>
                </div>
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    const pieData = <?= json_encode($pie_chart_data) ?>;
    if (pieData && pieData.length > 0) {
        const pieLabels = pieData.map(item => item.nama_kategori);
        const pieValues = pieData.map(item => item.total_anggaran_kategori);
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: pieLabels,
                datasets: [{
                    label: 'Total Anggaran',
                    data: pieValues,
                    backgroundColor: [ // Tambahkan warna-warni agar menarik
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                    ],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                }
            }
        });
    } else {
        $('#pieChart').parent().html('<p class="text-center text-muted mt-5">Belum ada data anggaran per kategori untuk ditampilkan.</p>');
    }
});
</script>
</body>
</html>