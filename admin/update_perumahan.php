<?php
include("../config/koneksi_mysql.php");

// Pastikan request method POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan sanitasi data dari form
    $id_perumahan = mysqli_real_escape_string($koneksi, $_POST['id_perumahan']);
    $nama_perumahan = mysqli_real_escape_string($koneksi, $_POST['nama_perumahan']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);

    // Query update data perumahan
    $sql = "UPDATE master_perumahan SET 
            nama_perumahan = '$nama_perumahan',
            lokasi = '$lokasi'
            WHERE id_perumahan = '$id_perumahan'";

    if (mysqli_query($koneksi, $sql)) {
        // Redirect ke halaman master_perumahan dengan tanda sukses update
        header("Location: master_perumahan.php?update_success=1");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($koneksi);
    }
} else {
    // Jika bukan POST, redirect ke halaman utama
    header("Location: master_perumahan.php");
    exit();
}
?>
