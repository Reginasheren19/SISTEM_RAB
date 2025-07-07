<?php
session_start();
include("../config/koneksi_mysql.php");

// =========================================================================
$logged_in_user_id = $_SESSION['id_user'] ?? 0;
$user_role = strtolower($_SESSION['role'] ?? 'guest');
$can_see_all_roles = ['admin', 'super admin', 'direktur'];
$is_editor_role = in_array($user_role, $can_see_all_roles);

if ($logged_in_user_id === 0) {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}
// =========================================================================

function getStatusClass($status) {
    switch (strtolower(trim($status))) {
        case 'disetujui': return 'bg-success text-white';
        case 'dibayar':   return 'bg-primary text-white';
        case 'ditolak':   return 'bg-danger text-white';
        case 'diajukan':  return 'bg-warning text-dark';
        default:          return 'bg-secondary text-white';
    }
}

// Query utama untuk daftar pengajuan yang sudah dibuat
$sql = "SELECT 
    pu.id_pengajuan_upah, pu.tanggal_pengajuan, pu.total_pengajuan, pu.status_pengajuan, pu.keterangan,
    ru.id_rab_upah, mpe.nama_perumahan, mpr.kavling, mm.nama_mandor, pu.bukti_bayar,
    (SELECT MAX(p.id_pengajuan_upah) FROM pengajuan_upah p WHERE p.id_rab_upah = ru.id_rab_upah) AS id_pengajuan_terakhir
    FROM pengajuan_upah pu
    LEFT JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah
    LEFT JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
    LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
    LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
";
if ($user_role === 'pj proyek') {
    $safe_user_id = (int) $logged_in_user_id;
    $sql .= " WHERE mpr.id_user_pj = $safe_user_id";
} 
else if (!in_array($user_role, $can_see_all_roles)) {
    $sql .= " WHERE 1=0";
}
$sql .= " GROUP BY pu.id_pengajuan_upah ORDER BY pu.id_pengajuan_upah DESC";
$pengajuanresult = mysqli_query($koneksi, $sql);
if (!$pengajuanresult) { die("Query Error (Main): " . mysqli_error($koneksi)); }

// [PERBAIKAN TOTAL] Query untuk modal, sekarang ikut mengambil status dan total pembayaran yang akurat
$rabUpahUntukModalSql = "
    SELECT 
        ru.id_rab_upah, ru.id_proyek, ru.total_rab_upah,
        mpe.nama_perumahan, mpr.kavling, mm.nama_mandor,
        pu_last.status_pengajuan AS status_terakhir,
        (SELECT SUM(dpu.nilai_upah_diajukan) 
         FROM detail_pengajuan_upah dpu
         JOIN pengajuan_upah pu_paid ON dpu.id_pengajuan_upah = pu_paid.id_pengajuan_upah
         WHERE pu_paid.id_rab_upah = ru.id_rab_upah AND pu_paid.status_pengajuan = 'dibayar'
        ) AS total_dibayar
    FROM rab_upah ru
    LEFT JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
    LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
    LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
    LEFT JOIN (
        SELECT id_rab_upah, MAX(id_pengajuan_upah) as max_id
        FROM pengajuan_upah
        GROUP BY id_rab_upah
    ) AS last_pengajuan ON ru.id_rab_upah = last_pengajuan.id_rab_upah
    LEFT JOIN pengajuan_upah pu_last ON last_pengajuan.max_id = pu_last.id_pengajuan_upah
";
if ($user_role === 'pj proyek') {
    $safe_user_id = (int) $logged_in_user_id;
    $rabUpahUntukModalSql .= " WHERE mpr.id_user_pj = $safe_user_id";
}
$rabUpahUntukModalSql .= " ORDER BY ru.id_rab_upah DESC";
$rabUpahUntukModalResult = mysqli_query($koneksi, $rabUpahUntukModalSql);
if (!$rabUpahUntukModalResult) { die("Query Error (Modal RAB): " . mysqli_error($koneksi)); }

