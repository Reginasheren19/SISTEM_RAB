<?php
// Include file koneksi Anda
include("../config/koneksi_mysql.php");

// Pastikan ID RAB Upah ada dan aman
if (!isset($_GET['id_rab_upah'])) {
    echo "ID RAB Upah tidak diberikan.";
    exit;
}
$id_rab_upah = mysqli_real_escape_string($koneksi, $_GET['id_rab_upah']);

// [PERBAIKAN] Query utama untuk mendapatkan informasi header RAB
// JOIN diubah agar melalui master_proyek terlebih dahulu
$sql_rab = "SELECT 
                tr.id_rab_upah,
                                       tr.tanggal_mulai,
           tr.tanggal_selesai,
                CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS pekerjaan,
                mpr.type_proyek,
                u.nama_lengkap AS pj_proyek,
                mpe.lokasi,
                YEAR(tr.tanggal_mulai) AS tahun,
                mm.nama_mandor
            FROM rab_upah tr
            JOIN master_proyek mpr ON tr.id_proyek = mpr.id_proyek
            LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
            LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
                    LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
            WHERE tr.id_rab_upah = '$id_rab_upah'";

$rab_result = mysqli_query($koneksi, $sql_rab);
if (!$rab_result || mysqli_num_rows($rab_result) == 0) {
    // Jika query gagal atau tidak ada baris yang ditemukan, berikan pesan error yang lebih detail
    die("Data RAB Upah dengan ID '$id_rab_upah' tidak ditemukan atau terjadi error. Query: " . mysqli_error($koneksi));
}
$rab_info = mysqli_fetch_assoc($rab_result);

// [TAMBAHAN] Query untuk menghitung termin ke berapa pengajuan ini
$sql_termin = "SELECT COUNT(id_pengajuan_upah) AS jumlah_sebelumnya FROM pengajuan_upah WHERE id_rab_upah = $id_rab_upah";
$termin_result = mysqli_query($koneksi, $sql_termin);
$termin_data = mysqli_fetch_assoc($termin_result);
$termin_ke = ($termin_data['jumlah_sebelumnya'] ?? 0) + 1; 

// Query detail item pekerjaan pada RAB (sudah benar, tidak perlu diubah)
$sql_detail = "SELECT 
                    d.id_detail_rab_upah, 
                    k.nama_kategori, 
                    mp.uraian_pekerjaan, 
                    d.sub_total
                FROM detail_rab_upah d 
                LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan 
                LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori 
                WHERE d.id_rab_upah = '$id_rab_upah' 
                ORDER BY k.id_kategori, mp.uraian_pekerjaan";
$detail_result = mysqli_query($koneksi, $sql_detail);

// Fungsi getProgressLaluPersen (sudah benar, tidak perlu diubah)
function getProgressLaluPersen($koneksi, $id_detail_rab_upah) {
    $id_detail_rab_upah = (int)$id_detail_rab_upah;
    $query = "SELECT SUM(dp.progress_pekerjaan) AS total_progress 
              FROM detail_pengajuan_upah dp
              JOIN pengajuan_upah pu ON dp.id_pengajuan_upah = pu.id_pengajuan_upah
              WHERE dp.id_detail_rab_upah = $id_detail_rab_upah 
              AND pu.status_pengajuan IN ('diajukan', 'disetujui', 'ditolak', 'dibayar')"; 

    $result = mysqli_query($koneksi, $query);
    if (!$result) {
        echo "<div class='alert alert-danger'><b>Error Query SQL:</b> " . mysqli_error($koneksi) . "</div>";
        return 0;
    }
    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        return (float)($data['total_progress'] ?? 0);
    }
    return 0;
}

