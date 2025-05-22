<?php
include("../config/koneksi_mysql.php");

// Proses saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_mandor = mysqli_real_escape_string($koneksi, $_POST['nama_mandor']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $no_telp = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    
    // Query untuk menyimpan data ke database
    $sql = "INSERT INTO master_mandor (nama_mandor, alamat, no_telp) 
            VALUES ('$nama_mandor', '$alamat', '$no_telp')";
    if (mysqli_query($koneksi, $sql)) {
        echo "<script>window.location.href='master_mandor.php?msg=Data%20berhasil%20ditambahkan';</script>";

    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>
