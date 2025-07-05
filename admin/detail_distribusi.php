<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Ambil ID Distribusi dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID Distribusi tidak valid.";
    header("Location: distribusi_material.php");
    exit();
}
$id_distribusi = $_GET['id'];

// 2. Query untuk Data Header Distribusi
$header_sql = "
    SELECT 
        d.id_distribusi, d.tanggal_distribusi, d.keterangan_umum,
        u.nama_lengkap AS nama_pj_distribusi,
        CONCAT(pr.nama_perumahan, ' - Kavling ', p.kavling) AS nama_proyek_lengkap
    FROM distribusi_material d
    LEFT JOIN master_user u ON d.id_user_pj = u.id_user
    LEFT JOIN master_proyek p ON d.id_proyek = p.id_proyek 
    LEFT JOIN master_perumahan pr ON p.id_perumahan = pr.id_perumahan
    WHERE d.id_distribusi = ?
";
$stmt_header = mysqli_prepare($koneksi, $header_sql);
mysqli_stmt_bind_param($stmt_header, "i", $id_distribusi);
mysqli_stmt_execute($stmt_header);
$header_result = mysqli_stmt_get_result($stmt_header);
$distribusi = mysqli_fetch_assoc($header_result);

if (!$distribusi) {
    $_SESSION['error_message'] = "Data Distribusi tidak ditemukan.";
    header("Location: distribusi_material.php");
    exit();
}

// 3. Query untuk Daftar Item yang Sudah Didistribusikan
$detail_sql = "
    SELECT dd.id_detail, m.nama_material, dd.jumlah_distribusi, s.nama_satuan AS satuan
    FROM detail_distribusi dd
    JOIN master_material m ON dd.id_material = m.id_material
    LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan
    WHERE dd.id_distribusi = ?
    ORDER BY dd.id_detail ASC
";
$stmt_detail = mysqli_prepare($koneksi, $detail_sql);
mysqli_stmt_bind_param($stmt_detail, "i", $id_distribusi);
mysqli_stmt_execute($stmt_detail);
$detail_items = mysqli_stmt_get_result($stmt_detail);

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Distribusi Material</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="assets/img/logo/LOGO PT.jpg"
      type="image/x-icon"
    />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular",
            "Font Awesome 5 Brands",
            "simple-line-icons",
          ],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="assets/css/demo.css" />
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
                            <img src="../assets/img/logo/LOGO PT.jpg" alt="Logo PT" class="navbar-brand" height="30" />
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
                                        <img src="../../uploads/user_photos/<?= !empty($_SESSION['profile_pic']) ? htmlspecialchars($_SESSION['profile_pic']) : 'default.jpg' ?>" alt="Foto Profil" class="avatar-img rounded-circle" onerror="this.onerror=null; this.src='../assets/img/profile.jpg';">
                                    </div>
                                    <span class="profile-username">
                                        <span class="op-7">Hi,</span>
                                        <span class="fw-bold"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest') ?></span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box">
                                                <div class="avatar-lg">
                                                    <img src="../../uploads/user_photos/<?= !empty($_SESSION['profile_pic']) ? htmlspecialchars($_SESSION['profile_pic']) : 'default.jpg' ?>" alt="Foto Profil" class="avatar-img rounded" onerror="this.onerror=null; this.src='../assets/img/profile.jpg';">
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
                <h3 class="fw-bold mb-3">Distribusi Material</h3>
                <ul class="breadcrumbs mb-3">
                    <li class="nav-home">
                        <a href="dashboard.php">
                            <i class="icon-home"></i>
                        </a>
                    </li>
                    <li class="separator">
                        <i class="icon-arrow-right"></i>
                    </li>
                    <li class="nav-item">
                        <a href="distribusi_material.php">Distribusi Material</a>
                    </li>
                    <li class="separator">
                        <i class="icon-arrow-right"></i>
                    </li>
                    </li>
                    <li class="nav-item">
                        <a href="#">Detail Distribusi Material</a>
                    </li>

                </ul>
            </div>

                    <div class="card mb-4">
                        <div class="card-header"><h4 class="card-title">Informasi Transaksi</h4></div>
                          <div class="card-body">
                              <div class="row">
                                  <div class="col-md-6">
                                      <p><strong>ID Distribusi:</strong> DIST<?= htmlspecialchars($distribusi['id_distribusi']) . date('Y', strtotime($distribusi['tanggal_distribusi'])) ?></p>
                                      <p><strong>Proyek Tujuan:</strong> <?= htmlspecialchars($distribusi['nama_proyek_lengkap']) ?></p>
                                  </div>
                                  <div class="col-md-6">
                                      <p><strong>Tanggal:</strong> <?= date("d F Y", strtotime($distribusi['tanggal_distribusi'])) ?></p>
                                      <p><strong>PJ Proyek:</strong> <?= htmlspecialchars($distribusi['nama_pj_distribusi']) ?></p>
                                  </div>
                                  <div class="col-12 mt-2">
                                      <p><strong>Keterangan:</strong> <?= nl2br(htmlspecialchars($distribusi['keterangan_umum'])) ?></p>
                                  </div>
                              </div>
                          </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Rincian Material Didistribusikan</h4></div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Material</th>
                                        <th>Jumlah</th>
                                        <th>Satuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; if(mysqli_num_rows($detail_items) > 0): ?>
                                        <?php while($item = mysqli_fetch_assoc($detail_items)): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($item['nama_material']) ?></td>
                                                <td><?= htmlspecialchars($item['jumlah_distribusi']) ?></td>
                                                <td><?= htmlspecialchars($item['satuan']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Tidak ada rincian material untuk transaksi ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <div class="text-end mt-4">
                                <a href="distribusi_material.php" class="btn btn-secondary">Kembali ke Daftar</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    </body>
</html>