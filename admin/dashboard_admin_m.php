<?php
session_start();
include("../config/koneksi_mysql.php");

// Proteksi Halaman
if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak. Halaman ini khusus untuk Admin.");
}

// Inisialisasi variabel untuk mencegah warning
$kpi_pembelian_bulan_ini = 0;
$kpi_nilai_pembelian_bulan_ini = 0;
$kpi_perlu_diproses = 0;
$result_perlu_retur = false;
$result_pembelian_terakhir = false;
$pembelian_harian = [];
$labels_harian = [];
$result_stok_terendah = false;

// --- KPI Pembelian Bulan Ini ---
$bulan_ini = date('m');
$tahun_ini = date('Y');
$q_pembelian_bulan_ini = mysqli_query($koneksi, "SELECT COUNT(id_pembelian) as total_transaksi, SUM(total_biaya) as total_nilai FROM pencatatan_pembelian WHERE MONTH(tanggal_pembelian) = '$bulan_ini' AND YEAR(tanggal_pembelian) = '$tahun_ini'");
if($q_pembelian_bulan_ini) {
    $pembelian_bulan_ini = mysqli_fetch_assoc($q_pembelian_bulan_ini);
    $kpi_pembelian_bulan_ini = $pembelian_bulan_ini['total_transaksi'] ?? 0;
    $kpi_nilai_pembelian_bulan_ini = $pembelian_bulan_ini['total_nilai'] ?? 0;
}

// --- KPI & Widget "Menunggu Proses Retur" ---
$sql_perlu_retur = "
    SELECT p.id_pembelian, p.keterangan_pembelian, p.tanggal_pembelian
    FROM pencatatan_pembelian p
    WHERE EXISTS (SELECT 1 FROM log_penerimaan_material WHERE id_pembelian = p.id_pembelian AND jumlah_rusak > 0)
    AND 
    (SELECT COALESCE(SUM(jumlah_rusak), 0) FROM log_penerimaan_material WHERE id_pembelian = p.id_pembelian) > 
    (SELECT COALESCE(SUM(quantity), 0) FROM detail_pencatatan_pembelian WHERE id_pembelian = p.id_pembelian AND harga_satuan_pp = 0)
    ORDER BY p.tanggal_pembelian DESC
";
$result_perlu_retur = mysqli_query($koneksi, $sql_perlu_retur);
if ($result_perlu_retur) {
    $kpi_perlu_diproses = mysqli_num_rows($result_perlu_retur);
}

// --- Widget "5 Pembelian Terakhir" ---
$sql_pembelian_terakhir = "SELECT id_pembelian, tanggal_pembelian, keterangan_pembelian, total_biaya FROM pencatatan_pembelian ORDER BY id_pembelian DESC LIMIT 5";
$result_pembelian_terakhir = mysqli_query($koneksi, $sql_pembelian_terakhir);

// --- Data untuk Grafik Pembelian Harian ---
for ($i = 6; $i >= 0; $i--) {
    $hari = date('Y-m-d', strtotime("-$i days"));
    $labels_harian[] = date('d M', strtotime("-$i days"));
    $q_pembelian_harian = mysqli_query($koneksi, "SELECT SUM(total_biaya) as total FROM pencatatan_pembelian WHERE DATE(tanggal_pembelian) = '$hari'");
    $pembelian_harian[] = (int)(mysqli_fetch_assoc($q_pembelian_harian)['total'] ?? 0);
}

// --- [DIUBAH] Data untuk Stok Terendah dengan query yang lebih akurat ---
$sql_stok = "
    SELECT 
        m.nama_material, 
        s.nama_satuan,
        (
            COALESCE((SELECT SUM(jumlah_diterima) FROM log_penerimaan_material WHERE id_material = m.id_material), 0)
            - 
            COALESCE((SELECT SUM(jumlah_distribusi) FROM detail_distribusi WHERE id_material = m.id_material), 0)
        ) as sisa_stok
    FROM 
        master_material m
    LEFT JOIN 
        master_satuan s ON m.id_satuan = s.id_satuan
    HAVING 
        sisa_stok < 10
    ORDER BY 
        sisa_stok ASC
    LIMIT 5
