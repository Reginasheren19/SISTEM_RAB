<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Inisialisasi & Pengambilan ID dari URL (Tidak Berubah)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID Distribusi tidak valid.";
    header("Location: distribusi_material.php");
    exit();
}
$id_distribusi = $_GET['id'];

// 2. Query untuk Data Header Distribusi (Tidak Berubah)
$header_sql = "
    SELECT 
        d.id_distribusi, d.tanggal_distribusi, d.keterangan_umum,
        u.nama_lengkap AS nama_pj_distribusi,
        CONCAT(pr.nama_perumahan, ' - Kavling ', p.kavling) AS nama_proyek_lengkap
    FROM distribusi_material d
    LEFT JOIN master_user u ON d.id_user_pj = u.id_user
    LEFT JOIN master_proyek p ON d.id_proyek = p.id_proyek 
    LEFT JOIN master_perumahan pr ON p.id_perumahan = pr.id_perumahan
    WHERE d.id_distribusi = ?
";
$stmt_header = mysqli_prepare($koneksi, $header_sql);
mysqli_stmt_bind_param($stmt_header, "i", $id_distribusi);
mysqli_stmt_execute($stmt_header);
$header_result = mysqli_stmt_get_result($stmt_header);
$distribusi = mysqli_fetch_assoc($header_result);

if (!$distribusi) {
    $_SESSION['error_message'] = "Data Distribusi tidak ditemukan.";
    header("Location: distribusi_material.php");
    exit();
}

// 3. Query untuk Daftar Item yang Sudah Ditambahkan (Tidak Berubah)
$detail_sql = "
    SELECT dd.id_detail, m.nama_material, dd.jumlah_distribusi, s.nama_satuan AS satuan
    FROM detail_distribusi dd
    JOIN master_material m ON dd.id_material = m.id_material
    JOIN master_satuan s ON m.id_satuan = s.id_satuan
    WHERE dd.id_distribusi = ?
    ORDER BY dd.id_detail ASC
";
$stmt_detail = mysqli_prepare($koneksi, $detail_sql);
if ($stmt_detail === false) { die("Query Gagal Disiapkan. Error SQL: " . mysqli_error($koneksi)); }
mysqli_stmt_bind_param($stmt_detail, "i", $id_distribusi);
mysqli_stmt_execute($stmt_detail);
$detail_items = mysqli_stmt_get_result($stmt_detail);


$material_sql = "
    SELECT 
        m.id_material, 
        m.nama_material, 
        s.nama_satuan AS satuan,
        -- Mengambil stok, jika stoknya belum ada (NULL), dianggap 0
        COALESCE(sm.jumlah_stok_tersedia, 0) AS jumlah_stok_tersedia
    FROM 
        master_material m
    LEFT JOIN 
        master_satuan s ON m.id_satuan = s.id_satuan
    LEFT JOIN
        stok_material sm ON m.id_material = sm.id_material
    ORDER BY 
        m.nama_material ASC
