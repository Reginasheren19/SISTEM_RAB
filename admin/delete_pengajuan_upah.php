<?php
// Memulai session untuk menangani pesan notifikasi
session_start();

// Include file koneksi ke database Anda
include("../config/koneksi_mysql.php");

// 1. Validasi ID Pengajuan
// Memastikan ID pengajuan ada, dan merupakan angka yang valid.
if (!isset($_GET['id_rab_upah']) || !is_numeric($_GET['id_rab_upah'])) {
    $_SESSION['message'] = [
        'type' => 'error',
        'text' => 'Gagal menghapus! ID pengajuan tidak valid atau tidak ditemukan.'
    ];
    header("Location: pengajuan_upah.php");
    exit;
}

$id_rab_upah = (int)$_GET['id_rab_upah'];

// Memulai transaksi untuk memastikan konsistensi data
mysqli_autocommit($koneksi, false);
$error = false;

// 2. Hapus Data dari Tabel Detail (`detail_pengajuan_upah`)
// Menghapus semua item pekerjaan yang terkait dengan pengajuan ini terlebih dahulu.
$sql_delete_detail = "DELETE FROM detail_pengajuan_upah WHERE id_rab_upah = ?";
$stmt_detail = mysqli_prepare($koneksi, $sql_delete_detail);
mysqli_stmt_bind_param($stmt_detail, 'i', $id_rab_upah);

// Debug: Periksa apakah query detail dieksekusi dengan benar
if (!mysqli_stmt_execute($stmt_detail)) {
    // Jika penghapusan detail gagal, tandai ada error.
    echo "Error detail: " . mysqli_error($koneksi);  // Debugging output
    $error = true;
}
mysqli_stmt_close($stmt_detail);

// 3. Hapus Data dari Tabel Induk (`pengajuan_upah`)
// Hanya jalankan jika tidak ada error pada langkah sebelumnya.
if (!$error) {
    $sql_delete_master = "DELETE FROM pengajuan_upah WHERE id_rab_upah = ?";
    $stmt_master = mysqli_prepare($koneksi, $sql_delete_master);
    mysqli_stmt_bind_param($stmt_master, 'i', $id_rab_upah);

    // Debug: Periksa apakah query master dieksekusi dengan benar
    if (!mysqli_stmt_execute($stmt_master)) {
        echo "Error master: " . mysqli_error($koneksi);  // Debugging output
        $error = true;
    }
    mysqli_stmt_close($stmt_master);
}

// 4. Proses Transaksi (Commit atau Rollback)
if ($error) {
    // Jika ada error di salah satu proses, batalkan semua perubahan (rollback).
    mysqli_rollback($koneksi);
    $_SESSION['message'] = [
        'type' => 'error',
        'text' => 'Gagal menghapus data pengajuan. Terjadi kesalahan pada database.' . (isset($_SESSION['db_error']) ? ' Error: ' . $_SESSION['db_error'] : '')
    ];
    unset($_SESSION['db_error']); // Hapus pesan error database dari session
} else {
    // Jika semua proses berhasil, simpan perubahan secara permanen (commit).
    mysqli_commit($koneksi);
    $_SESSION['message'] = [
        'type' => 'success',
        'text' => 'Data pengajuan upah berhasil dihapus.'
    ];
}

// Mengaktifkan kembali autocommit ke mode normal
mysqli_autocommit($koneksi, true);

// 5. Kembalikan pengguna ke halaman daftar pengajuan
header("Location: pengajuan_upah.php");
exit;
?>
