<?php
// FILE: master_proyek.php (Dengan logika dropdown yang diperbaiki)
include("../config/koneksi_mysql.php");

// Mengatur error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch data for Nama Perumahan
$perumahanResult = mysqli_query($koneksi, "SELECT id_perumahan, nama_perumahan, lokasi FROM master_perumahan ORDER BY nama_perumahan ASC");
$perumahan_options = [];
if($perumahanResult) {
    while ($row = mysqli_fetch_assoc($perumahanResult)) {
        $perumahan_options[] = $row;
    }
}

// Fetch data for Nama Mandor
$mandorResult = mysqli_query($koneksi, "SELECT id_mandor, nama_mandor FROM master_mandor ORDER BY nama_mandor ASC");
$mandor_options = [];
if($mandorResult) {
    while ($row = mysqli_fetch_assoc($mandorResult)) {
        $mandor_options[] = $row;
    }
}


// Ambil data PJ Proyek
$pj_proyek_options = [];
$pjProyekResult = mysqli_query($koneksi, "SELECT id_user, nama_lengkap FROM master_user WHERE role = 'PJ Proyek' ORDER BY nama_lengkap ASC");
if($pjProyekResult){
    while ($row = mysqli_fetch_assoc($pjProyekResult)) {
        $pj_proyek_options[] = $row;
    }
}

// --- Query utama untuk menampilkan tabel ---
$sql = "SELECT 
            mpr.id_proyek, mpr.kavling, mpr.type_proyek,
            mpr.id_perumahan, mpe.nama_perumahan, mpe.lokasi,
            mpr.id_mandor, mm.nama_mandor,
            mpr.id_user_pj, u.nama_lengkap AS nama_pj_proyek
        FROM master_proyek mpr
        LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
        LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user";
$result = mysqli_query($koneksi, $sql);
if (!$result) {
    die("Query gagal dijalankan: " . mysqli_error($koneksi));
}
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Proyek</title>
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
                  <a href="#">Master Proyek</a>
                </li>
              </ul>
            </div>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex align-items-center">
        <h4 class="card-title">Master Proyek</h4>
        <button
          class="btn btn-primary btn-round ms-auto"
          data-bs-toggle="modal"
          data-bs-target="#addProyekModal"
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
        <table id="basic-datatables" class="display table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID Proyek</th>
                    <th>Perumahan</th>
                    <th>Kavling</th>
                    <th>Type Proyek</th>
                    <th>Mandor</th>
                    <th>PJ Proyek</th> <!-- Menambahkan kolom PJ Proyek -->
                    <th>Lokasi</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id_proyek']) ?></td>
                        <td><?= htmlspecialchars($row['nama_perumahan']) ?></td>
                        <td><?= htmlspecialchars($row['kavling']) ?></td>
                        <td><?= htmlspecialchars($row['type_proyek']) ?></td>
                        <td><?= htmlspecialchars($row['nama_mandor']) ?></td>
                        <td><?= htmlspecialchars($row['nama_pj_proyek'] ?? 'N/A') ?></td> <!-- Menampilkan PJ Proyek -->
                        <td><?= htmlspecialchars($row['lokasi']) ?></td>
                        <td>
                            <!-- Update Button -->
                            <button class="btn btn-warning btn-sm btn-update" 
                                    data-id_proyek='<?= $row['id_proyek'] ?>' 
                                    data-id_perumahan='<?= $row['id_perumahan'] ?>' 
                                    data-kavling='<?= $row['kavling'] ?>' 
                                    data-type_proyek='<?= $row['type_proyek'] ?>' 
                                    data-id_mandor='<?= $row['id_mandor'] ?>'
                                    data-id_user_pj='<?= $row['id_user_pj'] ?>'
                                    data-bs-toggle="modal" 
                                    data-bs-target="#updateProyekModal">
                                <i class="fa fa-edit"></i> Update
                            </button>

                            <!-- Delete Button -->
                            <button class="btn btn-danger btn-sm btn-delete" 
                                    data-id_proyek='<?= $row['id_proyek'] ?>'
                                    data-bs-toggle="modal" 
                                    data-bs-target="#confirmDeleteModal">
                                <i class="fa fa-trash"></i> Delete
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

<!-- Modal Tambah Data Pekerjaan -->
<div class="modal fade" id="addProyekModal" tabindex="-1" aria-labelledby="addProyekModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="add_proyek.php">
        <input type="hidden" name="action" value="add" />
        <div class="modal-header">
          <h5 class="modal-title" id="addProyekModalLabel">Tambah Data Proyek</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

