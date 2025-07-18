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

// Query ambil detail rab material (pekerjaan dan biaya)
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
               WHERE d.id_rab_material = '$id_rab_material'";;

$detail_result = mysqli_query($koneksi, $sql_detail);

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Rancang RAB Material</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="assets/img/logo/LOGO PT.jpg"
      type="image/x-icon"
    />
    <link
  rel="stylesheet"
  href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css"
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
                  <a href="#">Rancang RAB</a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="#">RAB Material</a>
                </li>
              </ul>
            </div>


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

<div class="card">
  <div class="card-header">
    Detail RAB
  </div>
      <div class="card-body"> 

        <button
          id="btnTambahKategori"
          class="btn btn-primary btn-round ms-auto mb-3">
          <i class="fa fa-plus"></i> Tambah Kategori
        </button>
        
  <div class="table-responsive">
    <table class="table table-bordered" id="tblKategori">
      <thead>
        <tr>
          <th scope="col" style="width:5%;">No</th>
          <th scope="col">Uraian Pekerjaan</th>
          <th scope="col" style="width:10%;">Satuan</th>
          <th scope="col" style="width:10%;">Volume</th>
          <th scope="col" style="width:15%;">Harga Satuan</th>
          <th scope="col" style="width:15%;">Jumlah</th>
          <th scope="col" style="width:15%;">Aksi</th>
        </tr>
      </thead>
      
      <tbody>
        <?php
        if ($detail_result && mysqli_num_rows($detail_result) > 0) {
            $no = 1;
            $grand_total = 0;
            while ($row = mysqli_fetch_assoc($detail_result)) {
                $grand_total += $row['sub_total'];
                echo "<tr>
                        <td>" . $no++ . "</td>
                        <td>" . htmlspecialchars($row['uraian_pekerjaan']) . "</td>
                        <td>" . htmlspecialchars($row['nama_satuan']) . "</td>
                        <td>" . htmlspecialchars($row['volume']) . "</td>
                        <td>Rp " . number_format($row['harga_satuan'], 0, ',', '.') . "</td>
                        <td>Rp " . number_format($row['sub_total'], 0, ',', '.') . "</td>  
                                                                      
                        <td class='text-center'>
                        </td>
                      </tr>";
            }
            echo "<tr>
                    <td colspan='5' class='text-end fw-bold'>Total</td>
                    <td class='fw-bold'>Rp " . number_format($grand_total, 0, ',', '.') . "</td>
                  </tr>";
        } 
        ?>
        </tbody>
      </table>
      
    </div>
    <div class="mt-3 text-end">
  <button id="btnSimpanSemua" class="btn btn-success">
    <i class="fa fa-save"></i> Simpan RAB
  </button>
</div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="/SISTEM_RAB/assets/js/bootstrap.bundle.min.js"></script>


