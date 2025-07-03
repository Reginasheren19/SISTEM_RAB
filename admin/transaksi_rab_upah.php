<?php

session_start();

include("../config/koneksi_mysql.php");

// [DIUBAH] Mengambil role pengguna yang sedang login
$user_role = strtolower($_SESSION['role'] ?? 'guest');
$can_add_edit = in_array($user_role, ['divisi teknik']); 
// Mengatur error reporting untuk membantu debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Query ini mengambil SEMUA data untuk ditampilkan di tabel utama.
$sql = "SELECT 
          tr.id_rab_upah,
          tr.id_proyek,
          mpe.nama_perumahan,
          mpr.kavling,
          mpr.type_proyek,
          mpe.lokasi,
          mm.nama_mandor,
          u.nama_lengkap AS pj_proyek, -- Mengambil nama lengkap user sebagai 'pj_proyek'
          tr.tanggal_mulai,
          tr.tanggal_selesai,
          tr.total_rab_upah
        FROM rab_upah tr
        JOIN master_proyek mpr ON tr.id_proyek = mpr.id_proyek
        LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
        LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
        ORDER BY tr.id_rab_upah DESC"; // Mengurutkan berdasarkan data terbaru

$result = mysqli_query($koneksi, $sql);
if (!$result) {
    die("Query Error (rab_upah): " . mysqli_error($koneksi));
}