// Fungsi toRoman (sudah benar, tidak perlu diubah)
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

        <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="assets/css/demo.css" />
        <style>
        .upload-card { border: 2px dashed #e0e0e0; border-radius: 0.5rem; transition: all 0.3s ease; background-color: #ffffff; }
        .upload-card.is-dragging { border-color: #0d6efd; background-color: #f0f8ff; }
        .upload-label { display: block; text-align: center; padding: 20px; cursor: pointer; }
        .upload-icon { font-size: 2.5rem; color: #adb5bd; }
        #preview-container { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; }
        .preview-item { position: relative; width: 100px; height: 100px; border-radius: 0.5rem; overflow: hidden; border: 1px solid #dee2e6; }
        .preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .preview-item .remove-btn { position: absolute; top: 5px; right: 5px; width: 22px; height: 22px; background-color: rgba(0, 0, 0, 0.6); color: white; border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity 0.3s ease; font-size: 0.75rem; }
        .preview-item:hover .remove-btn { opacity: 1; }
    </style>
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
              <h3 class="fw-bold mb-3">Form Pengajuan RAB Upah</h3>
            </div>
            
            <form method="POST" action="add_pengajuan.php" enctype="multipart/form-data">
                <input type="hidden" name="id_rab_upah" value="<?= htmlspecialchars($id_rab_upah) ?>">

                <!-- Informasi Proyek & RAB -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Informasi Proyek & RAB</h4>
                        <!-- [DITAMBAHKAN] Info Termin ke berapa -->
                        <span class="badge bg-primary fs-6">Pengajuan Termin Ke-<?= htmlspecialchars($termin_ke) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">Pekerjaan</dt><dd class="col-sm-8">: <?= htmlspecialchars($rab_info['pekerjaan']) ?></dd>
                                    <dt class="col-sm-4">Lokasi</dt><dd class="col-sm-8">: <?= htmlspecialchars($rab_info['lokasi']) ?></dd>
                                    <dt class="col-sm-4">Type Proyek</dt><dd class="col-sm-8">: <?= htmlspecialchars($rab_info['type_proyek']) ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                    <dt class="col-sm-4">ID RAB</dt>
                    <dd class="col-sm-8">: RABP<?= htmlspecialchars($rab_info['id_rab_upah']) ?></dd>                                    <dt class="col-sm-4">Mandor</dt><dd class="col-sm-8">: <?= htmlspecialchars($rab_info['nama_mandor']) ?></dd>
                                    <dt class="col-sm-4">PJ Proyek</dt><dd class="col-sm-8">: <?= htmlspecialchars($rab_info['pj_proyek']) ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Input Progress -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light"><h4 class="card-title mb-0">Detail Input Progress Pekerjaan</h4></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-vcenter mb-0" id="tblDetailRAB">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:5%;" class="text-center">No</th>
                                        <th>Uraian Pekerjaan</th>
                                        <th style="width:12%;" class="text-center">Jumlah (Rp)</th>
                                        <th style="width:12%;" class="text-center">Progress Lalu (%)</th>
                                        <th style="width:15%;" class="text-center">Progress Saat Ini (%)</th>
                                        <th style="width:20%;" class="text-center">Nilai Pengajuan (Rp)</th>
                                    </tr>
                                </thead>
                                        <tbody>
                                            <?php
                                            $grandTotalRAB = 0;
                                            if ($detail_result && mysqli_num_rows($detail_result) > 0) {
                                                mysqli_data_seek($detail_result, 0);
                                                $prevKategori = null; $noKategori = 0; $noPekerjaan = 1;
                                                while ($row = mysqli_fetch_assoc($detail_result)) {
                                                    if ($prevKategori !== $row['nama_kategori']) {
                                                        $noKategori++;
                                                        echo "<tr class='table-primary fw-bold'><td class='text-center'>" . toRoman($noKategori) . "</td><td colspan='5'>" . htmlspecialchars($row['nama_kategori']) . "</td></tr>";
                                                        $prevKategori = $row['nama_kategori']; $noPekerjaan = 1;
                                                    }
                                                    $idDetail = $row['id_detail_rab_upah'];
                                                    $progressLalu = getProgressLaluPersen($koneksi, $idDetail);
                                                    $sisaProgress = 100 - $progressLalu;
                                                    $isLunas = $sisaProgress <= 0.001;
                                            ?>
                                                    <tr>
                                                        <td class='text-center'><?= $noPekerjaan ?></td>
                                                        <td><span class='ms-3'><?= htmlspecialchars($row['uraian_pekerjaan']) ?></span></td>
                                                        <td class='text-end'><?= number_format($row['sub_total'], 0, ',', '.') ?></td>
                                                        <td class='text-center'><?= number_format($progressLalu, 2, ',', '.') ?>%</td>
                                                        <!-- [DIUBAH] Menambahkan Checkbox Lunas -->
                                                        <td class="p-1 align-middle">
                                                            <div class="input-group">
                                                                <input type="number" class="form-control form-control-sm progress-input text-center" data-subtotal="<?= $row['sub_total'] ?>" data-id="<?= $idDetail ?>" name="progress[<?= $idDetail ?>]" min="0" max="<?= number_format($sisaProgress, 2, '.', '') ?>" step="0.01" <?= $isLunas ? 'disabled placeholder="Lunas"' : 'placeholder="0.00"' ?>>
                                                                <div class="input-group-text">
                                                                    <input class="form-check-input mt-0 lunas-checkbox" type="checkbox" title="Tandai Lunas (100% Progress)" <?= $isLunas ? 'disabled' : '' ?>>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class='text-end fw-bold nilai-pengajuan' data-id='<?= $idDetail ?>'>Rp 0</td>
                                                    </tr>
                                            <?php
                                                    $noPekerjaan++; $grandTotalRAB += $row['sub_total'];
                                                }
                                            }
                                            ?>
                                        </tbody>
                                <!-- [DIPERBAIKI] Footer Tabel Ditambahkan Kembali -->
                                <tfoot>
                                    <tr class='table-light fw-bolder'>
                                        <td colspan="5" class='text-end'>TOTAL NILAI RAB</td>
                                        <td class='text-end'>Rp <?= number_format($grandTotalRAB, 0, ',', '.') ?></td>
                                    </tr>
                                    <tr class='table-succes fw-bolder'>
                                        <td colspan="5" class='text-end'>TOTAL PENGAJUAN SAAT INI</td>
                                        <td id="total-pengajuan-saat-ini" class='text-end'>Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

<!-- Upload File Section - Tampilan Input Pengajuan Baru -->
<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h4 class="card-title mb-0">Ringkasan & Kirim Pengajuan</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-7">
<!-- Upload/Kelola Bukti -->
<div class="mb-3">
  <label class="form-label fw-bold">Upload/Kelola Bukti</label>
  <div id="upload-card" 
       class="border border-dashed rounded d-flex flex-column align-items-center justify-content-center text-center p-4" 
       style="height: 160px; cursor: pointer; background-color: #fdfdfd;">
    
    <label for="file-input" class="d-block" style="cursor:pointer;">
      <i class="fas fa-cloud-upload-alt fa-2x text-secondary mb-2"></i>
      <h6 class="fw-bold mb-1">Seret & lepas file baru di sini</h6>
      <p class="text-muted small mb-0">atau klik untuk menambah file</p>
    </label>

    <input type="file" id="file-input" name="bukti_pengajuan[]" multiple accept="image/*,application/pdf" class="d-none">
  </div>
</div>

<div id="preview-container" class="d-flex flex-wrap gap-2 mt-3"></div>

            </div>
            <div class="col-md-5">
                <div class="mb-3">
                    <label for="tanggal_pengajuan" class="form-label fw-bold">Tanggal Pengajuan</label>
                    <input type="date" id="tanggal_pengajuan" name="tanggal_pengajuan" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="nominal-pengajuan" class="form-label fw-bold">Nominal Final Diajukan</label>
                    <input type="number" class="form-control form-control-lg text-end" id="nominal-pengajuan" name="nominal_pengajuan_final" placeholder="0">
                    <div id="error-nominal" class="form-text text-danger d-none">Nominal tidak boleh melebihi Total Pengajuan Dihitung.</div>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="pengajuan_upah.php" class="btn btn-secondary">Kembali</a>
                    <button type="submit" id="btn-submit" class="btn btn-primary" disabled>
                        <i class="fa fa-paper-plane"></i> Kirim Pengajuan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
            </form> 
          </div>
        </div>
      </div>
    </div>

    <!-- Core JS Files -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const tableBody = document.querySelector("#tblDetailRAB tbody");
        const totalPengajuanEl = document.getElementById('total-pengajuan-saat-ini');
        const nominalPengajuanInput = document.getElementById('nominal-pengajuan');
        const errorNominalEl = document.getElementById('error-nominal');
        const btnSubmit = document.getElementById('btn-submit');
        const uploadCard = document.getElementById('upload-card');
        const fileInput = document.getElementById('file-input');
        const previewContainer = document.getElementById('preview-container');
        // [PERBAIKAN] Gunakan satu DataTransfer sebagai "source of truth" yang persisten
        const dataTransfer = new DataTransfer();

        function formatRupiah(angka) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka || 0); }
        
        function calculateTotals() {
            let totalPengajuan = 0;
            document.querySelectorAll('.progress-input').forEach(input => {
                if (input.disabled && !input.closest('tr').querySelector('.lunas-checkbox')?.checked) return;

                const subtotal = parseFloat(input.dataset.subtotal);
                let progressDiajukan = parseFloat(input.value) || 0;
                const maxProgress = parseFloat(input.max);
                
                if (progressDiajukan > maxProgress) { progressDiajukan = maxProgress; input.value = maxProgress.toFixed(2); }
                if (progressDiajukan < 0) { progressDiajukan = 0; input.value = '0.00'; }

                const nilaiPengajuan = (progressDiajukan / 100) * subtotal;
                const nilaiCell = document.querySelector(`.nilai-pengajuan[data-id='${input.dataset.id}']`);
                if (nilaiCell) { nilaiCell.textContent = formatRupiah(nilaiPengajuan); }
                totalPengajuan += nilaiPengajuan;
            });
            totalPengajuanEl.textContent = formatRupiah(totalPengajuan);
            nominalPengajuanInput.value = Math.round(totalPengajuan);
            validateNominal();
        }
        
        function validateNominal() {
            const totalDihitung = parseFloat((totalPengajuanEl.textContent || 'Rp 0').replace(/[^0-9]/g, '')) || 0;
            const nominalFinal = parseFloat(nominalPengajuanInput.value) || 0;
            if (nominalFinal > Math.ceil(totalDihitung)) {
                errorNominalEl.classList.remove('d-none');
                btnSubmit.disabled = true;
            } else {
                errorNominalEl.classList.add('d-none');
                btnSubmit.disabled = nominalFinal <= 0;
            }
        }

        if (tableBody) {
            tableBody.addEventListener('input', e => { if (e.target.classList.contains('progress-input')) calculateTotals(); });
            
            tableBody.addEventListener('change', function(e) {
                if (e.target.classList.contains('lunas-checkbox')) {
                    const tr = e.target.closest('tr');
                    const progressInput = tr.querySelector('.progress-input');
                    if (!progressInput) return;
                    
                    const maxProgress = parseFloat(progressInput.max);
                    if (e.target.checked) {
                        progressInput.value = maxProgress.toFixed(2);
                        progressInput.disabled = true;
                    } else {
                        progressInput.value = '';
                        progressInput.disabled = false;
                    }
                    calculateTotals(); 
                }
            });
        }
        
        if (nominalPengajuanInput) nominalPengajuanInput.addEventListener('input', validateNominal);
        calculateTotals();

        // [PERBAIKAN TOTAL] Logika upload file dibuat lebih robust
        function updatePreviewsAndInput() {
            // 1. Sinkronkan `fileInput` dengan `dataTransfer`
            fileInput.files = dataTransfer.files;

            // 2. Kosongkan container dan render ulang semua preview dari `dataTransfer`
            previewContainer.innerHTML = '';
            Array.from(dataTransfer.files).forEach(file => {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.dataset.filename = file.name;

                const removeBtnHTML = `<button type="button" class="remove-btn" title="Hapus">&times;</button>`;
                
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewItem.innerHTML = `<img src="${e.target.result}" alt="${file.name}">${removeBtnHTML}`;
                    }
                    reader.readAsDataURL(file);
                } else { // Untuk PDF dan file lainnya
                     let iconClass = 'fas fa-file-alt'; // Icon default
                     let colorClass = 'text-secondary';
                     if (file.type.includes('pdf')) {
                         iconClass = 'fas fa-file-pdf';
                         colorClass = 'text-danger';
                     }
                     previewItem.innerHTML = `
                        <div class="file-icon-preview d-flex flex-column align-items-center justify-content-center h-100">
                            <i class="${iconClass} fa-3x ${colorClass}"></i>
                            <small class="text-muted mt-2 text-truncate" style="max-width: 90px;">${file.name}</small>
                        </div>
                        ${removeBtnHTML}`;
                }
                previewContainer.appendChild(previewItem);
            });
        }

        // Fungsi untuk MENAMBAH file ke `dataTransfer`
        function addFiles(newFiles) {
            Array.from(newFiles).forEach(newFile => {
                // Mencegah duplikat file dengan nama yang sama
                if (!Array.from(dataTransfer.files).some(f => f.name === newFile.name && f.size === newFile.size)) {
                    dataTransfer.items.add(newFile);
                }
            });
            // Setelah data diubah, panggil fungsi update utama
            updatePreviewsAndInput();
        }

        // Event listeners untuk upload
        if (uploadCard) {
            uploadCard.addEventListener('click', () => fileInput.click());
            uploadCard.addEventListener('dragover', e => { e.preventDefault(); uploadCard.classList.add('is-dragging'); });
            uploadCard.addEventListener('dragleave', () => uploadCard.classList.remove('is-dragging'));
            uploadCard.addEventListener('drop', e => { 
                e.preventDefault(); 
                uploadCard.classList.remove('is-dragging'); 
                addFiles(e.dataTransfer.files); 
            });
            fileInput.addEventListener('change', e => {
                if (e.target.files.length > 0) {
                    addFiles(e.target.files);
                }
                // Reset input value agar 'change' event bisa ter-trigger lagi untuk file yang sama
                e.target.value = '';
            });
        }

        // Event listener untuk tombol hapus pada preview
        previewContainer.addEventListener('click', function(e){
            if (e.target.classList.contains('remove-btn')) {
                const previewItem = e.target.closest('.preview-item');
                const fileNameToRemove = previewItem.dataset.filename;
                
                // Buat daftar file baru tanpa file yang akan dihapus
                const newDt = new DataTransfer();
                Array.from(dataTransfer.files).forEach(file => {
                    if (file.name !== fileNameToRemove) {
                        newDt.items.add(file);
                    }
                });

                // Ganti `dataTransfer` lama dengan yang baru
                dataTransfer.items.clear();
                for (const file of newDt.files) dataTransfer.items.add(file);
                
                // Panggil fungsi update utama
                updatePreviewsAndInput();
            }
        });
    });
</script>

</body>
</html>