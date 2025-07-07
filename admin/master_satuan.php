<?php
include("../config/koneksi_mysql.php");

// Mengatur error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Mengambil data satuan dari database
$result = mysqli_query($koneksi, "SELECT * FROM master_satuan");
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Satuan</title>
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
        <?php //include 'sidebar_m.php'; ?>
        <?php include 'sidebar.php'; ?>

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
                  <a href="#">Master Satuan</a>
                </li>
              </ul>
            </div>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex align-items-center">
        <h4 class="card-title">Master Satuan</h4>
        <button
          class="btn btn-primary btn-round ms-auto"
          data-bs-toggle="modal"
          data-bs-target="#addSatuanModal"
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
          <table
            id="basic-datatables"
            class="display table table-striped table-hover"
          >
            <thead>
              <tr>
                <th>ID Satuan</th>
                <th>Nama Satuan</th>
                <th>Keterangan Satuan</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $result = mysqli_query($koneksi, "SELECT * FROM master_satuan");
              while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                    <td>" . htmlspecialchars($row['id_satuan']) . "</td>
                    <td>" . htmlspecialchars($row['nama_satuan']) . "</td>
                    <td>" . htmlspecialchars($row['keterangan_satuan']) . "</td>
                    <td>
                      <button class='btn btn-primary btn-sm btn-update' data-id_satuan='" . htmlspecialchars($row['id_satuan']) . "'>Update</button>
                      <button class='btn btn-danger btn-sm delete-btn' data-id_satuan='" . htmlspecialchars($row['id_satuan']) . "'>Delete</button>                    
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

<!-- Modal Tambah Data Satuan -->
<div class="modal fade" id="addSatuanModal" tabindex="-1" aria-labelledby="addSatuanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="add_satuan.php">
        <input type="hidden" name="action" value="add" />
        <div class="modal-header">
          <h5 class="modal-title" id="addSatuanModalLabel">Tambah Data Satuan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <div class="mb-3">
            <label for="nama_satuan" class="form-label">Nama Satuan</label>
            <input type="text" class="form-control" id="nama_satuan" name="nama_satuan" placeholder="Masukkan nama satuan" required />
          </div>
          <div class="mb-3">
            <label for="keterangan_satuan" class="form-label">Keterangan Satuan</label>
            <input type="text" class="form-control" id="keterangan_satuan" name="keterangan_satuan" placeholder="Masukkan keterangan satuan" required />
          </div>


        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
</div>

<!-- Modal Update Data Satuan -->
<div class="modal fade" id="updateSatuanModal" tabindex="-1" aria-labelledby="updateSatuanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="update_satuan.php">
        <input type="hidden" name="id_satuan" id="update_id_satuan" />
        <div class="modal-header">
          <h5 class="modal-title" id="updateSatuanModalLabel">Update Data Satuan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <div class="mb-3">
            <label for="update_nama_satuan" class="form-label">Nama Satuan</label>
            <input type="text" class="form-control" id="update_nama_satuan" name="nama_satuan" placeholder="Ubah nama satuan" required />
          </div>
          <div class="mb-3">
            <label for="update_keterangan_satuan" class="form-label">Keterangan Satuan</label>
            <input type="text" class="form-control" id="update_keterangan_satuan" name="keterangan_satuan" placeholder="Ubah keterangan satuan" required />
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
          <p>Are you sure you want to delete this satuan?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <a href="#" id="confirmDeleteLink" class="btn btn-danger">Delete</a>
        </div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
  $(document).ready(function() {
    $('#basic-datatables').DataTable();
  });
</script>

  <script>
    // Konfirmasi penghapusan data satuan
    document.querySelectorAll('.delete-btn').forEach(button => {
      button.addEventListener('click', function() {
        const satuanId = this.dataset.id_satuan;
        const deleteLink = document.getElementById('confirmDeleteLink');
        deleteLink.href = 'delete_satuan.php?satuan=' + satuanId;
        const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        deleteModal.show();
      });
    });
  </script>
<script>
  // Menangani klik tombol update pada Master Satuan
document.querySelectorAll('.btn-update').forEach(button => {
  button.addEventListener('click', function() {
    const row = this.closest('tr');

    // Ambil nilai kolom yang sesuai
    const idSatuan = row.cells[0].innerText.trim();
    const namaSatuan = row.cells[1].innerText.trim();
    const keteranganSatuan = row.cells[2].innerText.trim();


    // Isi modal update dengan data tersebut
    document.getElementById('update_id_satuan').value = idSatuan;
    document.getElementById('update_nama_satuan').value = namaSatuan;
    document.getElementById('update_keterangan_satuan').value = keteranganSatuan;

    // Tampilkan modal update
    const updateModal = new bootstrap.Modal(document.getElementById('updateSatuanModal'));
    updateModal.show();
  });
});

</script>

</body>
</html>