// Query ini untuk mengisi dropdown 'Nama Perumahan' di dalam modal tambah data.
$perumahanResult = mysqli_query($koneksi, "SELECT id_perumahan, nama_perumahan FROM master_perumahan ORDER BY nama_perumahan ASC");
if (!$perumahanResult) {
    die("Query Error (perumahan): " . mysqli_error($koneksi));
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
              <h3 class="fw-bold mb-3">Rancang RAB</h3>
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
                  <a href="#">Rancang RAB</a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="#">RAB Upah</a>
                </li>
              </ul>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header d-flex align-items-center">
                    <h4 class="card-title">RAB Upah</h4>
                                                        <?php if ($can_add_edit): ?>
                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addRABUpahModal">
                      <i class="fa fa-plus"></i> Tambah Data RAB
                                                          <?php endif; ?>
                    </button>
                  </div>

                  <div class="card-body">
                    <?php if (isset($_GET['msg'])): ?>
                      <div id="alert-message" class="alert alert-success fade show m-3" role="alert">
                          <?= htmlspecialchars($_GET['msg']) ?>
                      </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                      <table id="basic-datatables" class="display table table-striped table-hover">
                        <thead>
                          <tr>
                            <th>ID RAB</th>
                            <th>Perumahan</th>
                            <th>Mandor</th>
                            <th>PJ Proyek</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Total</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while ($row = mysqli_fetch_assoc($result)): ?>
                          <?php
                            $tahun = date('Y', strtotime($row['tanggal_mulai']));
                            $bulan = date('m', strtotime($row['tanggal_mulai']));
$formatted_id = 'RABP' . $row['id_rab_upah'];                            
$totalFormatted = 'Rp ' . number_format($row['total_rab_upah'], 0, ',', '.');
                          ?>
                          <tr>
                            <td><?= htmlspecialchars($formatted_id) ?></td>
                            <td><?= htmlspecialchars($row['nama_perumahan']) . ' - ' . htmlspecialchars($row['kavling']) ?></td> <!-- Combined Column -->   
                            <td><?= htmlspecialchars($row['nama_mandor']) ?></td>
                            <td><?= htmlspecialchars($row['pj_proyek']) ?></td>
                            <td><?= date('d-m-Y', strtotime($row['tanggal_mulai'])) ?></td>
                            <td><?= date('d-m-Y', strtotime($row['tanggal_selesai'])) ?></td>
                            <td><?= htmlspecialchars($totalFormatted) ?></td>
                            <td>
                              <a href="detail_rab_upah.php?id_rab_upah=<?= urlencode($row['id_rab_upah']) ?>" class="btn btn-info btn-sm">Detail</a>
                                                        <?php if ($can_add_edit): ?>
                                                        <button class="btn btn-danger btn-sm delete-btn" data-id_rab_upah="<?= htmlspecialchars($row['id_rab_upah']) ?>">Delete</button>
                                                        <?php endif; ?>                            </td>
                          </tr>
                          <?php endwhile; ?>
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

    <!-- Modal Tambah Data RAB Upah -->
    <div class="modal fade" id="addRABUpahModal" tabindex="-1" aria-labelledby="addRABUpahModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="POST" action="add_rab_upah.php">
            <div class="modal-header">
              <h5 class="modal-title" id="addRABUpahModalLabel">Tambah Data RAB Upah</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="id_perumahan" class="form-label">Nama Perumahan</label>
                <select class="form-select" id="id_perumahan" name="id_perumahan" required>
                  <option value="" disabled selected>Pilih Perumahan</option>
                  <?php 
                    mysqli_data_seek($perumahanResult, 0); // Reset pointer
                    while ($perumahan = mysqli_fetch_assoc($perumahanResult)): 
                  ?>
                    <option value="<?= htmlspecialchars($perumahan['id_perumahan']) ?>"><?= htmlspecialchars($perumahan['nama_perumahan']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="mb-3">
                <label for="id_proyek" class="form-label">Kavling</label>
                <select class="form-select" id="id_proyek" name="id_proyek" required>
                  <option value="" disabled selected>Pilih Kavling</option>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3"><label>Tipe Proyek</label><input type="text" class="form-control" id="type_proyek" readonly /></div>
                <div class="col-md-6 mb-3"><label>Lokasi</label><input type="text" class="form-control" id="lokasi" readonly /></div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3"><label>Mandor</label><input type="text" class="form-control" id="nama_mandor" readonly /></div>
                <div class="col-md-6 mb-3"><label>PJ Proyek</label><input type="text" class="form-control" id="pj_proyek" readonly /></div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3"><label>Tanggal Mulai</label><input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required /></div>
                <div class="col-md-6 mb-3"><label>Tanggal Selesai</label><input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required /></div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary" id="btn-submit-rab" disabled>Lanjut & Buat RAB</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body"><p>Apakah Anda yakin ingin menghapus data ini?</p></div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><a href="#" id="confirmDeleteLink" class="btn btn-danger">Hapus</a></div>
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
        // 1. INISIALISASI DATATABLES
        $('#basic-datatables').DataTable();

        // 2. LOGIKA NOTIFIKASI
        if ($('#alert-message').length) {
            setTimeout(function() {
                let bsAlert = new bootstrap.Alert($('#alert-message')[0]);
                bsAlert.close();
                if (window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('msg');
                    window.history.replaceState({ path: url.href }, '', url.href);
                }
            }, 3000);
        }

        // 3. LOGIKA UNTUK MODAL TAMBAH DATA
        const addModal = document.getElementById('addRABUpahModal');
        const idProyekDropdown = $('#id_proyek');
        const submitBtn = $('#btn-submit-rab');

        // Fungsi untuk mereset field di modal
        function resetProyekFields() {
            idProyekDropdown.html('<option value="" disabled selected>Pilih Perumahan Dahulu</option>');
            $('#type_proyek, #lokasi, #nama_mandor, #pj_proyek').val('');
            submitBtn.prop('disabled', true);
        }

        // Ketika dropdown Perumahan berubah
        $('#id_perumahan').on('change', function() {
            const idPerumahan = $(this).val();
            resetProyekFields();
            if (!idPerumahan) return;

            idProyekDropdown.html('<option value="">Memuat...</option>');
            $.ajax({
                url: 'get_kavling.php',
                method: 'POST',
                data: { id_perumahan: idPerumahan },
                dataType: 'json',
                success: function(response) {
                    let options = '<option value="" disabled selected>Pilih Kavling</option>';
                    if (response.length > 0) {
                        response.forEach(function(proyek) {
                            options += `<option value="${proyek.id_proyek}" 
                                        data-type_proyek="${proyek.type_proyek}"
                                        data-lokasi="${proyek.lokasi}"
                                        data-mandor="${proyek.nama_mandor}"
                                        data-pj_proyek="${proyek.pj_proyek}">
                                        ${proyek.kavling}
                                      </option>`;
                        });
                    } else {
                        options = '<option value="" disabled>Tidak ada kavling</option>';
                    }
                    idProyekDropdown.html(options);
                },
                error: function() { alert('Gagal mengambil data kavling.'); resetProyekFields(); }
            });
        });

        // Ketika dropdown Kavling berubah
        idProyekDropdown.on('change', function() {
            const selected = $(this).find(':selected');
            $('#type_proyek').val(selected.data('type_proyek') || '');
            $('#lokasi').val(selected.data('lokasi') || '');
            $('#nama_mandor').val(selected.data('mandor') || '');
            $('#pj_proyek').val(selected.data('pj_proyek') || '');
            submitBtn.prop('disabled', !$(this).val());
        });
        
        // Reset modal saat ditutup
        $(addModal).on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            resetProyekFields();
        });

            // 4. [DIUBAH] LOGIKA TOMBOL DELETE MENGGUNAKAN SWEETALERT
            $('#basic-datatables tbody').on('click', '.delete-btn', function(e) {
                e.preventDefault();
                const idRabUpah = $(this).data('id_rab_upah');
                
                swal({
                    title: "Apakah Anda Yakin?",
                    text: "Data RAB yang dihapus tidak dapat dikembalikan.",
                    icon: "warning",
                    buttons: {
                        cancel: {
                            text: "Batal",
                            value: null,
                            visible: true,
                            className: "btn btn-secondary",
                            closeModal: true,
                        },
                        confirm: {
                            text: "Ya, Hapus",
                            value: true,
                            visible: true,
                            className: "btn btn-danger",
                            closeModal: true
                        }
                    }
                }).then((willDelete) => {
                    if (willDelete) {
                        // Jika pengguna mengklik "Ya, Hapus", arahkan ke skrip delete
                        window.location.href = 'delete_rab_upah.php?id_rab_upah=' + idRabUpah;
                    }
                });
            });
      });
    </script>
</body>
</html>
