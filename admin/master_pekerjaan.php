<?php
include("../config/koneksi_mysql.php");

// Mengatur error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Mengambil data user dari database
$result = mysqli_query($koneksi, "SELECT * FROM master_pekerjaan");
$satuanResult = mysqli_query($koneksi, "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan ASC");
if (!$satuanResult) {
    die("Query Error (satuan): " . mysqli_error($koneksi));
}
$row = mysqli_fetch_assoc($result);
if (isset($row)) {
    $id_satuan_selected = $row['id_satuan'];
} else {
    $id_satuan_selected = ''; // Atau nilai default jika tidak ada
}
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Pekerjaan</title>
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
                            <img src="../assets/img/logo/LOGO PT.jpg" alt="Logo PT" class="navbar-brand" height="30" />
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                            <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                        </div>
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
                  <a href="#">Master Pekerjaan</a>
                </li>
              </ul>
            </div>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex align-items-center">
        <h4 class="card-title">Master Pekerjaan</h4>
        <button
          class="btn btn-primary btn-round ms-auto"
          data-bs-toggle="modal"
          data-bs-target="#addPekerjaanModal"
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
                <th>ID Pekerjaan</th>
                <th>Uraian Pekerjaan</th>
                <th>Nama Satuan</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $sql = "SELECT mp.id_pekerjaan, mp.uraian_pekerjaan, mp.id_satuan, ms.nama_satuan
                      FROM master_pekerjaan mp
                      JOIN master_satuan ms ON mp.id_satuan = ms.id_satuan";

              $result = mysqli_query($koneksi, $sql);

              if (!$result) {
                  die("Query Error: " . mysqli_error($koneksi));
              }

              while ($row = mysqli_fetch_assoc($result)) {
                  echo "<tr>
                      <td>" . htmlspecialchars($row['id_pekerjaan']) . "</td>
                      <td>" . htmlspecialchars($row['uraian_pekerjaan']) . "</td>
                      <td>" . htmlspecialchars($row['nama_satuan']) . "</td>
                      <td>
                         <button 
                          class='btn btn-primary btn-sm btn-update' 
                          data-id_pekerjaan='" . htmlspecialchars($row['id_pekerjaan']) . "' 
                          data-uraian_pekerjaan='" . htmlspecialchars($row['uraian_pekerjaan']) . "' 
                          data-id_satuan='" . htmlspecialchars($row['id_satuan']) . "'>Update</button>
                         <button class='btn btn-danger btn-sm delete-btn' data-id_pekerjaan='" . htmlspecialchars($row['id_pekerjaan']) . "'>Delete</button>                                        
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

<!-- Modal Tambah Data Pekerjaan -->
<div class="modal fade" id="addPekerjaanModal" tabindex="-1" aria-labelledby="addPekerjaanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="add_pekerjaan.php">
        <input type="hidden" name="action" value="add" />
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

              <?php while ($satuan = mysqli_fetch_assoc($satuanResult)): ?>
                <option value="<?= htmlspecialchars($satuan['id_satuan']) ?>">
                  <?= htmlspecialchars($satuan['nama_satuan']) ?>
                </option>
              <?php endwhile; ?>

            </select>
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

<!-- Modal Update Data Pekerjaan -->
<div class="modal fade" id="updatePekerjaanModal" tabindex="-1" aria-labelledby="updatePekerjaanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="update_pekerjaan.php">
        <input type="hidden" name="id_pekerjaan" id="update_id_pekerjaan" />
        <div class="modal-header">
          <h5 class="modal-title" id="updateMandorModalLabel">Update Data Pekerjaan</h5>
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
              <option value="" disabled selected>Pilih Nama Satuan</option>
              <?php
              $id_satuan_selected = $row['id_satuan'];
              $satuanResult = mysqli_query($koneksi, "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan ASC");
              while ($satuan = mysqli_fetch_assoc($satuanResult)) {
                $selected = ($satuan['id_satuan'] == $id_satuan_selected) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($satuan['id_satuan']) . '">' . htmlspecialchars($satuan['nama_satuan']) . '</option>';
              }
              ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
  $(document).ready(function() {
    $('#basic-datatables').DataTable();
  });
</script>

  <script>
    // Konfirmasi penghapusan data pekerjaan
    document.querySelectorAll('.delete-btn').forEach(button => {
      button.addEventListener('click', function() {
        const pekerjaanId = this.dataset.id_pekerjaan;
        const deleteLink = document.getElementById('confirmDeleteLink');
        deleteLink.href = 'delete_pekerjaan.php?pekerjaan=' + pekerjaanId;
        const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        deleteModal.show();
      });
    });
  </script>
<script>
  // Menangani klik tombol update pada Master Pekerjaan
document.querySelectorAll('.btn-update').forEach(button => {
  button.addEventListener('click', function() {
    // Ambil data dari atribut tombol
    const idPekerjaan = this.dataset.id_pekerjaan;
    const uraianPekerjaan = this.dataset.uraian_pekerjaan;
    const idSatuan = this.dataset.id_satuan;  // ini id satuan yang benar

    // Set nilai input modal
    document.getElementById('update_id_pekerjaan').value = idPekerjaan;
    document.getElementById('update_uraian_pekerjaan').value = uraianPekerjaan;
    document.getElementById('update_id_satuan').value = idSatuan;  // ini akan otomatis pilih dropdown sesuai id_satuan

    // Tampilkan modal update
    const updateModal = new bootstrap.Modal(document.getElementById('updatePekerjaanModal'));
    updateModal.show();
  });
});

</script>

</body>
</html>
