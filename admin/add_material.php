<?php
include("../config/koneksi_mysql.php");

// Proses saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_material = mysqli_real_escape_string($koneksi, $_POST['nama_material']);
    $id_satuan = mysqli_real_escape_string($koneksi, $_POST['id_satuan']);
    $keterangan_material = mysqli_real_escape_string($koneksi, $_POST['keterangan_material']);


    // Query untuk menyimpan data ke database
    $sql = "INSERT INTO master_material (nama_material, id_satuan,keterangan_material ) 
            VALUES ('$nama_material', '$id_satuan', '$keterangan_material')";
    if (mysqli_query($koneksi, $sql)) {
        echo "<script>window.location.href='master_material.php?msg=Data%20berhasil%20ditambahkan';</script>";

    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>
