<?php
include("../config/koneksi_mysql.php");

// Pastikan request method POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan sanitasi data dari form
    $id_mandor = mysqli_real_escape_string($koneksi, $_POST['id_mandor']);
    $nama_mandor = mysqli_real_escape_string($koneksi, $_POST['nama_mandor']);
    $no_telp = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    // Query update data mandor
    $sql = "UPDATE master_mandor SET 
            nama_mandor = '$nama_mandor',
            no_telp = '$no_telp',
            alamat = '$alamat'
            WHERE id_mandor = '$id_mandor'";

    if (mysqli_query($koneksi, $sql)) {
        // Redirect ke halaman master_mandor dengan tanda sukses update
        header("Location: master_mandor.php?update_success=1");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($koneksi);
    }
} else {
    // Jika bukan POST, redirect ke halaman utama
    header("Location: master_mandor.php");
    exit();
}
?>
