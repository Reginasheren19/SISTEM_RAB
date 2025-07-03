<?php
session_start();

// Include file koneksi
include("../config/koneksi_mysql.php");

// Pastikan ID Pengajuan Upah ada
if (!isset($_GET['id_pengajuan_upah'])) {
    die("ID Pengajuan Upah tidak diberikan."); // Gunakan die() untuk menghentikan eksekusi
}
$id_pengajuan_upah = (int)$_GET['id_pengajuan_upah']; // Casting ke integer untuk keamanan

// Query utama untuk mengambil data pengajuan dan info RAB terkait
$sql_pengajuan_info = "SELECT 
                        pu.tanggal_pengajuan,
                        pu.total_pengajuan,
                        pu.status_pengajuan,
                        pu.keterangan,
                        pu.bukti_bayar,
                        tr.id_rab_upah,
                        tr.total_rab_upah,
                        tr.tanggal_mulai,
                        tr.tanggal_selesai,
                        CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS pekerjaan,
                        mpr.type_proyek,
                        mpe.lokasi,
                        mm.nama_mandor,
                        u.nama_lengkap AS pj_proyek -- <-- Sekarang ini bisa diambil
                    FROM pengajuan_upah pu
                    LEFT JOIN rab_upah tr ON pu.id_rab_upah = tr.id_rab_upah
                    LEFT JOIN master_proyek mpr ON tr.id_proyek = mpr.id_proyek
                    LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
                    LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
                    LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user -- <-- DITAMBAHKAN JOIN INI
                    WHERE pu.id_pengajuan_upah = $id_pengajuan_upah";

$pengajuan_result = mysqli_query($koneksi, $sql_pengajuan_info);
// Periksa jika query info pengajuan gagal
if (!$pengajuan_result) {
    die("Error query data pengajuan: " . mysqli_error($koneksi));
}
if (mysqli_num_rows($pengajuan_result) == 0) {
    die("Data Pengajuan Upah tidak ditemukan.");
}
$pengajuan_info = mysqli_fetch_assoc($pengajuan_result);

$id_rab_upah = $pengajuan_info['id_rab_upah'];

// [BARU] Query untuk menghitung data keuangan proyek
$sql_keuangan = "SELECT SUM(total_pengajuan) as total_cair FROM pengajuan_upah WHERE id_rab_upah = $id_rab_upah AND status_pengajuan = 'dibayar'";
$keuangan_result = mysqli_query($koneksi, $sql_keuangan);
$total_pencairan = mysqli_fetch_assoc($keuangan_result)['total_cair'] ?? 0;
$total_kontrak = (float) $pengajuan_info['total_rab_upah'];
$sisa_anggaran = $total_kontrak - $total_pencairan;
// Query detail pengajuan upah
// =================================== KESALAHAN DI SINI ===================================
// Nama tabel yang benar adalah 'detail_pengajuan_upah', bukan 'detail_pengajuan'
$sql_detail = "SELECT
                    dp.id_detail_rab_upah,
                    dp.progress_pekerjaan, 
                    dp.nilai_upah_diajukan, 
                    k.nama_kategori, 
                    mp.uraian_pekerjaan, 
                    d.sub_total 
                FROM detail_pengajuan_upah dp 
                LEFT JOIN detail_rab_upah d ON dp.id_detail_rab_upah = d.id_detail_rab_upah
                LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan 
                LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori 
                WHERE dp.id_pengajuan_upah = '$id_pengajuan_upah'
                ORDER BY k.id_kategori, mp.uraian_pekerjaan";
// =========================================================================================

$detail_result = mysqli_query($koneksi, $sql_detail);
// Tambahkan pengecekan error SETELAH query dijalankan
if (!$detail_result) {
    // Jika query gagal, hentikan eksekusi dan tampilkan pesan error dari MySQL
    die("Error query detail pengajuan: " . mysqli_error($koneksi));
}

// Query untuk mengambil bukti pekerjaan dari tabel bukti_pengajuan_upah
$sql_bukti_pekerjaan = "SELECT nama_file, path_file FROM bukti_pengajuan_upah WHERE id_pengajuan_upah = $id_pengajuan_upah";
$bukti_pekerjaan_result = mysqli_query($koneksi, $sql_bukti_pekerjaan);

// Query untuk menghitung termin pengajuan
$id_rab_upah = $pengajuan_info['id_rab_upah'];
$sql_termin = "SELECT COUNT(id_pengajuan_upah) AS termin_ke 
               FROM pengajuan_upah 
               WHERE id_rab_upah = $id_rab_upah AND id_pengajuan_upah <= $id_pengajuan_upah";
$termin_result = mysqli_query($koneksi, $sql_termin);
$termin_data = mysqli_fetch_assoc($termin_result);
$termin_ke = $termin_data['termin_ke'];

