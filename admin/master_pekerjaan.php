<?php
// Hal paling penting: Memulai sesi untuk menggunakan variabel $_SESSION
session_start();

// Menyertakan file koneksi database
include("../config/koneksi_mysql.php");

// Mengatur error reporting untuk menampilkan semua error (baik untuk development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- OPTIMISASI ---
// Ambil data satuan satu kali saja di awal, lalu simpan di array.
// Ini lebih efisien daripada query berulang kali ke database.
$satuan_options = [];
$satuan_query = "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan ASC";
$satuan_result = mysqli_query($koneksi, $satuan_query);
if (!$satuan_result) {
    // Jika query gagal, hentikan eksekusi dan tampilkan error
    die("Query Error (master_satuan): " . mysqli_error($koneksi));
}
while ($satuan = mysqli_fetch_assoc($satuan_result)) {
    $satuan_options[] = $satuan;
}

// Query utama untuk mengambil data pekerjaan yang akan ditampilkan di tabel
$pekerjaan_query = "SELECT mp.id_pekerjaan, mp.uraian_pekerjaan, mp.id_satuan, ms.nama_satuan
                    FROM master_pekerjaan mp
                    JOIN master_satuan ms ON mp.id_satuan = ms.id_satuan
                    ORDER BY mp.id_pekerjaan ASC";