$flash_message = $_SESSION['flash_message'] ?? null;
if (isset($_SESSION['flash_message'])) {
    unset($_SESSION['flash_message']);
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
              <h3 class="fw-bold mb-3">Pengajuan Upah RAB</h3>
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
                  <a href="#">Pengajuan Upah RAB</a>
                </li>
              </ul>
            </div>

<div class="row">
  <div class="col-md-12">
    <div class="card">
                                <div class="card-header d-flex align-items-center">
                                    <h4 class="card-title">Pengajuan Upah RAB</h4>
                                    <?php if ($user_role === 'pj proyek'): // Tombol tambah hanya untuk PJ Proyek ?>
                                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#selectProyekModal">
                                        <i class="fa fa-plus"></i> Buat Pengajuan Baru
                                    </button>
                                    <?php endif; ?>
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
                                        <tr>
                                            <th style="width: 5%;">ID</th>
                                            <th style="width: 20%;">Proyek</th>
                                            <th style="width: 10%;">Mandor</th>
                                            <th style="width: 10%;">Tanggal</th>
                                            <th style="width: 15%;">Total Pengajuan</th>
                                            <th style="width: 15%;">Status</th>
                                            <th style="width: 20%;" class="text-center">Aksi</th>
                                        </tr>                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (mysqli_num_rows($pengajuanresult) > 0): ?>
                                            <?php while ($row = mysqli_fetch_assoc($pengajuanresult)):
                                                $is_deletable = ($row['id_pengajuan_upah'] == $row['id_pengajuan_terakhir'] && in_array($row['status_pengajuan'], ['diajukan', 'ditolak']));
$formattedpengajuan = 'PU' . $row['id_pengajuan_upah'];

                                            ?>
                                            <tr>
<td class="text-center"><?= htmlspecialchars($formattedpengajuan) ?></td>
                                                <td><?= htmlspecialchars($row['nama_perumahan']) . ' - ' . htmlspecialchars($row['kavling']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_mandor']) ?></td>
                                                <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal_pengajuan'])) ?></td>
                                                <td class="text-end">Rp <?= number_format($row['total_pengajuan'], 0, ',', '.') ?></td>
<td>
    <?php
    // [LOGIKA BARU] Tentukan apakah dropdown bisa diedit.
    // Bisa diedit jika role-nya adalah editor DAN statusnya belum 'dibayar'.
    $is_editable = $is_editor_role && strtolower($row['status_pengajuan']) !== 'dibayar';
    ?>
    <select class="form-select status-select <?= getStatusClass($row['status_pengajuan']) ?>" 
            data-id="<?= htmlspecialchars($row['id_pengajuan_upah']) ?>" 
            data-current-status="<?= strtolower($row['status_pengajuan']) ?>" 
            <?= !$is_editable ? 'disabled' : '' ?>
    >
        
        <?php $current_status = strtolower($row['status_pengajuan']); ?>

        <option value="diajukan" <?= $current_status == 'diajukan' ? 'selected' : '' ?>
            <?= !in_array($current_status, ['diajukan', 'ditolak']) ? 'disabled' : '' ?>>
            Diajukan
        </option>
        
        <option value="disetujui" <?= $current_status == 'disetujui' ? 'selected' : '' ?>
            <?= !in_array($current_status, ['diajukan', 'ditolak']) ? 'disabled' : '' ?>>
            Disetujui
        </option>
        
        <option value="ditolak" <?= $current_status == 'ditolak' ? 'selected' : '' ?>
            <?= !in_array($current_status, ['diajukan', 'ditolak']) ? 'disabled' : '' ?>>
            Ditolak
        </option>
        
        <option value="dibayar" <?= $current_status == 'dibayar' ? 'selected' : '' ?>
            <?= $current_status !== 'disetujui' ? 'disabled' : '' ?>>
            Dibayar
        </option>

    </select>
</td>
                                                    <td class="text-center">
                                                        <!-- Tombol Detail bisa dilihat semua orang -->
                                                        <a href="get_pengajuan_upah.php?id_pengajuan_upah=<?= urlencode($row['id_pengajuan_upah']) ?>" class="btn btn-info btn-sm mx-1" title="Lihat Detail">Detail</a>
                                                        
                                                        <!-- [PERBAIKAN] Tombol Update & Delete hanya untuk PJ Proyek -->
                                                        <?php if ($user_role === 'pj proyek' && in_array($current_status, ['diajukan', 'ditolak'])): ?>
                                                            <a href="update_pengajuan_upah.php?id_pengajuan_upah=<?= urlencode($row['id_pengajuan_upah']) ?>" class="btn btn-warning btn-sm mx-1" title="Update">Update</a>
                                                            <?php if($is_deletable): ?>
                                                                <button class="btn btn-danger btn-sm delete-btn mx-1" data-id="<?= htmlspecialchars($row['id_pengajuan_upah']) ?>" title="Hapus">Delete</button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                            </tr>
                                            <?php endwhile; ?>
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
    </div>
</div>

    <!-- Modal: Pilih Proyek -->
    <div class="modal fade" id="selectProyekModal" tabindex="-1" aria-labelledby="selectProyekModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="selectProyekModalLabel">Pilih Proyek RAB</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <table id="tabel-proyek-modal" class="display table table-striped table-hover" style="width:100%">
                        <thead><tr><th>ID RAB</th><th>Proyek</th><th>Mandor</th><th class="text-end">Total RAB</th><th class="text-center">Aksi</th></tr></thead>
                        <tbody>
                        <?php if (mysqli_num_rows($rabUpahUntukModalResult) > 0): mysqli_data_seek($rabUpahUntukModalResult, 0); ?>
                            <?php while ($rab = mysqli_fetch_assoc($rabUpahUntukModalResult)):
                                // [PERBAIKAN TOTAL] Logika untuk menonaktifkan tombol
                                $status_terakhir = strtolower($rab['status_terakhir'] ?? '');
                                $is_blocked = in_array($status_terakhir, ['diajukan', 'ditolak']);
                                
                                $total_rab = (float)($rab['total_rab_upah'] ?? 0);
                                $total_dibayar = (float)($rab['total_dibayar'] ?? 0);
                                $is_lunas = ($total_rab > 0) && ($total_dibayar >= $total_rab);
                            ?>
                                <tr>
                                    <td><?= 'RABP' . $rab['id_rab_upah'] ?></td>
                                    <td><?= htmlspecialchars($rab['nama_perumahan'] . ' - ' . $rab['kavling']) ?></td>
                                    <td><?= htmlspecialchars($rab['nama_mandor']) ?></td>
                                    <td class="text-end"><?= 'Rp ' . number_format($rab['total_rab_upah'], 0, ',', '.') ?></td>
                                    <td class="text-center">
                                        <?php if ($is_lunas): ?>
                                            <span class="badge bg-primary">Lunas</span>
                                        <?php elseif ($is_blocked): ?>
                                            <button type="button" class="btn btn-secondary btn-sm disabled-pilih-btn" data-bs-toggle="modal" data-bs-target="#pengajuanBlockedModal" data-status-terakhir="<?= htmlspecialchars($status_terakhir) ?>">Pilih</button>
                                        <?php else: ?>
                                            <a href="detail_pengajuan_upah.php?id_rab_upah=<?= $rab['id_rab_upah'] ?>" class="btn btn-success btn-sm">Pilih</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<!-- [FIXED] Modal Konfirmasi Status (untuk 'Disetujui', 'Diajukan') -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Perubahan Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Anda yakin ingin mengubah status pengajuan ini menjadi <strong id="new-status-text-modal"></strong>?</p>
            </div>
            <div class="modal-footer">
                <form id="statusUpdateForm">
                    <input type="hidden" name="id_pengajuan_upah" id="update_id_pengajuan">
                    <input type="hidden" name="new_status" id="update_new_status">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Ya, Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- [FIXED] Modal Alasan Penolakan (untuk 'Ditolak') -->
<div class="modal fade" id="rejectionReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Alasan Penolakan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rejectionForm">
                    <input type="hidden" name="id_pengajuan_upah" id="rejection_id_pengajuan">
                    <input type="hidden" name="new_status" value="ditolak">
                    <p>Anda akan mengubah status menjadi <strong>Ditolak</strong>. Harap masukkan alasan penolakan.</p>
                    <div class="mb-3">
                        <label for="rejectionReasonText" class="form-label">Alasan Penolakan:</label>
                        <textarea class="form-control" name="keterangan" id="rejectionReasonText" rows="3" placeholder="Contoh: Perhitungan progress tidak sesuai..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="submitRejectionBtn">Tolak Pengajuan</button>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Notifikasi Pengajuan Diblokir -->
    <div class="modal fade" id="pengajuanBlockedModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title text-warning"><i class="fas fa-exclamation-triangle"></i> Pengajuan Terkunci</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body"><p>Anda tidak dapat membuat pengajuan baru untuk proyek ini.</p><p class="mb-0"><strong>Alasan:</strong> <span id="blockReasonText"></span></p></div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mengerti</button></div>
        </div>
      </div>
    </div>

<!-- [FIXED] Modal Upload Bukti Bayar (untuk 'Dibayar') -->
<div class="modal fade" id="uploadBuktiBayarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Upload Bukti Pembayaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm" enctype="multipart/form-data">
                    <input type="hidden" name="id_pengajuan_upah" id="payment_id_pengajuan">
                    <input type="hidden" name="new_status" value="dibayar">
                    <p>Anda akan mengubah status menjadi <strong>Dibayar</strong>. Harap lampirkan bukti transfer.</p>
                    <div class="mb-3">
                        <label for="paymentProof" class="form-label">File Bukti (JPG, PNG, PDF):</label>
                        <input class="form-control" type="file" name="bukti_bayar" id="paymentProof" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="submitPaymentBtn">Simpan & Tandai Dibayar</button>
            </div>
        </div>
    </div>
</div>

    <!-- [PERBAIKAN] Modal Notifikasi Pengajuan Diblokir -->
    <div class="modal fade" id="pengajuanBlockedModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title text-warning"><i class="fas fa-exclamation-triangle"></i> Pengajuan Terkunci</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Anda tidak dapat membuat pengajuan baru untuk proyek ini.</p>
            <p class="mb-0"><strong>Alasan:</strong> <span id="blockReasonText"></span></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mengerti</button>
          </div>
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
<!-- [FIXED] SCRIPT UTAMA -->
<script>
$(document).ready(function() {
    $('#basic-datatables').DataTable();
    $('#tabel-proyek-modal').DataTable();

    const statusUpdateModal = new bootstrap.Modal(document.getElementById('statusUpdateModal'));
    const rejectionModal = new bootstrap.Modal(document.getElementById('rejectionReasonModal'));
    const uploadBuktiModal = new bootstrap.Modal(document.getElementById('uploadBuktiBayarModal'));

    let currentSelectElement;

    // Saat dropdown status diubah
    $('#basic-datatables').on('change', '.status-select', function() {
        currentSelectElement = $(this);
        const newStatus = currentSelectElement.val();
        const originalStatus = currentSelectElement.data('current-status');
        const pengajuanId = currentSelectElement.data('id');

        // Reset dropdown ke nilai awal untuk mencegah perubahan UI sebelum konfirmasi
        currentSelectElement.val(originalStatus);

        if (newStatus === originalStatus) return;

        if (newStatus === 'dibayar') {
            $('#payment_id_pengajuan').val(pengajuanId);
            $('#paymentForm')[0].reset(); // Bersihkan form
            uploadBuktiModal.show();
        } else if (newStatus === 'ditolak') {
            $('#rejection_id_pengajuan').val(pengajuanId);
            $('#rejectionForm')[0].reset(); // Bersihkan form
            rejectionModal.show();
        } else { // Untuk 'disetujui' atau 'diajukan'
            $('#update_id_pengajuan').val(pengajuanId);
            $('#update_new_status').val(newStatus);
            $('#new-status-text-modal').text(`"${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}"`);
            statusUpdateModal.show();
        }
    });

    // Fungsi AJAX universal untuk mengirim data (termasuk file)
    function submitUpdate(formData) {
        $.ajax({
            url: 'update_status_pengajuan.php',
            type: 'POST',
            data: formData,
            processData: false, // Wajib untuk FormData
            contentType: false, // Wajib untuk FormData
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    swal("Berhasil!", response.message, "success").then(() => location.reload());
                } else {
                    swal("Gagal!", response.message, "error");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                swal("Error!", "Terjadi kesalahan pada server: " + textStatus, "error");
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
            }
        });
    }

    // Submit dari modal status umum
    $('#statusUpdateForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        submitUpdate(formData);
        statusUpdateModal.hide();
    });

    // Submit dari modal penolakan
    $('#submitRejectionBtn').on('click', function() {
        const form = $('#rejectionForm')[0];
        if (form.checkValidity()) {
            const formData = new FormData(form);
            submitUpdate(formData);
            rejectionModal.hide();
        } else {
            swal("Oops!", "Alasan penolakan tidak boleh kosong.", "error");
        }
    });

    // Submit dari modal pembayaran
    $('#submitPaymentBtn').on('click', function() {
        const form = $('#paymentForm')[0];
        if (form.checkValidity()) {
            const formData = new FormData(form);
            submitUpdate(formData);
            uploadBuktiModal.hide();
        } else {
            swal("Oops!", "Anda harus memilih file bukti pembayaran.", "error");
        }
    });

    // Logika untuk tombol delete dengan SweetAlert
    $('#basic-datatables').on('click', '.delete-btn', function(e) {
        e.preventDefault();
        const pengajuanId = $(this).data('id');
        swal({
            title: "Apakah Anda Yakin?",
            text: "Data pengajuan yang dihapus tidak dapat dikembalikan.",
            icon: "warning",
            buttons:{
                cancel: {text: "Batal", value: null, visible: true, className: "btn btn-secondary", closeModal: true},
                confirm: {text: "Ya, Hapus", value: true, visible: true, className: "btn btn-danger", closeModal: true}
            }
        }).then((willDelete) => {
            if (willDelete) {
                window.location.href = `delete_pengajuan_upah.php?id_pengajuan_upah=${pengajuanId}`;
            }
        });
    });

        // Script untuk menampilkan pesan yang dinamis di modal notifikasi
        $('#tabel-proyek-modal').on('click', '.disabled-pilih-btn', function() {
            const status = $(this).data('status-terakhir');
            let reasonText = 'Masih ada pengajuan termin sebelumnya yang belum selesai.';
            if (status === 'diajukan') {
                reasonText = 'Masih ada termin sebelumnya yang berstatus <strong>"Diajukan"</strong> dan menunggu persetujuan Direktur.';
            } else if (status === 'ditolak') {
                reasonText = 'Termin sebelumnya berstatus <strong>"Ditolak"</strong>. Harap perbaiki atau hubungi Admin.';
            }
            $('#blockReasonText').html(reasonText);
        });

    // Logika untuk notifikasi pop-up dari session
    <?php if ($flash_message): ?>
    swal({
        title: "<?= ($flash_message['type'] == 'success') ? 'Berhasil!' : 'Gagal!'; ?>",
        text: "<?= addslashes($flash_message['message']); ?>",
        icon: "<?= $flash_message['type']; ?>",
        button: { text: "OK", className: "btn btn-primary" },
    });
    <?php endif; ?>
});
</script>
</body>
</html>