// Fungsi untuk mengubah angka menjadi Angka Romawi
function toRoman($num) {
    $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $result = '';
    foreach ($map as $roman => $value) {
        while ($num >= $value) {
            $result .= $roman;
            $num -= $value;
        }
    }
    return $result;
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
                  <i class="fas fa-calculator"></i>
                  <p>Rancang RAB Upah</p>
                </a>
              </li>
                            <li class="nav-item">
                <a href="pengajuan_upah.php">
                  <i class="fas fa-hand-holding-usd"></i>
                  <p>Pengajuah Upah</p>
                </a>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Laporan</h4>
              </li>
                            <li class="nav-item">
                <a href="lap_pengajuan_upah.php">
                  <i class="fas fa-file"></i>
                  <p>Pengajuan Upah</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="lap_realisasi_anggaran.php">
                  <i class="fas fa-file"></i>
                  <p>Realisasi Anggaran</p>
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
  <a href="#" class="disabled">
    <i class="fas fa-database"></i>
    <p>Master Pekerjaan</p>
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
                <h3 class="fw-bold mb-3">Detail Pengajuan Upah</h3>
                <div class="ms-auto">
                    <a href="pengajuan_upah.php" class="btn btn-secondary btn-round">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>

            <!-- [DIUBAH] Bagian Informasi Header yang lebih rapi -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Informasi Pengajuan</h4>
                    <span class="badge bg-primary fs-6">Pengajuan Termin Ke-<?= htmlspecialchars($termin_ke) ?></span>
                </div>
                <div class="card-body">
                                        <div class="row mb-3">
                                        <div class="col-sm-6 col-md-3 mb-3"><div class="card card-stats card-info card-round m-0"><div class="card-body"><div class="row"><div class="col-5"><div class="icon-big text-center"><i class="fas fa-file-contract"></i></div></div><div class="col-7 col-stats"><div class="numbers"><p class="card-category">Total Kontrak</p><h4 class="card-title" style="font-size: 1rem;">Rp <?= number_format($total_kontrak, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                                        <div class="col-sm-6 col-md-3 mb-3"><div class="card card-stats card-success card-round m-0"><div class="card-body"><div class="row"><div class="col-5"><div class="icon-big text-center"><i class="fas fa-hand-holding-usd"></i></div></div><div class="col-7 col-stats"><div class="numbers"><p class="card-category">Total Sudah Cair</p><h4 class="card-title" style="font-size: 1rem;">Rp <?= number_format($total_pencairan, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                                        <div class="col-sm-6 col-md-3 mb-3"><div class="card card-stats card-secondary card-round m-0"><div class="card-body"><div class="row"><div class="col-5"><div class="icon-big text-center"><i class="fas fa-wallet"></i></div></div><div class="col-7 col-stats"><div class="numbers"><p class="card-category">Sisa Anggaran</p><h4 class="card-title" style="font-size: 1rem;">Rp <?= number_format($sisa_anggaran, 0, ',', '.') ?></h4></div></div></div></div></div></div>
                                        <div class="col-sm-6 col-md-3 mb-3"><div class="card card-stats card-warning card-round m-0"><div class="card-body"><div class="row"><div class="col-5"><div class="icon-big text-center"><i class="fas fa-file-invoice-dollar"></i></div></div><div class="col-7 col-stats"><div class="numbers"><p class="card-category">Nilai Pengajuan Ini</p><h4 class="card-title" style="font-size: 1rem;">Rp <?= number_format($pengajuan_info['total_pengajuan'], 0, ',', '.') ?></h4></div></div></div></div></div></div>
                                    </div>
                    <div class="row">
                        <!-- Kolom Kiri -->
                        <div class="col-md-6">
                            <dl class="row">
        <dt class="col-sm-4">ID Pengajuan</dt>
        <dd class="col-sm-8">: PU<?= htmlspecialchars($id_pengajuan_upah) ?></dd>

                                <dt class="col-sm-4">Pekerjaan</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['pekerjaan']) ?></dd>
                                
                                <dt class="col-sm-4">Type Proyek</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['type_proyek']) ?></dd>

                                <dt class="col-sm-4">Lokasi</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['lokasi']) ?></dd>
                                
                                <dt class="col-sm-4">Keterangan</dt>
                                <dd class="col-sm-8">: <?= !empty($pengajuan_info['keterangan']) ? htmlspecialchars($pengajuan_info['keterangan']) : '-' ?></dd>
                            </dl>
                        </div>
                        <!-- Kolom Kanan -->
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Tanggal Pengajuan</dt>
                                <dd class="col-sm-8">: <?= date('d F Y', strtotime($pengajuan_info['tanggal_pengajuan'])) ?></dd>
                                
                                <dt class="col-sm-4">Status</dt>
                                <dd class="col-sm-8">: <span class="badge bg-info"><?= ucwords(htmlspecialchars($pengajuan_info['status_pengajuan'])) ?></span></dd>
                                
                                <dt class="col-sm-4">Mandor</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['nama_mandor']) ?></dd>
                                
                                <dt class="col-sm-4">PJ Proyek</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['pj_proyek']) ?></dd>
                                
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Detail Pekerjaan -->
            <div class="card shadow-sm">
