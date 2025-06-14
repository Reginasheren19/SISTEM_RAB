<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Akses tidak sah.";
    header("Location: pencatatan_pembelian.php");
    exit();
}

$pembelian_id = $_POST['pembelian_id'] ?? null;
$items_json = $_POST['items_json'] ?? null;

if (empty($pembelian_id) || empty($items_json)) {
    $_SESSION['error_message'] = "Data tidak lengkap. Gagal menyimpan.";
    header("Location: input_detail_pembelian.php?id=" . $pembelian_id);
    exit();
}

$items = json_decode($items_json, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($items) || empty($items)) {
    $_SESSION['error_message'] = "Format data item tidak valid atau tidak ada item yang dikirim.";
    header("Location: input_detail_pembelian.php?id=" . $pembelian_id);
    exit();
}


$koneksi->begin_transaction();

$stmt_detail = null;
$stmt_stok = null;
$stmt_total = null;

try {
    // Siapkan query untuk insert detail di luar loop agar lebih efisien
    $sql_detail = "INSERT INTO detail_pencatatan_pembelian (id_pembelian, id_material, quantity, harga_satuan_pp, sub_total_pp) VALUES (?, ?, ?, ?, ?)";
    $stmt_detail = $koneksi->prepare($sql_detail);

    // Siapkan juga query untuk update stok di luar loop
    $sql_stok = "UPDATE stok_material SET jumlah_stok_tersedia = jumlah_stok_tersedia + ? WHERE id_material = ?";
    $stmt_stok = $koneksi->prepare($sql_stok);

    if (!$stmt_detail || !$stmt_stok) {
        throw new Exception("Gagal menyiapkan query: " . $koneksi->error);
    }

    // Loop melalui setiap item yang akan disimpan
    foreach ($items as $item) {
        // Validasi kelengkapan data per item
        if (!isset($item['id_material'], $item['quantity'], $item['harga_satuan_pp'], $item['sub_total_pp'])) {
            throw new Exception("Data item tidak lengkap pada salah satu baris.");
        }

        // Langkah 1: Simpan ke tabel detail_pencatatan_pembelian
        $stmt_detail->bind_param("iiddi", $pembelian_id, $item['id_material'], $item['quantity'], $item['harga_satuan_pp'], $item['sub_total_pp']);
        if (!$stmt_detail->execute()) {
            throw new Exception("Gagal menyimpan detail item: " . $stmt_detail->error);
        }

        // Langkah 2 (BARU): Update (tambah) stok di tabel stok_material
        $stmt_stok->bind_param("di", $item['quantity'], $item['id_material']);
        if (!$stmt_stok->execute()) {
            throw new Exception("Gagal mengupdate stok material: " . $stmt_stok->error);
        }
    }

    // Langkah 3 (BONUS): Hitung ulang dan update total_biaya di tabel induk
    $sql_update_total = "UPDATE pencatatan_pembelian SET total_biaya = (SELECT SUM(sub_total_pp) FROM detail_pencatatan_pembelian WHERE id_pembelian = ?) WHERE id_pembelian = ?";
    $stmt_total = $koneksi->prepare($sql_update_total);
    $stmt_total->bind_param("ii", $pembelian_id, $pembelian_id);
    $stmt_total->execute();
    
    // Jika semua proses berhasil, simpan permanen
    $koneksi->commit();
    $_SESSION['pesan_sukses'] = "Pembelian ID #{$pembelian_id} berhasil disimpan dan stok telah diperbarui.";
    header("Location: pencatatan_pembelian.php");
    exit();

} catch (Exception $e) {
    // Jika ada satu saja error, batalkan semua
    $koneksi->rollback();
    $_SESSION['error_message'] = "Terjadi kesalahan, semua data dibatalkan: " . $e->getMessage();
    header("Location: input_detail_pembelian.php?id=" . $pembelian_id);
    exit();

} finally {
    // Pastikan semua statement ditutup
    if (isset($stmt_detail)) $stmt_detail->close();
    if (isset($stmt_stok)) $stmt_stok->close();
    if (isset($stmt_total)) $stmt_total->close();
    if (isset($koneksi)) $koneksi->close();
}
?>