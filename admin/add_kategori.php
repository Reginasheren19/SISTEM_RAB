<?php
include("../config/koneksi_mysql.php");

// Proses saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);

    // Query untuk menyimpan data ke database
    $sql = "INSERT INTO master_kategori (nama_kategori) 
            VALUES ('$nama_kategori')";
    if (mysqli_query($koneksi, $sql)) {
        echo "<script>window.location.href='master_kategori.php?msg=Data%20berhasil%20ditambahkan';</script>";

    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>
