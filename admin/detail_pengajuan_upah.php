<?php

session_start();

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
// [PERBAIKAN KEAMANAN] Menggunakan Prepared Statements
$sql_rab = "SELECT 
              tr.id_rab_upah, tr.tanggal_mulai, tr.tanggal_selesai,
              CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS pekerjaan,
              mpr.type_proyek, u.nama_lengkap AS pj_proyek, mpe.lokasi, mm.nama_mandor
            FROM rab_upah tr
            JOIN master_proyek mpr ON tr.id_proyek = mpr.id_proyek
            LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
            LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
            LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
            WHERE tr.id_rab_upah = ?";
            
$stmt_rab = mysqli_prepare($koneksi, $sql_rab);
mysqli_stmt_bind_param($stmt_rab, 'i', $id_rab_upah);
mysqli_stmt_execute($stmt_rab);
$rab_result = mysqli_stmt_get_result($stmt_rab);
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

// Query detail item pekerjaan pada RAB menggunakan Prepared Statements
// [PERBAIKAN] Query untuk rincian pekerjaan, sekarang menggunakan Prepared Statement
$sql_detail = "SELECT d.id_detail_rab_upah, k.nama_kategori, mp.uraian_pekerjaan, d.volume AS volume_rab, ms.nama_satuan, d.sub_total, d.harga_satuan FROM detail_rab_upah d LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori LEFT JOIN master_satuan ms ON mp.id_satuan = ms.id_satuan WHERE d.id_rab_upah = ? ORDER BY d.nomor_urut_kategori ASC, d.id_detail_rab_upah ASC";
$stmt_detail = mysqli_prepare($koneksi, $sql_detail);
mysqli_stmt_bind_param($stmt_detail, 'i', $id_rab_upah);
mysqli_stmt_execute($stmt_detail);
$detail_result = mysqli_stmt_get_result($stmt_detail);

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
        .preview-item { position: relative; width: 120px; height: 120px; border-radius: 0.5rem; overflow: hidden; border: 1px solid #dee2e6; background-color: #f8f9fa; }
        .preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .file-icon-preview { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 10px; box-sizing: border-box; }
        .file-icon-preview .file-icon { font-size: 2.5rem; color: #adb5bd; }
        .file-icon-preview .file-name { font-size: 0.75rem; color: #6c757d; margin-top: 0.5rem; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: center; }
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
              <h3 class="fw-bold mb-3">Form Pengajuan RAB Upah</h3>
            </div>

                                <!-- [BARU] Tempat untuk Notifikasi Flash Message -->
                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?= $_SESSION['flash_message']['type'] == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>
            
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
                                            <th style="width:12%;" class="text-center">Satuan</th> <!-- Kolom Satuan -->
                                            <th style="width:10%;" class="text-center">Volume</th> <!-- Kolom Volume -->
                                            <th style="width:12%;" class="text-center">Jumlah (Rp)</th>
                                            <th style="width:12%;" class="text-center">Progress Lalu (%)</th>
                                            <th style="width:15%;" class="text-center">Progress Saat Ini (%)</th>
                                            <th style="width:15%;" class="text-center">Nilai Pengajuan (Rp)</th>
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
                                                    echo "<tr class='table-primary fw-bold'><td class='text-center'>" . toRoman($noKategori) . "</td><td colspan='7'>" . htmlspecialchars($row['nama_kategori']) . "</td></tr>";
                                                    $prevKategori = $row['nama_kategori']; $noPekerjaan = 1;
                                                }
                                                $idDetail = $row['id_detail_rab_upah'];
                                                $progressLalu = getProgressLaluPersen($koneksi, $idDetail);
                                                $sisaProgress = 100 - $progressLalu;
                                                $isLunas = $sisaProgress <= 0.001;
                                        ?>
                                            <tr>
                                                <td class='text-center'><?= $noPekerjaan ?></td>
<td><?= htmlspecialchars($row['uraian_pekerjaan']) ?></td>
<td class="text-center"><?= number_format($row['volume_rab'], 2, ',', '.') ?></td>

                                                <td class='text-center'><?= htmlspecialchars($row['nama_satuan']) ?></td> <!-- Kolom Satuan -->
                                                </td> <!-- Kolom Volume -->
                                                <td class='text-end'><?= number_format($row['sub_total'], 0, ',', '.') ?></td>
                                                <td class='text-center'><?= number_format($progressLalu, 2, ',', '.') ?>%</td>
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
                                    <tfoot>
                                        <tr class='table-light fw-bolder'>
                                            <td colspan="7" class='text-end'>TOTAL NILAI RAB</td>
                                            <td class='text-end'>Rp <?= number_format($grandTotalRAB, 0, ',', '.') ?></td>
                                        </tr>
                                        <tr class='table-success fw-bolder'>
                                            <td colspan="7" class='text-end'>TOTAL PENGAJUAN SAAT INI</td>
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
                                            <label for="file-input-standar" class="form-label fw-bold">Upload Bukti Pekerjaan:</label>
                                            <input class="form-control" type="file" id="file-input-standar" name="bukti_pengajuan[]" multiple accept="image/*,application/pdf">
                                            <small class="form-text text-muted">Anda bisa memilih lebih dari satu file.</small>
                                        </div>
                                        <div id="preview-container" class="mt-3 d-flex flex-wrap gap-2"></div>

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
<script>
document.addEventListener("DOMContentLoaded", function() {

    // ========================================================
    // Definisi Variabel Elemen Halaman
    // ========================================================
    const tableBody = document.querySelector("#tblDetailRAB tbody");
    const totalPengajuanEl = document.getElementById('total-pengajuan-saat-ini');
    const nominalPengajuanInput = document.getElementById('nominal-pengajuan');
    const errorNominalEl = document.getElementById('error-nominal');
    const btnSubmit = document.getElementById('btn-submit');
    const fileInput = document.getElementById('file-input-standar');
    const previewContainer = document.getElementById('preview-container');

    // ========================================================
    // BAGIAN FUNGSI KALKULASI OTOMATIS
    // ========================================================

    // Fungsi untuk format angka ke Rupiah
    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka || 0);
    }

    // Fungsi utama untuk menghitung semua total
    function calculateTotals() {
        let totalPengajuan = 0;
        document.querySelectorAll('.progress-input').forEach(input => {
            if (input.disabled) return; // Abaikan input yang statusnya sudah lunas dari awal

            const subtotal = parseFloat(input.dataset.subtotal) || 0;
            let progressDiajukan = parseFloat(input.value) || 0;
            const maxProgress = parseFloat(input.max);

            // Validasi input progress agar tidak melebihi sisa
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

        // Update tampilan total dan input nominal final
        totalPengajuanEl.textContent = formatRupiah(totalPengajuan);
        nominalPengajuanInput.value = Math.round(totalPengajuan);
        validateNominal();
    }

    // Fungsi untuk validasi tombol submit
    function validateNominal() {
        const nominalFinal = parseFloat(nominalPengajuanInput.value) || 0;
        // Tombol submit hanya aktif jika ada nominal yang diajukan
        btnSubmit.disabled = nominalFinal <= 0;
    }

    // Event listener untuk tabel progress
    if (tableBody) {
        // Memicu kalkulasi saat angka diinput
        tableBody.addEventListener('input', e => {
            if (e.target.classList.contains('progress-input')) {
                calculateTotals();
            }
        });
        
        // Memicu kalkulasi saat checkbox "Lunas" dicentang
        tableBody.addEventListener('change', function(e) {
            if (e.target.classList.contains('lunas-checkbox')) {
                const tr = e.target.closest('tr');
                const progressInput = tr.querySelector('.progress-input');
                if (!progressInput) return;

                const maxProgress = parseFloat(progressInput.max);
                if (e.target.checked) {
                    progressInput.value = maxProgress.toFixed(2);
                    progressInput.readOnly = true;
                    progressInput.style.backgroundColor = '#e9ecef';
                } else {
                    progressInput.value = '';
                    progressInput.readOnly = false;
                    progressInput.style.backgroundColor = '#ffffff';
                }
                calculateTotals();
            }
        });
    }

    // Panggil kalkulasi saat halaman pertama kali dimuat
    calculateTotals();


    // ========================================================
    // BAGIAN UNTUK PREVIEW FILE UPLOAD
    // ========================================================
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            previewContainer.innerHTML = ''; // Kosongkan preview lama setiap kali ada perubahan
            if (!this.files || this.files.length === 0) return;

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
                previewContainer.appendChild(previewItem);
            });
        });
    }

});
</script>

</body>
</html>