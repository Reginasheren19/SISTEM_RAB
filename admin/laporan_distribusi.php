<?php
session_start();
include("../config/koneksi_mysql.php");

// BAGIAN 1: LOGIKA FILTER
if (isset($_POST['filter'])) {
    $_SESSION['ld_tanggal_mulai'] = $_POST['tanggal_mulai'];
    $_SESSION['ld_tanggal_selesai'] = $_POST['tanggal_selesai'];
    $_SESSION['ld_id_proyek'] = $_POST['id_proyek'];
    $_SESSION['ld_id_material'] = $_POST['id_material'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
if (isset($_POST['reset'])) {
    unset($_SESSION['ld_tanggal_mulai'], $_SESSION['ld_tanggal_selesai'], $_SESSION['ld_id_proyek'], $_SESSION['ld_id_material']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
$tanggal_mulai = $_SESSION['ld_tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_SESSION['ld_tanggal_selesai'] ?? date('Y-m-t');
$id_proyek_filter = $_SESSION['ld_id_proyek'] ?? '';
$id_material_filter = $_SESSION['ld_id_material'] ?? '';

// BAGIAN 2: LOGIKA SUB-JUDUL
$sub_judul_parts = ["Periode: " . date('d M Y', strtotime($tanggal_mulai)) . " s/d " . date('d M Y', strtotime($tanggal_selesai))];
$sub_judul = implode(" | ", $sub_judul_parts);

// BAGIAN 3: QUERY PENGAMBILAN DATA
$proyek_result = mysqli_query($koneksi, "SELECT p.id_proyek, CONCAT(pr.nama_perumahan, ' - Kavling ', p.kavling) AS nama_proyek_lengkap FROM master_proyek p JOIN master_perumahan pr ON p.id_perumahan = pr.id_perumahan ORDER BY nama_proyek_lengkap ASC");
$material_result = mysqli_query($koneksi, "SELECT id_material, nama_material FROM master_material ORDER BY nama_material ASC");

$sql_parts = [
    "select" => "
        SELECT 
            d.id_distribusi, d.tanggal_distribusi, d.keterangan_umum, 
            u.nama_lengkap AS nama_pj,
            CONCAT(pr.nama_perumahan, ' - Kavling ', p.kavling) AS nama_proyek_lengkap,
            m.nama_material,
            s.nama_satuan,
            dd.jumlah_distribusi
    ",
    "from"   => "FROM detail_distribusi dd",
    "join"   => "
        JOIN distribusi_material d ON dd.id_distribusi = d.id_distribusi
        JOIN master_material m ON dd.id_material = m.id_material
        LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan
        LEFT JOIN master_user u ON d.id_user_pj = u.id_user 
        LEFT JOIN master_proyek p ON d.id_proyek = p.id_proyek 
        LEFT JOIN master_perumahan pr ON p.id_perumahan = pr.id_perumahan
    ",
    "where"  => "WHERE d.tanggal_distribusi BETWEEN ? AND ?",
    "order"  => "ORDER BY d.id_distribusi DESC, d.tanggal_distribusi DESC"
];
$params = [$tanggal_mulai, $tanggal_selesai];
$param_types = "ss";

if (!empty($id_proyek_filter)) {
    $sql_parts['where'] .= " AND d.id_proyek = ?";
    $params[] = $id_proyek_filter;
    $param_types .= "i";
}
if (!empty($id_material_filter)) {
    $sql_parts['where'] .= " AND dd.id_material = ?";
    $params[] = $id_material_filter;
    $param_types .= "i";
}

$sql = implode(" ", $sql_parts);
$stmt = mysqli_prepare($koneksi, $sql);
if($stmt === false) { die("Query Gagal Disiapkan: " . mysqli_error($koneksi)); }
mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// [BARU] Siapkan data dan hitung rowspan
$data_laporan = [];
$rowspan_counts = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data_laporan[] = $row;
        if (!isset($rowspan_counts[$row['id_distribusi']])) {
            $rowspan_counts[$row['id_distribusi']] = 0;
        }
        $rowspan_counts[$row['id_distribusi']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Laporan Distribusi Material</title>
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
                <h3 class="fw-bold mb-3">Laporan Distribusi Material</h3>
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
                        <a href="#">Laporan Distribusi Material</a>
                    </li>
                </ul>
            </div>

<div class="card">
                        <div class="card-header"><h4 class="card-title">Filter Laporan</h4></div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-3 form-group">
                                        <label>Dari Tanggal</label>
                                        <input type="date" class="form-control" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai) ?>">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Sampai Tanggal</label>
                                        <input type="date" class="form-control" name="tanggal_selesai" value="<?= htmlspecialchars($tanggal_selesai) ?>">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Filter Proyek</label>
                                        <select name="id_proyek" class="form-select">
                                            <option value="">Semua Proyek</option>
                                            <?php mysqli_data_seek($proyek_result, 0); while($proyek = mysqli_fetch_assoc($proyek_result)): ?>
                                                <option value="<?= $proyek['id_proyek'] ?>" <?= ($id_proyek_filter == $proyek['id_proyek']) ? 'selected' : '' ?>><?= htmlspecialchars($proyek['nama_proyek_lengkap']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Filter Material</label>
                                        <select name="id_material" class="form-select">
                                            <option value="">Semua Material</option>
                                            <?php mysqli_data_seek($material_result, 0); while($material = mysqli_fetch_assoc($material_result)): ?>
                                                <option value="<?= $material['id_material'] ?>" <?= ($id_material_filter == $material['id_material']) ? 'selected' : '' ?>><?= htmlspecialchars($material['nama_material']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <hr class="mt-3">
                                <div class="row">
                                    <div class="col-12 text-end">
                                        <button type="submit" name="reset" class="btn btn-secondary">Reset</button>
                                        <button type="submit" name="filter" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
                                        <a href="cetak_lap_distribusi.php?start=<?= $tanggal_mulai ?>&end=<?= $tanggal_selesai ?>&material=<?= $id_material_filter ?>" target="_blank" class="btn btn-success">
                                            <i class="fas fa-print"></i> Unduh (PDF)
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        </div>
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h4 class="card-title mb-1">Laporan Distribusi Material</h4>
                                <p class="text-muted small mb-0"><?= $sub_judul ?></p>
                            </div>
                        </div>
                        <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>ID Distribusi</th>
                                        <th>Tanggal</th>
                                        <th>Proyek Tujuan</th>
                                        <th>Material</th>
                                        <th class="text-end">Jumlah</th>
                                        <th>Oleh</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (!empty($data_laporan)):
                                        $nomor = 1;
                                        $last_id = null;
                                        foreach ($data_laporan as $row):
                                            $tahun_distribusi = date('Y', strtotime($row['tanggal_distribusi']));
                                            $formatted_id = 'DIST' . $row['id_distribusi'] . $tahun_distribusi;
                                    ?>
                                    <tr>
                                        <?php if ($row['id_distribusi'] != $last_id): 
                                            $rowspan = $rowspan_counts[$row['id_distribusi']];
                                        ?>
                                            <td rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= $nomor++ ?></td>
                                            <td rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= htmlspecialchars($formatted_id) ?></td>
                                            <td rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= date("d M Y", strtotime($row['tanggal_distribusi'])) ?></td>
                                            <td rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= htmlspecialchars($row['nama_proyek_lengkap']) ?></td>
                                        <?php endif; ?>
                                        
                                        <td><?= htmlspecialchars($row['nama_material']) ?></td>
                                        <td class="text-end"><?= number_format($row['jumlah_distribusi'], 2, ',', '.') ?> <?= htmlspecialchars($row['nama_satuan']) ?></td>
                                        
                                        <?php if ($row['id_distribusi'] != $last_id): ?>
                                            <td rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= htmlspecialchars($row['nama_pj']) ?></td>
                                            <td rowspan="<?= $rowspan ?>" style="vertical-align: top;"><?= htmlspecialchars($row['keterangan_umum']) ?></td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php 
                                            $last_id = $row['id_distribusi'];
                                        endforeach; 
                                    else:
                                    ?>
                                    <tr><td colspan="8" class="text-center">Tidak ada data distribusi pada periode ini.</td></tr>
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
</body>
</html>
