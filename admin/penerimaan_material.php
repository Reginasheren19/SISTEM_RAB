<?php
session_start();
include("../config/koneksi_mysql.php");

// --- [DIUBAH TOTAL] --- Query final yang paling akurat
// Memastikan pesanan hilang dari daftar jika total pesanan == (total diterima baik + total rusak)
$sql = "
    SELECT 
        p.id_pembelian,
        p.tanggal_pembelian,
        p.keterangan_pembelian
    FROM 
        pencatatan_pembelian p
    -- Subquery (1): Menghitung total kuantitas yang dipesan (asli + pengganti). Ini sudah benar.
    JOIN (
        SELECT id_pembelian, SUM(quantity) as total_dipesan
        FROM detail_pencatatan_pembelian
        GROUP BY id_pembelian
    ) AS pesanan ON p.id_pembelian = pesanan.id_pembelian
    -- --- [DIUBAH] --- Subquery (2) sekarang menjumlahkan item baik + rusak menjadi 'total_diproses'
    LEFT JOIN (
        SELECT id_pembelian, SUM(jumlah_diterima + jumlah_rusak) as total_diproses
        FROM log_penerimaan_material
        GROUP BY id_pembelian
    ) AS diproses ON p.id_pembelian = diproses.id_pembelian
    -- --- [DIUBAH] --- Kondisi WHERE sekarang membandingkan dengan total_diproses
    WHERE 
        pesanan.total_dipesan > COALESCE(diproses.total_diproses, 0)
    ORDER BY 
        p.tanggal_pembelian ASC, p.id_pembelian ASC
";
        