<div class="card-header bg-light d-flex justify-content-between align-items-center">
  <h4 class="card-title mb-0">Rincian Pekerjaan yang Diajukan</h4>
                          <a href="cetak_formulir_pengajuan.php?id=<?= $id_pengajuan_upah ?>" target="_blank" class="btn btn-label-info btn-round btn-sm">
                          <span class="btn-label">
                            <i class="fa fa-print"></i>
                          </span>
                          Cetak
                        </a>
</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-vcenter mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%;" class="text-center">No</th>
                                    <th>Uraian Pekerjaan</th>
                                    <th style="width:15%;" class="text-center">Jumlah RAB (Rp)</th>
                                    <th style="width:15%;" class="text-center">Progress Diajukan (%)</th>
                                    <th style="width:15%;" class="text-center">Nilai Diajukan (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($detail_result && mysqli_num_rows($detail_result) > 0) {
                                    $prevKategori = null;
                                    $noKategori = 0;
                                    $noPekerjaan = 1;
                                    while ($row = mysqli_fetch_assoc($detail_result)) {
                                        if ($prevKategori !== $row['nama_kategori']) {
                                            $noKategori++;
                                            echo "<tr class='table-primary fw-bold'><td class='text-center'>" . toRoman($noKategori) . "</td><td colspan='4'>" . htmlspecialchars($row['nama_kategori']) . "</td></tr>";
                                            $prevKategori = $row['nama_kategori'];
                                            $noPekerjaan = 1;
                                        }
                                        echo "<tr>
                                                <td class='text-center'>" . $noPekerjaan++ . "</td>
                                                <td><span class='ms-3'>" . htmlspecialchars($row['uraian_pekerjaan']) . "</span></td>
                                                <td class='text-end'>" . number_format($row['sub_total'], 0, ',', '.') . "</td>
                                                <td class='text-center'>" . number_format($row['progress_pekerjaan'], 2, ',', '.') . "%</td>
                                                <td class='text-end fw-bold'>" . number_format($row['nilai_upah_diajukan'], 0, ',', '.') . "</td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>Tidak ada rincian pekerjaan untuk pengajuan ini.</td></tr>";
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr class='table-success fw-bolder'>
                                    <td colspan="4" class='text-end'>TOTAL PENGAJUAN</td>
                                    <td class='text-end'>Rp <?= number_format($pengajuan_info['total_pengajuan'], 0, ',', '.') ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
              </div>
<div class="row align-items-stretch">
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light"><h4 class="card-title mb-0">Bukti Progress Pekerjaan</h4></div>
            <div class="card-body">
                <div class="row g-2">
                    <?php if ($bukti_pekerjaan_result && mysqli_num_rows($bukti_pekerjaan_result) > 0):
                        while ($bukti = mysqli_fetch_assoc($bukti_pekerjaan_result)):
                            $path_pekerjaan = "../" . htmlspecialchars($bukti['path_file']);
                    ?>
                    <div class="col-4">
                        <a href="<?= $path_pekerjaan ?>" target="_blank" title="<?= htmlspecialchars($bukti['nama_file']) ?>">
                            <img src="<?= $path_pekerjaan ?>" class="img-thumbnail gallery-item" onerror="this.onerror=null;this.src='https://placehold.co/100x100/EEE/31343C?text=File';">
                        </a>
                    </div>
                    <?php 
                        endwhile;
                    else: ?>
                    <div class="col-12 d-flex align-items-center justify-content-center h-100">
                        <p class="text-muted text-center py-3 m-0">Tidak ada bukti progress yang dilampirkan.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light"><h4 class="card-title mb-0">Bukti Pembayaran</h4></div>
            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                <?php if (!empty($pengajuan_info['bukti_bayar'])):
                    $path_bayar = "../" . htmlspecialchars($pengajuan_info['bukti_bayar']);
                ?>
                <div class="col-12">
                    <a href="<?= $path_bayar ?>" target="_blank" title="Lihat Bukti Pembayaran">
                        <img src="<?= $path_bayar ?>" class="img-thumbnail" style="width: 100%; height: auto; max-height: 240px; object-fit: contain;" onerror="this.onerror=null;this.src='https://placehold.co/600x400/EEE/31343C?text=File';">
                    </a>
                </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3 m-0">Tidak ada bukti pembayaran yang dilampirkan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

          </div>
        </div>
      </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</body>
</html>