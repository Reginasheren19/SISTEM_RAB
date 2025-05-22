<?php
include("../config/koneksi_mysql.php");

// Proses saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_perumahan = mysqli_real_escape_string($koneksi, $_POST['nama_perumahan']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);

    // Query untuk menyimpan data ke database
    $sql = "INSERT INTO master_perumahan (nama_perumahan, lokasi) 
            VALUES ('$nama_perumahan', '$lokasi')";
    if (mysqli_query($koneksi, $sql)) {
        echo "<script>window.location.href='master_perumahan.php?msg=Data%20berhasil%20ditambahkan';</script>";

    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>
