<?php
session_start();
include("../config/koneksi_mysql.php");

$sql = "
    SELECT 
        d.id_distribusi,
        d.tanggal_distribusi,
        d.keterangan_umum,
        u.nama_lengkap AS nama_pj_distribusi,
        -- Menggabungkan nama perumahan dan kavling untuk membuat nama proyek yang lengkap
        CONCAT(pr.nama_perumahan, ' - Kavling ', p.kavling) AS nama_proyek_lengkap
    FROM 
        distribusi_material d
    LEFT JOIN
        master_user u ON d.id_user_pj = u.id_user
    LEFT JOIN
        master_proyek p ON d.id_proyek = p.id_proyek 
    LEFT JOIN
        master_perumahan pr ON p.id_perumahan = pr.id_perumahan
    ORDER BY 
        d.id_distribusi DESC
";
$result = mysqli_query($koneksi, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}

// Pastikan user sudah login sebelum mengambil daftar proyek
if (!isset($_SESSION['id_user'])) {
    // Jika user belum login, kita buat variabel $proyek_result sebagai array kosong
    // agar tidak terjadi error di bagian HTML.
    $proyek_result = [];
    // Idealnya, jika belum login, user seharusnya diarahkan ke halaman login.
    // header("Location: login.php"); exit();
} else {
    // Ambil ID user yang sedang login dari session
    $logged_in_user_id = $_SESSION['id_user'];

    // Query ini hanya akan mengambil proyek yang PJ-nya adalah user yang sedang login
    $proyek_sql = "
        SELECT 
            p.id_proyek,
            CONCAT(pr.nama_perumahan, ' - Kavling ', p.kavling) AS nama_proyek_lengkap
        FROM 
            master_proyek p
        LEFT JOIN 
            master_perumahan pr ON p.id_perumahan = pr.id_perumahan
        WHERE 
            p.id_user_pj = ?
        ORDER BY 
            nama_proyek_lengkap ASC
    ";

    // Gunakan Prepared Statement untuk keamanan
    $stmt_proyek = mysqli_prepare($koneksi, $proyek_sql);
    mysqli_stmt_bind_param($stmt_proyek, "i", $logged_in_user_id);
    mysqli_stmt_execute($stmt_proyek);
    $proyek_result = mysqli_stmt_get_result($stmt_proyek);
}
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
                        <a href="#">Distribusi Material</a>
                    </li>
                </ul>
            </div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h4 class="card-title">Daftar Transaksi Distribusi</h4>
                <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addDistribusiModal">
                    <i class="fa fa-plus"></i> Tambah Distribusi
                </button>
            </div>
            <div class="card-body">
                <?php
                // Blok notifikasi (sudah benar)
                if (isset($_SESSION['pesan_sukses'])) {
                    echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($_SESSION['pesan_sukses']) . '</div>';
                    unset($_SESSION['pesan_sukses']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>
                <div class="table-responsive">
                    <table id="tabelDistribusi" class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>ID Distribusi</th>
                                <th>Tanggal</th>
                                <th>Proyek Tujuan</th>
                                <th>PJ Proyek</th>
                                <th>Keterangan</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result && mysqli_num_rows($result) > 0): 
                                $nomor = 1; 
                                while ($row = mysqli_fetch_assoc($result)):
                                    $tahun_distribusi = date('Y', strtotime($row['tanggal_distribusi']));
                                    $formatted_id = 'DIST' . $row['id_distribusi'] . $tahun_distribusi;
                            ?>
                                <tr>
                                    <td><?= $nomor++ ?></td>
                                    <td><?= htmlspecialchars($formatted_id) ?></td>
                                    <td><?= date("d F Y", strtotime($row['tanggal_distribusi'])) ?></td>
                                    <td><?= htmlspecialchars($row['nama_proyek_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_pj_distribusi']) ?></td>
                                    <td><?= htmlspecialchars($row['keterangan_umum']) ?></td>
                                    <td>
                                        <a href="detail_distribusi.php?id=<?= urlencode($row['id_distribusi']) ?>" class="btn btn-info btn-sm">Detail</a>
                                        <button class="btn btn-danger btn-sm btn-delete" 
                                                data-id="<?= $row['id_distribusi'] ?>">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center">Belum ada data distribusi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addDistribusiModal" tabindex="-1" aria-labelledby="addDistribusiModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="add_distribusi.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDistribusiModalLabel">Buat Transaksi Distribusi Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="id_proyek" class="form-label">Proyek Tujuan</label>
                        <select class="form-select" id="id_proyek" name="id_proyek" required>
                            <option value="" disabled selected>-- Pilih Proyek --</option>
                            <?php
                            if ($proyek_result && mysqli_num_rows($proyek_result) > 0) {
                                mysqli_data_seek($proyek_result, 0); 
                                while ($proyek = mysqli_fetch_assoc($proyek_result)) {
                                    echo '<option value="' . htmlspecialchars($proyek['id_proyek']) . '">' . htmlspecialchars($proyek['nama_proyek_lengkap']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tanggal_distribusi" class="form-label">Tanggal Distribusi</label>
                        <input type="date" class="form-control" id="tanggal_distribusi" name="tanggal_distribusi" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="keterangan_umum" class="form-label">Keterangan Umum (Opsional)</label>
                        <textarea class="form-control" id="keterangan_umum" name="keterangan_umum" rows="3" placeholder="Contoh: Pengambilan material untuk Pengecoran Blok A"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Lanjut ke Input Detail</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data distribusi ini?</p>
                <p class="text-danger small"><strong>Peringatan:</strong> Stok material akan dikembalikan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="confirmDeleteLink" class="btn btn-danger">Ya, Hapus</a>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/plugin/datatables/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    $('#tabelDistribusi').DataTable({ "order": [] });

    // Script notifikasi
    const alertBox = $('.alert');
    if (alertBox.length) {
        setTimeout(function() {
            alertBox.fadeOut('slow');
        }, 5000);
    }

    // JAVASCRIPT FINAL UNTUK MODAL DELETE
    $('#tabelDistribusi').on('click', '.btn-delete', function() {
        // 1. Ambil ID dari tombol
        const id = $(this).data('id');
        const deleteUrl = `delete_distribusi.php?id=${id}`;

        // 2. Isi link di tombol "Ya, Hapus" di dalam modal
        $('#confirmDeleteLink').attr('href', deleteUrl);
        
        // 3. Perintahkan modal untuk muncul secara manual (INI KUNCINYA)
        $('#confirmDeleteModal').modal('show'); 
    });
});
</script>
</body>
</html>