<!-- Dropdown for Nama Perumahan -->
<div class="mb-3">
    <label for="id_perumahan" class="form-label">Nama Perumahan</label>
    <select class="form-select" id="id_perumahan" name="id_perumahan" required>
        <option value="" disabled selected>Pilih Nama Perumahan</option>
        <?php foreach ($perumahan_options as $perumahan): ?>
            <option value="<?= htmlspecialchars($perumahan['id_perumahan']) ?>" data-lokasi="<?= htmlspecialchars($perumahan['lokasi']) ?>">
                <?= htmlspecialchars($perumahan['nama_perumahan']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

          <div class="mb-3">
            <label for="kavling" class="form-label">Kavling</label>
            <input type="text" class="form-control" id="kavling" name="kavling" placeholder="Masukkan kavling" required />
          </div>

          <div class="mb-3">
            <label for="type_proyek" class="form-label">Type Proyek</label>
            <input type="text" class="form-control" id="type_proyek" name="type_proyek" placeholder="Masukkan type proyek" required />
          </div>

<!-- Dropdown for Nama Mandor -->
<div class="mb-3">
    <label for="id_mandor" class="form-label">Nama Mandor</label>
    <select class="form-select" id="id_mandor" name="id_mandor" required>
        <option value="" disabled selected>Pilih Nama Mandor</option>
        <?php foreach ($mandor_options as $mandor): ?>
            <option value="<?= htmlspecialchars($mandor['id_mandor']) ?>">
                <?= htmlspecialchars($mandor['nama_mandor']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


        <!-- [DITAMBAHKAN] Dropdown untuk memilih PJ Proyek -->
        <div class="mb-3">
            <label class="form-label">PJ Proyek</label>
            <select class="form-select" name="id_user_pj" required>
                <option value="">Pilih PJ Proyek</option>
                <?php foreach ($pj_proyek_options as $opt): ?>
                    <option value="<?= $opt['id_user'] ?>"><?= htmlspecialchars($opt['nama_lengkap']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

          <!-- Lokasi (read-only) -->
          <div class="mb-3">
            <label for="lokasi" class="form-label">Lokasi</label>
            <input type="text" class="form-control" id="lokasi" name="lokasi" readonly placeholder="Lokasi akan muncul otomatis" />
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

<!-- Modal Update Data Proyek -->
<div class="modal fade" id="updateProyekModal" tabindex="-1" aria-labelledby="updateProyekModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="update_proyek.php">
                <input type="hidden" name="id_proyek" id="update_id_proyek" />
                <div class="modal-header">
                    <h5 class="modal-title" id="updateProyekModalLabel">Update Data Proyek</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                     <div class="mb-3">
                            <label class="form-label">Nama Perumahan</label>
                            <select class="form-select" name="id_perumahan" id="update_id_perumahan" required>
                                <?php foreach ($perumahan_options as $opt): ?><option value="<?= $opt['id_perumahan'] ?>" data-lokasi="<?= htmlspecialchars($opt['lokasi']) ?>"><?= htmlspecialchars($opt['nama_perumahan']) ?></option><?php endforeach; ?>
                            </select>
                        </div>

          <div class="mb-3">
            <label for="update_kavling" class="form-label">Kavling</label>
            <input type="text" class="form-control" id="update_kavling" name="kavling" value="<?= htmlspecialchars($row['kavling']) ?>" placeholder="Ubah kavling" required />
          </div>

          <div class="mb-3">
            <label for="update_type_proyek" class="form-label">Type Proyek</label>
            <input type="text" class="form-control" id="update_type_proyek" name="type_proyek" value="<?= htmlspecialchars($row['type_proyek']) ?>" placeholder="Ubah type proyek" required />
          </div>

          <div class="mb-3">
              <label class="form-label">Mandor</label>
              <select class="form-select" name="id_mandor" id="update_id_mandor" required>
                  <?php foreach ($mandor_options as $opt): ?><option value="<?= $opt['id_mandor'] ?>"><?= htmlspecialchars($opt['nama_mandor']) ?></option><?php endforeach; ?>
              </select>
          </div>
          <div class="mb-3">
              <label class="form-label">PJ Proyek</label>
              <select class="form-select" name="id_user_pj" id="update_id_user_pj" required>
                  <?php foreach ($pj_proyek_options as $opt): ?><option value="<?= $opt['id_user'] ?>"><?= htmlspecialchars($opt['nama_lengkap']) ?></option><?php endforeach; ?>
              </select>
          </div>

          <div class="mb-3">
            <label for="update_lokasi" class="form-label">Lokasi</label>
            <input type="text" class="form-control" id="update_lokasi" name="lokasi" readonly value="<?= htmlspecialchars($row['lokasi'] ?? '') ?>" />
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
          <a href="master_proyek.php" id="confirmDeleteLink" class="btn btn-danger">Delete</a>
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
    // Handle dropdown change for "id_perumahan" to update location
    $('#id_perumahan').on('change', function() {
        const lokasi = $(this).find(':selected').data('lokasi') || ''; // Get location from selected option
        $('#lokasi').val(lokasi); // Set the location input field value
    });

    // Handle update button click event to populate the modal
    document.querySelectorAll('.btn-update').forEach(button => {
        button.addEventListener('click', function() {
            // Get data attributes and set modal inputs
            const idProyek = this.dataset.id_proyek;
            const idPerumahan = this.dataset.id_perumahan;
            const kavling = this.dataset.kavling;
            const typeProyek = this.dataset.type_proyek;
            const idMandor = this.dataset.id_mandor;
            const idUserPj = this.dataset.id_user_pj;

            // Fill the form fields inside the modal
            document.getElementById('update_id_proyek').value = idProyek;
            document.getElementById('update_id_perumahan').value = idPerumahan;
            document.getElementById('update_kavling').value = kavling;
            document.getElementById('update_type_proyek').value = typeProyek;
            document.getElementById('update_id_mandor').value = idMandor;

            // Update the location field based on selected "id_perumahan"
            const perumahanSelect = document.getElementById('update_id_perumahan');
            const selectedOption = perumahanSelect.querySelector(`option[value="${idPerumahan}"]`);
            const lokasi = selectedOption ? selectedOption.getAttribute('data-lokasi') : '';
            document.getElementById('update_lokasi').value = lokasi;

            // Show the update modal
            const updateModal = new bootstrap.Modal(document.getElementById('updateProyekModal'));
            updateModal.show();
        });
    });
});

</script>

    <script>
    $(document).ready(function() {
        $('#basic-datatables').DataTable();
        
        const updateModal = new bootstrap.Modal(document.getElementById('updateProyekModal'));
        
        function updateLokasi(selectElement, targetInput) {
            const selectedOption = $(selectElement).find('option:selected');
            const lokasi = selectedOption.data('lokasi') || '';
            $(targetInput).val(lokasi);
        }

        $('#add_id_perumahan').on('change', function() { updateLokasi(this, '#add_lokasi'); });
        $('#update_id_perumahan').on('change', function() { updateLokasi(this, '#update_lokasi'); });

        // Event delegation untuk tombol di dalam tabel
        $('#basic-datatables').on('click', '.btn-update', function() {
            // Ambil data dari atribut data-* tombol yang diklik
            $('#update_id_proyek').val($(this).data('id_proyek'));
            $('#update_id_perumahan').val($(this).data('id_perumahan'));
            $('#update_kavling').val($(this).data('kavling'));
            $('#update_type_proyek').val($(this).data('type_proyek'));
            $('#update_id_mandor').val($(this).data('id_mandor'));

            // [PERBAIKAN KUNCI] Cek jika data PJ Proyek ada
            const pjId = $(this).data('id_user_pj');
            if (pjId && pjId !== 0) {
                $('#update_id_user_pj').val(pjId);
            } else {
                // Jika tidak ada, set ke nilai kosong agar placeholder "-- Pilih PJ Proyek --" yang tampil
                $('#update_id_user_pj').val('');
            }
            
            updateLokasi('#update_id_perumahan', '#update_lokasi');
            updateModal.show();
        });

        // ... (logika delete bisa ditambahkan di sini) ...
    });
</script>

<script>
$(document).ready(function() {
    // Handle delete button click event to confirm deletion
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const proyekId = this.dataset.id_proyek;
            const deleteLink = document.getElementById('confirmDeleteLink');
            deleteLink.href = 'delete_proyek.php?proyek=' + proyekId; // Update the link with the correct project ID
            const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            deleteModal.show(); // Show the delete confirmation modal
        });
    });
});

</script>


</body>
</html>
