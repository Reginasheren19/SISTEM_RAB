<?php
include("../config/koneksi_mysql.php");

if (!isset($_GET['id_rab_material'])) {
    echo "ID RAB Material tidak diberikan.";
    exit;
}

$id_rab_material = mysqli_real_escape_string($koneksi, $_GET['id_rab_material']);

// Query ambil data RAB Material beserta data terkait
$sql = "SELECT 
            tr.id_rab_material,
            CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS pekerjaan,
           mpe.nama_perumahan,
           mpr.kavling,
           mpr.type_proyek,            
           mpe.lokasi,
            YEAR(tr.tanggal_mulai_mt) AS tahun,
            mm.nama_mandor,
                       tr.tanggal_mulai_mt,
           tr.tanggal_selesai_mt,
            u.nama_lengkap AS pj_proyek
        FROM rab_material tr
        JOIN master_proyek mpr ON tr.id_proyek = mpr.id_proyek
        LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
        LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
        WHERE tr.id_rab_material = '$id_rab_material'";

$result = mysqli_query($koneksi, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    echo "Data RAB Material tidak ditemukan.";
    exit;
}

$data = mysqli_fetch_assoc($result);

// Query detail RAB harus include kategori nama
$sql_detail = "SELECT 
                 d.id_detail_rab_material, 
                 d.id_kategori,
                 d.id_pekerjaan, 
                 mp.uraian_pekerjaan, 
                 k.nama_kategori,
                 ms.nama_satuan,
                 d.volume, 
                 d.harga_satuan, 
                 d.sub_total
               FROM detail_rab_material d
               LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan
               LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori
               LEFT JOIN master_satuan ms ON mp.id_satuan = ms.id_satuan
               WHERE d.id_rab_material = '$id_rab_material'
               ORDER BY k.id_kategori, mp.uraian_pekerjaan";

$detail_result = mysqli_query($koneksi, $sql_detail);
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Detail RAB Material</title>
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
              <h3 class="fw-bold mb-3">Rancang RAB</h3>
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
                  <a href="transaksi_rab_material.php"> Daftar RAB Material</a>
                </li>
              </ul>
            </div>

          <div class="container mt-4">

          <div class="card shadow-sm mb-4">
            <div class="card-header fw-bold">
              Info RAB Material
            </div>
            <div class="card-body">
              <div class="row row-cols-1 row-cols-md-2 g-3">
            <!-- ID RAB -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">ID RAB</span>
                    <span>: <?= htmlspecialchars($data['id_rab_material']) ?></span>
                </div>
            </div>


                        <!-- Mandor -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">Mandor</span>
                    <span>: <?= htmlspecialchars($data['nama_mandor']) ?></span>
                </div>
            </div>

            <!-- Type Proyek -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">Type Proyek</span>
                    <span>: <?= htmlspecialchars($data['type_proyek']) ?></span>
                </div>
            </div>

                        <!-- PJ Proyek -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">PJ Proyek</span>
                    <span>: <?= htmlspecialchars($data['pj_proyek']) ?></span>
                </div>
            </div>

            <!-- Pekerjaan -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">Pekerjaan</span>
                    <span>: <?= htmlspecialchars($data['pekerjaan']) ?></span>
                </div>
            </div>

            <!-- Tanggal Mulai -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">Tanggal Mulai</span>
                    <span>: <?= htmlspecialchars($data['tanggal_mulai_mt']) ?></span>
                </div>
            </div>

            <!-- Lokasi -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">Lokasi</span>
                    <span>: <?= htmlspecialchars($data['lokasi']) ?></span>
                </div>
            </div>

            <!-- Tanggal Selesai -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">Tanggal Selesai</span>
                    <span>: <?= htmlspecialchars($data['tanggal_selesai_mt']) ?></span>
                </div>
            </div>

              </div>
            </div>
          </div>


<?php
// Ganti bagian table body Anda dengan kode ini:
?>

<!-- Tabel Detail Pekerjaan -->
<div class="card shadow-sm">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <h4 class="card-title mb-0">Rincian RAB Material</h4>
<a href="cetak_rab_material.php?id_rab_material=<?= $id_rab_material ?>" target="_blank" class="btn btn-label-info btn-round btn-sm">
    <span class="btn-label">
    <i class="fa fa-print"></i>
    </span>
    Cetak
  </a>
</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered" id="tblDetailRAB">
        <thead>
          <tr>
            <th style="width:5%;">No</th>
            <th>Uraian Pekerjaan</th>
            <th style="width:10%;">Satuan</th>
            <th style="width:10%;">Volume</th>
            <th style="width:15%;">Harga Satuan</th>
            <th style="width:15%;">Jumlah</th>
          </tr>
        </thead>
        <tbody>
