<?php
// FILE: update_pengajuan_upah.php (VERSI FINAL - LENGKAP & AMAN)

session_start();
include("../config/koneksi_mysql.php");

// Aktifkan error reporting untuk development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fungsi untuk mengambil progres termin sebelumnya
function getProgressLalu($koneksi, $id_detail_rab_upah, $id_pengajuan_to_exclude) {
    $query = "SELECT SUM(dpu.progress_pekerjaan) AS total_progress 
              FROM detail_pengajuan_upah dpu 
              JOIN pengajuan_upah pu ON dpu.id_pengajuan_upah = pu.id_pengajuan_upah 
              WHERE dpu.id_detail_rab_upah = ? AND pu.id_pengajuan_upah != ? 
              AND pu.status_pengajuan != 'ditolak'";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id_detail_rab_upah, $id_pengajuan_to_exclude);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return (float)($data['total_progress'] ?? 0);
}

function toRoman($num) { $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1]; $result = ''; foreach ($map as $roman => $value) { while ($num >= $value) { $result .= $roman; $num -= $value; } } return $result; }

// Validasi & Ambil Data Utama
if (!isset($_GET['id_pengajuan_upah']) || !is_numeric($_GET['id_pengajuan_upah'])) {
    die("Akses tidak sah. ID Pengajuan Upah tidak ditemukan.");
}
$id_pengajuan_upah = (int)$_GET['id_pengajuan_upah'];

// Mengambil data pengajuan utama
$sql_pengajuan = "SELECT pu.*, CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS pekerjaan, mpr.type_proyek, mpe.lokasi, mm.nama_mandor, u.nama_lengkap AS pj_proyek FROM pengajuan_upah pu LEFT JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah LEFT JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user WHERE pu.id_pengajuan_upah = ?";
$stmt_pengajuan = mysqli_prepare($koneksi, $sql_pengajuan);
mysqli_stmt_bind_param($stmt_pengajuan, 'i', $id_pengajuan_upah);
mysqli_stmt_execute($stmt_pengajuan);
$pengajuan_result = mysqli_stmt_get_result($stmt_pengajuan);
if (mysqli_num_rows($pengajuan_result) == 0) { die("Data Pengajuan Upah dengan ID $id_pengajuan_upah tidak ditemukan."); }
$pengajuan_info = mysqli_fetch_assoc($pengajuan_result);
$id_rab_upah = (int)$pengajuan_info['id_rab_upah'];
mysqli_stmt_close($stmt_pengajuan);

if (!in_array($pengajuan_info['status_pengajuan'], ['diajukan', 'ditolak'])) {
    die("Pengajuan dengan status '" . htmlspecialchars($pengajuan_info['status_pengajuan']) . "' tidak dapat diupdate lagi.");
}

// Fetch data pendukung (termin, RAB items, progress, bukti)
$sql_termin = "SELECT COUNT(id_pengajuan_upah) AS urutan FROM pengajuan_upah WHERE id_rab_upah = ? AND id_pengajuan_upah <= ?";
$stmt_termin = mysqli_prepare($koneksi, $sql_termin);
mysqli_stmt_bind_param($stmt_termin, 'ii', $id_rab_upah, $id_pengajuan_upah);
mysqli_stmt_execute($stmt_termin);
$termin_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_termin));
$termin_ke = $termin_data['urutan'] ?? 0;
mysqli_stmt_close($stmt_termin);

// [PERBAIKAN] Query untuk rincian pekerjaan, sekarang menggunakan urutan yang benar
$sql_rab_items = "SELECT d.id_detail_rab_upah, k.nama_kategori, mp.uraian_pekerjaan, d.volume AS volume_rab, ms.nama_satuan, d.sub_total, d.harga_satuan FROM detail_rab_upah d LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori LEFT JOIN master_satuan ms ON mp.id_satuan = ms.id_satuan WHERE d.id_rab_upah = ? ORDER BY d.nomor_urut_kategori ASC, d.id_detail_rab_upah ASC";
$stmt_rab_items = mysqli_prepare($koneksi, $sql_rab_items);
mysqli_stmt_bind_param($stmt_rab_items, 'i', $id_rab_upah);
mysqli_stmt_execute($stmt_rab_items);
$rab_items_result = mysqli_stmt_get_result($stmt_rab_items);

