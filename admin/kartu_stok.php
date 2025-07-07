<?php
session_start();
include("../config/koneksi_mysql.php");

// Fungsi untuk menghilangkan angka koma jika tidak perlu
function formatAngka($angka) {
    return rtrim(rtrim(number_format($angka, 2, ',', '.'), '0'), ',');
}

// Inisialisasi variabel
$id_material_filter = $_POST['id_material'] ?? null;
$tanggal_mulai = $_POST['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_POST['tanggal_selesai'] ?? date('Y-m-t');

// Selalu ambil daftar material untuk dropdown filter
$material_result = mysqli_query($koneksi, "SELECT id_material, nama_material FROM master_material ORDER BY nama_material ASC");

// --- MODIFIKASI DIMULAI DI SINI ---

$nama_material_terpilih = "";
$detail_transaksi = [];
$saldo_awal = 0;
$ringkasan_stok = []; // Variabel baru untuk menampung data ringkasan

if (!empty($id_material_filter)) {
    // --- BLOK INI TETAP SAMA, UNTUK MENAMPILKAN DETAIL PER MATERIAL ---
    $stmt_nama = $koneksi->prepare("SELECT nama_material FROM master_material WHERE id_material = ?");
    $stmt_nama->bind_param("i", $id_material_filter);
    $stmt_nama->execute();
    $nama_material_terpilih = $stmt_nama->get_result()->fetch_assoc()['nama_material'] ?? '';
    $stmt_nama->close();

    $stmt_saldo = $koneksi->prepare("SELECT (COALESCE(SUM(masuk), 0) - COALESCE(SUM(keluar), 0)) as saldo_awal FROM (
        (SELECT SUM(jumlah_diterima) as masuk, 0 as keluar FROM log_penerimaan_material WHERE id_material = ? AND tanggal_penerimaan < ?)
        UNION ALL
        (SELECT 0 as masuk, SUM(dd.jumlah_distribusi) as keluar FROM detail_distribusi dd JOIN distribusi_material dm ON dd.id_distribusi = dm.id_distribusi WHERE dd.id_material = ? AND dm.tanggal_distribusi < ?)
    ) as history");
    $stmt_saldo->bind_param("isis", $id_material_filter, $tanggal_mulai, $id_material_filter, $tanggal_mulai);
    $stmt_saldo->execute();
    $saldo_awal = (float)($stmt_saldo->get_result()->fetch_assoc()['saldo_awal'] ?? 0);
    $stmt_saldo->close();

    $sql_detail = "
        SELECT waktu, uraian, masuk, keluar, keterangan FROM (
            SELECT 
                lpm.tanggal_penerimaan AS waktu,
                CONCAT('Penerimaan dari Pembelian: ', p.keterangan_pembelian) AS uraian,
                lpm.jumlah_diterima AS masuk,
                0 AS keluar,
                lpm.catatan AS keterangan
            FROM log_penerimaan_material lpm
            JOIN pencatatan_pembelian p ON lpm.id_pembelian = p.id_pembelian
            WHERE lpm.id_material = ? AND lpm.tanggal_penerimaan BETWEEN ? AND ?
            UNION ALL
            SELECT 
                dm.created_at AS waktu,
                CONCAT('Distribusi ke: ', per.nama_perumahan, ' - ', mp.kavling) AS uraian,
                0 AS masuk,
                dd.jumlah_distribusi AS keluar,
                dm.keterangan_umum AS keterangan
            FROM detail_distribusi dd
            JOIN distribusi_material dm ON dd.id_distribusi = dm.id_distribusi
            JOIN master_proyek mp ON dm.id_proyek = mp.id_proyek
            JOIN master_perumahan per ON mp.id_perumahan = per.id_perumahan
            WHERE dd.id_material = ? AND dm.tanggal_distribusi BETWEEN ? AND ?
        ) AS transaksi
        ORDER BY waktu ASC
    ";
    $stmt_trx = $koneksi->prepare($sql_detail);
    $stmt_trx->bind_param("isssis", $id_material_filter, $tanggal_mulai, $tanggal_selesai, $id_material_filter, $tanggal_mulai, $tanggal_selesai);
    $stmt_trx->execute();
    $result = $stmt_trx->get_result();
    $detail_transaksi = $result->fetch_all(MYSQLI_ASSOC);
    $stmt_trx->close();

} else {
    // --- BAGIAN BARU: JIKA TIDAK ADA MATERIAL DIPILIH, AMBIL RINGKASAN SEMUA STOK ---
    $sql_ringkasan = "
        SELECT 
            m.nama_material,
            (COALESCE(penerimaan.total_masuk, 0) - COALESCE(distribusi.total_keluar, 0)) AS saldo_akhir
        FROM 
            master_material m
        LEFT JOIN 
            (SELECT id_material, SUM(jumlah_diterima) as total_masuk FROM log_penerimaan_material GROUP BY id_material) AS penerimaan 
            ON m.id_material = penerimaan.id_material
        LEFT JOIN 
            (SELECT id_material, SUM(jumlah_distribusi) as total_keluar FROM detail_distribusi GROUP BY id_material) AS distribusi 
            ON m.id_material = distribusi.id_material
        ORDER BY 
            m.nama_material ASC
    ";
    $result_ringkasan = mysqli_query($koneksi, $sql_ringkasan);
    if ($result_ringkasan) {
        $ringkasan_stok = mysqli_fetch_all($result_ringkasan, MYSQLI_ASSOC);
    }
}
// --- MODIFIKASI SELESAI ---
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Kartu Stok</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="assets/img/logo/LOGO PT.jpg"
      type="image/x-icon"
    />

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

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <link rel="stylesheet" href="assets/css/demo.css" />
  </head>
<body>
    <div class="wrapper">
        <?php include 'sidebar_m.php'; ?>


        <div class="main-panel">
            <div class="main-header">
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
                </div>

<div class="container">
            <div class="page-inner">
                <div class="page-header"><h3 class="fw-bold mb-3">Kartu Stok Material</h3></div>
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Filter Laporan</h4></div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Pilih Material</label>
                                        <select name="id_material" class="form-select">
                                            <option value="">-- Tampilkan Ringkasan Semua --</option>
                                            <?php mysqli_data_seek($material_result, 0); while($material = mysqli_fetch_assoc($material_result)): ?>
                                                <option value="<?= $material['id_material'] ?>" <?= ($id_material_filter == $material['id_material']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($material['nama_material']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3"><div class="form-group"><label>Dari Tanggal</label><input type="date" name="tanggal_mulai" class="form-control" value="<?= htmlspecialchars($tanggal_mulai) ?>"></div></div>
                                <div class="col-md-3"><div class="form-group"><label>Sampai Tanggal</label><input type="date" name="tanggal_selesai" class="form-control" value="<?= htmlspecialchars($tanggal_selesai) ?>"></div></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Tampilkan</button></div>
                            </div>
                        </form>
                    </div>
                </div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title m-0">
                <?= !empty($id_material_filter) ? 'Kartu Stok untuk: ' . htmlspecialchars($nama_material_terpilih) : 'Ringkasan Stok Semua Material' ?>
            </h4>

            <?php if (!empty($id_material_filter)): ?>
            <a href="cetak_kartu_stok.php?material=<?= $id_material_filter ?>&id_nama=<?= urlencode($nama_material_terpilih) ?>&tgl_mulai=<?= $tanggal_mulai ?>&tgl_selesai=<?= $tanggal_selesai ?>"
            target="_blank" 
            class="btn btn-danger btn-sm">
            <i class="fas fa-file-pdf"></i> Cetak PDF
            </a>
            <?php endif; ?>
        </div>

    </div>
    <div class="card-body">
        <div class="table-responsive">
            <?php if (!empty($id_material_filter)): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr class="text-center">
                            <th>Tanggal</th>
                            <th>Uraian</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Sisa</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $saldo = $saldo_awal;
                        if (!empty($detail_transaksi)):
                            foreach ($detail_transaksi as $trx):
                                $saldo += $trx['masuk'] - $trx['keluar'];
                        ?>
                        <tr>
                            <td><?= date('d-m-Y H:i', strtotime($trx['waktu'])) ?></td>
                            <td><?= htmlspecialchars($trx['uraian']) ?></td>
                            <td class="text-end text-success">
                                <?= ($trx['masuk'] > 0) ? '+' . number_format($trx['masuk']) : '-' ?>
                            </td>
                            <td class="text-end text-danger">
                                <?= ($trx['keluar'] > 0) ? '-' . number_format($trx['keluar']) : '-' ?>
                            </td>
                            <td class="text-end fw-bold">
                                <?= number_format($saldo) ?>
                            </td>
                            <td><?= htmlspecialchars($trx['keterangan']) ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted"><i>Tidak ada riwayat transaksi pada periode ini.</i></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
            <?php else: ?>
                <table class="table table-bordered">
                    <thead>
                        <tr class="text-center">
                            <th>No</th>
                            <th>Nama Material</th>
                            <th>Stok Saat Ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ringkasan_stok)): ?>
                            <?php $no = 1; foreach ($ringkasan_stok as $item): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($item['nama_material']) ?></td>
                                    <td class="text-center fw-bold"><?= number_format($item['saldo_akhir']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted"><i>Tidak ada data material untuk ditampilkan.</i></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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

</body>
</html>