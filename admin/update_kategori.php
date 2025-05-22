<?php
include("../config/koneksi_mysql.php");

// Pastikan request method POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan sanitasi data dari form
    $id_kategori = mysqli_real_escape_string($koneksi, $_POST['id_kategori']);
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);

    // Query update data kategori (hapus koma sebelum WHERE)
    $sql = "UPDATE master_kategori SET 
            nama_kategori = '$nama_kategori'
            WHERE id_kategori = '$id_kategori'";

    if (mysqli_query($koneksi, $sql)) {
        // Redirect ke halaman master_kategori dengan tanda sukses update
        header("Location: master_kategori.php?msg=Data%20berhasil%20diupdate");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($koneksi);
    }
} else {
    // Jika bukan POST, redirect ke halaman utama
    header("Location: master_kategori.php");
    exit();
}
?>
