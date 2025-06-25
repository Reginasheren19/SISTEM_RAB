<?php
session_start();
include("../config/koneksi_mysql.php");

// Pastikan user sudah login
$logged_in_user_id = $_SESSION['id_user'] ?? 0;
if ($logged_in_user_id === 0) {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

// Ambil parameter filter dari URL
$proyek_filter = $_GET['proyek'] ?? 'semua';
$mandor_filter = $_GET['mandor'] ?? 'semua';
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

// Ambil data untuk dropdown filter
$proyek_list = mysqli_query($koneksi, "SELECT id_proyek, CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek FROM master_proyek mpr LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan ORDER BY nama_proyek ASC");
$mandor_list = mysqli_query($koneksi, "SELECT id_mandor, nama_mandor FROM master_mandor ORDER BY nama_mandor ASC");

// Bangun query utama dengan filter dinamis
$sql = "SELECT 
            mpr.id_proyek,
            CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek,
            ru.total_rab_upah,
            (SELECT SUM(pu.total_pengajuan) 
             FROM pengajuan_upah pu 
             WHERE pu.id_rab_upah = ru.id_rab_upah AND pu.status_pengajuan = 'dibayar'
             " . (!empty($tanggal_mulai) ? "AND pu.tanggal_pengajuan >= '" . mysqli_real_escape_string($koneksi, $tanggal_mulai) . "'" : "") . "
             " . (!empty($tanggal_selesai) ? "AND pu.tanggal_pengajuan <= '" . mysqli_real_escape_string($koneksi, $tanggal_selesai) . "'" : "") . "
            ) AS total_terbayar
        FROM master_proyek mpr
        LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        INNER JOIN rab_upah ru ON mpr.id_proyek = ru.id_proyek";

$where_conditions = [];
if ($proyek_filter !== 'semua') {
    $where_conditions[] = "mpr.id_proyek = " . (int)$proyek_filter;
}
if ($mandor_filter !== 'semua') {
    $where_conditions[] = "mpr.id_mandor = " . (int)$mandor_filter;
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " GROUP BY mpr.id_proyek, nama_proyek, ru.total_rab_upah ORDER BY mpe.nama_perumahan, mpr.kavling ASC";

$result_laporan = mysqli_query($koneksi, $sql);
if (!$result_laporan) {
    die("Query Gagal: " . mysqli_error($koneksi));
}

// [PERBAIKAN] Bangun query string untuk link download, tambahkan 'laporan'
$download_query_string = http_build_query([
    'laporan' => 'realisasi_anggaran',
    'proyek' => $proyek_filter,
    'mandor' => $mandor_filter,
    'tanggal_mulai' => $tanggal_mulai,
    'tanggal_selesai' => $tanggal_selesai,
]);

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
                        <h3 class="fw-bold mb-3">Laporan Realisasi Anggaran Upah</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="dashboard.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Laporan</a></li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Filter Laporan</h4></div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="filter-proyek" class="form-label">Proyek</label>
                                        <select id="filter-proyek" name="proyek" class="form-select">
                                            <option value="semua">Semua Proyek</option>
                                            <?php if($proyek_list) mysqli_data_seek($proyek_list, 0); ?>
                                            <?php while($p = mysqli_fetch_assoc($proyek_list)): ?>
                                                <option value="<?= $p['id_proyek'] ?>" <?= ($proyek_filter == $p['id_proyek']) ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_proyek']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="filter-mandor" class="form-label">Mandor</label>
                                        <select id="filter-mandor" name="mandor" class="form-select">
                                            <option value="semua">Semua Mandor</option>
                                             <?php if($mandor_list) mysqli_data_seek($mandor_list, 0); ?>
                                             <?php while($m = mysqli_fetch_assoc($mandor_list)): ?>
                                                <option value="<?= $m['id_mandor'] ?>" <?= ($mandor_filter == $m['id_mandor']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_mandor']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="filter-tanggal-mulai" class="form-label">Dari Tanggal</label>
                                        <input type="date" id="filter-tanggal-mulai" name="tanggal_mulai" class="form-control" value="<?= htmlspecialchars($tanggal_mulai) ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="filter-tanggal-selesai" class="form-label">Sampai Tanggal</label>
                                        <input type="date" id="filter-tanggal-selesai" name="tanggal_selesai" class="form-control" value="<?= htmlspecialchars($tanggal_selesai) ?>">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">Terapkan Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title">Perbandingan Anggaran vs Realisasi per Proyek</h4>
                                <a href="cetak_lap_upah.php?<?= $download_query_string ?>" target="_blank" class="btn btn-success btn-round ms-auto">
                                    <i class="fas fa-print"></i> Cetak Ringkasan
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="report-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nama Proyek</th>
                                            <th class="text-end">Total Anggaran (RAB)</th>
                                            <th class="text-end">Total Terbayar</th>
                                            <th class="text-end">Sisa Anggaran</th>
                                            <th class="text-center" style="width: 20%;">Realisasi (%)</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result_laporan && mysqli_num_rows($result_laporan) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($result_laporan)): 
                                                $total_rab = (float)($row['total_rab_upah'] ?? 0);
                                                $total_terbayar = (float)($row['total_terbayar'] ?? 0);
                                                $sisa_anggaran = $total_rab - $total_terbayar;
                                                $realisasi_persen = ($total_rab > 0) ? ($total_terbayar / $total_rab) * 100 : 0;
                                                $progress_color = 'bg-success';
                                                if ($realisasi_persen > 80) $progress_color = 'bg-warning';
                                                if ($realisasi_persen >= 100) $progress_color = 'bg-danger';
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['nama_proyek']) ?></td>
                                                <td class="text-end fw-bold">Rp <?= number_format($total_rab, 0, ',', '.') ?></td>
                                                <td class="text-end">Rp <?= number_format($total_terbayar, 0, ',', '.') ?></td>
                                                <td class="text-end fw-bold text-success">Rp <?= number_format($sisa_anggaran, 0, ',', '.') ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar <?= $progress_color ?>" role="progressbar" style="width: <?= number_format($realisasi_persen, 2) ?>%;" aria-valuenow="<?= $realisasi_persen ?>" aria-valuemin="0" aria-valuemax="100">
                                                            <?= number_format($realisasi_persen, 2) ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <a href="cetak_detail_progres.php?proyek_id=<?= $row['id_proyek'] ?>" target="_blank" class="btn btn-success btn-sm" title="Cetak Detail Progres">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center text-muted">Tidak ada data proyek untuk ditampilkan. Coba ubah filter Anda.</td></tr>
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