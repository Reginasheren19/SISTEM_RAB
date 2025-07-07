<?php
session_start();
include("../config/koneksi_mysql.php");

// Validasi: Pastikan ada ID pembelian
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID Pembelian tidak ditemukan.";
    header("Location: pencatatan_pembelian.php");
    exit();
}

$pembelian_id = $_GET['id'];

// Ambil data pembelian
// Menggunakan SELECT * untuk mengambil SEMUA kolom dari tabel
$stmt = $koneksi->prepare("SELECT * FROM pencatatan_pembelian WHERE id_pembelian = ?");$stmt->bind_param("i", $pembelian_id);
$stmt->execute();
$result = $stmt->get_result();
$pembelian = $result->fetch_assoc();

if (!$pembelian) {
    $_SESSION['error_message'] = "Data pembelian tidak ditemukan.";
    header("Location: pencatatan_pembelian.php");
    exit();
}

// Ambil data material + satuan
$result_material = mysqli_query($koneksi, "
  SELECT 
    m.id_material, 
    m.nama_material, 
    s.nama_satuan 
  FROM 
    master_material m
  LEFT JOIN 
    master_satuan s ON m.id_satuan = s.id_satuan
  ORDER BY 
    m.nama_material ASC
");

$materials = [];
while ($row = mysqli_fetch_assoc($result_material)) {
    $materials[] = $row;
}

// Proses penyimpanan detail pembelian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari POST
    $items_json = $_POST['items_json'] ?? null;

    if (empty($items_json)) {
        $_SESSION['error_message'] = "Data detail tidak ditemukan.";
        header("Location: input_detail_pembelian.php?id=" . $pembelian_id);
        exit();
    }

    $items = json_decode($items_json, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($items)) {
        $_SESSION['error_message'] = "Format data item tidak valid.";
        header("Location: input_detail_pembelian.php?id=" . $pembelian_id);
        exit();
    }

    $koneksi->begin_transaction();

    try {
        // SQL untuk memasukkan detail pembelian
        $sql = "INSERT INTO detail_pencatatan_pembelian (id_pembelian, id_material, quantity, harga_satuan_pp, sub_total_pp) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($sql);

        if (!$stmt) {
            throw new Exception("Gagal menyiapkan query: " . $koneksi->error);
        }

        // Loop melalui semua item dan masukkan data
        foreach ($items as $item) {
            // Bind parameter ke statement yang sudah disiapkan
            $stmt->bind_param("iiidd", 
                $pembelian_id, 
                $item['id_material'], 
                $item['quantity'], 
                $item['harga_satuan_pp'], 
                $item['sub_total_pp']
            );

            // Eksekusi query untuk item ini
            if (!$stmt->execute()) {
                throw new Exception("Gagal mengeksekusi query: " . $stmt->error);
            }
        }

        // Setelah semua detail dimasukkan, update total_biaya
        $update_total_biaya = "UPDATE pencatatan_pembelian SET total_biaya = 
                               (SELECT SUM(sub_total_pp) 
                                FROM detail_pencatatan_pembelian 
                                WHERE id_pembelian = ?) 
                               WHERE id_pembelian = ?";
        $update_stmt = $koneksi->prepare($update_total_biaya);
        $update_stmt->bind_param("ii", $pembelian_id, $pembelian_id);
        $update_stmt->execute();

        // Commit transaksi
        $koneksi->commit();

        $_SESSION['pesan_sukses'] = "Semua detail material untuk pembelian ID #{$pembelian_id} berhasil disimpan!";
        header("Location: pencatatan_pembelian.php");
        exit();

    } catch (Exception $e) {
        // Jika terjadi error, batalkan transaksi
        $koneksi->rollback();

        $_SESSION['error_message'] = "Terjadi kesalahan, data gagal disimpan: " . $e->getMessage();
        header("Location: input_detail_pembelian.php?id=" . $pembelian_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Input Pembelian</title>
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
            <h3 class="fw-bold mb-3">Pencatatan Pembelian</h3>
            <ul class="breadcrumbs mb-3">
                <li class="nav-home"><a href="dashboard.php"><i class="icon-home"></i></a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="pencatatan_pembelian.php">Pencatatan Pembelian</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="">Input Pembelian Material</a></li>
            </ul>
        </div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Informasi Transaksi</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="row mb-2">
                    <div class="col-4 fw-bold">ID Pembelian</div>
                    <div class="col-8">
                        <?php
                            $tahun_pembelian = date('Y', strtotime($pembelian['tanggal_pembelian']));
                            $formatted_id = 'PB' . $pembelian['id_pembelian'] . $tahun_pembelian;
                            echo ": " . htmlspecialchars($formatted_id);
                        ?>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Tanggal</div>
                    <div class="col-8">: <?= date("d F Y", strtotime(htmlspecialchars($pembelian['tanggal_pembelian'] ?? ''))) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Keterangan</div>
                    <div class="col-8">: <?= htmlspecialchars($pembelian['keterangan_pembelian'] ?? '') ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Bukti Pembayaran</div>
                    <div class="col-8">
                        <?php if (!empty($pembelian['bukti_pembayaran'])): ?>
                            <?php $image_path = '../uploads/bukti_pembayaran/' . htmlspecialchars($pembelian['bukti_pembayaran']); ?>
                            : <a href="<?= $image_path ?>" target="_blank">
                                <img src="<?= $image_path ?>" alt="Bukti Pembayaran" style="max-width: 150px; height: auto; border-radius: 5px; vertical-align: top;">
                              </a>
                        <?php else: ?>
                            : <span class="text-muted fst-italic">Tidak diunggah.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>            
        
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Tambah Material</h4>
            </div>
            <div class="card-body">
                <form id="form-add-item" enctype="multipart/form-data">
                  <div class="row align-items-end">
                      <div class="col-md-3 mb-3">
                          <label for="id_material" class="form-label">Nama Material</label>
                          <select class="form-select" id="id_material" name="id_material" required>
                              <option value="" selected disabled>-- Pilih Material --</option>
                              <?php foreach ($materials as $material): ?>
                                  <option 
                                      value="<?= $material['id_material'] ?? '' ?>" 
                                      data-nama="<?= htmlspecialchars($material['nama_material'] ?? '') ?>"
                                      data-satuan="<?= htmlspecialchars($material['nama_satuan'] ?? '') ?>"
                                      data-allow-upload="<?= $material['allow_upload'] ?? '0' ?>">
                                      <?= htmlspecialchars($material['nama_material'] ?? '') ?>
                                  </option>
                              <?php endforeach; ?>
                          </select>
                      </div>

                      <div class="col-md-2 mb-3">
                          <label for="satuan" class="form-label">Satuan</label>
                          <input type="text" class="form-control" id="satuan" name="satuan" readonly>
                      </div>

                      <div class="col-md-2 mb-3">
                          <label for="quantity" class="form-label">Jumlah</label>
                          <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                      </div>

                      <div class="col-md-2 mb-3">
                          <label for="harga_satuan" class="form-label">Harga Satuan (Rp)</label>
                          <input type="number" class="form-control" id="harga_satuan" name="harga_satuan" min="0" required>
                      </div>

                      <div class="col-md-3 mb-3">
                          <label for="sub_total" class="form-label">Sub Total (Rp)</label>
                          <input type="text" class="form-control" id="sub_total" name="sub_total" readonly>
                      </div>
                  </div>
                  <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah ke Daftar</button>
                </form>

            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Daftar Material yang Akan Disimpan</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="detail-pembelian-table">
                        <thead>
                            <tr>
                                <th>Nama Material</th>
                                <th>Satuan</th>
                                <th>Jumlah</th>
                                <th>Harga Satuan</th>
                                <th>Sub Total</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Grand Total</td>
                                <td class="grand-total" id="grand_total">Rp 0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <form id="form-save-all" action="add_detail_pembelian.php" method="POST" style="display:inline;">
                        <input type="hidden" name="pembelian_id" value="<?= $pembelian_id ?>">
                        <input type="hidden" name="items_json" id="items_json">
                        <button type="button" id="btn-save-all" class="btn btn-success btn-lg">
                            <i class="fa fa-save"></i> Simpan Semua Pembelian
                        </button>
                    </form>
                    <a href="pencatatan_pembelian.php" class="btn btn-secondary btn-lg ms-2">Batal</a>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {

    // ... (fungsi formatRupiah, calculateSubtotal, dll tidak berubah) ...
    function formatRupiah(angka) {
        let number_string = String(angka).replace(/[^,\d]/g, ''),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return 'Rp ' + (rupiah ? rupiah : '0');
    }
    
    function calculateSubtotal() {
        const quantity = parseFloat($('#quantity').val()) || 0;
        const harga = parseFloat($('#harga_satuan').val()) || 0;
        const subtotal = quantity * harga;
        $('#sub_total').val(formatRupiah(subtotal));
    }

    $('#quantity, #harga_satuan').on('keyup input', calculateSubtotal);

    function updateGrandTotal() {
        let grandTotal = 0;
        $('#detail-pembelian-table tbody tr').each(function() {
            const subtotal = parseFloat($(this).data('subtotal')) || 0;
            grandTotal += subtotal;
        });
        $('#grand_total').text(formatRupiah(grandTotal));
    }

    $('#id_material').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const satuan = selectedOption.data('satuan');
        $('#satuan').val(satuan || '');
    });
    
    $('#form-add-item').on('submit', function(e) {
        e.preventDefault();
        const materialSelect = $('#id_material');
        const id_material = materialSelect.val();
        const nama_material = materialSelect.find('option:selected').data('nama');
        const satuan = $('#satuan').val();
        const quantity = $('#quantity').val();
        const harga_satuan = $('#harga_satuan').val();

        if (!id_material) {
            alert('Harap pilih Nama Material.');
            return;
        }
        if (!quantity || parseFloat(quantity) <= 0) {
            alert('Jumlah harus diisi dan lebih besar dari 0.');
            $('#quantity').focus();
            return;
        }
        if (harga_satuan === '' || parseFloat(harga_satuan) < 0) {
            alert('Harga Satuan harus diisi dan tidak boleh negatif.');
            $('#harga_satuan').focus();
            return;
        }

        const subtotal = parseFloat(quantity) * parseFloat(harga_satuan);
        const newRow = `
            <tr data-id_material="${id_material}" data-quantity="${quantity}" data-harga_satuan="${harga_satuan}" data-subtotal="${subtotal}">
                <td>${nama_material}</td>
                <td>${satuan}</td>
                <td>${quantity}</td>
                <td>${formatRupiah(harga_satuan)}</td>
                <td>${formatRupiah(subtotal)}</td>
                <td><button type="button" class="btn btn-sm btn-danger btn-delete-item"><i class="fa fa-trash"></i></button></td>
            </tr>
        `;
        $('#detail-pembelian-table tbody').append(newRow);
        updateGrandTotal();
        $('#form-add-item')[0].reset();
        $('#satuan').val('');
        $('#sub_total').val('');
    });

    $('#detail-pembelian-table').on('click', '.btn-delete-item', function() {
        if (confirm('Anda yakin ingin menghapus item ini?')) {
            $(this).closest('tr').remove();
            updateGrandTotal();
        }
    });

    $('#btn-save-all').on('click', function() {
        const items = [];
        $('#detail-pembelian-table tbody tr').each(function() {
            const row = $(this);
            items.push({
                id_material: row.data('id_material'),
                quantity: row.data('quantity'),
                harga_satuan_pp: row.data('harga_satuan'),
                // --- INI PERBAIKANNYA ---
                // DARI 'sub_total' MENJADI 'subtotal' (TANPA UNDERSCORE)
                sub_total_pp: row.data('subtotal')
            });
        });

        if (items.length === 0) {
            alert('Tidak ada item untuk disimpan.');
            return;
        }
        
        $('#items_json').val(JSON.stringify(items));
        $('#form-save-all').submit();
    });
    
    $('#id_material').trigger('change');
});
</script>
</body>
</html>