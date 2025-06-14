<?php
// Memulai session untuk bisa mengirim pesan sukses/error kembali ke halaman utama
session_start();

// Menyertakan file koneksi database
include("../config/koneksi_mysql.php");

// 1. Memeriksa apakah ada 'id' yang dikirim lewat URL (contoh: delete_pembelian.php?id=123)
if (isset($_GET['id']) && !empty($_GET['id'])) { 
    
    // 2. Membersihkan ID untuk mencegah SQL Injection, meniru gayamu
    $hapus_id_pembelian = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 3. Hapus dulu semua baris di 'detail_pencatatan_pembelian' yang terkait dengan ID ini
    // Ini adalah langkah menghapus "anak-anaknya" terlebih dahulu
    $sql_delete_detail = "DELETE FROM detail_pencatatan_pembelian WHERE id_pembelian = '$hapus_id_pembelian'";
    $hapus_detail = mysqli_query($koneksi, $sql_delete_detail);

    // Jika proses hapus detail gagal, hentikan script dan tampilkan error
    if (!$hapus_detail) {
        // Sebaiknya, kita simpan error di session dan redirect, agar lebih rapi
        $_SESSION['error_message'] = "Gagal menghapus detail pembelian: " . mysqli_error($koneksi);
        header("Location: pencatatan_pembelian.php");
        exit;
    }

    // 4. Setelah detail berhasil dihapus, baru hapus data di tabel utama 'pencatatan_pembelian'
    // Ini adalah langkah menghapus "induknya"
    $sql_delete_master = "DELETE FROM pencatatan_pembelian WHERE id_pembelian = '$hapus_id_pembelian'";
    $hapus_master = mysqli_query($koneksi, $sql_delete_master);

    // Jika proses hapus data utama berhasil dan ada baris yang terpengaruh (terhapus)
    if ($hapus_master && mysqli_affected_rows($koneksi) > 0) {
        // Kirim pesan sukses dan arahkan kembali ke halaman daftar
        $_SESSION['pesan_sukses'] = "Data pembelian ID #{$hapus_id_pembelian} dan seluruh detailnya berhasil dihapus.";
        header("Location: pencatatan_pembelian.php");
        exit;
    } else {
        // Jika gagal atau tidak ada data yang cocok dengan ID
        $_SESSION['error_message'] = "Error menghapus data pembelian utama atau ID tidak ditemukan: " . mysqli_error($koneksi);
        header("Location: pencatatan_pembelian.php");
        exit;
    }

} else {
    // Jika tidak ada ID yang dikirim di URL
    $_SESSION['error_message'] = "Tidak ada ID pembelian yang dipilih untuk dihapus.";
    header("Location: pencatatan_pembelian.php");
    exit;
}

// Menutup koneksi (opsional karena script biasanya berhenti setelah redirect)
mysqli_close($koneksi);
?>