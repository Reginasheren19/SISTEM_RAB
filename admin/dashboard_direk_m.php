<?php
session_start();
include("../config/koneksi_mysql.php");

// =========================================================================
// BAGIAN 1: PENGAMBILAN DATA UNTUK KARTU KPI
// =========================================================================
$q_proyek_rab = mysqli_query($koneksi, "SELECT COUNT(id_rab_material) as total FROM rab_material");
$proyek_dengan_rab = mysqli_fetch_assoc($q_proyek_rab)['total'] ?? 0;

$q_total_rab = mysqli_query($koneksi, "SELECT SUM(total_rab_material) as total FROM rab_material");
$total_rab_material = mysqli_fetch_assoc($q_total_rab)['total'] ?? 0;

$q_total_realisasi = mysqli_query($koneksi, "SELECT SUM(dd.jumlah_distribusi * COALESCE(harga.harga_rata_rata, 0)) as total FROM detail_distribusi dd LEFT JOIN (SELECT id_material, (SUM(sub_total_pp) / NULLIF(SUM(quantity), 0)) as harga_rata_rata FROM detail_pencatatan_pembelian WHERE quantity > 0 AND harga_satuan_pp > 0 GROUP BY id_material) as harga ON dd.id_material = harga.id_material");
$total_realisasi_material = mysqli_fetch_assoc($q_total_realisasi)['total'] ?? 0;

$selisih_total = $total_rab_material - $total_realisasi_material;


// =========================================================================
// BAGIAN 2: PENGAMBILAN DATA UNTUK GRAFIK & TABEL
// =========================================================================

// Grafik 1: Total Pembelian Material 6 Bulan Terakhir
$pembelian_per_bulan = [];
$labels_bulan = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('m', strtotime("-$i month"));
    $tahun = date('Y', strtotime("-$i month"));
    $labels_bulan[] = date('M Y', strtotime("-$i month"));
    $q_pembelian_bulan = mysqli_query($koneksi, "SELECT SUM(total_biaya) as total FROM pencatatan_pembelian WHERE MONTH(tanggal_pembelian) = '$bulan' AND YEAR(tanggal_pembelian) = '$tahun'");
    $pembelian_per_bulan[] = (int)(mysqli_fetch_assoc($q_pembelian_bulan)['total'] ?? 0);
}

// Grafik 2: Perbandingan Proyek dengan Filter
$perumahan_id_terpilih = $_GET['perumahan_id'] ?? 'semua';
$q_daftar_perumahan = mysqli_query($koneksi, "SELECT id_perumahan, nama_perumahan FROM master_perumahan ORDER BY nama_perumahan ASC");

$sql_perbandingan = "SELECT p.id_proyek, CONCAT(per.nama_perumahan, ' - Kavling: ', p.kavling) as nama_proyek, r.total_rab_material FROM master_proyek p JOIN master_perumahan per ON p.id_perumahan = per.id_perumahan JOIN rab_material r ON p.id_proyek = r.id_proyek";
if ($perumahan_id_terpilih !== 'semua' && is_numeric($perumahan_id_terpilih)) {
    $sql_perbandingan .= " WHERE p.id_perumahan = " . (int)$perumahan_id_terpilih;
}
$sql_perbandingan .= " ORDER BY nama_proyek ASC LIMIT 10";
$q_perbandingan = mysqli_query($koneksi, $sql_perbandingan);

$labels_proyek = [];
$data_rab = [];
$data_realisasi = [];
$tabel_data = []; // [PENTING] Inisialisasi $tabel_data di sini

if ($q_perbandingan) {
    while($row = mysqli_fetch_assoc($q_perbandingan)) {
        $labels_proyek[] = $row['nama_proyek'];
        $data_rab[] = (float)$row['total_rab_material'];
        $id_proyek = $row['id_proyek'];
        $realisasi_proyek = 0;

        $stmt_dist = $koneksi->prepare("SELECT dd.id_material, SUM(dd.jumlah_distribusi) as total_distribusi FROM detail_distribusi dd JOIN distribusi_material dm ON dd.id_distribusi = dm.id_distribusi WHERE dm.id_proyek = ? GROUP BY dd.id_material");
        $stmt_dist->bind_param("i", $id_proyek);
        $stmt_dist->execute();
        $res_dist = $stmt_dist->get_result();
        while($item_dist = $res_dist->fetch_assoc()){
            $id_mat = $item_dist['id_material'];
            $qty_dist = $item_dist['total_distribusi'];
            $stmt_hrg = $koneksi->prepare("SELECT SUM(sub_total_pp) / NULLIF(SUM(quantity), 0) AS harga_rata_rata FROM detail_pencatatan_pembelian WHERE id_material = ? AND quantity > 0");
            $stmt_hrg->bind_param("i", $id_mat);
            $stmt_hrg->execute();
            $hrg_rata = (float)($stmt_hrg->get_result()->fetch_assoc()['harga_rata_rata'] ?? 0);
            $stmt_hrg->close();
            $realisasi_proyek += $qty_dist * $hrg_rata;
        }
        $stmt_dist->close();
        $data_realisasi[] = $realisasi_proyek;

        // Data untuk tabel disimpan di sini
        $tabel_data[] = [
            'nama_proyek' => $row['nama_proyek'],
            'anggaran' => $row['total_rab_material'],
            'realisasi' => $realisasi_proyek,
            'selisih' => $row['total_rab_material'] - $realisasi_proyek
        ];
    }
}

