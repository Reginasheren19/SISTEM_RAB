<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. OTENTIKASI
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$pesan_sukses = '';
$pesan_error = '';

// 2. LOGIKA PEMROSESAN FORM
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // A. PROSES UPDATE PROFIL
    if (isset($_POST['update_profil'])) {
        $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
        $username = mysqli_real_escape_string($koneksi, $_POST['username']);

        $stmt = mysqli_prepare($koneksi, "UPDATE master_user SET nama_lengkap = ?, username = ? WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $nama_lengkap, $username, $id_user);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['pesan_sukses'] = "Profil berhasil diperbarui.";
        } else {
            $_SESSION['pesan_error'] = "Gagal memperbarui profil: " . mysqli_error($koneksi);
        }
        header("Location: profile.php");
        exit;
    }

    // B. PROSES UBAH PASSWORD
    if (isset($_POST['update_password'])) {
        // ... Logika ubah password tidak berubah, sudah benar ...
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi_password = $_POST['konfirmasi_password'];

        $stmt_get_pass = mysqli_prepare($koneksi, "SELECT password FROM master_user WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt_get_pass, "i", $id_user);
        mysqli_stmt_execute($stmt_get_pass);
        $user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get_pass));

        if ($user_data && password_verify($password_lama, $user_data['password'])) {
            if (strlen($password_baru) < 6) {
                $_SESSION['pesan_error'] = "Password baru minimal harus 6 karakter.";
            } elseif ($password_baru === $konfirmasi_password) {
                $password_hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt_update = mysqli_prepare($koneksi, "UPDATE master_user SET password = ? WHERE id_user = ?");
                mysqli_stmt_bind_param($stmt_update, "si", $password_hash_baru, $id_user);
                
                if (mysqli_stmt_execute($stmt_update)) {
                    $_SESSION['pesan_sukses'] = "Password berhasil diubah.";
                } else {
                    $_SESSION['pesan_error'] = "Gagal mengubah password.";
                }
            } else {
                $_SESSION['pesan_error'] = "Password baru dan konfirmasi tidak cocok.";
            }
        } else {
            $_SESSION['pesan_error'] = "Password lama yang Anda masukkan salah.";
        }
        header("Location: profile.php");
        exit;
    }
    
    // C. PROSES UPLOAD FOTO PROFIL
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        // [DIUBAH] Path upload disesuaikan sesuai instruksi Anda
        $target_dir = "../uploads/user_photos/"; 
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $nama_file_asli = basename($_FILES["foto_profil"]["name"]);
        $ekstensi_file = strtolower(pathinfo($nama_file_asli, PATHINFO_EXTENSION));
        $nama_file_unik = "user_" . $id_user . "_" . time() . "." . $ekstensi_file;
        $target_file = $target_dir . $nama_file_unik;

        $izin_upload = true;
        $tipe_gambar = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($ekstensi_file, $tipe_gambar)) {
            $_SESSION['pesan_error'] = "Maaf, hanya file JPG, JPEG, PNG & GIF yang diizinkan.";
            $izin_upload = false;
        }

        if ($izin_upload) {
            if (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $target_file)) {
                $stmt_foto = mysqli_prepare($koneksi, "UPDATE master_user SET profile_pic = ? WHERE id_user = ?");
                mysqli_stmt_bind_param($stmt_foto, "si", $nama_file_unik, $id_user);
                if(mysqli_stmt_execute($stmt_foto)){
                    $_SESSION['pesan_sukses'] = "Foto profil berhasil diperbarui.";
                } else {
                    $_SESSION['pesan_error'] = "Gagal menyimpan path foto ke database.";
                }
            } else {
                $_SESSION['pesan_error'] = "Maaf, terjadi kesalahan saat mengunggah file.";
            }
        }
        header("Location: profile.php");
        exit;
    }
}

// 3. AMBIL DATA PENGGUNA TERBARU UNTUK DITAMPILKAN
$query_ambil_data = "SELECT id_user, nama_lengkap, username, role, profile_pic FROM master_user WHERE id_user = ?";
$stmt = mysqli_prepare($koneksi, $query_ambil_data);
if ($stmt === false) {
    die("Error dalam mempersiapkan query: " . mysqli_error($koneksi));
}
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
if (!$user) {
    die("Tidak dapat menemukan data pengguna dengan ID: " . $id_user);
}