$result = mysqli_query($koneksi, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Penerimaan Material</title>
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
                <h3 class="fw-bold mb-3">Penerimaan Material</h3>
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
                        <a href="#">Penerimaan Material</a>
                    </li>
                </ul>
            </div>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Daftar Tunggu Penerimaan Material</h4>
            </div>
            <div class="card-body">
                <?php 
                    if (isset($_SESSION['pesan_sukses'])) {
                        echo '<div class="alert alert-success">' . $_SESSION['pesan_sukses'] . '</div>';
                        unset($_SESSION['pesan_sukses']);
                    }
                    if (isset($_SESSION['error_message'])) {
                        echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                        unset($_SESSION['error_message']);
                    }
                ?>
                <div class="table-responsive">
                    <table id="tabelPenerimaan" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>ID Pembelian</th>
                                <th>Tanggal Pesan</th>
                                <th>Keterangan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($result) > 0):
                                $nomor = 1; 
                                while ($row = mysqli_fetch_assoc($result)): 
                                    $tahun_pembelian = date('Y', strtotime($row['tanggal_pembelian']));
                                    $formatted_id = 'PB' . $row['id_pembelian'] . $tahun_pembelian;
                            ?>
                            <tr>
                                <td class="text-center"><?= $nomor++ ?></td>
                                <td><?= htmlspecialchars($formatted_id) ?></td>
                                <td><?= date("d F Y", strtotime($row['tanggal_pembelian'])) ?></td>
                                <td><?= htmlspecialchars($row['keterangan_pembelian']) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-primary btn-sm btn-terima" 
                                            data-id="<?= $row['id_pembelian'] ?>" 
                                            data-formatted-id="<?= htmlspecialchars($formatted_id) ?>">
                                        <i class="fa fa-box-open"></i> Konfirmasi Penerimaan
                                    </button>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted"><em>Tidak ada pesanan yang menunggu untuk diterima.</em></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
  <div class="modal fade" id="konfirmasiModal" tabindex="-1" aria-labelledby="konfirmasiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form action="add_penerimaan.php" method="POST" id="form-konfirmasi">
                <div class="modal-header">
                    <h5 class="modal-title" id="konfirmasiModalLabel">Konfirmasi Penerimaan Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan melakukan konfirmasi untuk Pembelian ID: <strong id="modal-pembelian-id-text"></strong></p>
                    <input type="hidden" name="id_pembelian" id="modal-pembelian-id">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Material</th>
                                    <th class="text-center">Sisa Perlu Diterima</th>
                                    <th class="text-center" style="width: 18%;">Jml Diterima Baik</th>
                                    <th class="text-center" style="width: 18%;">Jml Rusak</th>
                                    <th style="width: 20%;">Catatan</th>
                                </tr>
                            </thead>
                            <tbody id="rincian-pembelian-body">
                                </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    $('#tabelPenerimaan').DataTable({ "order": [] });

    // -------------------------------------------------------------------
    // Event listener untuk tombol "Konfirmasi Penerimaan"
    // -------------------------------------------------------------------
    $('#tabelPenerimaan').on('click', '.btn-terima', function() {
        const id_pembelian = $(this).data('id');
        const formatted_id = $(this).data('formatted-id');
        
        // Mengisi data awal di modal
        $('#modal-pembelian-id').val(id_pembelian);
        $('#modal-pembelian-id-text').text(formatted_id);

        var tbody = $('#rincian-pembelian-body');
        tbody.html('<tr><td colspan="5" class="text-center"><span class="spinner-border spinner-border-sm"></span> Memuat rincian...</td></tr>');
        
        // Menampilkan modal
        $('#konfirmasiModal').modal('show');

        // Memanggil data rincian dengan AJAX
        $.ajax({
            url: 'get_rincian_penerimaan.php',
            type: 'GET',
            data: { id_pembelian: id_pembelian },
            dataType: 'json',
            success: function(response) {
                tbody.empty();
                if (response.length > 0) {
                    // Mengubah header tabel di modal
                    $('#konfirmasiModal table thead').html(`
                        <tr>
                            <th>Nama Material</th>
                            <th class="text-center">Sisa Perlu Diterima</th>
                            <th class="text-center" style="width: 15%;">Kondisi Sesuai?</th>
                            <th style="width: 40%;">Input Penerimaan</th>
                        </tr>
                    `);

                    // Loop untuk setiap item dan membuat baris tabel
                    $.each(response, function(index, item) {
                        var sisa = parseFloat(item.sisa_dipesan);
                        var row = `
                            <tr class="item-row" data-sisa="${sisa}">
                                <td class="align-middle">
                                    ${item.nama_material}
                                    <input type="hidden" name="id_detail_pembelian[]" value="${item.id_detail_pembelian}">
                                    <input type="hidden" name="id_material[]" value="${item.id_material}">
                                    <input type="hidden" class="input-diterima" name="jumlah_diterima[]" value="${sisa.toFixed(2)}">
                                </td>
                                <td class="text-center align-middle">${sisa.toFixed(2)} ${item.nama_satuan}</td>
                                <td class="text-center align-middle">
                                    <div class="form-check form-switch d-flex justify-content-center p-0">
                                        <input class="form-check-input sesuai-checkbox" type="checkbox" role="switch" style="height: 1.5em; width: 3em;" checked title="Hapus centang jika kondisi tidak sesuai">
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div class="manual-input-area" style="display: none;">
                                        <div class="input-group input-group-sm mb-1">
                                            <span class="input-group-text" style="width: 70px;">Rusak</span>
                                            <input type="number" name="jumlah_rusak[]" class="form-control form-control-sm input-rusak" step="0.01" min="0" value="0" disabled>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text" style="width: 70px;">Catatan</span>
                                            <input type="text" name="catatan[]" class="form-control form-control-sm" placeholder="Opsional..." disabled>
                                        </div>
                                    </div>
                                    <div class="status-ok-area">
                                        <span class="badge bg-success">Sesuai</span>
                                        <input type="hidden" name="jumlah_rusak[]" value="0">
                                        <input type="hidden" name="catatan[]" value="">
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    }); // <-- Akhir dari $.each
                } else {
                    $('#konfirmasiModal table thead').html('');
                    tbody.html('<tr><td colspan="5" class="text-center">Semua item untuk pembelian ini sudah diterima.</td></tr>');
                }
            }, // <-- Akhir dari success callback
            error: function() {
                tbody.html('<tr><td colspan="5" class="text-center">Terjadi kesalahan saat meminta data ke server.</td></tr>');
            } // <-- Akhir dari error callback
        }); // <-- Akhir dari $.ajax
    }); // <-- Akhir dari event listener .btn-terima

    // -------------------------------------------------------------------
    // Event listener untuk checkbox "Sesuai?"
    // -------------------------------------------------------------------
    $('#konfirmasiModal').on('change', '.sesuai-checkbox', function() {
        var tr = $(this).closest('.item-row');
        var manualInputArea = tr.find('.manual-input-area');
        var statusOkArea = tr.find('.status-ok-area');
        var sisa = parseFloat(tr.data('sisa'));

        if (this.checked) {
            manualInputArea.hide();
            manualInputArea.find('input').prop('disabled', true);
            statusOkArea.show();
            statusOkArea.find('input').prop('disabled', false);
            tr.find('.input-diterima').val(sisa.toFixed(2));
        } else {
            manualInputArea.show();
            manualInputArea.find('input').prop('disabled', false);
            statusOkArea.hide();
            statusOkArea.find('input').prop('disabled', true);
            tr.find('.input-rusak').val(0).focus();
            tr.find('.input-diterima').val(sisa.toFixed(2));
        }
    }); // <-- Akhir dari event listener .sesuai-checkbox

    // -------------------------------------------------------------------
    // Event listener untuk perhitungan otomatis saat input jumlah rusak
    // -------------------------------------------------------------------
    $('#konfirmasiModal').on('input', '.input-rusak', function() {
        var tr = $(this).closest('.item-row');
        var sisa = parseFloat(tr.data('sisa'));
        var rusak = parseFloat($(this).val()) || 0;

        if (rusak > sisa) {
            alert('Jumlah rusak tidak boleh melebihi sisa (' + sisa + ')');
            $(this).val(sisa);
            rusak = sisa;
        }
        var diterima = sisa - rusak;
        tr.find('.input-diterima').val(diterima.toFixed(2));
    }); // <-- Akhir dari event listener .input-rusak

}); // <-- AKHIR DARI $(document).ready()
</script>
</body>
</html>