<script>
  let kategoriList = [];
  let pekerjaanList = [];

  $(function() {
    // Konversi angka ke angka romawi
    function toRoman(num) {
      const romans = [
        ["M",1000], ["CM",900], ["D",500], ["CD",400],
        ["C",100], ["XC",90], ["L",50], ["XL",40],
        ["X",10], ["IX",9], ["V",5], ["IV",4], ["I",1]
      ];
      let result = '';
      for (let [letter, value] of romans) {
        while (num >= value) {
          result += letter;
          num -= value;
        }
      }
      return result;
    }

    // Load data kategori dari server (harus berupa objek dengan id_kategori & nama_kategori)
    function loadKategori() {
      return $.ajax({
        url: 'get_kategori.php',
        dataType: 'json',
        success: data => kategoriList = data,
        error: () => kategoriList = []
      });
    }

    // Load data pekerjaan & satuan (harus berupa objek dengan id_pekerjaan, uraian_pekerjaan, nama_satuan)
    function loadPekerjaanSatuan() {
      return $.ajax({
        url: 'get_pekerjaan.php',
        dataType: 'json',
        success: data => pekerjaanList = data,
        error: () => pekerjaanList = []
      });
    }

    // Autocomplete input kategori
    function bindAutocompleteKategori(input) {
      input.autocomplete({
        source: kategoriList.map(k => k.nama_kategori),
        minLength: 0,
        delay: 100,
      }).focus(function() {
        $(this).autocomplete("search", "");
      });
    }

    // Autocomplete input pekerjaan
    function bindAutocompletePekerjaan(input) {
      input.autocomplete({
        source: pekerjaanList.map(p => p.uraian_pekerjaan),
        minLength: 0,
        delay: 100,
        select: function(event, ui) {
          let pekerjaan = pekerjaanList.find(p => p.uraian_pekerjaan === ui.item.value);
          if (pekerjaan) {
            $(this).closest('tr').find('input.satuan').val(pekerjaan.nama_satuan);
          }
        }
      }).focus(function() {
        $(this).autocomplete("search", "");
      });
    }

    // Update nomor kategori, pekerjaan, subtotal kategori, dan total keseluruhan
    function updateRowNumber() {
      let kategoriCount = 0;
      $('#tblKategori tbody tr').each(function() {
        if ($(this).hasClass('kategori') || $(this).hasClass('input-kategori')) {
          kategoriCount++;
          $(this).find('td:first').text(toRoman(kategoriCount));

          let pekerjaanCount = 0;
          let totalKategori = 0;
          let nextRow = $(this).next();

          while (nextRow.length && (nextRow.hasClass('pekerjaan') || nextRow.hasClass('input-pekerjaan') || nextRow.hasClass('sub-total'))) {
            if (nextRow.hasClass('pekerjaan') || nextRow.hasClass('input-pekerjaan')) {
              pekerjaanCount++;
              nextRow.find('td:first').text(pekerjaanCount);

              let jumlahText = nextRow.find('td').eq(5).text().replace(/[^\d]/g, '');
              let jumlahVal = parseInt(jumlahText) || 0;
              totalKategori += jumlahVal;
            }
            nextRow = nextRow.next();
          }
          updateSubTotalRow($(this), totalKategori);
        }
      });
      updateTotalKeseluruhan();
    }

    // Update atau buat baris subtotal kategori
    function updateSubTotalRow(kategoriRow, totalKategori) {
      if (totalKategori === 0) {
        kategoriRow.nextUntil('tr.kategori').filter('.sub-total').remove();
        return;
      }

      let subTotalRow = kategoriRow.nextUntil('tr.kategori').filter('.sub-total').first();

      if (subTotalRow.length) {
        subTotalRow.find('td').eq(1).text('Sub Total');
        subTotalRow.find('td').eq(5).text('Rp ' + totalKategori.toLocaleString('id-ID'));
      } else {
        let insertAfter = kategoriRow;
        let nextRow = kategoriRow.next();

        while(nextRow.length && (nextRow.hasClass('pekerjaan') || nextRow.hasClass('input-pekerjaan'))) {
          insertAfter = nextRow;
          nextRow = nextRow.next();
        }

        const subTotalHtml = $(` 
          <tr class="sub-total">
            <td></td>
            <td class="fw-bold">Sub Total</td>
            <td></td>
            <td></td>
            <td></td>
            <td class="fw-bold">Rp ${totalKategori.toLocaleString('id-ID')}</td>
            <td></td>
          </tr>
        `);

        insertAfter.after(subTotalHtml);
      }
    }

    // Update atau buat baris total keseluruhan
    function updateTotalKeseluruhan() {
      let totalKeseluruhan = 0;
      $('#tblKategori tbody tr.sub-total').each(function() {
        let subtotalText = $(this).find('td').eq(5).text().replace(/[^\d]/g, '');
        let subtotalVal = parseInt(subtotalText) || 0;
        totalKeseluruhan += subtotalVal;
      });

      $('#tblKategori tbody tr.total-keseluruhan').remove();

      if (totalKeseluruhan === 0) return;

      const totalRowHtml = $(`
        <tr class="table-success total-keseluruhan">
          <td></td>
          <td class="fw-bold">Total Keseluruhan</td>
          <td></td>
          <td></td>
          <td></td>
          <td class="fw-bold">Rp ${totalKeseluruhan.toLocaleString('id-ID')}</td>
          <td></td>
        </tr>
      `);

      $('#tblKategori tbody').append(totalRowHtml);
    }

    // Tambah baris kategori baru
    function tambahBarisKategori() {
      $('#tblKategori tbody tr.no-data').remove();
      const newRow = $(`
        <tr class="input-kategori" data-kategori-id="${Date.now()}">
          <td></td>
          <td colspan="5"><input type="text" class="form-control kategori-autocomplete" placeholder="Ketik kategori" autocomplete="off" /></td>
          <td class="text-center">
            <button type="button" class="btn btn-success btn-sm btn-simpan"><i class="fa fa-check"></i></button>
            <button type="button" class="btn btn-danger btn-sm btn-batal"><i class="fa fa-times"></i></button>
          </td>
        </tr>
      `);
      $('#tblKategori tbody').append(newRow);
      bindAutocompleteKategori(newRow.find('input.kategori-autocomplete'));
      updateRowNumber();
    }

    // Event tombol tambah kategori
    $('#btnTambahKategori').on('click', function() {
      if (kategoriList.length === 0) {
        $.when(loadKategori()).done(tambahBarisKategori);
      } else {
        tambahBarisKategori();
      }
    });

    // Event simpan kategori baru
    $('#tblKategori').on('click', '.btn-simpan', function() {
      const row = $(this).closest('tr');
      const val = row.find('input.kategori-autocomplete').val().trim();

      if (!val) {
        alert('Kategori tidak boleh kosong!');
        return;
      }

      const kategoriId = row.data('kategori-id') || Date.now();
      row.removeClass('input-kategori').addClass('kategori').attr('data-kategori-id', kategoriId);
      row.html(`
        <td></td>
        <td>
          ${val}
          <button type="button" class="btn btn-outline-secondary btn-sm btn-toggle-pekerjaan ms-2" title="Tampilkan / Sembunyikan Pekerjaan" style="padding: 2px 6px; font-size: 0.75rem;">
            <i class="fa fa-chevron-up"></i>
          </button>
        </td>
        <td colspan="4"></td>
        <td class="text-center">
          <button class="btn btn-primary btn-sm btn-tambah-pekerjaan" title="Tambah Pekerjaan" style="border-radius:50%;padding:6px 9px;">
            <i class="fa fa-plus"></i>
          </button>
          <button class="btn btn-danger btn-sm btn-batal ms-1" title="Hapus Kategori">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      `);
      row.addClass('table-primary mt-4');
      updateRowNumber();
    });

    // Event batal input kategori
    $('#tblKategori').on('click', '.btn-batal', function() {
      const row = $(this).closest('tr');
      row.remove();

      if ($('#tblKategori tbody tr').length === 0) {
        $('#tblKategori tbody').append('<tr class="no-data"><td colspan="7" class="text-center">Tidak ada detail pekerjaan</td></tr>');
      }

      updateRowNumber();
    });

    // Event tambah pekerjaan pada kategori
    $('#tblKategori').on('click', '.btn-tambah-pekerjaan', function() {
      const kategoriRow = $(this).closest('tr');
      const kategoriId = kategoriRow.data('kategori-id');

      let maxNomor = 0;
      kategoriRow.nextAll('tr.pekerjaan, tr.input-pekerjaan').each(function() {
        if ($(this).data('parent-kategori-id') === kategoriId) {
          let nomor = parseInt($(this).find('td:first').text());
          if (!isNaN(nomor) && nomor > maxNomor) maxNomor = nomor;
        } else {
          return false;
        }
      });

      if (kategoriRow.next().hasClass('input-pekerjaan')) return;

      const pekerjaanRow = $(`
        <tr class="input-pekerjaan" data-parent-kategori-id="${kategoriId}">
          <td>${maxNomor + 1}</td>
          <td><input type="text" class="form-control uraian-pekerjaan" placeholder="Ketik uraian pekerjaan" autocomplete="off" /></td>
          <td><input type="text" class="form-control satuan" placeholder="Satuan" readonly /></td>
          <td><input type="number" class="form-control volume" placeholder="Volume" min="0" /></td>
          <td><input type="number" class="form-control harga-satuan" placeholder="Harga Satuan" min="0" /></td>
          <td><input type="text" class="form-control jumlah" placeholder="Jumlah" readonly /></td>
          <td class="text-center">
            <button type="button" class="btn btn-success btn-sm btn-simpan-pekerjaan"><i class="fa fa-check"></i></button>
            <button type="button" class="btn btn-danger btn-sm btn-batal-pekerjaan"><i class="fa fa-times"></i></button>
          </td>
        </tr>
      `);

      let lastPekerjaan = null;
      kategoriRow.nextAll('tr.pekerjaan, tr.input-pekerjaan').each(function() {
        if ($(this).data('parent-kategori-id') === kategoriId) {
          lastPekerjaan = $(this);
        } else {
          return false;
        }
      });

      if (lastPekerjaan) lastPekerjaan.after(pekerjaanRow);
      else kategoriRow.after(pekerjaanRow);

      bindAutocompletePekerjaan(pekerjaanRow.find('input.uraian-pekerjaan'));
    });

    // Event simpan pekerjaan
    $('#tblKategori').on('click', '.btn-simpan-pekerjaan', function() {
      const row = $(this).closest('tr');
      const uraian = row.find('input.uraian-pekerjaan').val().trim();
      const satuan = row.find('input.satuan').val().trim();
      const volume = parseFloat(row.find('input.volume').val());
      const hargaSatuan = parseFloat(row.find('input.harga-satuan').val());

      if (!uraian) { alert('Uraian pekerjaan tidak boleh kosong!'); return; }
      if (!satuan) { alert('Satuan tidak boleh kosong!'); return; }
      if (isNaN(volume) || volume <= 0) { alert('Volume harus lebih dari 0!'); return; }
      if (isNaN(hargaSatuan) || hargaSatuan <= 0) { alert('Harga satuan harus lebih dari 0!'); return; }

      const total = volume * hargaSatuan;
      const nomor = row.find('td:first').text();

      row.removeClass('input-pekerjaan').addClass('pekerjaan');
      row.html(`
        <td>${nomor}</td>
        <td>${uraian}</td>
        <td>${satuan}</td>
        <td>${volume}</td>
        <td>Rp ${hargaSatuan.toLocaleString('id-ID')}</td>
        <td>Rp ${total.toLocaleString('id-ID')}</td>
        <td class="text-center">
          <button class="btn btn-danger btn-sm btn-batal-pekerjaan" title="Hapus Pekerjaan">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      `);

      updateRowNumber();
    });

    // Event batal pekerjaan
    $('#tblKategori').on('click', '.btn-batal-pekerjaan', function() {
      $(this).closest('tr').remove();
      updateRowNumber();
    });

    // Toggle pekerjaan show/hide di kategori
    $('#tblKategori').on('click', '.btn-toggle-pekerjaan', function() {
      const kategoriRow = $(this).closest('tr.kategori, tr.input-kategori');
      if (!kategoriRow.length) return;
      const kategoriId = kategoriRow.data('kategori-id');
      if (!kategoriId) return;

      const pekerjaanRows = $(`#tblKategori tbody tr.pekerjaan[data-parent-kategori-id='${kategoriId}'], #tblKategori tbody tr.input-pekerjaan[data-parent-kategori-id='${kategoriId}']`);

      if (pekerjaanRows.is(':visible')) {
        pekerjaanRows.hide();
        $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
      } else {
        pekerjaanRows.show();
        $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
      }
    });

    // Load data kategori & pekerjaan, bind autocomplete, update nomor
    $.when(loadKategori(), loadPekerjaanSatuan()).done(function() {
      bindAutocompleteKategori($('input.kategori-autocomplete'));
      bindAutocompletePekerjaan($('input.uraian-pekerjaan'));
      updateRowNumber();
    });

    // Simpan semua data ke server via AJAX
    $('#btnSimpanSemua').on('click', function() {
      const id_rab_material = <?= json_encode($id_rab_material) ?>;

      let dataToSend = [];

      $('#tblKategori tbody tr.kategori').each(function() {
        const kategoriId = $(this).data('kategori-id');
        const kategoriNama = $(this).find('td').eq(1).text().trim();

        $(this).nextAll('tr.pekerjaan').each(function() {
          if ($(this).data('parent-kategori-id') !== kategoriId) return false;

          const uraian = $(this).find('td').eq(1).text().trim();
          const satuan = $(this).find('td').eq(2).text().trim();
          const volume = parseInt($(this).find('td').eq(3).text());
          const harga_satuan_text = $(this).find('td').eq(4).text().replace(/[^\d]/g, '');
          const harga_satuan = parseInt(harga_satuan_text);
          const sub_total_text = $(this).find('td').eq(5).text().replace(/[^\d]/g, '');
          const sub_total = parseInt(sub_total_text);

          const pekerjaanObj = pekerjaanList.find(p => p.uraian_pekerjaan === uraian);
          const id_pekerjaan = pekerjaanObj ? pekerjaanObj.id_pekerjaan : null;

          const kategoriObj = kategoriList.find(k => k.nama_kategori.trim().toLowerCase() === kategoriNama.trim().toLowerCase());
          const id_kategori = kategoriObj ? kategoriObj.id_kategori : null;

          if (!id_pekerjaan || !id_kategori) {
            console.warn('Data id_pekerjaan atau id_kategori tidak ditemukan:', { id_pekerjaan, id_kategori, uraian, kategoriNama });
            return;
          }

          dataToSend.push({
            id_kategori,
            id_pekerjaan,
            volume,
            harga_satuan,
            sub_total
          });
        });
      });

      console.log('Data yang dikirim:', dataToSend);

      if (dataToSend.length === 0) {
        alert('Tidak ada data yang disimpan. Pastikan Anda telah menambahkan pekerjaan.');
        return;
      }

      $.ajax({
        url: 'add_detail_rab_material.php',
        method: 'POST',
        data: {
          id_rab_material,
          detail: JSON.stringify(dataToSend)
        },
        success: function(res) {
          alert(res.message || 'Data berhasil disimpan');
                window.location.href = 'transaksi_rab_material.php';  // Redirect setelah simpan sukses
          // Kalau perlu, reload halaman atau update UI di sini
        },
        error: function() {
          alert('Gagal menyimpan data');
        }
      });
    });

  });
</script>
</body>
</html>