// [DIUBAH] Path untuk menampilkan foto disesuaikan dengan folder upload Anda
$path_ke_foto_tersimpan = '../uploads/user_photos/' . $user['profile_pic'];
$path_default = '../assets/img/kaiadmin/default-avatar.png';

$foto_profil_path = (!empty($user['profile_pic']) && file_exists($path_ke_foto_tersimpan))
                    ? $path_ke_foto_tersimpan
                    : $path_default;

// Ambil pesan dari session jika ada, lalu hapus
if(isset($_SESSION['pesan_sukses'])){ $pesan_sukses = $_SESSION['pesan_sukses']; unset($_SESSION['pesan_sukses']); }
if(isset($_SESSION['pesan_error'])){ $pesan_error = $_SESSION['pesan_error']; unset($_SESSION['pesan_error']); }
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
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Profil Pengguna</h3>
                        <ul class="breadcrumbs">
                            <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Pengguna</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Profil</a></li>
                        </ul>
                    </div>

                    <?php if ($pesan_sukses): ?>
                    <div class="alert alert-success" role="alert"><?= $pesan_sukses ?></div>
                    <?php endif; ?>
                    <?php if ($pesan_error): ?>
                    <div class="alert alert-danger" role="alert"><?= $pesan_error ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card card-profile">
                                <div class="card-header" style="background-image: url('assets/img/blogpost.jpg')">
                                    <div class="profile-picture">
                                        <div class="avatar avatar-xl">
                                            <img src="<?= $foto_profil_path ?>?t=<?= time() ?>" alt="Foto Profil" class="avatar-img rounded-circle">
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="user-profile text-center">
                                        <div class="name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                                        <div class="job fw-light text-muted"><?= htmlspecialchars(ucfirst($user['role'])) ?></div>
                                        <form action="profile.php" method="POST" enctype="multipart/form-data" id="form-ganti-foto">
                                            <div class="d-grid mt-3">
                                                 <label for="foto_profil_input" class="btn btn-primary btn-sm"><i class="fas fa-camera"></i> Ganti Foto</label>
                                                 <input type="file" id="foto_profil_input" name="foto_profil" class="d-none">
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header"><div class="card-title">Pengaturan Akun</div></div>
                                <div class="card-body">
                                    <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
                                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#pills-profile">Edit Profil</a></li>
                                        <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#pills-password">Ubah Password</a></li>
                                    </ul>
                                    <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                                        <div class="tab-pane fade show active" id="pills-profile" role="tabpanel">
                                            <form action="profile.php" method="POST" class="mt-3">
                                                <input type="hidden" name="update_profil" value="1">
                                                <div class="mb-3">
                                                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="username" class="form-label">Username</label>
                                                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                                                </div>
                                                <div class="text-end"><button type="submit" class="btn btn-success">Simpan Perubahan</button></div>
                                            </form>
                                        </div>
                                        <div class="tab-pane fade" id="pills-password" role="tabpanel">
                                            <form action="profile.php" method="POST" class="mt-3">
                                                <input type="hidden" name="update_password" value="1">
                                                <div class="mb-3"><label for="password_lama" class="form-label">Password Lama</label><input type="password" class="form-control" id="password_lama" name="password_lama" required></div>
                                                <div class="mb-3"><label for="password_baru" class="form-label">Password Baru</label><input type="password" class="form-control" id="password_baru" name="password_baru" required minlength="6"></div>
                                                <div class="mb-3"><label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru</label><input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required></div>
                                                <div class="text-end"><button type="submit" class="btn btn-success">Ubah Password</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const fotoProfilInput = document.getElementById('foto_profil_input');
            const formGantiFoto = document.getElementById('form-ganti-foto');
            if (fotoProfilInput && formGantiFoto) {
                fotoProfilInput.addEventListener('change', function(event) {
                    if (event.target.files.length > 0) {
                        formGantiFoto.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>