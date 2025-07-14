<?php
session_start();
include("../config/koneksi_mysql.php");

// Pastikan hanya PJ Proyek yang bisa akses
if (($_SESSION['role'] ?? '') !== 'PJ Proyek' || !isset($_SESSION['id_user'])) {
    die("Akses ditolak. Anda harus login sebagai PJ Proyek.");
}
$id_user_pj = $_SESSION['id_user'];

// =========================================================================
// BAGIAN 1: PENGAMBILAN DATA UNTUK KPI & WIDGET
// =========================================================================

// --- KPI & Widget yang sudah ada (tidak diubah) ---
$sql_menunggu = "SELECT COUNT(p.id_pembelian) as total FROM pencatatan_pembelian p WHERE (SELECT SUM(quantity) FROM detail_pencatatan_pembelian WHERE id_pembelian = p.id_pembelian) > (SELECT COALESCE(SUM(jumlah_diterima + jumlah_rusak), 0) FROM log_penerimaan_material WHERE id_pembelian = p.id_pembelian)";
$kpi_menunggu = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_menunggu))['total'] ?? 0;

$tanggal_hari_ini = date('Y-m-d');
$stmt_kpi_distribusi = $koneksi->prepare("SELECT COUNT(dd.id_detail) as total FROM detail_distribusi dd JOIN distribusi_material dm ON dd.id_distribusi = dm.id_distribusi WHERE dm.id_user_pj = ? AND DATE(dm.tanggal_distribusi) = ?");
$stmt_kpi_distribusi->bind_param("is", $id_user_pj, $tanggal_hari_ini);
$stmt_kpi_distribusi->execute();
$kpi_distribusi_hari_ini = $stmt_kpi_distribusi->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_kpi_distribusi->close();

$sql_daftar_tunggu = "SELECT p.id_pembelian, p.tanggal_pembelian, p.keterangan_pembelian FROM pencatatan_pembelian p WHERE (SELECT SUM(quantity) FROM detail_pencatatan_pembelian WHERE id_pembelian = p.id_pembelian) > (SELECT COALESCE(SUM(jumlah_diterima + jumlah_rusak), 0) FROM log_penerimaan_material WHERE id_pembelian = p.id_pembelian) ORDER BY p.tanggal_pembelian ASC LIMIT 5";
$result_daftar_tunggu = mysqli_query($koneksi, $sql_daftar_tunggu);

$stmt_stok_proyek = $koneksi->prepare("SELECT m.nama_material, s.nama_satuan, SUM(dd.jumlah_distribusi) as total_kuantitas FROM detail_distribusi dd JOIN distribusi_material dm ON dd.id_distribusi = dm.id_distribusi JOIN master_material m ON dd.id_material = m.id_material LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan WHERE dm.id_user_pj = ? GROUP BY dd.id_material, m.nama_material, s.nama_satuan ORDER BY m.nama_material ASC");
$stmt_stok_proyek->bind_param("i", $id_user_pj);
$stmt_stok_proyek->execute();
$result_stok_proyek = $stmt_stok_proyek->get_result();