";
$result_stok_terendah = mysqli_query($koneksi, $sql_stok);

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
                <h4 class="text-section">Transaksi RAB Material</h4>
              </li>
              <li class="nav-item">
                <a href="transaksi_rab_material.php">
                  <i class="fas fa-calculator"></i>
                  <p>Rancang RAB Material</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="pencatatan_pembelian.php">
                  <i class="fas fa-file-invoice-dollar"></i>
                  <p>Pencatatan Pembelian</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="penerimaan_material.php">
                  <i class="fas fa-pen-square"></i>
                  <p>Penerimaan Material</p>
                </a>
              </li>

              <li class="nav-item">
                <a href="distribusi_material.php">
                  <i class="fas fa-truck"></i>
                  <p>Distribusi Material</p>
                </a>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Laporan Upah</h4>
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
                <h4 class="text-section">Laporan Material</h4>
              </li>
              <li class="nav-item">
                <a href="lap_material/laporan_pembelian.php">
                  <i class="fas fa-file"></i>
                  <p>Laporan Pembelian</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="lap_material/laporan_distribusi.php">
                  <i class="fas fa-file"></i>
                  <p>Laporan Distribusi</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="lap_material/lap_realisasi_anggaran.php">
                  <i class="fas fa-file"></i>
                  <p>Laporan Realisasi</p>
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
                <a href="master_pekerjaan.php">
                  <i class="fas fa-database"></i>
                  <p>Master Pekerjaan</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="master_material.php">
                  <i class="fas fa-database"></i>
                  <p>Master Material</p>
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
                        <h3 class="fw-bold mb-3">Dashboard Admin</h3>
                        <h6 class="op-7 mb-2">Ringkasan Transaksi & Tugas Harian</h6>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6 col-md-4"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-primary bubble-shadow-small"><i class="fas fa-shopping-cart"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Pembelian Bulan Ini</p><h4 class="card-title"><?= $kpi_pembelian_bulan_ini ?></h4></div></div></div></div></div></div>
                    <div class="col-sm-6 col-md-4"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-success bubble-shadow-small"><i class="fas fa-money-bill-wave"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Nilai Pembelian</p><h4 class="card-title">Rp <?= number_format($kpi_nilai_pembelian_bulan_ini, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                    <div class="col-sm-6 col-md-4"><div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center"><div class="col-icon"><div class="icon-big text-center icon-warning bubble-shadow-small"><i class="fas fa-sync-alt"></i></div></div><div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Retur Perlu Diproses</p><h4 class="card-title"><?= $kpi_perlu_diproses ?></h4></div></div></div></div></div></div>
                </div>

                <div class="row">
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Aktivitas Pembelian (7 Hari Terakhir)</h4></div>
                            <div class="card-body"><div class="chart-container"><canvas id="pembelianHarianChart"></canvas></div></div>
                        </div>
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Pembelian Menunggu Proses Retur</h4></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if ($result_perlu_retur && mysqli_num_rows($result_perlu_retur) > 0): while($row = mysqli_fetch_assoc($result_perlu_retur)): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>ID: PB<?= $row['id_pembelian'] . date('Y', strtotime($row['tanggal_pembelian'])) ?><br><small class="text-muted"><?= htmlspecialchars($row['keterangan_pembelian']) ?></small></span>
                                        <a href="detail_pembelian.php?id=<?= $row['id_pembelian'] ?>" class="btn btn-warning btn-sm">Proses Retur</a>
                                    </li>
                                    <?php endwhile; else: ?>
                                    <li class="list-group-item text-center text-muted">Tidak ada retur yang perlu diproses.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Peringatan: Stok Gudang Terendah</h4></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if($result_stok_terendah && mysqli_num_rows($result_stok_terendah) > 0): while($stok = mysqli_fetch_assoc($result_stok_terendah)): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($stok['nama_material']) ?>
                                            <span class="badge bg-warning text-dark"><?= number_format($stok['sisa_stok'], 2, ',', '.') ?> <?= $stok['nama_satuan'] ?></span>
                                        </li>
                                    <?php endwhile; else: ?>
                                        <li class="list-group-item text-center text-muted">Stok aman.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">5 Pembelian Terakhir</h4></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if ($result_pembelian_terakhir && mysqli_num_rows($result_pembelian_terakhir) > 0): while($row = mysqli_fetch_assoc($result_pembelian_terakhir)): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>ID: PB<?= $row['id_pembelian'] . date('Y', strtotime($row['tanggal_pembelian'])) ?><br><small class="text-muted">Rp <?= number_format($row['total_biaya'], 0,',','.') ?></small></span>
                                        <a href="detail_pembelian.php?id=<?= $row['id_pembelian'] ?>" class="btn btn-info btn-sm">Lihat</a>
                                    </li>
                                    <?php endwhile; else: ?>
                                    <li class="list-group-item text-center text-muted">Belum ada transaksi pembelian.</li>
                                    <?php endif; ?>
                                </ul>
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

<script>
$(document).ready(function() {
    const labelsHarian = <?= json_encode($labels_harian) ?>;
    const dataHarian = <?= json_encode($pembelian_harian) ?>;

    if (dataHarian.some(v => v > 0)) {
        const ctxHarian = document.getElementById('pembelianHarianChart').getContext('2d');
        new Chart(ctxHarian, {
            type: 'line',
            data: {
                labels: labelsHarian,
                datasets: [{
                    label: "Nilai Pembelian",
                    borderColor: "#1d7af3",
                    pointBackgroundColor: "#1d7af3",
                    data: dataHarian,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); } } } }
            }
        });
    } else {
         $('#pembelianHarianChart').parent().html('<p class="text-center text-muted mt-5">Tidak ada aktivitas pembelian dalam 7 hari terakhir.</p>');
    }
});
</script>
</body>
</html>