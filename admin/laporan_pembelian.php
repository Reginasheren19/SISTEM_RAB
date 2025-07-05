<?php
session_start();
// Sesuaikan path ini jika perlu
include("../config/koneksi_mysql.php");

// =============================================================================
// BAGIAN 1: LOGIKA FILTER DENGAN POLA PRG (POST/REDIRECT/GET)
// =============================================================================
if (isset($_POST['filter'])) {
    $_SESSION['lp_tanggal_mulai'] = $_POST['tanggal_mulai'];
    $_SESSION['lp_tanggal_selesai'] = $_POST['tanggal_selesai'];
    $_SESSION['lp_id_material'] = $_POST['id_material'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
if (isset($_POST['reset'])) {
    unset($_SESSION['lp_tanggal_mulai'], $_SESSION['lp_tanggal_selesai'], $_SESSION['lp_id_material']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
$tanggal_mulai = $_SESSION['lp_tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_SESSION['lp_tanggal_selesai'] ?? date('Y-m-t');
$id_material_filter = $_SESSION['lp_id_material'] ?? '';

// =============================================================================
// BAGIAN 2: LOGIKA UNTUK MEMBUAT SUB-JUDUL DINAMIS
// =============================================================================
// ... (Tidak ada perubahan di bagian ini) ...
$sub_judul_parts = [];
if ($tanggal_mulai == $tanggal_selesai) {
    $sub_judul_parts[] = "Untuk tanggal: " . date('d F Y', strtotime($tanggal_mulai));
} else {
    $sub_judul_parts[] = "Periode: " . date('d M Y', strtotime($tanggal_mulai)) . " s/d " . date('d M Y', strtotime($tanggal_selesai));
}
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
$sub_judul = implode(" | ", $sub_judul_parts);


// =============================================================================
// BAGIAN 3: QUERY PENGAMBILAN DATA
// =============================================================================
$material_sql = "SELECT id_material, nama_material FROM master_material ORDER BY nama_material ASC";
$material_result = mysqli_query($koneksi, $material_sql);

$sql_parts = [
    "select"  => "SELECT p.tanggal_pembelian, p.id_pembelian, p.keterangan_pembelian, m.nama_material, s.nama_satuan, dp.quantity, dp.harga_satuan_pp, dp.sub_total_pp",
    "from"    => "FROM detail_pencatatan_pembelian dp",
    "join"    => "JOIN pencatatan_pembelian p ON dp.id_pembelian = p.id_pembelian
                  JOIN master_material m ON dp.id_material = m.id_material
                  LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan",
    // --- [DITAMBAHKAN] Filter item pengganti dengan harga 0 ---
    "where"   => "WHERE p.tanggal_pembelian BETWEEN ? AND ? AND dp.harga_satuan_pp > 0",
    "order"   => "ORDER BY p.tanggal_pembelian, p.id_pembelian, m.nama_material"
];
$params = [$tanggal_mulai, $tanggal_selesai];
$param_types = "ss";

if (!empty($id_material_filter)) {
    $sql_parts['where'] .= " AND dp.id_material = ?";
    $params[] = $id_material_filter;
    $param_types .= "i";
}

$sql = implode(" ", $sql_parts);
$stmt = mysqli_prepare($koneksi, $sql);
if ($stmt === false) { die("Query Gagal Disiapkan. Error: " . mysqli_error($koneksi)); }
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
                                        <a href="cetak_lap_pembelian.php?start=<?= htmlspecialchars($tanggal_mulai) ?>&end=<?= htmlspecialchars($tanggal_selesai) ?>&material=<?= htmlspecialchars($id_material_filter) ?>" target="_blank" class="btn btn-success">
                                            <i class="fas fa-print"></i> Cetak Laporan
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
                                    <h4 class="card-title mb-1">Laporan Rincian Pembelian</h4>
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
                                            <th>Tanggal</th>
                                            <th>ID Pembelian</th>
                                            <th>Keterangan</th>
                                            <th>Nama Material</th>
                                            <th class="text-end">Kuantitas</th>
                                            <th class="text-end">Harga Satuan</th>
                                            <th class="text-end">Sub Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $nomor = 1;
                                        $grand_total = 0;
                                        if ($result && mysqli_num_rows($result) > 0):
                                            while ($row = mysqli_fetch_assoc($result)):
                                                $grand_total += $row['sub_total_pp'];
                                                $tahun_pembelian = date('Y', strtotime($row['tanggal_pembelian']));
                                                $formatted_id = 'PB' . $row['id_pembelian'] . $tahun_pembelian;
                                        ?>
                                        <tr>
                                            <td><?= $nomor++ ?></td>
                                            <td><?= date("d M Y", strtotime($row['tanggal_pembelian'])) ?></td>
                                            <td><?= htmlspecialchars($formatted_id) ?></td>
                                            <td><?= htmlspecialchars($row['keterangan_pembelian']) ?></td> 
                                            <td><?= htmlspecialchars($row['nama_material']) ?></td>
                                            <td class="text-end"><?= number_format($row['quantity'], 2, ',', '.') ?> <?= htmlspecialchars($row['nama_satuan']) ?></td>
                                            <td class="text-end">Rp <?= number_format($row['harga_satuan_pp'] ?? 0, 0, ',', '.') ?></td>
                                            <td class="text-end">Rp <?= number_format($row['sub_total_pp'] ?? 0, 0, ',', '.') ?></td>
                                        </tr>
                                        <?php 
                                            endwhile; 
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Tidak ada data pembelian pada periode atau filter yang dipilih.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="7" class="text-end fw-bold">Grand Total</th>
                                            <th class="text-end fw-bold">Rp <?= number_format($grand_total, 0, ',', '.') ?></th>
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
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="assets/js/plugin/datatables/dataTables.bootstrap5.min.js"></script>
</body>
</html>