// --- [BARU] Widget 3: Log Aktivitas Distribusi Terakhir (Top 5) ---
$log_aktivitas = [];
$stmt_log = $koneksi->prepare("
    SELECT dm.tanggal_distribusi, m.nama_material, dd.jumlah_distribusi, s.nama_satuan, 
           CONCAT(per.nama_perumahan, ' - Kavling: ', pro.kavling) as nama_proyek
    FROM distribusi_material dm
    JOIN detail_distribusi dd ON dm.id_distribusi = dd.id_distribusi
    JOIN master_material m ON dd.id_material = m.id_material
    JOIN master_proyek pro ON dm.id_proyek = pro.id_proyek
    JOIN master_perumahan per ON pro.id_perumahan = per.id_perumahan
    LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan
    WHERE dm.id_user_pj = ?
    ORDER BY dm.tanggal_distribusi DESC, dm.id_distribusi DESC
    LIMIT 5
");
$stmt_log->bind_param("i", $id_user_pj);
$stmt_log->execute();
$result_log = $stmt_log->get_result();
if($result_log) {
    while($row_log = $result_log->fetch_assoc()) {
        $log_aktivitas[] = $row_log;
    }
}
$stmt_log->close();

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
                        <h3 class="fw-bold mb-3">Dashboard PJ Proyek</h3>
                        <h6 class="op-7 mb-2">Ringkasan Tugas & Stok Material di Proyek Anda</h6>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="penerimaan_material.php" class="btn btn-primary btn-lg w-100"><i class="fas fa-box-open"></i> Konfirmasi Penerimaan Material</a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="distribusi_material.php" class="btn btn-success btn-lg w-100"><i class="fas fa-truck"></i> Distribusi Material ke Proyek</a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-6 col-md-6"><div class="card card-stats card-round"><div class="card-body "><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-warning bubble-shadow-small"><i class="fas fa-dolly-flatbed"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Pesanan Menunggu Penerimaan</p><h4 class="card-title"><?= $kpi_menunggu ?></h4></div></div></div></div></div></div>
                    <div class="col-sm-6 col-md-6"><div class="card card-stats card-round"><div class="card-body "><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-secondary bubble-shadow-small"><i class="fas fa-shipping-fast"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Distribusi Hari Ini</p><h4 class="card-title"><?= $kpi_distribusi_hari_ini ?></h4></div></div></div></div></div></div>
                </div>

<div class="row">
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Daftar Tunggu Penerimaan (Top 5)</h4></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                <?php if (mysqli_num_rows($result_daftar_tunggu) > 0): while($row = mysqli_fetch_assoc($result_daftar_tunggu)): ?>
                                    <li class="list-group-item">
                                        <a href="" class="text-decoration-none">
                                            <strong>ID: PB<?= $row['id_pembelian'] . date('Y', strtotime($row['tanggal_pembelian'])) ?></strong>
                                            <small class="d-block text-muted"><?= htmlspecialchars($row['keterangan_pembelian']) ?></small>
                                        </a>
                                    </li>
                                <?php endwhile; else: ?>
                                    <li class="list-group-item text-center text-muted">Tidak ada penerimaan yang tertunda.</li>
                                <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Log Aktivitas Distribusi Terakhir</h4></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                <?php if (empty($log_aktivitas)): ?>
                                    <li class="list-group-item text-center text-muted">Belum ada aktivitas distribusi.</li>
                                <?php else: foreach ($log_aktivitas as $log): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex">
                                            <div class="avatar avatar-sm me-3"><i class="fas fa-truck-loading fa-2x text-primary"></i></div>
                                            <div class="flex-1">
                                                <span>Anda mendistribusikan <strong><?= number_format($log['jumlah_distribusi'], 2, ',', '.') ?> <?= $log['nama_satuan'] ?> <?= htmlspecialchars($log['nama_material']) ?></strong></span>
                                                <small class="d-block text-muted">Ke: <?= htmlspecialchars($log['nama_proyek']) ?> - <?= date('d M Y', strtotime($log['tanggal_distribusi'])) ?></small>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Ringkasan Stok Material di Proyek Anda</h4></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead><tr><th>Material</th><th class="text-end">Total Didistribusikan</th></tr></thead>
                                        <tbody>
                                        <?php if (mysqli_num_rows($result_stok_proyek) > 0): mysqli_data_seek($result_stok_proyek, 0); while($row = mysqli_fetch_assoc($result_stok_proyek)): ?>
                                            <tr><td><?= htmlspecialchars($row['nama_material']) ?></td><td class="text-end"><?= number_format($row['total_kuantitas'], 2, ',', '.') ?> <?= $row['nama_satuan'] ?></td></tr>
                                        <?php endwhile; else: ?>
                                            <tr><td colspan="2" class="text-center text-muted">Belum ada material yang didistribusikan.</td></tr>
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
    </div>
</div>
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>