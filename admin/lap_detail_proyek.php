<?php
session_start();
include("../config/koneksi_mysql.php");

// Proteksi & Validasi
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}
$proyek_id = isset($_GET['proyek_id']) ? (int)$_GET['proyek_id'] : 0;
if ($proyek_id === 0) {
    die("ID Proyek tidak valid.");
}

// 1. Ambil Info Utama Proyek
$info_sql = "SELECT CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek, mm.nama_mandor, u.nama_lengkap as pj_proyek FROM master_proyek mpr LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user WHERE mpr.id_proyek = ?";
$stmt_info = mysqli_prepare($koneksi, $info_sql);
mysqli_stmt_bind_param($stmt_info, 'i', $proyek_id);
mysqli_stmt_execute($stmt_info);
$proyek_info_result = mysqli_stmt_get_result($stmt_info);
$proyek_info = mysqli_fetch_assoc($proyek_info_result);
if (!$proyek_info) {
    die("Proyek tidak ditemukan.");
}

// 2. Ambil data keuangan untuk grafik
$rab_upah_info = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id_rab_upah, total_rab_upah FROM rab_upah WHERE id_proyek = $proyek_id"));
$id_rab_upah = $rab_upah_info['id_rab_upah'] ?? 0;
$total_rab = (float)($rab_upah_info['total_rab_upah'] ?? 0);

$total_realisasi = 0;
if ($id_rab_upah > 0) {
    $realisasi_result = mysqli_query($koneksi, "SELECT SUM(total_pengajuan) as total FROM pengajuan_upah WHERE id_rab_upah = $id_rab_upah AND status_pengajuan = 'dibayar'");
    $total_realisasi = (float)(mysqli_fetch_assoc($realisasi_result)['total'] ?? 0);
}
$sisa_anggaran = $total_rab - $total_realisasi;

// 3. Ambil riwayat pengajuan (termin)
$pengajuan_history = [];
if ($id_rab_upah > 0) {
    $history_sql = "SELECT id_pengajuan_upah, tanggal_pengajuan, total_pengajuan, status_pengajuan FROM pengajuan_upah WHERE id_rab_upah = $id_rab_upah ORDER BY tanggal_pengajuan DESC";
    $history_result = mysqli_query($koneksi, $history_sql);
    if($history_result){ while($row = mysqli_fetch_assoc($history_result)){ $pengajuan_history[] = $row; } }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Detail Proyek - Kaiadmin</title>
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
                <!-- Kode Navbar Header di sini -->
            </div>
        <div class="container">
          <div class="page-inner">
            <div class="page-header">
                <h3 class="fw-bold mb-3">Dashboard Rekapitulasi Proyek</h3>
                <div class="ms-auto">
                    <a href="lap_realisasi_anggaran.php" class="btn btn-secondary btn-round">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header"><h4 class="card-title"><?= htmlspecialchars($proyek_info['nama_proyek']) ?></h4></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="chart-container" style="height: 300px">
                                                <canvas id="proyekChart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <h5>Informasi Proyek</h5>
                                            <dl class="row">
                                                <dt class="col-5">PJ Proyek</dt><dd class="col-7">: <?= htmlspecialchars($proyek_info['pj_proyek']) ?></dd>
                                                <dt class="col-5">Mandor</dt><dd class="col-7">: <?= htmlspecialchars($proyek_info['nama_mandor']) ?></dd>
                                            </dl>
                                            <h5 class="mt-3">Ringkasan Keuangan</h5>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between"><span>Total Anggaran (RAB)</span> <strong class="text-primary">Rp <?= number_format($total_rab,0,',','.') ?></strong></li>
                                                <li class="list-group-item d-flex justify-content-between"><span>Total Realisasi</span> <strong class="text-success">Rp <?= number_format($total_realisasi,0,',','.') ?></strong></li>
                                                <li class="list-group-item d-flex justify-content-between"><span>Sisa Anggaran</span> <strong class="text-danger">Rp <?= number_format($sisa_anggaran,0,',','.') ?></strong></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header"><h4 class="card-title">Riwayat Pengajuan Termin</h4></div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead><tr><th class="text-center">ID Pengajuan</th><th class="text-center">Tanggal</th><th class="text-end">Total Diajukan</th><th class="text-center">Status</th></tr></thead>
                                            <tbody>
                                            <?php if (empty($pengajuan_history)): ?>
                                                <tr><td colspan="4" class="text-center text-muted">Belum ada riwayat pengajuan.</td></tr>
                                            <?php else: foreach($pengajuan_history as $hist): ?>
                                                <tr>
                                                    <td class="text-center"><a href="get_pengajuan_upah.php?id_pengajuan_upah=<?= $hist['id_pengajuan_upah'] ?>">PU<?= $hist['id_pengajuan_upah'] ?></a></td>
                                                    <td class="text-center"><?= date('d M Y', strtotime($hist['tanggal_pengajuan'])) ?></td>
                                                    <td class="text-end">Rp <?= number_format($hist['total_pengajuan'],0,',','.') ?></td>
                                                    <td class="text-center"><span class="badge bg-info"><?= ucwords($hist['status_pengajuan']) ?></span></td>
                                                </tr>
                                            <?php endforeach; endif; ?>
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
    <!-- Core JS Files -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    
    <!-- [WAJIB] Pastikan path ke Chart.js ini sudah benar -->
    <script src="assets/js/plugin/chart.js/chart.min.js"></script>
    
    <script src="assets/js/kaiadmin.min.js"></script>
    
    <!-- [PERBAIKAN] Kode untuk membuat chart dibungkus agar aman -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Pastikan elemen canvas ada sebelum menjalankan script
            const canvasElement = document.getElementById('proyekChart');
            if (canvasElement) {
                const ctx = canvasElement.getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Anggaran', 'Realisasi', 'Sisa'],
                        datasets: [{
                            label: 'Status Keuangan Proyek (Rp)',
                            data: [<?= $total_rab ?>, <?= $total_realisasi ?>, <?= $sisa_anggaran ?>],
                            backgroundColor: ['#1d7af3', '#28a745', '#dc3545'],
                            borderColor: ['#1d7af3', '#28a745', '#dc3545'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: { 
                            y: { 
                                beginAtZero: true, 
                                ticks: { 
                                    callback: function(value) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); } 
                                } 
                            } 
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
