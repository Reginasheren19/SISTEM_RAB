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

try {
    $sql = "INSERT INTO detail_pencatatan_pembelian (id_pembelian, id_material, quantity, harga_satuan_pp, sub_total_pp) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $koneksi->prepare($sql);

    if (!$stmt) {
        throw new Exception("Gagal menyiapkan query: " . $koneksi->error);
    }

    foreach ($items as $item) {
        if (!isset($item['id_material'], $item['quantity'], $item['harga_satuan_pp'], $item['sub_total_pp'])) {
            throw new Exception("Data item tidak lengkap pada salah satu baris.");
        }

        $stmt->bind_param(
            "iiiii",
            $pembelian_id,
            $item['id_material'],
            $item['quantity'],
            $item['harga_satuan_pp'],
            $item['sub_total_pp']
        );

        if (!$stmt->execute()) {
            throw new Exception("GAGAL execute(): " . $stmt->error);
        }
    }

    $koneksi->commit();
    $_SESSION['pesan_sukses'] = "Pembelian ID #{$pembelian_id} berhasil disimpan!";
    header("Location: pencatatan_pembelian.php");
    exit();

} catch (Exception $e) {
    $koneksi->rollback();
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: input_detail_pembelian.php?id=" . $pembelian_id);
    exit();

} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($koneksi)) {
        $koneksi->close();
    }
}
?>