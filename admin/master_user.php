<?php
include("../config/koneksi_mysql.php");

// Mengatur error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Mengambil data user dari database
$result = mysqli_query($koneksi, "SELECT * FROM master_user");
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
        <?php include 'sidebar_m.php'; ?>
        <?php //include 'sidebar.php'; ?>

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
              <h3 class="fw-bold mb-3">Mastering</h3>
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
                  <a href="#">Mastering</a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="#">Master User</a>
                </li>
              </ul>
            </div>


<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex align-items-center">
        <h4 class="card-title">Master User</h4>
        <button
          class="btn btn-primary btn-round ms-auto"
          data-bs-toggle="modal"
          data-bs-target="#addUserModal"
        >
          <i class="fa fa-plus"></i> Tambah Data
        </button>
      </div>

      <?php if (isset($_GET['msg'])): ?>
        <div class="mb-3">
          <div class="alert alert-success fade show" role="alert">
            <?= htmlspecialchars($_GET['msg']) ?>
          </div>
        </div>
      <?php endif; ?>

      <script>
      window.setTimeout(function() {
        const alert = document.querySelector('.alert');
        if (alert) {
          alert.classList.add('fade');
          alert.classList.remove('show');
          setTimeout(() => alert.remove(), 350);
        }
      }, 3000);

        // Hapus parameter 'msg' dari URL agar tidak muncul lagi saat reload
      if (window.history.replaceState) {
        const url = new URL(window.location);
        if (url.searchParams.has('msg')) {
          url.searchParams.delete('msg');
          window.history.replaceState({}, document.title, url.pathname);
        }
      }
      </script>

                            <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="tabelUser" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID User</th>
                                                    <th>Foto</th>
                                                    <th>Nama Lengkap</th>
                                                    <th>Username</th>
                                                    <th>Role</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Menggunakan nama kolom yang sesuai dari databasemu
                                                $result = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, username, role, profile_pic FROM master_user ORDER BY id_user DESC");
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    // Logika untuk menentukan path foto dipindahkan ke dalam loop
                                                    $foto_path = (!empty($row['profile_pic']) && file_exists('../uploads/user_photos/' . $row['profile_pic']))
                                                                ? '../uploads/user_photos/' . $row['profile_pic']
                                                                : 'assets/img/default-avatar.png'; // Sediakan gambar default ini
                                                ?>
                                                    <tr>
                                                        <td class='text-center'><?= htmlspecialchars($row['id_user']) ?></td>
                                                        <td>
                                                            <div class="avatar avatar-md">
                                                                <img src="<?= htmlspecialchars($foto_path) ?>" alt="Foto" class="avatar-img rounded-circle">
                                                            </div>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                                        <td><?= htmlspecialchars($row['role']) ?></td>
                                                        <td>
                                                            <button class="btn btn-primary btn-sm btn-update" 
                                                                    data-id_user="<?= $row['id_user'] ?>"
                                                                    data-nama_lengkap="<?= htmlspecialchars($row['nama_lengkap']) ?>"
                                                                    data-username="<?= htmlspecialchars($row['username']) ?>"
                                                                    data-role="<?= htmlspecialchars($row['role']) ?>"
                                                                    data-foto="<?= htmlspecialchars($foto_path) ?>"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#updateUserModal">
                                                                Update
                                                            </button>
                                                            <button class="btn btn-danger btn-sm btn-delete" 
                                                                    data-id_user="<?= $row['id_user'] ?>"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#confirmDeleteModal">
                                                                Delete
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
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

<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="add_user.php" enctype="multipart/form-data" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Tambah Data User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="avatar avatar-xxl">
                            <img id="addAvatarPreview" src="assets/img/default-avatar.png" alt="..." class="avatar-img rounded-circle">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="addProfilePic" class="form-label">Foto Profil (Opsional)</label>
                        <input type="file" class="form-control" id="addProfilePic" name="profile_pic" accept="image/jpeg, image/png">
                    </div>
                    <div class="mb-3">
                        <label for="addNamaLengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="addNamaLengkap" name="nama_lengkap" placeholder="Masukkan nama lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label for="addUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="addUsername" name="username" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="addPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="addPassword" name="password" placeholder="Masukkan password" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label for="addRole" class="form-label">Role</label>
                        <select class="form-select" id="addRole" name="role" required>
                            <option value="" disabled selected>Pilih Role</option>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Admin">Admin</option>
                            <option value="Direktur">Direktur</option>
                            <option value="PJ Proyek">PJ Proyek</option>
                            <option value="Divisi Teknik">Divisi Teknik</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <div class="modal fade" id="updateUserModal" tabindex="-1" aria-labelledby="updateUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="update_user.php" enctype="multipart/form-data">
                    <input type="hidden" name="id_user" id="updateUserId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateUserModalLabel">Update Data User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <div class="avatar avatar-xxl">
                                <img id="updateAvatarPreview" src="assets/img/default-avatar.png" alt="..." class="avatar-img rounded-circle">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="updateProfilePic" class="form-label">Ganti Foto Profil (Opsional)</label>
                            <input type="file" class="form-control" id="updateProfilePic" name="profile_pic" accept="image/jpeg, image/png">
                        </div>
                        <div class="mb-3">
                            <label for="updateNamaLengkap" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="updateNamaLengkap" name="nama_lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label for="updateUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="updateUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="updatePassword" class="form-label">Password Baru (Opsional)</label>
                            <input type="password" class="form-control" id="updatePassword" name="password">
                            <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password.</small>
                        </div>
                        <div class="mb-3">
                            <label for="updateRole" class="form-label">Role</label>
                            <select class="form-select" id="updateRole" name="role" required>
                                <option value="Super Admin">Super Admin</option>
                                <option value="Admin">Admin</option>
                                <option value="Direktur">Direktur</option>
                                <option value="Sekretaris">Sekretaris</option>
                                <option value="PJ Proyek">PJ Proyek</option>
                                <option value="Divisi Teknik">Divisi Teknik</option>
                                <option value="Bendahara">Bendahara</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

  <!-- Modal Delete Confirmation -->
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this user?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <a href="#" id="confirmDeleteLink" class="btn btn-danger">Delete</a>
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
    $(document).ready(function() {
        // Inisialisasi DataTable
        $('#tabelUser').DataTable();

        // Fungsi terpusat untuk preview gambar
        function previewImage(event, previewElementId) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById(previewElementId);
                output.src = reader.result;
            }
            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }

        // Event listener untuk preview di modal Tambah dan Update
        $('#addProfilePic').on('change', function(event) { previewImage(event, 'addAvatarPreview'); });
        $('#updateProfilePic').on('change', function(event) { previewImage(event, 'updateAvatarPreview'); });

        // Event listener untuk tombol Update
        $('#tabelUser').on('click', '.btn-update', function() {
            const button = $(this);
            $('#updateUserId').val(button.data('id_user'));
            $('#updateNamaLengkap').val(button.data('nama_lengkap'));
            $('#updateUsername').val(button.data('username'));
            $('#updateRole').val(button.data('role'));
            $('#updateAvatarPreview').attr('src', button.data('foto'));
            $('#updatePassword').val('');
        });

        // Event listener untuk tombol Delete
        $('#tabelUser').on('click', '.btn-delete', function() {
            const userId = $(this).data('id_user');
            $('#confirmDeleteLink').attr('href', 'delete_user.php?id_user=' + userId);
        });
    });
    </script>
</body>
</html>