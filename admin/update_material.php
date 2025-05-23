<?php
include("../config/koneksi_mysql.php");

// Pastikan request method POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan sanitasi data dari form
    $id_material = mysqli_real_escape_string($koneksi, $_POST['id_material']);
    $nama_material = mysqli_real_escape_string($koneksi, $_POST['nama_material']);
    $id_satuan = mysqli_real_escape_string($koneksi, $_POST['id_satuan']);
    $keterangan_material = mysqli_real_escape_string($koneksi, $_POST['keterangan_material']);

    // Query update data material
    $sql = "UPDATE 
                master_material 
            SET 
                nama_material = '$nama_material',
                id_satuan = '$id_satuan',
                keterangan_material = '$keterangan_material'
            WHERE 
                id_material = '$id_material'";

    if (mysqli_query($koneksi, $sql)) {
        // Redirect ke halaman master_mandor dengan tanda sukses update
        header("Location: master_material.php?msg=Data%20berhasil%20diupdate");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($koneksi);
    }
} else {
    // Jika bukan POST, redirect ke halaman utama
    header("Location: master_material.php");
    exit();
}
?>
