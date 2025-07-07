<?php
session_start();
include("../config/koneksi_mysql.php");

// Query utama sekarang mengambil semua data yang dibutuhkan untuk status
$sql = "
    SELECT 
        p.id_pembelian, p.tanggal_pembelian, p.keterangan_pembelian, p.bukti_pembayaran, p.total_biaya,
        COALESCE(pesanan.total_dipesan, 0) AS total_dipesan,
        COALESCE(diproses.total_diproses, 0) AS total_diproses,
        COALESCE(info_rusak.total_rusak, 0) AS total_rusak,
        COALESCE(pengganti.total_pengganti, 0) AS total_sudah_diretur
    FROM pencatatan_pembelian p
    LEFT JOIN (
        SELECT id_pembelian, SUM(quantity) as total_dipesan
        FROM detail_pencatatan_pembelian GROUP BY id_pembelian
    ) AS pesanan ON p.id_pembelian = pesanan.id_pembelian
    LEFT JOIN (
        SELECT id_pembelian, SUM(jumlah_diterima + jumlah_rusak) as total_diproses
        FROM log_penerimaan_material GROUP BY id_pembelian
    ) AS diproses ON p.id_pembelian = diproses.id_pembelian
    LEFT JOIN (
        SELECT id_pembelian, SUM(jumlah_rusak) as total_rusak
        FROM log_penerimaan_material GROUP BY id_pembelian
    ) AS info_rusak ON p.id_pembelian = info_rusak.id_pembelian
    LEFT JOIN (
        SELECT id_pembelian, SUM(quantity) as total_pengganti
        FROM detail_pencatatan_pembelian WHERE harga_satuan_pp = 0 GROUP BY id_pembelian
    ) AS pengganti ON p.id_pembelian = pengganti.id_pembelian
    ORDER BY p.id_pembelian DESC
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
    <title>Pencatatan Pembelian</title>
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
                <h3 class="fw-bold mb-3">Pencatatan Pembelian</h3>
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
                        <a href="#">Pencatatan Pembelian</a>
                    </li>
                </ul>
            </div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h4 class="card-title">Daftar Transaksi Pembelian</h4>
                <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addPembelianModal">
                    <i class="fa fa-plus"></i> Tambah Pembelian
                </button>
            </div>
            <div class="card-body">
                <?php /* ... Blok Notifikasi ... */ ?>
                <div class="table-responsive">
                    <table id="tabelPembelian" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>ID Pembelian</th>
                                <th>Tanggal Pembelian</th>
                                <th>Keterangan</th>
                                <th>Total Biaya</th>
                                <th>Bukti Pembelian</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (isset($result) && $result && mysqli_num_rows($result) > 0): 
                                $nomor = 1; 
                                while ($row = mysqli_fetch_assoc($result)): 
                                    $tahun_pembelian = date('Y', strtotime($row['tanggal_pembelian']));
                                    $formatted_id = 'PB' . $row['id_pembelian'] . $tahun_pembelian;
                                    
                                    // --- [DIUBAH] --- Logika Status Baru yang Lengkap
                                        $total_dipesan_all = $row['total_dipesan'];
                                        $total_diproses = $row['total_diproses'];
                                        $ada_retur_terbuka = $row['total_rusak'] > $row['total_sudah_diretur'];

                                        if ($total_dipesan_all <= 0) {
                                            $status_text = 'Kosong';
                                            $badge_class = 'bg-dark';
                                        } elseif ($total_diproses >= $total_dipesan_all && !$ada_retur_terbuka) {
                                            $status_text = 'Selesai';
                                            $badge_class = 'bg-success';
                                        } elseif ($ada_retur_terbuka) {
                                            $status_text = 'Perlu Tindakan Retur';
                                            $badge_class = 'bg-warning text-dark';
                                        } else {
                                            $status_text = 'Menunggu Pengganti';
                                            $badge_class = 'bg-primary';
                                        }
                            ?>
                                    <tr>
                                        <td><?= $nomor++ ?></td>
                                        <td><?= htmlspecialchars($formatted_id) ?></td>
                                        <td><?= date("d F Y", strtotime($row['tanggal_pembelian'])) ?></td>
                                        <td><?= htmlspecialchars($row['keterangan_pembelian']) ?></td>
                                        <td><?= 'Rp ' . number_format($row['total_biaya'] ?? 0, 0, ',', '.') ?></td>
                                        <td>
                                            <?php if (!empty($row['bukti_pembayaran'])): ?>
                                                <a href="../uploads/bukti_pembayaran/<?= htmlspecialchars($row['bukti_pembayaran']) ?>" target="_blank">Lihat Bukti</a>
                                            <?php else: ?>
                                                <span>-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($status_text) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="detail_pembelian.php?id=<?= urlencode($row['id_pembelian']) ?>" class="btn btn-info btn-sm" title="Lihat Detail">Detail</a>
                                        </td>
                                    </tr>
                            <?php 
                                endwhile; 
                            else:
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center">Belum ada data pembelian.</td>
                                </tr>
                            <?php 
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" id="addPembelianModal" tabindex="-1" aria-labelledby="addPembelianModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="add_pembelian.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPembelianModalLabel">Tambah Pembelian Material</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tanggal_pembelian" class="form-label">Tanggal Pembelian</label>
                            <input type="date" class="form-control" id="tanggal_pembelian" name="tanggal_pembelian" required>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan_pembelian" class="form-label">Keterangan</label>
                            <input type="text" class="form-control" id="keterangan_pembelian" name="keterangan_pembelian" required>
                        </div>
                        <div class="mb-3">
                            <label for="bukti_pembayaran" class="form-label">Upload Nota Pembelian</label>
                            <input type="file" class="form-control" id="bukti_pembayaran" name="bukti_pembayaran" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Lanjut</button>
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
            <p>Apakah Anda yakin ingin menghapus data pembelian ini?</p>
            <p class="text-danger small">Semua detail material yang terkait juga akan ikut terhapus.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <a href="#" id="confirmDeleteLink" class="btn btn-danger">Hapus</a>
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

        // Inisialisasi DataTable tanpa properti 'order'.
        // Ini akan menjaga urutan asli dari HTML (yang sudah diurutkan dari server/PHP).
        $('#tabelPembelian').DataTable();

        // Bagian untuk modal hapus
        $('#tabelPembelian').on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const deleteUrl = `delete_pembelian.php?id=${id}`;
            $('#confirmDeleteLink').attr('href', deleteUrl);
        });

        // Bagian notifikasi otomatis hilang
        const alertBox = $('.alert');
        if (alertBox.length) {
            setTimeout(function() {
                alertBox.fadeOut('slow');
            }, 5000);
        }
        
    });
    </script>
</body>
</html>