$pekerjaan_result = mysqli_query($koneksi, $pekerjaan_query);
if (!$pekerjaan_result) {
    die("Query Error (master_pekerjaan): " . mysqli_error($koneksi));
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
                            <img src="../assets/img/logo/LOGO PT.jpg" alt="Logo PT" class="navbar-brand" height="30" />
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                            <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                        </div>
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
                        <h3 class="fw-bold mb-3">Mastering</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home">
                                <a href="dashboard.php"><i class="icon-home"></i></a>
                            </li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Mastering</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Master Pekerjaan</a></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center">
                                    <h4 class="card-title">Master Pekerjaan</h4>
                                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addPekerjaanModal">
                                        <i class="fa fa-plus"></i> Tambah Data
                                    </button>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_GET['msg'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?= htmlspecialchars($_GET['msg']) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                    <?php endif; ?>

                                    <div class="table-responsive">
                                        <table id="basic-datatables" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID Pekerjaan</th>
                                                    <th>Uraian Pekerjaan</th>
                                                    <th>Nama Satuan</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Loop melalui hasil query pekerjaan yang sudah diambil di atas
                                                while ($row = mysqli_fetch_assoc($pekerjaan_result)) {
                                                    echo "<tr>
                                                        <td class='text-center'>" . htmlspecialchars($row['id_pekerjaan']) . "</td>
                                                        <td>" . htmlspecialchars($row['uraian_pekerjaan']) . "</td>
                                                        <td class='text-center'>" . htmlspecialchars($row['nama_satuan']) . "</td>
                                                        <td>
                                                            <button 
                                                                class='btn btn-primary btn-sm btn-update' 
                                                                data-id_pekerjaan='" . htmlspecialchars($row['id_pekerjaan']) . "' 
                                                                data-uraian_pekerjaan='" . htmlspecialchars($row['uraian_pekerjaan']) . "' 
                                                                data-id_satuan='" . htmlspecialchars($row['id_satuan']) . "'
                                                                data-bs-toggle='modal' 
                                                                data-bs-target='#updatePekerjaanModal'>
                                                                Update
                                                            </button>
                                                            <button 
                                                                class='btn btn-danger btn-sm delete-btn' 
                                                                data-id_pekerjaan='" . htmlspecialchars($row['id_pekerjaan']) . "'>
                                                                Delete
                                                            </button>
                                                        </td>
                                                    </tr>";
                                                }
                                                ?>
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

    <!-- Modal Tambah Data Pekerjaan -->
    <div class="modal fade" id="addPekerjaanModal" tabindex="-1" aria-labelledby="addPekerjaanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="add_pekerjaan.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPekerjaanModalLabel">Tambah Data Pekerjaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="uraian_pekerjaan" class="form-label">Uraian Pekerjaan</label>
                            <input type="text" class="form-control" id="uraian_pekerjaan" name="uraian_pekerjaan" placeholder="Masukkan uraian pekerjaan" required />
                        </div>
                        <div class="mb-3">
                            <label for="id_satuan" class="form-label">Nama Satuan</label>
                            <select class="form-select" id="id_satuan" name="id_satuan" required>
                                <option value="" disabled selected>Pilih Nama Satuan</option>
                                <?php 
                                // Gunakan array $satuan_options yang sudah diambil sebelumnya
                                foreach ($satuan_options as $satuan): ?>
                                    <option value="<?= htmlspecialchars($satuan['id_satuan']) ?>">
                                        <?= htmlspecialchars($satuan['nama_satuan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_pekerjaan" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Update Data Pekerjaan -->
    <div class="modal fade" id="updatePekerjaanModal" tabindex="-1" aria-labelledby="updatePekerjaanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="update_pekerjaan.php">
                    <input type="hidden" name="id_pekerjaan" id="update_id_pekerjaan" />
                    <div class="modal-header">
                        <h5 class="modal-title" id="updatePekerjaanModalLabel">Update Data Pekerjaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="update_uraian_pekerjaan" class="form-label">Uraian Pekerjaan</label>
                            <input type="text" class="form-control" id="update_uraian_pekerjaan" name="uraian_pekerjaan" placeholder="Ubah uraian pekerjaan" required />
                        </div>
                        <div class="mb-3">
                            <label for="update_id_satuan" class="form-label">Nama Satuan</label>
                            <select class="form-select" id="update_id_satuan" name="id_satuan" required>
                                <option value="" disabled>Pilih Nama Satuan</option>
                                <?php 
                                // Gunakan lagi array $satuan_options yang sama
                                foreach ($satuan_options as $satuan): ?>
                                    <option value="<?= htmlspecialchars($satuan['id_satuan']) ?>">
                                        <?= htmlspecialchars($satuan['nama_satuan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_pekerjaan" class="btn btn-primary">Update</button>
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
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus data ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirmDeleteLink" class="btn btn-danger">Hapus</a>
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
        $(document).ready(function() {
            $('#basic-datatables').DataTable();
            
            // Hapus notifikasi setelah beberapa detik
            window.setTimeout(function() {
                $(".alert").fadeTo(500, 0).slideUp(500, function(){
                    $(this).remove(); 
                });
            }, 4000);

            // Hapus parameter 'msg' dari URL setelah halaman dimuat
            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                if (url.searchParams.has('msg')) {
                    url.searchParams.delete('msg');
                    window.history.replaceState({ path: url.href }, '', url.href);
                }
            }
        });

        // Event listener untuk tombol update
        document.querySelectorAll('.btn-update').forEach(button => {
            button.addEventListener('click', function() {
                const idPekerjaan = this.dataset.id_pekerjaan;
                const uraianPekerjaan = this.dataset.uraian_pekerjaan;
                const idSatuan = this.dataset.id_satuan;

                // Mengisi form di dalam modal update
                document.getElementById('update_id_pekerjaan').value = idPekerjaan;
                document.getElementById('update_uraian_pekerjaan').value = uraianPekerjaan;
                document.getElementById('update_id_satuan').value = idSatuan; // Ini akan memilih option yang sesuai
            });
        });

        // Event listener untuk tombol delete
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const pekerjaanId = this.dataset.id_pekerjaan;
                const deleteLink = document.getElementById('confirmDeleteLink');
                // Arahkan link ke file delete_pekerjaan.php dengan parameter id
                deleteLink.href = 'delete_pekerjaan.php?id=' + pekerjaanId; 
                
                // Tampilkan modal konfirmasi
                const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>
