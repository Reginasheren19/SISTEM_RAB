<?php
// Selalu mulai dengan session_start()
session_start();
include("../config/koneksi_mysql.php");

// Langkah 2: Mengambil data pembelian yang statusnya 'Dipesan'
$sql = "SELECT 
            p.id_pembelian,
            p.tanggal_pembelian,
            p.keterangan_pembelian
        FROM 
            pencatatan_pembelian p
        WHERE 
            p.status_pembelian = 'Dipesan'
        ORDER BY 
            p.tanggal_pembelian ASC, p.id_pembelian ASC";

$result = mysqli_query($koneksi, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Penerimaan Material</title>
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
                  <i class="fas fa-pen-square"></i>
                  <p>Rancang RAB Upah</p>
                </a>
              </li>
                            <li class="nav-item">
                <a href="pengajuan_upah.php">
                  <i class="fas fa-pen-square"></i>
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
                  <i class="fas fa-pen-square"></i>
                  <p>Rancang RAB Material</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="pencatatan_pembelian.php">
                  <i class="fas fa-pen-square"></i>
                  <p>Pencatatan Pembelian</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="penerimaan_material.php">
                  <i class="fas fa-pen-square"></i>
                  <p>Pencatatan Pembelian</p>
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
                <h4 class="text-section">Laporan</h4>
              </li>
              <li class="nav-item">
                <a href="#">
                  <i class="fas fa-file"></i>
                  <p>Laporan RAB Upah</p>
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
                                        <span class="op-7">Hi,</span>
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
                <h3 class="fw-bold mb-3">Penerimaan Material</h3>
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
                        <a href="#">Penerimaan Material</a>
                    </li>
                </ul>
            </div>
                        <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Daftar Tunggu Penerimaan Material</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tabelPenerimaan" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>ID Pembelian</th>
                                            <th>Tanggal Pesan</th>
                                            <th>Keterangan</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if (mysqli_num_rows($result) > 0):
                                            $nomor = 1; 
                                            while ($row = mysqli_fetch_assoc($result)): 
                                                $tahun_pembelian = date('Y', strtotime($row['tanggal_pembelian']));
                                                $formatted_id = 'PB' . $row['id_pembelian'] . $tahun_pembelian;
                                        ?>
                                            <tr>
                                                <td class="text-center"><?= $nomor++ ?></td>
                                                <td><?= htmlspecialchars($formatted_id) ?></td>
                                                <td><?= date("d F Y", strtotime($row['tanggal_pembelian'])) ?></td>
                                                <td><?= htmlspecialchars($row['keterangan_pembelian']) ?></td>
                                                <td class="text-center">
                                                    <button class="btn btn-primary btn-sm btn-terima" data-id="<?= $row['id_pembelian'] ?>">
                                                        <i class="fa fa-box-open"></i> Terima Material
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php 
                                            endwhile; 
                                        else: 
                                        ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted"><em>Tidak ada pesanan yang menunggu untuk diterima.</em></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> <div class="modal fade" id="konfirmasiModal" tabindex="-1" aria-labelledby="konfirmasiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form action="proses_penerimaan.php" method="POST" id="form-konfirmasi">
                <div class="modal-header">
                    <h5 class="modal-title" id="konfirmasiModalLabel">Konfirmasi Penerimaan Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan melakukan konfirmasi untuk Pembelian ID: <strong id="modal-pembelian-id-text"></strong></p>
                    <input type="hidden" name="id_pembelian" id="modal-pembelian-id">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Material</th>
                                    <th class="text-center">Jml Dipesan</th>
                                    <th class="text-center" style="width: 15%;">Kondisi Sesuai?</th>
                                    <th>Input Manual (Jika tidak sesuai)</th>
                                </tr>
                            </thead>
                            <tbody id="rincian-pembelian-body">
                                </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>

<script>
$(document).ready(function() {
    $('#tabelPenerimaan').DataTable({ "order": [] });

    $('#tabelPenerimaan').on('click', '.btn-terima', function() {
        const id_pembelian = $(this).data('id');
        
        $('#modal-pembelian-id').val(id_pembelian);
        $('#modal-pembelian-id-text').text('PB' + id_pembelian + '<?= date('Y'); ?>');

        var tbody = $('#rincian-pembelian-body');
        tbody.html('<tr><td colspan="4" class="text-center"><span class="spinner-border spinner-border-sm"></span> Memuat rincian...</td></tr>');
        
        $('#konfirmasiModal').modal('show');

        $.ajax({
            url: 'get_rincian_pembelian.php',
            type: 'GET',
            data: { id_pembelian: id_pembelian },
            dataType: 'json',
            success: function(response) {
                tbody.empty();
                if (response.length > 0) {
                    $.each(response, function(index, item) {
                        var row = `
                            <tr>
                                <td class="align-middle">
                                    ${item.nama_material}
                                    <input type="hidden" name="id_detail_pembelian[]" value="${item.id_detail_pembelian}">
                                </td>
                                <td class="text-center align-middle">${item.jumlah_dipesan} ${item.nama_satuan}</td>
                                <td class="text-center align-middle">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input sesuai-checkbox" type="checkbox" role="switch" checked data-bs-toggle="tooltip" title="Hapus centang untuk input manual jika barang bermasalah">
                                    </div>
                                </td>
                                <td>
                                    <div class="manual-input-area" style="display: none;">
                                        <div class="input-group input-group-sm mb-1">
                                            <span class="input-group-text">Baik</span>
                                            <input type="number" name="jumlah_diterima_baik[]" class="form-control" step="0.01" value="0">
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Rusak</span>
                                            <input type="number" name="jumlah_rusak[]" class="form-control" step="0.01" value="0">
                                        </div>
                                    </div>
                                    <div class="default-input-area">
                                        <input type="hidden" name="jumlah_diterima_baik[]" value="${item.jumlah_dipesan}" disabled>
                                        <input type="hidden" name="jumlah_rusak[]" value="0" disabled>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                } else {
                    tbody.html('<tr><td colspan="4" class="text-center">Gagal memuat rincian atau tidak ada item.</td></tr>');
                }
            },
            error: function() {
                tbody.html('<tr><td colspan="4" class="text-center">Terjadi kesalahan saat meminta data ke server.</td></tr>');
            }
        });
    });

    $('#konfirmasiModal').on('change', '.sesuai-checkbox', function() {
        var tr = $(this).closest('tr');
        var manualInputArea = tr.find('.manual-input-area');
        var defaultInputArea = tr.find('.default-input-area');

        if (this.checked) {
            manualInputArea.hide();
            manualInputArea.find('input').prop('disabled', true);
            defaultInputArea.find('input').prop('disabled', false);
        } else {
            manualInputArea.show();
            manualInputArea.find('input').prop('disabled', false);
            defaultInputArea.find('input').prop('disabled', true);
        }
    });
});
</script>
</body>
</html>