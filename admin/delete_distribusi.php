<?php
session_start();
include("../config/koneksi_mysql.php");

// -----------------------------------------------------------------------------
// FILE: delete_distribusi.php
// FUNGSI: Menghapus data distribusi header & detail, sekaligus mengembalikan stok.
// -----------------------------------------------------------------------------

// 1. Validasi ID dari URL yang dikirim oleh modal konfirmasi
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID Distribusi tidak valid untuk dihapus.";
    header("Location: distribusi_material.php");
    exit();
}
$id_distribusi_to_delete = (int)$_GET['id'];

// 2. Mulai Transaksi Database (Prinsip: Semua atau Tidak Sama Sekali)
mysqli_begin_transaction($koneksi);

try {
    // 3. Ambil semua item detail yang akan dihapus untuk tahu material & jumlahnya
    $sql_get_items = "SELECT id_material, jumlah_distribusi FROM detail_distribusi WHERE id_distribusi = ?";
    $stmt_get_items = mysqli_prepare($koneksi, $sql_get_items);
    mysqli_stmt_bind_param($stmt_get_items, "i", $id_distribusi_to_delete);
    mysqli_stmt_execute($stmt_get_items);
    $result_items = mysqli_stmt_get_result($stmt_get_items);

    $items_to_restore = [];
    while ($row = mysqli_fetch_assoc($result_items)) {
        $items_to_restore[] = $row;
    }
    mysqli_stmt_close($stmt_get_items); // Selalu tutup statement setelah selesai

    // 4. Loop untuk MENGEMBALIKAN stok material ke tabel stok_material
    if (!empty($items_to_restore)) {
        $sql_update_stok = "UPDATE stok_material SET jumlah_stok_tersedia = jumlah_stok_tersedia + ? WHERE id_material = ?";
        $stmt_update_stok = mysqli_prepare($koneksi, $sql_update_stok);

        foreach ($items_to_restore as $item) {
            mysqli_stmt_bind_param($stmt_update_stok, "di", $item['jumlah_distribusi'], $item['id_material']);
            mysqli_stmt_execute($stmt_update_stok);
        }
        mysqli_stmt_close($stmt_update_stok);
    }

    // 5. Setelah stok dikembalikan, hapus semua item dari tabel detail_distribusi
    $sql_delete_details = "DELETE FROM detail_distribusi WHERE id_distribusi = ?";
    $stmt_delete_details = mysqli_prepare($koneksi, $sql_delete_details);
    mysqli_stmt_bind_param($stmt_delete_details, "i", $id_distribusi_to_delete);
    mysqli_stmt_execute($stmt_delete_details);
    mysqli_stmt_close($stmt_delete_details);

    // 6. Terakhir, hapus data utama (header) dari tabel distribusi_material
    $sql_delete_header = "DELETE FROM distribusi_material WHERE id_distribusi = ?";
    $stmt_delete_header = mysqli_prepare($koneksi, $sql_delete_header);
    mysqli_stmt_bind_param($stmt_delete_header, "i", $id_distribusi_to_delete);
    mysqli_stmt_execute($stmt_delete_header);
    mysqli_stmt_close($stmt_delete_header);
    
    // 7. Jika semua langkah di atas berhasil, simpan semua perubahan secara permanen
    mysqli_commit($koneksi);

    $_SESSION['pesan_sukses'] = "Data distribusi berhasil dihapus dan stok telah dikembalikan.";

} catch (mysqli_sql_exception $exception) {
    // Jika ada satu saja error, batalkan semua query yang sudah dijalankan
    mysqli_rollback($koneksi);
    
    $_SESSION['error_message'] = "Gagal menghapus data. Terjadi kesalahan pada database. Error: " . $exception->getMessage();

} finally {
    // Apapun hasilnya, arahkan kembali ke halaman daftar
    header("Location: distribusi_material.php");
    exit();
}

?>