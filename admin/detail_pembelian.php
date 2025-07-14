<?php
// Selalu mulai dengan session_start()
session_start();
include("../config/koneksi_mysql.php");

// 1. VALIDASI
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID Pembelian tidak valid atau tidak ditemukan.";
    header("Location: pencatatan_pembelian.php");
    exit();
}
$pembelian_id = (int)$_GET['id'];

// 2. AMBIL DATA INDUK
$stmt_master = $koneksi->prepare("SELECT * FROM pencatatan_pembelian WHERE id_pembelian = ?");
$stmt_master->bind_param("i", $pembelian_id);
$stmt_master->execute();
$result_master = $stmt_master->get_result();
$pembelian = $result_master->fetch_assoc();

if (!$pembelian) { /* ... handling error ... */ }

// 3. AMBIL DATA DETAIL & LOG
$sql_detail = "SELECT dp.id_detail_pembelian, dp.id_material, dp.quantity, dp.harga_satuan_pp, dp.sub_total_pp, m.nama_material, s.nama_satuan FROM detail_pencatatan_pembelian dp JOIN master_material m ON dp.id_material = m.id_material LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan WHERE dp.id_pembelian = ? ORDER BY dp.id_detail_pembelian ASC";
$stmt_detail = $koneksi->prepare($sql_detail);
$stmt_detail->bind_param("i", $pembelian_id);
$stmt_detail->execute();
$detail_items = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);

$sql_log = "SELECT log.jumlah_diterima, log.jumlah_rusak, log.catatan, log.tanggal_penerimaan, log.jenis_penerimaan, m.nama_material, s.nama_satuan FROM log_penerimaan_material log JOIN master_material m ON log.id_material = m.id_material LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan WHERE log.id_pembelian = ? ORDER BY log.tanggal_penerimaan DESC";
$stmt_log = $koneksi->prepare($sql_log);
$stmt_log->bind_param("i", $pembelian_id);
$stmt_log->execute();
$result_log = $stmt_log->get_result();

// --- [DIUBAH TOTAL] --- BAGIAN PERSIAPAN DATA UNTUK STATUS DAN TABEL ---

// Hitung semua data yang dibutuhkan untuk logika status
$stmt_total_pesanan_all = $koneksi->prepare("SELECT SUM(quantity) as total FROM detail_pencatatan_pembelian WHERE id_pembelian = ?");
$stmt_total_pesanan_all->bind_param("i", $pembelian_id);
$stmt_total_pesanan_all->execute();
$total_dipesan_all = $stmt_total_pesanan_all->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_total_pesanan_all->close();

$stmt_total_diproses = $koneksi->prepare("SELECT SUM(jumlah_diterima + jumlah_rusak) as total FROM log_penerimaan_material WHERE id_pembelian = ?");
$stmt_total_diproses->bind_param("i", $pembelian_id);
$stmt_total_diproses->execute();
$total_diproses = $stmt_total_diproses->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_total_diproses->close();

$stmt_total_rusak = $koneksi->prepare("SELECT SUM(jumlah_rusak) as total FROM log_penerimaan_material WHERE id_pembelian = ?");
$stmt_total_rusak->bind_param("i", $pembelian_id);
$stmt_total_rusak->execute();
$total_rusak_historis = $stmt_total_rusak->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_total_rusak->close();

$stmt_total_retur = $koneksi->prepare("SELECT SUM(quantity) as total FROM detail_pencatatan_pembelian WHERE id_pembelian = ? AND harga_satuan_pp = 0");
$stmt_total_retur->bind_param("i", $pembelian_id);
$stmt_total_retur->execute();
$total_sudah_diretur = $stmt_total_retur->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_total_retur->close();

$perlu_proses_retur = $total_rusak_historis > $total_sudah_diretur;