// --- [DIPINDAHKAN] LOGIKA UNTUK PROYEK PALING BOROS SEKARANG ADA DI SINI ---
$proyek_boros = [];
// Pastikan $tabel_data sudah ada dan merupakan array sebelum di-loop
if (is_array($tabel_data)) {
    foreach ($tabel_data as $proyek) {
        if ($proyek['selisih'] < 0) {
            $proyek_boros[] = $proyek;
        }
    }
}
// Urutkan array $proyek_boros dari yang paling boros
usort($proyek_boros, function($a, $b) {
    return $a['selisih'] <=> $b['selisih'];
});
// Ambil 5 teratas saja
$top_5_boros = array_slice($proyek_boros, 0, 5);
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
                        <h3 class="fw-bold mb-3">Dashboard Direktur</h3>
                        <h6 class="op-7 mb-2">Ringkasan Kinerja Proyek & Biaya Material</h6>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-primary bubble-shadow-small"><i class="fas fa-building"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Proyek dengan RAB</p><h4 class="card-title"><?= $proyek_dengan_rab ?></h4></div></div></div></div></div></div>
                    <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-info bubble-shadow-small"><i class="fas fa-file-contract"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Total Anggaran</p><h4 class="card-title">Rp <?= number_format($total_rab_material, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                    <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-success bubble-shadow-small"><i class="fas fa-truck-loading"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Total Realisasi</p><h4 class="card-title">Rp <?= number_format($total_realisasi_material, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                    <div class="col-sm-6 col-md-3"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center <?= ($selisih_total >= 0) ? 'icon-success' : 'icon-danger' ?> bubble-shadow-small"><i class="fas fa-balance-scale-right"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Selisih Total</p><h4 class="card-title">Rp <?= number_format($selisih_total, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header"><div class="card-title">Total Pembelian Material (6 Bulan Terakhir)</div></div>
                            <div class="card-body"><div class="chart-container"><canvas id="pembelianBulananChart"></canvas></div></div>
                        </div>
                    </div>
<div class="col-md-4">
    <div class="card">
        <div class="card-header"><div class="card-title">Top 5 Proyek Paling Boros</div></div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                <?php if (empty($top_5_boros)): ?>
                    <li class="list-group-item text-center text-muted">Tidak ada proyek yang boros. Kerja bagus!</li>
                <?php else: foreach ($top_5_boros as $pt): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= htmlspecialchars($pt['nama_proyek']) ?></span>
                        <span class="badge bg-danger">Rp <?= number_format(abs($pt['selisih']),0,',','.') ?></span>
                    </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
</div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">Perbandingan Anggaran vs Realisasi per Proyek</h4>
                                    <form method="GET" action="" class="form-inline">
                                        <div class="input-group">
                                            <select class="form-select" name="perumahan_id" onchange="this.form.submit()">
                                                <option value="semua">-- Semua Perumahan --</option>
                                                <?php mysqli_data_seek($q_daftar_perumahan, 0); while($perumahan = mysqli_fetch_assoc($q_daftar_perumahan)): ?>
                                                <option value="<?= $perumahan['id_perumahan'] ?>" <?= ($perumahan['id_perumahan'] == $perumahan_id_terpilih) ? 'selected' : '' ?>><?= htmlspecialchars($perumahan['nama_perumahan']) ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="height: 350px">
                                    <canvas id="perbandinganProyekChart"></canvas>
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

<script>
    const labelsProyek = <?= json_encode($labels_proyek) ?>;
    const dataRab = <?= json_encode($data_rab) ?>;
    const dataRealisasi = <?= json_encode($data_realisasi) ?>;
    const labelsBulan = <?= json_encode($labels_bulan) ?>;
    const dataPembelianBulanan = <?= json_encode($pembelian_per_bulan) ?>;

    if (labelsProyek && labelsProyek.length > 0) {
        new Chart(document.getElementById('perbandinganProyekChart').getContext('2d'), {
            type: 'bar',
            data: { labels: labelsProyek, datasets: [{ label: 'Anggaran', data: dataRab, backgroundColor: 'rgba(54, 162, 235, 0.6)' }, { label: 'Realisasi', data: dataRealisasi, backgroundColor: 'rgba(40, 167, 69, 0.6)' }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true } } }
        });
    }

    new Chart(document.getElementById('pembelianBulananChart').getContext('2d'), {
        type: 'bar',
        data: { labels: labelsBulan, datasets: [{ label: "Total Pembelian", backgroundColor: '#0077b6', data: dataPembelianBulanan }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
</script>
</body>
</html>