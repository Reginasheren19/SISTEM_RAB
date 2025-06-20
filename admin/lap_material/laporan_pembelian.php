<?php
session_start();
// Sesuaikan path ini jika perlu
include("../../config/koneksi_mysql.php");

// =============================================================================
// BAGIAN 1: LOGIKA FILTER DENGAN POLA PRG (POST/REDIRECT/GET)
// =============================================================================

// Jika pengguna menekan tombol "Tampilkan"
if (isset($_POST['filter'])) {
    // Simpan semua nilai filter ke dalam session
    $_SESSION['lp_tanggal_mulai'] = $_POST['tanggal_mulai'];
    $_SESSION['lp_tanggal_selesai'] = $_POST['tanggal_selesai'];
    $_SESSION['lp_id_material'] = $_POST['id_material'];

    // Redirect ke halaman ini sendiri untuk membersihkan state POST
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Jika pengguna menekan tombol "Reset"
if (isset($_POST['reset'])) {
    // Hapus semua session filter yang berhubungan dengan laporan ini
    unset($_SESSION['lp_tanggal_mulai']);
    unset($_SESSION['lp_tanggal_selesai']);
    unset($_SESSION['lp_id_material']);
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Ambil nilai filter dari session, atau gunakan nilai default jika session tidak ada
$tanggal_mulai = $_SESSION['lp_tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_SESSION['lp_tanggal_selesai'] ?? date('Y-m-t');
$id_material_filter = $_SESSION['lp_id_material'] ?? ''; // '' berarti "Semua Material"

// =============================================================================
// BAGIAN 2: LOGIKA UNTUK MEMBUAT SUB-JUDUL DINAMIS
// =============================================================================

// 1. Buat array kosong sebagai "wadah"
$sub_judul_parts = [];

// 2. Isi "wadah" dengan informasi tanggal
if ($tanggal_mulai == $tanggal_selesai) {
    $sub_judul_parts[] = "Untuk tanggal: " . date('d F Y', strtotime($tanggal_mulai));
} else {
    $sub_judul_parts[] = "Periode: " . date('d M Y', strtotime($tanggal_mulai)) . " s/d " . date('d M Y', strtotime($tanggal_selesai));
}

// 3. Jika ada filter material, ambil namanya dan tambahkan ke "wadah"
if (!empty($id_material_filter)) {
    $nama_material_sql = "SELECT nama_material FROM master_material WHERE id_material = ?";
    $stmt_nama = mysqli_prepare($koneksi, $nama_material_sql);
    mysqli_stmt_bind_param($stmt_nama, "i", $id_material_filter);
    mysqli_stmt_execute($stmt_nama);
    $result_nama = mysqli_stmt_get_result($stmt_nama);
    if ($nama_row = mysqli_fetch_assoc($result_nama)) {
        $sub_judul_parts[] = "Material: " . htmlspecialchars($nama_row['nama_material']);
    }
    mysqli_stmt_close($stmt_nama);
}

// 4. Setelah semua "bahan" terkumpul, gabungkan menjadi satu kalimat
$sub_judul = implode(" | ", $sub_judul_parts);


// =============================================================================
// BAGIAN 3: QUERY PENGAMBILAN DATA
// =============================================================================

// Query untuk mengisi dropdown filter material
$material_sql = "SELECT id_material, nama_material FROM master_material ORDER BY nama_material ASC";
$material_result = mysqli_query($koneksi, $material_sql);

// Query utama yang dinamis untuk mengambil data laporan
$sql_parts = [
    "select"    => "SELECT DISTINCT p.id_pembelian, p.tanggal_pembelian, p.keterangan_pembelian, p.total_biaya",
    "from"      => "FROM pencatatan_pembelian p",
    "join"      => "",
    "where"     => "WHERE p.tanggal_pembelian BETWEEN ? AND ?",
    "order"     => "ORDER BY p.tanggal_pembelian DESC"
];
$params = [$tanggal_mulai, $tanggal_selesai];
$param_types = "ss";

// Tambahkan join dan kondisi jika material difilter
if (!empty($id_material_filter)) {
    $sql_parts['join'] = "JOIN detail_pencatatan_pembelian dp ON p.id_pembelian = dp.id_pembelian";
    $sql_parts['where'] .= " AND dp.id_material = ?";
    $params[] = $id_material_filter;
    $param_types .= "i";
}

// Gabungkan semua bagian query menjadi satu
$sql = implode(" ", $sql_parts);

$stmt = mysqli_prepare($koneksi, $sql);
if ($stmt === false) { die("Query Gagal Disiapkan. Error: " . mysqli_error($koneksi)); }

// Bind parameter secara dinamis
mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Laporan Pencatatan Pembelian</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="../assets/img/logo/LOGO PT.jpg"
      type="image/x-icon"
    />

    <!-- Fonts and icons -->
    <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
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
          urls: ["../assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="../assets/css/demo.css" />
  </head>
  <body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo">
                <div class="logo-header" data-background-color="dark">
                    <a href="dashboard.php" class="logo">
                        <img src="../assets/img/logo/LOGO PT.jpg" alt="Logo PT" class="navbar-brand" height="30" />
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
                <h3 class="fw-bold mb-3">Laporan Pencatatan Pembelian</h3>
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
                        <a href="#">Laporan Pencatatan Pembelian</a>
                    </li>
                </ul>
            </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Filter Laporan</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tanggal_mulai" class="form-label">Dari Tanggal</label>
                                            <input type="date" class="form-control" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tanggal_selesai" class="form-label">Sampai Tanggal</label>
                                            <input type="date" class="form-control" name="tanggal_selesai" value="<?= htmlspecialchars($tanggal_selesai) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="id_material" class="form-label">Filter Material</label>
                                            <select name="id_material" class="form-select">
                                                <option value="">Semua Material</option>
                                                <?php 
                                                // Pastikan pointer direset jika variabel result dipakai lagi
                                                mysqli_data_seek($material_result, 0); 
                                                while($material = mysqli_fetch_assoc($material_result)): 
                                                ?>
                                                    <option value="<?= $material['id_material'] ?>" <?= ($id_material_filter == $material['id_material']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($material['nama_material']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <hr class="mt-3">
                                <div class="row">
                                    <div class="col-12 text-end">
                                        <button type="submit" name="reset" class="btn btn-secondary">Reset Filter</button>
                                        <button type="submit" name="filter" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Tampilkan Laporan
                                        </button>
                                        <a href="cetak_lap_pembelian.php?start=<?= $tanggal_mulai ?>&end=<?= $tanggal_selesai ?>&material=<?= $id_material_filter ?>" target="_blank" class="btn btn-success">
                                            <i class="fas fa-print"></i> Unduh (PDF)
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div>
                                <h4 class="card-title mb-1">Laporan Pencatatan Pembelian</h4>
                                <p class="text-muted small mb-0"><?= $sub_judul ?></p>
                            </div>
                        </div>
                    </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>ID Pembelian</th>
                                            <th>Tanggal</th>
                                            <th>Keterangan</th> <th class="text-end">Total Biaya</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $nomor = 1;
                                        $grand_total = 0;
                                        if ($result && mysqli_num_rows($result) > 0):
                                            while ($row = mysqli_fetch_assoc($result)):
                                            $grand_total += $row['total_biaya'];

                                            // TAMBAHKAN 2 BARIS INI UNTUK FORMAT ID
                                            $tahun_pembelian = date('Y', strtotime($row['tanggal_pembelian']));
                                            $formatted_id = 'PB' . $row['id_pembelian'] . $tahun_pembelian;
                                        ?>
                                            <tr>
                                                <td><?= $nomor++ ?></td>
                                                <td><?= htmlspecialchars($formatted_id) ?></td>
                                                <td><?= date("d F Y", strtotime($row['tanggal_pembelian'])) ?></td>
                                                <td><?= htmlspecialchars($row['keterangan_pembelian']) ?></td> 
                                                <td class="text-end">Rp <?= number_format($row['total_biaya'] ?? 0, 0, ',', '.') ?></td>
                                            </tr>
                                        <?php 
                                        endwhile; 
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Tidak ada data pembelian pada periode ini.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-end">Grand Total</th>
                                            <th class="text-end">Rp <?= number_format($grand_total, 0, ',', '.') ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>    
    <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="../assets/js/plugin/datatables/dataTables.bootstrap5.min.js"></script>

    </body>
</html>