// Data untuk tabel rincian (grouped)
$penerimaan_per_item = [];
$stmt_sum_item = $koneksi->prepare("SELECT id_detail_pembelian, SUM(jumlah_diterima) as diterima FROM log_penerimaan_material WHERE id_pembelian = ? GROUP BY id_detail_pembelian");
$stmt_sum_item->bind_param("i", $pembelian_id);
$stmt_sum_item->execute();
$result_sum_item = $stmt_sum_item->get_result();
while ($row = $result_sum_item->fetch_assoc()) {
    $penerimaan_per_item[$row['id_detail_pembelian']] = $row['diterima'];
}
$grouped_items = [];
foreach ($detail_items as $item) {
    $material_name = $item['nama_material'];
    if (!isset($grouped_items[$material_name])) {
        $grouped_items[$material_name] = ['nama_material' => $item['nama_material'], 'nama_satuan' => $item['nama_satuan'], 'total_dipesan_asli' => 0, 'total_sub_total_asli' => 0, 'total_diterima' => 0, 'detail_ids' => []];
    }
    $grouped_items[$material_name]['detail_ids'][] = $item['id_detail_pembelian'];
    if ((float)$item['harga_satuan_pp'] > 0) {
        $grouped_items[$material_name]['total_dipesan_asli'] += $item['quantity'];
        $grouped_items[$material_name]['total_sub_total_asli'] += $item['sub_total_pp'];
    }
}
foreach ($grouped_items as &$group_data) {
    $total_diterima_grup = 0;
    foreach ($group_data['detail_ids'] as $detail_id) {
        $total_diterima_grup += $penerimaan_per_item[$detail_id] ?? 0;
    }
    $group_data['total_diterima'] = $total_diterima_grup;
}
unset($group_data);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Detail Pembelian</title>
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
            <h3 class="fw-bold mb-3">Detail Pembelian</h3>
            <ul class="breadcrumbs mb-3">
                <li class="nav-home"><a href="dashboard.php"><i class="icon-home"></i></a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="pencatatan_pembelian.php">Daftar Pembelian</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="">Pencatatan Pembelian Material</a></li>
            </ul>
        </div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Informasi Transaksi</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>ID Pembelian:</strong> <?= 'PB' . htmlspecialchars($pembelian['id_pembelian']) . date('Y', strtotime($pembelian['tanggal_pembelian'])) ?></p>
                <p><strong>Tanggal:</strong> <?= date("d F Y", strtotime(htmlspecialchars($pembelian['tanggal_pembelian']))) ?></p>
                <p><strong>Keterangan:</strong> <?= htmlspecialchars($pembelian['keterangan_pembelian']) ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> 
                    <?php
                                        // --- [DIUBAH] --- Logika Status yang Sudah Sinkron ---
                                        if ($total_dipesan_all <= 0) {
                                            $status_text = 'Kosong'; $badge_class = 'bg-dark';
                                        } elseif ($total_diproses >= $total_dipesan_all && !$perlu_proses_retur) {
                                            $status_text = 'Selesai'; $badge_class = 'bg-success';
                                        } elseif ($perlu_proses_retur) {
                                            $status_text = 'Perlu Tindakan Retur'; $badge_class = 'bg-warning text-dark';
                                        } else {
                                            $status_text = 'Menunggu Pengganti'; $badge_class = 'bg-primary';
                                        }
                                        ?>
                    <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                </p>
                <p><strong>Bukti Pembayaran:</strong> 
                    <?php if (!empty($pembelian['bukti_pembayaran'])): ?>
                        <a href="../uploads/bukti_pembayaran/<?= htmlspecialchars($pembelian['bukti_pembayaran']) ?>" target="_blank">Lihat Bukti</a>
                    <?php else: ?>
                        <span class="text-muted"><em>Tidak ada</em></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($perlu_proses_retur): ?>
        <div class="alert alert-warning mt-3">
            <h5 class="alert-heading">Tindakan Diperlukan!</h5>
            <p>Terdapat barang yang dilaporkan rusak atau tidak sesuai oleh PJ Proyek. Silakan proses retur untuk memesan barang pengganti.</p>
            <hr>
            <a href="add_retur.php?id=<?= $pembelian_id ?>" class="btn btn-warning" onclick="return confirm('Anda yakin ingin memproses retur dan membuat pesanan pengganti untuk item yang rusak?')">
                <i class="fa fa-sync-alt"></i> Proses Retur & Pesan Pengganti
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title">Rincian Material Dipesan</h4></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Material</th>
                        <th class="text-end">Jumlah Dipesan (Asli)</th>
                        <th class="text-end">Total Diterima Baik</th>
                        <th class="text-end">Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $nomor = 1;
                    if (!empty($grouped_items)):
                        foreach ($grouped_items as $item):
                    ?>
                        <tr>
                            <td><?= $nomor++ ?></td>
                            <td><?= htmlspecialchars($item['nama_material']) ?></td>
                            <td class="text-end"><?= number_format($item['total_dipesan_asli'] ) ?> <?= htmlspecialchars($item['nama_satuan']) ?></td>
                            <td class="text-end fw-bold <?= ($item['total_diterima'] < $item['total_dipesan_asli']) ? 'text-warning' : 'text-success' ?>">
                                <?= number_format($item['total_diterima']) ?>
                            </td>
                            <td class="text-end">Rp <?= number_format($item['total_sub_total_asli'], 0, ',', '.') ?></td>
                        </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <tr><td colspan="5" class="text-center">Belum ada detail material.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end fw-bold">Grand Total Pembelian</th>
                        <th class="text-end fw-bold">Rp <?= number_format($pembelian['total_biaya'], 0, ',', '.') ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title">Riwayat Penerimaan Barang (Log)</h4></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Material</th>
                        <th>Jenis</th>
                        <th class="text-end">Diterima Baik</th>
                        <th class="text-end">Rusak</th>
                        <th>Catatan dari PJ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_log->num_rows > 0):
                        $result_log->data_seek(0);
                        while ($log_entry = $result_log->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= date("d M Y, H:i", strtotime($log_entry['tanggal_penerimaan'])) ?></td>
                            <td><?= htmlspecialchars($log_entry['nama_material']) ?></td>
                            <td>
                                <?php 
                                    $jenis_badge = $log_entry['jenis_penerimaan'] == 'Penerimaan Awal' ? 'bg-info' : 'bg-secondary';
                                ?>
                                <span class="badge <?= $jenis_badge ?>"><?= htmlspecialchars($log_entry['jenis_penerimaan']) ?></span>
                            </td>
                            <td class="text-end text-success"><?= number_format($log_entry['jumlah_diterima']) ?> <?= htmlspecialchars($log_entry['nama_satuan']) ?></td>
                            <td class="text-end text-danger"><?= number_format($log_entry['jumlah_rusak']) ?> <?= htmlspecialchars($log_entry['nama_satuan']) ?></td>
                            <td><?= htmlspecialchars($log_entry['catatan']) ?></td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr><td colspan="6" class="text-center"><em>Belum ada riwayat penerimaan barang untuk pembelian ini.</em></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="text-end mt-3 mb-3">
    <a href="pencatatan_pembelian.php" class="btn btn-secondary">Kembali ke Daftar</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

</body>
</html>