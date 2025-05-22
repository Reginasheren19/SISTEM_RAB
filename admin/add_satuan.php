<?php
include("../config/koneksi_mysql.php");

// Proses saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_satuan = mysqli_real_escape_string($koneksi, $_POST['nama_satuan']);
    $keterangan_satuan = mysqli_real_escape_string($koneksi, $_POST['keterangan_satuan']);

    // Query untuk menyimpan data ke database
    $sql = "INSERT INTO master_satuan (nama_satuan,keterangan_satuan) 
            VALUES ('$nama_satuan','$keterangan_satuan')";
    if (mysqli_query($koneksi, $sql)) {
        echo "<script>window.location.href='master_satuan.php?msg=Data%20berhasil%20ditambahkan';</script>";

    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>