$existing_progress = [];
$sql_detail_pengajuan = "SELECT id_detail_rab_upah, progress_pekerjaan FROM detail_pengajuan_upah WHERE id_pengajuan_upah = ?";
$stmt_detail = mysqli_prepare($koneksi, $sql_detail_pengajuan);
mysqli_stmt_bind_param($stmt_detail, 'i', $id_pengajuan_upah);
mysqli_stmt_execute($stmt_detail);
$detail_pengajuan_result = mysqli_stmt_get_result($stmt_detail);
while($row = mysqli_fetch_assoc($detail_pengajuan_result)) { $existing_progress[$row['id_detail_rab_upah']] = $row['progress_pekerjaan']; }
mysqli_stmt_close($stmt_detail);

$existing_bukti = [];
$sql_bukti = "SELECT id_bukti, nama_file, path_file FROM bukti_pengajuan_upah WHERE id_pengajuan_upah = ?";
$stmt_bukti = mysqli_prepare($koneksi, $sql_bukti);
mysqli_stmt_bind_param($stmt_bukti, 'i', $id_pengajuan_upah);
mysqli_stmt_execute($stmt_bukti);
$bukti_result = mysqli_stmt_get_result($stmt_bukti);
while($row = mysqli_fetch_assoc($bukti_result)) { $existing_bukti[] = $row; }
mysqli_stmt_close($stmt_bukti);
?>

<!DOCTYPE html>
<html lang="en">
<form id="pengajuan-form" action="proses_update_pengajuan.php" method="POST" enctype="multipart/form-data">
    </form>

