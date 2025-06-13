<?php
// FILE: delete_pengajuan_upah.php (dengan validasi status)

// Sertakan file koneksi database Anda
include("../config/koneksi_mysql.php");

// 1. Validasi Input: Pastikan ID pengajuan ada dan merupakan angka
if (!isset($_GET['id_pengajuan_upah']) || !filter_var($_GET['id_pengajuan_upah'], FILTER_VALIDATE_INT)) {
    header("Location: pengajuan_upah.php?msg=Error: ID pengajuan tidak valid.");
    exit;
}

// 2. Amankan ID dari SQL Injection
$id_pengajuan_upah = mysqli_real_escape_string($koneksi, $_GET['id_pengajuan_upah']);

// [BARU] Tambahkan validasi status di sisi server
// Langkah A: Ambil status pengajuan saat ini dari database
$sql_check_status = "SELECT status_pengajuan FROM pengajuan_upah WHERE id_pengajuan_upah = '$id_pengajuan_upah'";
$result_check = mysqli_query($koneksi, $sql_check_status);

if (mysqli_num_rows($result_check) > 0) {
    $pengajuan = mysqli_fetch_assoc($result_check);
    $status_sekarang = $pengajuan['status_pengajuan'];

    // Langkah B: Jika statusnya BUKAN 'diajukan' atau 'ditolak', hentikan proses dan beri pesan error
    if (!in_array($status_sekarang, ['diajukan', 'ditolak'])) {
        header("Location: pengajuan_upah.php?msg=Error: Pengajuan dengan status '$status_sekarang' tidak dapat dihapus.");
        exit;
    }
} else {
    // Jika pengajuan dengan ID tersebut tidak ditemukan
    header("Location: pengajuan_upah.php?msg=Error: Pengajuan tidak ditemukan.");
    exit;
}

// 3. Gunakan Transaksi Database untuk Menjamin Integritas Data
mysqli_begin_transaction($koneksi);

try {
    // Hapus semua baris terkait di tabel detail terlebih dahulu
    $sql_delete_detail = "DELETE FROM detail_pengajuan_upah WHERE id_pengajuan_upah = '$id_pengajuan_upah'";
    if (!mysqli_query($koneksi, $sql_delete_detail)) {
        throw new Exception("Gagal menghapus detail pengajuan: " . mysqli_error($koneksi));
    }

    // Hapus baris utama di tabel master pengajuan
    $sql_delete_master = "DELETE FROM pengajuan_upah WHERE id_pengajuan_upah = '$id_pengajuan_upah'";
    if (!mysqli_query($koneksi, $sql_delete_master)) {
        throw new Exception("Gagal menghapus pengajuan utama: " . mysqli_error($koneksi));
    }

    // 4. Jika kedua query berhasil, commit transaksi
    mysqli_commit($koneksi);

    // 5. Redirect kembali ke halaman daftar dengan pesan sukses
    header("Location: pengajuan_upah.php?msg=Pengajuan berhasil dihapus.");
    exit;

} catch (Exception $e) {
    // 6. Jika terjadi error, batalkan semua perubahan
    mysqli_rollback($koneksi);
    header("Location: pengajuan_upah.php?msg=Error: Terjadi kesalahan saat menghapus data.");
    exit;
}
?>
