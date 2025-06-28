<?php
session_start();
include("../../config/koneksi_mysql.php");

// BAGIAN 1: LOGIKA FILTER DENGAN POLA PRG (POST/REDIRECT/GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['filter_id_perumahan'] = $_POST['id_perumahan'] ?? null;
    $_SESSION['filter_id_proyek'] = $_POST['id_proyek'] ?? 'semua';
    $_SESSION['filter_tanggal_mulai'] = $_POST['tanggal_mulai'] ?? date('Y-m-01');
    $_SESSION['filter_tanggal_selesai'] = $_POST['tanggal_selesai'] ?? date('Y-m-t');
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
$id_perumahan_filter = $_SESSION['filter_id_perumahan'] ?? null;
$id_proyek_filter = $_SESSION['filter_id_proyek'] ?? 'semua';
$tanggal_mulai = $_SESSION['filter_tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_SESSION['filter_tanggal_selesai'] ?? date('Y-m-t');


// BAGIAN 2: LOGIKA PENGAMBILAN & PERHITUNGAN DATA
$laporan_data = [];

// [DIUBAH] Query dasar sekarang menggunakan INNER JOIN dan memiliki WHERE yang dinamis
$sql_proyek_rab = "
    SELECT 
        p.id_proyek,
        CONCAT(per.nama_perumahan, ' - Kavling: ', p.kavling, ' (Tipe: ', p.type_proyek, ')') AS nama_proyek_lengkap,
        rab.total_rab_material AS total_anggaran
    FROM master_proyek p
    JOIN master_perumahan per ON p.id_perumahan = per.id_perumahan
    INNER JOIN rab_material rab ON p.id_proyek = rab.id_proyek
";

// [DIUBAH] Logika WHERE yang lebih fleksibel untuk menangani semua filter
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($id_perumahan_filter)) {
    $where_conditions[] = "p.id_perumahan = ?";
    $params[] = $id_perumahan_filter;
    $param_types .= 'i';
}
if ($id_proyek_filter != 'semua' && is_numeric($id_proyek_filter)) {
    $where_conditions[] = "p.id_proyek = ?";
    $params[] = $id_proyek_filter;
    $param_types .= 'i';
}

if (!empty($where_conditions)) {
    $sql_proyek_rab .= " WHERE " . implode(' AND ', $where_conditions);
}
$sql_proyek_rab .= " ORDER BY per.nama_perumahan, p.kavling";

$stmt_proyek_rab = $koneksi->prepare($sql_proyek_rab);

if ($stmt_proyek_rab) {
    if (!empty($params)) {
        $stmt_proyek_rab->bind_param($param_types, ...$params);
    }
    $stmt_proyek_rab->execute();
    $result_proyek_rab = $stmt_proyek_rab->get_result();

    while ($proyek = $result_proyek_rab->fetch_assoc()) {
        $current_proyek_id = $proyek['id_proyek'];
        $total_realisasi_proyek = 0;
    
        $sql_distribusi = "SELECT dd.id_material, SUM(dd.jumlah_distribusi) AS total_kuantitas FROM detail_distribusi dd JOIN distribusi_material dm ON dd.id_distribusi = dm.id_distribusi WHERE dm.id_proyek = ? AND dm.tanggal_distribusi BETWEEN ? AND ? GROUP BY dd.id_material";
        $stmt_distribusi = $koneksi->prepare($sql_distribusi);
        $stmt_distribusi->bind_param("iss", $current_proyek_id, $tanggal_mulai, $tanggal_selesai);
        $stmt_distribusi->execute();
        $result_distribusi = $stmt_distribusi->get_result();
        
        while($item_distribusi = $result_distribusi->fetch_assoc()){
            $id_material = $item_distribusi['id_material'];
            $kuantitas_terpakai = (float)$item_distribusi['total_kuantitas'];
            $stmt_harga = $koneksi->prepare("SELECT SUM(sub_total_pp) / NULLIF(SUM(quantity), 0) AS harga_rata_rata FROM detail_pencatatan_pembelian WHERE id_material = ? AND quantity > 0 AND harga_satuan_pp > 0");
            $stmt_harga->bind_param("i", $id_material);
            $stmt_harga->execute();
            $harga_rata_rata = (float)($stmt_harga->get_result()->fetch_assoc()['harga_rata_rata'] ?? 0);
            $stmt_harga->close();
            $total_realisasi_proyek += $kuantitas_terpakai * $harga_rata_rata;
        }
        $stmt_distribusi->close();
    
        $laporan_data[] = ['nama_proyek' => $proyek['nama_proyek_lengkap'], 'anggaran' => $proyek['total_anggaran'], 'realisasi' => $total_realisasi_proyek, 'selisih' => $proyek['total_anggaran'] - $total_realisasi_proyek];
    }
    $stmt_proyek_rab->close();
}

