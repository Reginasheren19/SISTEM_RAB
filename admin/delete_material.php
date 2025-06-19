<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Validasi ID Material
if (!isset($_GET['id_material']) || !is_numeric($_GET['id_material'])) {
    $_SESSION['error_message'] = "ID Material tidak valid.";
    header("Location: master_material.php");
    exit();
}
$id_material_to_delete = (int)$_GET['id_material'];

try {
    // =============================================================================
    // BAGIAN PENGECEKAN (KUNCI UTAMA)
    // =============================================================================

    // 2. CEK PENGGUNAAN: Cek apakah material ada di detail pembelian
    $sql_check_pembelian = "SELECT COUNT(*) as total FROM detail_pencatatan_pembelian WHERE id_material = ?";
    $stmt_check_pembelian = $koneksi->prepare($sql_check_pembelian);
    $stmt_check_pembelian->bind_param("i", $id_material_to_delete);
    $stmt_check_pembelian->execute();
    $result_pembelian = $stmt_check_pembelian->get_result()->fetch_assoc();
    $stmt_check_pembelian->close();

    // 3. CEK PENGGUNAAN: Cek apakah material ada di detail distribusi
    $sql_check_distribusi = "SELECT COUNT(*) as total FROM detail_distribusi WHERE id_material = ?";
    $stmt_check_distribusi = $koneksi->prepare($sql_check_distribusi);
    $stmt_check_distribusi->bind_param("i", $id_material_to_delete);
    $stmt_check_distribusi->execute();
    $result_distribusi = $stmt_check_distribusi->get_result()->fetch_assoc();
    $stmt_check_distribusi->close();

    // 4. LOGIKA UTAMA: Jika tidak dipakai, baru hapus. Jika dipakai, beri peringatan.
    if ($result_pembelian['total'] > 0 || $result_distribusi['total'] > 0) {
        // JIKA MATERIAL SUDAH DIGUNAKAN: Batalkan proses dan beri peringatan
        $_SESSION['error_message'] = "Material tidak bisa dihapus karena sudah digunakan dalam transaksi pembelian atau distribusi.";
        header("Location: master_material.php");
        exit();

    } else {
        // JIKA MATERIAL AMAN UNTUK DIHAPUS: Lanjutkan proses hapus dengan transaksi
        $koneksi->begin_transaction();

        // Hapus catatan stok dari tabel 'stok_material'
        $sql_stok = "DELETE FROM stok_material WHERE id_material = ?";
        $stmt_stok = $koneksi->prepare($sql_stok);
        $stmt_stok->bind_param("i", $id_material_to_delete);
        $stmt_stok->execute();
        $stmt_stok->close();

        // Hapus data utama dari tabel 'master_material'
        $sql_master = "DELETE FROM master_material WHERE id_material = ?";
        $stmt_master = $koneksi->prepare($sql_master);
        $stmt_master->bind_param("i", $id_material_to_delete);
        $stmt_master->execute();

        if ($stmt_master->affected_rows === 0) {
            throw new Exception("Material tidak ditemukan untuk dihapus.");
        }
        $stmt_master->close();

        // Jika semua berhasil, simpan perubahan
        $koneksi->commit();
        $_SESSION['pesan_sukses'] = "Material dengan ID #{$id_material_to_delete} yang belum pernah dipakai berhasil dihapus.";
    }

} catch (Exception $e) {
    // Jika ada error di tengah jalan, batalkan semua
    $koneksi->rollback();
    $_SESSION['error_message'] = "Gagal menghapus material: " . $e->getMessage();

} finally {
    if (isset($koneksi)) {
        $koneksi->close();
    }
}

// Redirect kembali ke halaman daftar
header("Location: master_material.php");
exit();

?>