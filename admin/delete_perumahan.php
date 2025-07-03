<?php
// Selalu mulai session di paling atas
session_start();
include("../config/koneksi_mysql.php");

// Cek apakah parameter 'perumahan' ada dan valid
if (isset($_GET['perumahan']) && is_numeric($_GET['perumahan'])) {
    
    $hapus_id_perumahan = $_GET['perumahan'];

    // Gunakan prepared statement untuk keamanan yang lebih baik
    $sql = "DELETE FROM master_perumahan WHERE id_perumahan = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $hapus_id_perumahan);

    // Coba eksekusi query penghapusan
    if ($stmt->execute()) {
        // Jika query berhasil dieksekusi dan ada baris yang terhapus
        if ($stmt->affected_rows > 0) {
            $_SESSION['notification'] = [
                'type' => 'success', // Tipe untuk warna notifikasi (hijau)
                'message' => 'Data perumahan berhasil dihapus.'
            ];
        } else {
            // Jika tidak ada baris yang terhapus (misal: ID tidak ditemukan)
            $_SESSION['notification'] = [
                'type' => 'warning', // Tipe untuk warna notifikasi (kuning)
                'message' => 'Data tidak ditemukan atau sudah dihapus.'
            ];
        }
    } else {
        // Jika query GAGAL dieksekusi, cek kode errornya
        // Kode 1451 adalah untuk error foreign key constraint
        if (mysqli_errno($koneksi) == 1451) {
            $_SESSION['notification'] = [
                'type' => 'danger', // Tipe untuk warna notifikasi (merah)
                'message' => 'Gagal menghapus! Perumahan ini masih digunakan oleh data Proyek.'
            ];
        } else {
            // Untuk error database lainnya
            $_SESSION['notification'] = [
                'type' => 'danger',
                'message' => 'Terjadi kesalahan pada database: ' . mysqli_error($koneksi)
            ];
        }
    }

    $stmt->close();
    $koneksi->close();

} else {
    // Jika parameter tidak valid
    $_SESSION['notification'] = [
        'type' => 'warning',
        'message' => 'Permintaan penghapusan tidak valid.'
    ];
}

// Arahkan kembali ke halaman daftar perumahan
header("Location: master_perumahan.php");
exit();
?>