// [DIUBAH] Query untuk dropdown perumahan hanya mengambil perumahan yang punya proyek dengan RAB
$perumahan_dropdown_sql = "SELECT DISTINCT per.id_perumahan, per.nama_perumahan FROM master_perumahan per JOIN master_proyek pro ON per.id_perumahan = pro.id_perumahan JOIN rab_material r ON pro.id_proyek = r.id_proyek ORDER BY per.nama_perumahan ASC";
$perumahan_dropdown_result = mysqli_query($koneksi, $perumahan_dropdown_sql);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Laporan RAB Vs Realisasi</title>
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
                <h3 class="fw-bold mb-3">Laporan RAB Vs Realisasi</h3>
                <ul class="breadcrumbs mb-3">
                    <li class="nav-home">
                        <a href="../dashboard.php">
                            <i class="icon-home"></i>
                        </a>
                    </li>
                    <li class="separator">
                        <i class="icon-arrow-right"></i>
                    </li>
                    <li class="nav-item">
                        <a href="#">Laporan RAB Vs Realisasi</a>
                    </li>
                </ul>
            </div>

<div class="card">
            <div class="card-header"><h4 class="card-title">Filter Laporan</h4></div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row align-items-end">
                        <div class="col-md-3"><div class="form-group"><label>Dari Tanggal</label><input type="date" name="tanggal_mulai" class="form-control" value="<?= htmlspecialchars($tanggal_mulai) ?>"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Sampai Tanggal</label><input type="date" name="tanggal_selesai" class="form-control" value="<?= htmlspecialchars($tanggal_selesai) ?>"></div></div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="id_perumahan">Perumahan</label>
                                <select name="id_perumahan" id="filter_perumahan" class="form-select">
                                    <option value="">-- Semua Perumahan --</option>
                                    <?php mysqli_data_seek($perumahan_dropdown_result, 0); while($perumahan = mysqli_fetch_assoc($perumahan_dropdown_result)): ?>
                                        <option value="<?= $perumahan['id_perumahan'] ?>" <?= ($id_perumahan_filter == $perumahan['id_perumahan']) ? 'selected' : '' ?>><?= htmlspecialchars($perumahan['nama_perumahan']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="id_proyek">Proyek / Kavling</label>
                                <select name="id_proyek" id="filter_proyek" class="form-select" disabled>
                                    <option value="semua">-- Pilih Perumahan Dulu --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col text-end">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button>
                            <a href="cetak_lap_realisasi.php?proyek=<?= $id_proyek_filter ?>&perumahan=<?= $id_perumahan_filter ?>&start=<?= $tanggal_mulai ?>&end=<?= $tanggal_selesai ?>" target="_blank" class="btn btn-success"><i class="fa fa-print"></i> Cetak</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title">Ringkasan Realisasi Anggaran</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="text-center"><th>No</th><th>Nama Proyek</th><th>Anggaran (RAB)</th><th>Realisasi (Terpakai)</th><th>Selisih</th><th>Persentase</th></tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($laporan_data)): $nomor = 1; foreach ($laporan_data as $data): ?>
                            <tr>
                                <td class="text-center"><?= $nomor++ ?></td>
                                <td><?= htmlspecialchars($data['nama_proyek']) ?></td>
                                <td class="text-end">Rp <?= number_format($data['anggaran'], 2, ',', '.') ?></td>
                                <td class="text-end">Rp <?= number_format($data['realisasi'], 2, ',', '.') ?></td>
                                <td class="text-end fw-bold <?= ($data['selisih'] >= 0) ? 'text-success' : 'text-danger' ?>">
                                    Rp <?= number_format(abs($data['selisih']), 2, ',', '.') ?>
                                    <small class="fw-normal d-block"><?= ($data['selisih'] >= 0) ? '(Hemat)' : '(Boros)' ?></small>
                                </td>
                                <td class="text-center" style="width: 15%;">
                                    <?php 
                                    $persentase = ($data['anggaran'] > 0) ? ($data['realisasi'] / $data['anggaran']) * 100 : 0;
                                    $progress_class = $persentase >= 100 ? 'bg-danger' : ($persentase > 80 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <div class="progress" style="height: 20px;" title="Realisasi Aktual: <?= number_format($persentase, 1) ?>%">
                                        <div class="progress-bar <?= $progress_class ?>" role="progressbar" style="width: <?= min($persentase, 100) ?>%;" aria-valuenow="<?= $persentase ?>" aria-valuemin="0" aria-valuemax="100">
                                            <span style="white-space: nowrap;">
                                            <?php if($persentase >= 100): ?>
                                                <i class="fa fa-exclamation-triangle"></i>&nbsp;<?= number_format($persentase, 1) ?>%
                                            <?php else: ?>
                                                <?= number_format($persentase, 1) ?>%
                                            <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center text-muted"><i>Tidak ada data untuk filter yang dipilih.</i></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
<script>
// [DIUBAH] JavaScript untuk menangani dropdown dinamis
$(document).ready(function() {
    const perumahanFilter = $('#filter_perumahan');
    const proyekFilter = $('#filter_proyek');
    const idProyekTerpilih = '<?= $id_proyek_filter ?>';
    const idPerumahanTerpilih = '<?= $id_perumahan_filter ?>';

    function loadProyek(idPerumahan, selectedProyek) {
        if (!idPerumahan) {
            proyekFilter.html('<option value="semua">-- Tampilkan Semua Proyek --</option>');
            proyekFilter.prop('disabled', true);
            return;
        }

        proyekFilter.prop('disabled', true);
        proyekFilter.html('<option value="">Memuat...</option>');

        $.ajax({
            url: '../get_proyek_by_perumahan.php', // PASTIKAN PATH INI BENAR
            type: 'GET',
            data: { id_perumahan: idPerumahan },
            dataType: 'json',
            success: function(proyekList) {
                proyekFilter.empty();
                proyekFilter.append('<option value="semua">-- Semua Proyek di Perumahan Ini --</option>');
                $.each(proyekList, function(index, proyek) {
                    const isSelected = (proyek.id_proyek == selectedProyek) ? 'selected' : '';
                    proyekFilter.append(`<option value="${proyek.id_proyek}" ${isSelected}>${proyek.nama_proyek_lengkap}</option>`);
                });
                proyekFilter.prop('disabled', false);
            },
            error: function() {
                proyekFilter.html('<option value="">Gagal memuat data</option>');
                proyekFilter.prop('disabled', false);
            }
        });
    }

    // Panggil fungsi saat halaman dimuat, jika ada perumahan yang sudah terpilih
    if (idPerumahanTerpilih) {
        loadProyek(idPerumahanTerpilih, idProyekTerpilih);
    } else {
        proyekFilter.prop('disabled', true); // Jika tidak ada perumahan terpilih, disable proyek
    }

    // Panggil fungsi saat dropdown perumahan diubah
    perumahanFilter.on('change', function() {
        proyekFilter.val('semua');
        loadProyek($(this).val(), 'semua');
    });
});
</script>
</body>
</html>