</html>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Kaiadmin - Bootstrap 5 Admin Dashboard</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="assets/img/kaiadmin/favicon.ico"
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
                /* Ini membuat gambar menjadi redup saat ditandai */
        .preview-item.marked-for-deletion {
            opacity: 0.5;
            border-style: dashed;
        }

        /* INI KUNCINYA: Menyembunyikan tombol 'X' jika gambarnya sudah ditandai */
        .preview-item.marked-for-deletion .remove-btn {
            display: none;
        }

    </style>
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
              <h3 class="fw-bold mb-3">Form Update Pengajuan RAB Upah</h3>
            </div>

            <!-- [PERBAIKAN UTAMA] Atribut 'enctype' ditambahkan di sini. Inilah penyebab masalahnya. -->
            <form id="pengajuan-form" action="proses_update_pengajuan.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_pengajuan_upah" value="<?= htmlspecialchars($id_pengajuan_upah) ?>">
              
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
                                    <dt class="col-sm-4">Pekerjaan</dt><dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['pekerjaan']) ?></dd>
                                    <dt class="col-sm-4">Lokasi</dt><dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['lokasi']) ?></dd>
                                    <dt class="col-sm-4">Type Proyek</dt><dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['type_proyek']) ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">ID RAB</dt><dd class="col-sm-8">: RABP<?= htmlspecialchars($pengajuan_info['id_rab_upah']) ?></dd>
                                    <dt class="col-sm-4">Mandor</dt><dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['nama_mandor']) ?></dd>
                                    <dt class="col-sm-4">PJ Proyek</dt><dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['pj_proyek']) ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light"><h4 class="card-title mb-0">Update Detail Progress Pekerjaan</h4></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-vcenter mb-0" id="tblDetailRAB">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:5%;" class="text-center">No</th>
                                        <th>Uraian Pekerjaan</th>
                                        <th style="width:12%;" class="text-center">Jumlah (Rp)</th>
                                        <th style="width:12%;" class="text-center">Progress Lalu (%)</th>
                                        <th style="width:10%;" class="text-center">Progress Diajukan (%)</th>
                                        <th style="width:20%;" class="text-center">Nilai Pengajuan (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                  <?php
                                  $grandTotalRAB = 0;
                                  if ($rab_items_result && mysqli_num_rows($rab_items_result) > 0) {
                                      mysqli_data_seek($rab_items_result, 0);
                                      $prevKategori = null; $noKategori = 0; $noPekerjaan = 1;
                                      while ($row_rab = mysqli_fetch_assoc($rab_items_result)) {
                                          if ($prevKategori !== $row_rab['nama_kategori']) {
                                              $noKategori++;
                                              echo "<tr class='table-primary fw-bold'><td class='text-center'>" . toRoman($noKategori) . "</td><td colspan='5'>" . htmlspecialchars($row_rab['nama_kategori']) . "</td></tr>";
                                              $prevKategori = $row_rab['nama_kategori']; $noPekerjaan = 1;
                                          }
                                          $idDetailRab = $row_rab['id_detail_rab_upah'];
                                          $progressLalu = getProgressLalu($koneksi, $idDetailRab, $id_pengajuan_upah);
                                          $sisaProgress = 100 - $progressLalu;
                                          $progressSaatIni = (float)($existing_progress[$idDetailRab] ?? 0);
                                          $isLunas = $sisaProgress <= 0.001;
                                          echo "<tr><td class='text-center'>{$noPekerjaan}</td><td><span class='ms-3'>".htmlspecialchars($row_rab['uraian_pekerjaan'])."</span></td><td class='text-end'>".number_format($row_rab['sub_total'],0,',','.')."</td><td class='text-center'>".number_format($progressLalu,2,',','.')."%</td><td class='p-1'><input type='number' class='form-control form-control-sm progress-input text-center' data-subtotal='{$row_rab['sub_total']}' data-id='{$idDetailRab}' name='progress[{$idDetailRab}]' min='0' max='".number_format($sisaProgress, 2, '.', '')."' step='0.01' value='".number_format($progressSaatIni,2,'.','')."' ".($isLunas && $progressSaatIni==0 ? 'disabled placeholder="Lunas"':'')."></td><td class='text-end fw-bold nilai-pengajuan' data-id='{$idDetailRab}'>Rp 0</td></tr>";
                                          $noPekerjaan++; $grandTotalRAB += $row_rab['sub_total'];
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

             <div class="card shadow-sm">
                  <div class="card-header bg-light"><h4 class="card-title mb-0">Update Ringkasan, Bukti, & Kirim Pengajuan</h4></div>
                  <div class="card-body">
                      <div class="row">
                          <div class="col-md-7">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Bukti Tersimpan (Klik X untuk Hapus)</label>
                                            <div id="existing-preview-container" class="d-flex flex-wrap gap-2">
                                                <?php if (empty($existing_bukti)): ?>
                                                    <p class="text-muted small">Tidak ada bukti tersimpan.</p>
                                                <?php else: ?>
                                                    <?php foreach ($existing_bukti as $bukti): ?>
                                                    <div class="preview-item existing-proof" data-id-bukti="<?= $bukti['id_bukti'] ?>">
                                                        <img src="../<?= htmlspecialchars($bukti['path_file']) ?>" alt="<?= htmlspecialchars($bukti['nama_file']) ?>" onerror="this.onerror=null;this.src='https://placehold.co/100x100/EEE/31343C?text=File';">
                                                        <button type="button" class="remove-btn" title="Hapus file ini">&times;</button>
                                                    </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <input type="hidden" name="bukti_dihapus" id="bukti_dihapus">
                                        </div>

                                        <div class="mb-3">
                                            <label for="file-input-baru" class="form-label fw-bold">Upload Bukti Baru:</label>
                                            <input class="form-control" type="file" id="file-input-baru" name="bukti_baru[]" multiple accept="image/*,application/pdf">
                                        </div>
                                        <div id="new-preview-container" class="mt-2 d-flex flex-wrap gap-2"></div>

                                    </div>
                          <div class="col-md-5">
                              <div class="mb-3"><label for="tanggal_pengajuan" class="form-label fw-bold">Tanggal Pengajuan</label><input type="date" id="tanggal_pengajuan" name="tanggal_pengajuan" class="form-control" value="<?= htmlspecialchars($pengajuan_info['tanggal_pengajuan']) ?>" required></div>
                              <div class="mb-3"><label for="keterangan" class="form-label fw-bold">Keterangan</label><textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?= htmlspecialchars($pengajuan_info['keterangan'] ?? '') ?></textarea></div>
                              <div class="mb-3"><label for="nominal-pengajuan" class="form-label fw-bold">Nominal Final</label><input type="number" class="form-control form-control-lg text-end" id="nominal-pengajuan" name="nominal_pengajuan_final" value="<?= round($pengajuan_info['total_pengajuan']) ?>"><div id="error-nominal" class="form-text text-danger d-none">Nominal tidak valid.</div></div>
                              <div class="d-grid gap-2 d-md-flex justify-content-md-end"><a href="pengajuan_upah.php" class="btn btn-secondary">Kembali</a><button type="submit" id="btn-submit" class="btn btn-warning"><i class="fa fa-save"></i> Update Pengajuan</button></div>
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

   <!-- [PERBAIKAN TOTAL] Logika JavaScript di-refactor agar lebih kuat dan anti-bug -->
<script>
document.addEventListener("DOMContentLoaded", function() {

    // ========================================================
    // BAGIAN KALKULASI OTOMATIS (JANGAN DIHAPUS)
    // ========================================================
    const tableBody = document.querySelector("#tblDetailRAB tbody");
    const totalPengajuanEl = document.getElementById('total-pengajuan-saat-ini');
    const nominalPengajuanInput = document.getElementById('nominal-pengajuan');
    const errorNominalEl = document.getElementById('error-nominal');
    const btnSubmit = document.getElementById('btn-submit');

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka || 0);
    }

    function parseRupiah(rupiahStr) {
        return parseFloat(rupiahStr.replace(/[^0-9]/g, '')) || 0;
    }

    function calculateTotals() {
        let totalPengajuan = 0;
        document.querySelectorAll('.progress-input').forEach(input => {
            const subtotal = parseFloat(input.dataset.subtotal);
            let progressDiajukan = parseFloat(input.value) || 0;
            const maxProgress = parseFloat(input.max);

            if (progressDiajukan > maxProgress) {
                progressDiajukan = maxProgress;
                input.value = maxProgress.toFixed(2);
            }
            if (progressDiajukan < 0) {
                progressDiajukan = 0;
                input.value = '0.00';
            }

            const nilaiPengajuan = (progressDiajukan / 100) * subtotal;
            const nilaiCell = document.querySelector(`.nilai-pengajuan[data-id='${input.dataset.id}']`);
            if (nilaiCell) {
                nilaiCell.textContent = formatRupiah(nilaiPengajuan);
            }
            totalPengajuan += nilaiPengajuan;
        });

        totalPengajuanEl.textContent = formatRupiah(totalPengajuan);
        // Jangan update nominal final secara otomatis di halaman update, biarkan pengguna yang edit
        validateNominal();
    }

    function validateNominal() {
        const nominalFinal = parseRupiah(nominalPengajuanInput.value);
        btnSubmit.disabled = nominalFinal <= 0;
    }

    if (tableBody) {
        tableBody.addEventListener('input', e => {
            if (e.target.classList.contains('progress-input')) {
                calculateTotals();
            }
        });
    }

    if (nominalPengajuanInput) {
        // Format input nominal saat diketik
        nominalPengajuanInput.addEventListener('keyup', function(e) {
            let cursorPosition = this.selectionStart;
            let value = this.value;
            let originalLength = value.length;
            
            let number = parseRupiah(value);
            this.value = number.toLocaleString('id-ID');
            
            let newLength = this.value.length;
            cursorPosition = newLength - originalLength + cursorPosition;
            this.setSelectionRange(cursorPosition, cursorPosition);

            validateNominal();
        });
    }

    // Panggil kalkulasi saat halaman pertama kali dimuat
    calculateTotals();


    // ========================================================
    // BAGIAN MENGELOLA BUKTI (JANGAN DIHAPUS)
    // ========================================================
    const existingContainer = document.getElementById('existing-preview-container');
    const buktiDihapusInput = document.getElementById('bukti_dihapus');
    let idBuktiDihapus = [];

    if (existingContainer) {
        existingContainer.addEventListener('click', function(e) {
            // Cek apakah yang diklik adalah tombol remove di dalam preview-item
            if (e.target.classList.contains('remove-btn')) {
                const previewItem = e.target.closest('.preview-item');
                const idToRemove = previewItem.dataset.idBukti;

                if (idToRemove && !idBuktiDihapus.includes(idToRemove)) {
                    idBuktiDihapus.push(idToRemove);
                    // Beri tanda visual bahwa item akan dihapus
                    previewItem.classList.add('marked-for-deletion');
                }
                // Update nilai input hidden
                buktiDihapusInput.value = idBuktiDihapus.join(',');
            }
        });
    }

    const fileInputBaru = document.getElementById('file-input-baru');
    const newPreviewContainer = document.getElementById('new-preview-container');

    if (fileInputBaru) {
        fileInputBaru.addEventListener('change', function() {
            // Kosongkan preview lama setiap kali ada pemilihan file baru
            newPreviewContainer.innerHTML = '';
            if (!this.files) return;

            // Loop dan tampilkan preview untuk setiap file yang baru dipilih
            Array.from(this.files).forEach(file => {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        previewItem.innerHTML = `<img src="${e.target.result}" alt="${file.name}">`;
                    }
                    reader.readAsDataURL(file);
                } else {
                    // Tampilkan icon untuk file non-gambar (seperti PDF)
                    let iconClass = 'fa-file-alt';
                    if (file.type.includes('pdf')) iconClass = 'fa-file-pdf text-danger';
                    previewItem.innerHTML = `<div class="file-icon-preview"><i class="fas ${iconClass} file-icon"></i><span class="file-name" title="${file.name}">${file.name}</span></div>`;
                }
                newPreviewContainer.appendChild(previewItem);
            });
        });
    }
});
</script>
</body>
</html>