<?php
function toRoman($num) {
    $map = [
        'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
        'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
        'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
    ];
    $result = '';
    foreach ($map as $roman => $value) {
        while ($num >= $value) {
            $result .= $roman;
            $num -= $value;
        }
    }
    return $result;
}

if ($detail_result && mysqli_num_rows($detail_result) > 0) {
    $prevKategori = null;
    $grandTotal = 0;
    $subTotalKategori = 0;
    $noKategori = 0;
    $noPekerjaan = 1;
    
    while ($row = mysqli_fetch_assoc($detail_result)) {
        // Jika kategori berubah
        if ($prevKategori !== $row['nama_kategori']) {
            // Tampilkan subtotal kategori sebelumnya (jika ada)
            if ($prevKategori !== null) {
                echo "<tr class='table-secondary fw-bold subtotal-row'>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class='text-end'>Sub Total</td>
                        <td class='text-end'>Rp " . number_format($subTotalKategori, 0, ',', '.') . "</td>
                      </tr>";
            }
            
            // Header kategori baru
            $noKategori++;
            echo "<tr class='table-primary fw-bold category-header'>
                    <td>" . toRoman($noKategori) . "</td>
                    <td>" . htmlspecialchars($row['nama_kategori']) . "</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                  </tr>";
            
            $prevKategori = $row['nama_kategori'];
            $subTotalKategori = 0;
            $noPekerjaan = 1;
        }
        
        // Tampilkan detail pekerjaan
        echo "<tr class='category-item'>
                <td>" . $noPekerjaan++ . "</td>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;" . htmlspecialchars($row['uraian_pekerjaan']) . "</td>
                <td class='text-center'>" . htmlspecialchars($row['nama_satuan']) . "</td>
                <td class='text-center'>" . number_format($row['volume']) . "</td>
                <td class='text-end'>Rp " . number_format($row['harga_satuan'], 0, ',', '.') . "</td>
                <td class='text-end'>Rp " . number_format($row['sub_total'], 0, ',', '.') . "</td>
              </tr>";
        
        $subTotalKategori += $row['sub_total'];
        $grandTotal += $row['sub_total'];
    }
    
    // Subtotal kategori terakhir
    if ($prevKategori !== null) {
        echo "<tr class='table-secondary fw-bold subtotal-row'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class='text-end'>Sub Total</td>
                <td class='text-end'>Rp " . number_format($subTotalKategori, 0, ',', '.') . "</td>
              </tr>";
    }
    
} else {
    echo "<tr><td colspan='6' class='text-center'>Tidak ada detail pekerjaan</td></tr>";
}
?>
        </tbody>
  <tfoot>
    <tr class='table-success fw-bold'>
      <td colspan="5" class='text-end'>TOTAL KESELURUHAN</td> <!-- Merged cell for label -->
      <td class='text-end'>Rp <?= number_format($grandTotal ?? 0, 0, ',', '.') ?></td> <!-- Total value in the 6th column -->
    </tr>
  </tfoot>
      </table>
    </div>
  </div>
</div>
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="assets/js/plugin/datatables/dataTables.bootstrap5.min.js"></script>


<!-- <script>
$(document).ready(function() {
    $('#tblDetailRAB').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthChange: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
        columnDefs: [
            { 
                targets: [0, 2, 3, 4, 5], 
                className: 'text-center' 
            },
            { 
                targets: 1, 
                className: 'text-left' 
            },
            {
                // Disable sorting pada baris kategori dan subtotal
                targets: '_all',
                createdCell: function(td, cellData, rowData, row, col) {
                    var $row = $(td).closest('tr');
                    if ($row.hasClass('category-header') || $row.hasClass('subtotal-row')) {
                        $row.addClass('no-sort');
                    }
                }
            }
        ],
        order: [[0, 'asc']],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Data tidak ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Tidak ada data tersedia",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: {
                previous: "Sebelumnya",
                next: "Berikutnya",
                first: "Pertama",
                last: "Terakhir"
            }
        },
        // Callback setelah tabel digambar
        drawCallback: function(settings) {
            // Styling khusus untuk baris kategori dan subtotal
            $('#tblDetailRAB tbody tr.category-header').css({
                'background-color': '#e3f2fd',
                'font-weight': 'bold'
            });
            $('#tblDetailRAB tbody tr.subtotal-row').css({
                'background-color': '#f5f5f5',
                'font-weight': 'bold'
            });
        }
    });
});
</script> -->
</body>
</html>