";
$materials_result = mysqli_query($koneksi, $material_sql);

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Input Detail Distribusi Material</title>
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
                <h3 class="fw-bold mb-3">Distribusi Material</h3>
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
                        <a href="#">Distribusi Material</a>
                    </li>
                    <li class="separator">
                        <i class="icon-arrow-right"></i>
                    </li>
                    <li class="nav-item">
                        <a href="#">Input Detail Distribusi Material</a>
                    </li>
                </ul>
            </div>

                    <div class="card mb-4">
                        <div class="card-header"><h4 class="card-title">Informasi Transaksi</h4></div>
                          <div class="card-body">
                              <div class="row">
                                  <div class="col-md-6">
                                      <p><strong>ID Distribusi:</strong> DIST<?= htmlspecialchars($distribusi['id_distribusi']) . date('Y', strtotime($distribusi['tanggal_distribusi'])) ?></p>
                                      <p><strong>Proyek Tujuan:</strong> <?= htmlspecialchars($distribusi['nama_proyek_lengkap']) ?></p>
                                  </div>
                                  <div class="col-md-6">
                                      <p><strong>Tanggal:</strong> <?= date("d F Y", strtotime($distribusi['tanggal_distribusi'])) ?></p>
                                      <p><strong>PJ Proyek:</strong> <?= htmlspecialchars($distribusi['nama_pj_distribusi']) ?></p>
                                  </div>
                                  <div class="col-12 mt-2">
                                      <p><strong>Keterangan:</strong> <?= nl2br(htmlspecialchars($distribusi['keterangan_umum'])) ?></p>
                                  </div>
                              </div>
                          </div>
                    </div>
                        <div class="card mb-4">
                        <div class="card-header"><h4 class="card-title">Tambah Material</h4></div>
                        <div class="card-body">
                            <div class="row align-items-end">
                                <div class="col-md-4 mb-3">
                                    <label for="id_material" class="form-label">Material</label>
                                    <select class="form-select" id="id_material">
                                        <option value="" data-stok="" data-satuan="">-- Pilih Material --</option>
                                        <?php while($material = mysqli_fetch_assoc($materials_result)): ?>
                                            <option value="<?= $material['id_material'] ?>" data-stok="<?= $material['jumlah_stok_tersedia'] ?>" data-satuan="<?= $material['satuan'] ?>" data-nama="<?= htmlspecialchars($material['nama_material']) ?>">
                                                <?= htmlspecialchars($material['nama_material']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="stok_saat_ini" class="form-label">Stok</label>
                                    <input type="text" id="stok_saat_ini" class="form-control" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="jumlah_distribusi" class="form-label">Jumlah</label>
                                    <input type="number" step="0.01" class="form-control" id="jumlah_distribusi">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="button" id="btn-tambah-item" class="btn btn-primary w-100">Tambahkan</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="add_detail_distribusi.php" method="POST">
                        <input type="hidden" name="id_distribusi" value="<?= $id_distribusi ?>">

                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Daftar Material Didistribusikan</h4></div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Nama Material</th>
                                            <th>Jumlah</th>
                                            <th>Satuan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="daftar-item-body">
                                        </tbody>
                                </table>
                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-success">Selesaikan Transaksi & Simpan</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script>
    $(document).ready(function() {
        var nomorUrut = 1;

        // 1. Update stok saat material dipilih
        $('#id_material').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var stok = selectedOption.data('stok');
            var satuan = selectedOption.data('satuan');
            if (stok !== '') {
                $('#stok_saat_ini').val(stok + ' ' + satuan);
            } else {
                $('#stok_saat_ini').val('');
            }
        });

        // 2. Logika saat tombol "Tambahkan" diklik
        $('#btn-tambah-item').on('click', function() {
            var materialSelect = $('#id_material');
            var selectedOption = materialSelect.find('option:selected');
            var jumlahInput = $('#jumlah_distribusi');

            var idMaterial = materialSelect.val();
            var namaMaterial = selectedOption.data('nama');
            var stok = parseFloat(selectedOption.data('stok'));
            var satuan = selectedOption.data('satuan');
            var jumlah = parseFloat(jumlahInput.val());

            // Validasi
            if (!idMaterial) {
                alert('Silakan pilih material terlebih dahulu.');
                return;
            }
            if (isNaN(jumlah) || jumlah <= 0) {
                alert('Jumlah harus diisi dengan angka lebih dari 0.');
                return;
            }
            if (jumlah > stok) {
                alert('Jumlah distribusi tidak boleh melebihi stok yang tersedia!');
                return;
            }

            // Buat baris tabel baru
            var barisBaru = `
                <tr data-id="${idMaterial}">
                    <td>${nomorUrut}</td>
                    <td>
                        ${namaMaterial}
                        <input type="hidden" name="id_material[]" value="${idMaterial}">
                        <input type="hidden" name="jumlah_distribusi[]" value="${jumlah}">
                    </td>
                    <td>${jumlah}</td>
                    <td>${satuan}</td>
                    <td><button type="button" class="btn btn-danger btn-sm btn-hapus-item">Hapus</button></td>
                </tr>
            `;

            // Tambahkan baris baru ke tabel
            $('#daftar-item-body').append(barisBaru);
            nomorUrut++;

            // Reset form input
            materialSelect.val('').trigger('change');
            jumlahInput.val('');
        });

        // 3. Logika saat tombol "Hapus" di dalam baris tabel diklik
        $('#daftar-item-body').on('click', '.btn-hapus-item', function() {
            $(this).closest('tr').remove();
            
            // Atur ulang nomor urut
            nomorUrut = 1;
            $('#daftar-item-body tr').each(function() {
                $(this).find('td:first').text(nomorUrut);
                nomorUrut++;
            });
        });
    });
    </script>
</body>
</html>