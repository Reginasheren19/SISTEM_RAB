<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Akses tidak sah.";
    header("Location: penerimaan_material.php");
    exit();
}

$id_pembelian = $_POST['id_pembelian'] ?? null;
$ids_detail_pembelian = $_POST['id_detail_pembelian'] ?? [];
$ids_material = $_POST['id_material'] ?? [];
$jumlah_diterima_arr = $_POST['jumlah_diterima'] ?? [];
$jumlah_rusak_arr = $_POST['jumlah_rusak'] ?? [];
$catatan_arr = $_POST['catatan'] ?? [];

if (empty($id_pembelian) || empty($ids_detail_pembelian)) {
    $_SESSION['error_message'] = "Data tidak lengkap.";
    header("Location: penerimaan_material.php");
    exit();
}

$koneksi->begin_transaction();

try {
    // Query untuk log tidak berubah
    $sql_log = "INSERT INTO log_penerimaan_material (id_pembelian, id_detail_pembelian, id_material, jumlah_diterima, jumlah_rusak, catatan, tanggal_penerimaan, jenis_penerimaan) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
    $stmt_log = $koneksi->prepare($sql_log);

    // [DIUBAH TOTAL] Query stok sekarang menggunakan UPSERT (INSERT ... ON DUPLICATE KEY UPDATE)
    $sql_stok = "
        INSERT INTO stok_material (id_material, jumlah_stok_tersedia) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE jumlah_stok_tersedia = jumlah_stok_tersedia + VALUES(jumlah_stok_tersedia)
    ";
    $stmt_stok = $koneksi->prepare($sql_stok);

    $sql_cek_harga = "SELECT harga_satuan_pp FROM detail_pencatatan_pembelian WHERE id_detail_pembelian = ?";
    $stmt_cek_harga = $koneksi->prepare($sql_cek_harga);

    for ($i = 0; $i < count($ids_detail_pembelian); $i++) {
        $detail_id = $ids_detail_pembelian[$i];
        $material_id = $ids_material[$i];
        $diterima_baik = (float)($jumlah_diterima_arr[$i] ?? 0);
        $rusak = (float)($jumlah_rusak_arr[$i] ?? 0);
        $catatan = $catatan_arr[$i] ?? '';

        if ($diterima_baik > 0 || $rusak > 0) {
            // Tentukan jenis penerimaan
            $stmt_cek_harga->bind_param("i", $detail_id);
            $stmt_cek_harga->execute();
            $harga_item = (float)($stmt_cek_harga->get_result()->fetch_assoc()['harga_satuan_pp'] ?? 0);
            $jenis_penerimaan = ($harga_item > 0) ? 'Penerimaan Awal' : 'Penerimaan Pengganti';

            // Masukkan ke log
            $stmt_log->bind_param("iiiddss", $id_pembelian, $detail_id, $material_id, $diterima_baik, $rusak, $catatan, $jenis_penerimaan);
            $stmt_log->execute();

            // [DIUBAH] Update stok jika ada barang yang diterima dalam kondisi baik
            if ($diterima_baik > 0) {
                // bind_param sekarang butuh id_material dan jumlah yg diterima
                $stmt_stok->bind_param("id", $material_id, $diterima_baik);
                $stmt_stok->execute();
            }
        }
    }
    
    $koneksi->commit();
    $_SESSION['pesan_sukses'] = "Penerimaan untuk Pembelian ID #{$id_pembelian} berhasil dicatat dan stok telah diperbarui.";

} catch (Exception $e) {
    $koneksi->rollback();
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
} finally {
    if (isset($stmt_log)) $stmt_log->close();
    if (isset($stmt_stok)) $stmt_stok->close();
    if (isset($stmt_cek_harga)) $stmt_cek_harga->close();
    
    // Arahkan kembali ke halaman penerimaan material agar PJ bisa melihat daftar tunggu terbaru
    header("Location: penerimaan_material.php");
    exit();
}
?>