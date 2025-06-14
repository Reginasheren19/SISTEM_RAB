<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Validasi ID Material yang dikirim dari URL
if (!isset($_GET['id_material']) || !is_numeric($_GET['id_material'])) {
    $_SESSION['error_message'] = "ID Material tidak valid atau tidak ditemukan.";
    header("Location: master_material.php");
    exit();
}
$id_material_to_delete = (int)$_GET['id_material'];

// 2. Memulai Transaksi Database (PENTING!)
// Ini memastikan kedua proses hapus (stok dan master) berhasil, atau tidak sama sekali.
$koneksi->begin_transaction();

try {
    // 3. Hapus catatan stok terlebih dahulu dari tabel 'stok_material'
    $sql_stok = "DELETE FROM stok_material WHERE id_material = ?";
    $stmt_stok = $koneksi->prepare($sql_stok);
    if (!$stmt_stok) {
        throw new Exception("Gagal menyiapkan query untuk stok: " . $koneksi->error);
    }
    $stmt_stok->bind_param("i", $id_material_to_delete);
    $stmt_stok->execute();
    $stmt_stok->close();

    // 4. Setelah itu, baru hapus data utama dari tabel 'master_material'
    $sql_master = "DELETE FROM master_material WHERE id_material = ?";
    $stmt_master = $koneksi->prepare($sql_master);
    if (!$stmt_master) {
        throw new Exception("Gagal menyiapkan query untuk master: " . $koneksi->error);
    }
    $stmt_master->bind_param("i", $id_material_to_delete);
    $stmt_master->execute();

    // Cek apakah ada baris yang benar-benar terhapus
    if ($stmt_master->affected_rows === 0) {
        // Ini terjadi jika ID materialnya tidak ditemukan
        throw new Exception("Tidak ada data material dengan ID tersebut yang ditemukan.");
    }
    $stmt_master->close();

    // 5. Jika semua proses hapus berhasil, simpan perubahan secara permanen
    $koneksi->commit();
    $_SESSION['pesan_sukses'] = "Material dengan ID #{$id_material_to_delete} berhasil dihapus.";

} catch (Exception $e) {
    // 6. Jika ada SATU saja error, batalkan SEMUA proses hapus
    $koneksi->rollback();
    $_SESSION['error_message'] = "Gagal menghapus material: " . $e->getMessage();

} finally {
    // Tutup koneksi ke database
    if (isset($koneksi)) {
        $koneksi->close();
    }
}

// 7. Redirect kembali ke halaman daftar material
header("Location: master_material.php");
exit();
?>