<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Cukup 1x query untuk mengambil semua data satuan untuk modal Tambah dan Update
$satuan_result = mysqli_query($koneksi, "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan ASC");
$satuans = [];
while ($satuan = mysqli_fetch_assoc($satuan_result)) {
    $satuans[] = $satuan;
}

// 2. QUERY UTAMA: Menggabungkan 3 Tabel (Material, Satuan, Stok) dengan LEFT JOIN
$sql = "
    SELECT 
        m.id_material,
        m.nama_material,
        m.keterangan_material,
        m.id_satuan,
        s.nama_satuan,
        st.jumlah_stok_tersedia
    FROM 
        master_material m
    LEFT JOIN 
        master_satuan s ON m.id_satuan = s.id_satuan
    LEFT JOIN 
        stok_material st ON m.id_material = st.id_material
    ORDER BY 
        m.id_material DESC
";
$result = mysqli_query($koneksi, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Material</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="assets/img/logo/LOGO PT.jpg"
      type="image/x-icon"
    />
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" />

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
                  <a href="#">Master Material</a>
                </li>
              </ul>
            </div>

            <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <h4 class="card-title">Daftar Material</h4>
                                <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                                    <i class="fa fa-plus"></i> Tambah Data
                                </button>
                            </div>
                            <?php
                            // Cek apakah ada pesan sukses di dalam session
                            if (isset($_SESSION['pesan_sukses'])) {
                                // 1. Tampilkan pesannya
                                echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($_SESSION['pesan_sukses']) . '</div>';
                                
                                // 2. LANGKAH PENTING: Hapus pesan dari session agar tidak muncul lagi
                                unset($_SESSION['pesan_sukses']);
                            }

                            // Lakukan hal yang sama untuk pesan error
                            if (isset($_SESSION['error_message'])) {
                                echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                                unset($_SESSION['error_message']);
                            }
                            ?>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="tabelMaterial" class="display table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>No.</th>
                                                <th>Nama Material</th>
                                                <th>Satuan</th>
                                                <th>Stok Tersedia</th> <th>Keterangan</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $nomor = 1;
                                            while ($row = mysqli_fetch_assoc($result)):
                                            ?>
                                                <tr>
                                                    <td><?= $nomor++ ?></td>
                                                    <td><?= htmlspecialchars($row['nama_material']) ?></td>
                                                    <td><?= htmlspecialchars($row['nama_satuan']) ?></td>
                                                    <td>
                                                        <strong>
                                                            <?php
                                                                // Jika stok NULL (belum ada), tampilkan 0. Jika ada, format angkanya.
                                                                echo number_format($row['jumlah_stok_tersedia'] ?? 0, 2, ',', '.');
                                                            ?>
                                                        </strong>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['keterangan_material']) ?></td>
                                                    <td>
                                                        <button class="btn btn-primary btn-sm btn-update" 
                                                                data-id_material="<?= $row['id_material'] ?>" 
                                                                data-nama_material="<?= htmlspecialchars($row['nama_material']) ?>" 
                                                                data-id_satuan="<?= $row['id_satuan'] ?>"
                                                                data-keterangan_material="<?= htmlspecialchars($row['keterangan_material']) ?>"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#updateMaterialModal">
                                                            Update
                                                        </button>
                                                        <button class="btn btn-danger btn-sm btn-delete" 
                                                                data-id_material="<?= $row['id_material'] ?>"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#confirmDeleteModal">
                                                            Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

          <div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                      <form method="POST" action="add_material.php">
                          <div class="modal-header">
                              <h5 class="modal-title" id="addMaterialModalLabel">Tambah Data Material</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                              <div class="mb-3">
                                  <label for="nama_material" class="form-label">Nama Material</label>
                                  <input type="text" class="form-control" id="nama_material" name="nama_material" required />
                              </div>
                              <div class="mb-3">
                                  <label for="id_satuan" class="form-label">Nama Satuan</label>
                                  <select class="form-select" id="id_satuan" name="id_satuan" required>
                                      <option value="" disabled selected>Pilih Nama Satuan</option>
                                      <?php foreach ($satuans as $satuan): ?>
                                          <option value="<?= htmlspecialchars($satuan['id_satuan']) ?>"><?= htmlspecialchars($satuan['nama_satuan']) ?></option>
                                      <?php endforeach; ?>
                                  </select>
                              </div>
                              <div class="mb-3">
                                  <label for="keterangan_material" class="form-label">Keterangan Material</label>
                                  <input type="text" class="form-control" id="keterangan_material" name="keterangan_material" />
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

      <div class="modal fade" id="updateMaterialModal" tabindex="-1" aria-labelledby="updateMaterialModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
              <div class="modal-content">
                  <form method="POST" action="update_material.php">
                      <input type="hidden" name="id_material" id="update_id_material" />
                      <div class="modal-header">
                          <h5 class="modal-title" id="updateMaterialModalLabel">Update Data Material</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                          <div class="mb-3">
                              <label for="update_nama_material" class="form-label">Nama Material</label>
                              <input type="text" class="form-control" id="update_nama_material" name="nama_material" required />
                          </div>
                          <div class="mb-3">
                              <label for="update_id_satuan" class="form-label">Nama Satuan</label>
                              <select class="form-select" id="update_id_satuan" name="id_satuan" required>
                                  <option value="" disabled>Pilih Nama Satuan</option>
                                  <?php foreach ($satuans as $satuan): ?>
                                      <option value="<?= htmlspecialchars($satuan['id_satuan']) ?>"><?= htmlspecialchars($satuan['nama_satuan']) ?></option>
                                  <?php endforeach; ?>
                              </select>
                          </div>
                          <div class="mb-3">
                              <label for="update_keterangan_material" class="form-label">Keterangan Material</label>
                              <input type="text" class="form-control" id="update_keterangan_material" name="keterangan_material" />
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
                <p>Are you sure you want to delete this material?</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteLink" class="btn btn-danger">Delete</a>
              </div>
            </div>
          </div>
        </div>

    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    $('#tabelMaterial').DataTable({
        "order": [] // Menghormati urutan dari server
    });

    // Event listener untuk Tombol UPDATE
    $('#tabelMaterial').on('click', '.btn-update', function() {
        const button = $(this);
        var updateModal = new bootstrap.Modal(document.getElementById('updateMaterialModal'));
        
        $('#update_id_material').val(button.data('id_material'));
        $('#update_nama_material').val(button.data('nama_material'));
        $('#update_id_satuan').val(button.data('id_satuan'));
        $('#update_keterangan_material').val(button.data('keterangan_material'));
        
        updateModal.show();
    });

    // Event listener untuk Tombol DELETE
    $('#tabelMaterial').on('click', '.btn-delete', function() {
        const materialId = $(this).data('id_material');
        const deleteLink = $('#confirmDeleteLink');
        var deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        
        deleteLink.attr('href', 'delete_material.php?id_material=' + materialId);
        
        deleteModal.show();
    });

    // --- KODE TAMBAHAN UNTUK NOTIFIKASI OTOMATIS HILANG ---
    const alertBox = $('.alert');
    // Cek apakah notifikasi ada di halaman
    if (alertBox.length) {
        // Jika ada, tunggu 5 detik, lalu hilangkan dengan efek fade out
        setTimeout(function() {
            alertBox.fadeOut('slow');
        }, 5000); // 5000 milidetik = 5 detik
    }
    // --- AKHIR DARI KODE TAMBAHAN ---
});
</script>
    </body>
  </html>