<?php
session_start();
include("../config/koneksi_mysql.php");

// =========================================================================
// Pastikan nama session ini sesuai dengan file login.php Anda
$logged_in_user_id = $_SESSION['id_user'] ?? 0;
$user_role = strtolower($_SESSION['role'] ?? 'guest');

if ($logged_in_user_id === 0) {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}
// =========================================================================

// Ambil nama file saat ini untuk menandai menu yang aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil filter proyek dari URL
$proyek_filter = $_GET['proyek'] ?? null;

// Persiapan filter query jika yang login adalah PJ Proyek
$where_clause_pj = "";
if ($user_role === 'pj proyek') {
    $safe_user_id = (int) $logged_in_user_id;
    $where_clause_pj = " WHERE mpr.id_user_pj = $safe_user_id";
}

// Ambil data untuk dropdown filter proyek
$proyek_list_sql = "SELECT id_proyek, CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek FROM master_proyek mpr LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan $where_clause_pj ORDER BY nama_proyek ASC";
$proyek_list_result = mysqli_query($koneksi, $proyek_list_sql);

$result_laporan = null;
$proyek_info = null;

// Jika proyek sudah dipilih, jalankan query utama
if (!empty($proyek_filter)) {
    $safe_proyek_filter = (int)$proyek_filter;

    // Query untuk info header proyek
    $info_sql = "SELECT CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek, mm.nama_mandor 
                 FROM master_proyek mpr 
                 LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
                 LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
                 WHERE mpr.id_proyek = $safe_proyek_filter";
    $proyek_info = mysqli_fetch_assoc(mysqli_query($koneksi, $info_sql));

    // Query utama untuk mendapatkan riwayat pengajuan
    $laporan_sql = "SELECT pu.tanggal_pengajuan, pu.total_pengajuan, pu.status_pengajuan, pu.updated_at,
                           (SELECT COUNT(*) FROM pengajuan_upah p2 WHERE p2.id_rab_upah = ru.id_rab_upah AND p2.id_pengajuan_upah <= pu.id_pengajuan_upah) AS termin_ke
                    FROM pengajuan_upah pu
                    JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah
                    WHERE ru.id_proyek = $safe_proyek_filter
                    ORDER BY pu.tanggal_pengajuan ASC";
    $result_laporan = mysqli_query($koneksi, $laporan_sql);
}

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
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Laporan Rekapitulasi Proyek</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="dashboard.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Laporan</a></li>
                        </ul>
                    </div>

                    <!-- Filter Section -->
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Pilih Proyek</h4></div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row align-items-end">
                                    <div class="col-md-10">
                                        <label for="filter-proyek" class="form-label">Pilih Proyek untuk Melihat Laporan</label>
                                        <select id="filter-proyek" name="proyek" class="form-select">
                                            <option value="">-- Pilih Proyek --</option>
                                            <?php if($proyek_list_result) mysqli_data_seek($proyek_list_result, 0); ?>
                                            <?php while($p = mysqli_fetch_assoc($proyek_list_result)): ?>
                                                <option value="<?= $p['id_proyek'] ?>" <?= ($proyek_filter == $p['id_proyek']) ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_proyek']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <?php if (!empty($proyek_filter) && $proyek_info): ?>
                    <!-- Tabel Laporan -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h4 class="card-title">Rekapitulasi: <?= htmlspecialchars($proyek_info['nama_proyek']) ?></h4>
                                    <p class="card-category mb-0">Mandor: <?= htmlspecialchars($proyek_info['nama_mandor']) ?></p>
                                </div>
                                <div class="ms-auto btn-group">
                                    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-download"></i> Unduh
                                    </button>
<ul class="dropdown-menu">
    <li><a class="dropdown-item" href="cetak_lap_upah.php?laporan=rekapitulasi_proyek&proyek=<?= $proyek_filter ?>" target="_blank">PDF</a></li>
    <li><a class="dropdown-item" href="#">Excel</a></li>
</ul>                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="report-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Termin PengajuanKe-</th>    
                                            <th class="text-center">Tanggal Pengajuan</th>
                                            <th class="text-end">Total Pengajuan</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Tanggal Dibayar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result_laporan && mysqli_num_rows($result_laporan) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($result_laporan)): ?>
                                            <tr>
                                                <td class="text-center"><?= $row['termin_ke'] ?></td>
                                                <td class="text-center"><?= date("d F Y", strtotime($row['tanggal_pengajuan'])) ?></td>
                                                <td class="text-end">Rp <?= number_format($row['total_pengajuan'], 0, ',', '.') ?></td>
                                                <td class="text-center"><?= getStatusBadge($row['status_pengajuan']) ?></td>
                                                <td class="text-center">
                                                    <?php 
                                                        if (strtolower($row['status_pengajuan']) == 'dibayar' && !empty($row['updated_at'])) {
                                                            echo date("d F Y", strtotime($row['updated_at']));
                                                        } else {
                                                            echo "-";
                                                        }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Belum ada riwayat pengajuan untuk proyek ini.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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
    <!-- Core JS Files -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#report-table').DataTable({
                "pageLength": 10,
            });
        });
    </script>
</body>
</html>
