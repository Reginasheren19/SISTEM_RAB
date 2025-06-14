<?php
include("../config/koneksi_mysql.php");

// Pastikan request method POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan sanitasi data dari form
    $id_proyek = mysqli_real_escape_string($koneksi, $_POST['id_proyek']);
    $id_perumahan = mysqli_real_escape_string($koneksi, $_POST['id_perumahan']);
    $kavling = mysqli_real_escape_string($koneksi, $_POST['kavling']);
    $type_proyek = mysqli_real_escape_string($koneksi, $_POST['type_proyek']);
    $id_mandor = mysqli_real_escape_string($koneksi, $_POST['id_mandor']);
        $id_user_pj = (int)$_POST['id_user_pj']; // [BARU] Ambil ID PJ Proyek dari form

    // Query update data mandor
    $sql = "UPDATE 
                master_proyek 
            SET 
                id_perumahan = '$id_perumahan',
                kavling = '$kavling',
                type_proyek = '$type_proyek',
                id_mandor = '$id_mandor',
                id_user_pj = '$id_user_pj'
            WHERE 
                id_proyek = '$id_proyek'";

    if (mysqli_query($koneksi, $sql)) {
        // Redirect ke halaman master_mandor dengan tanda sukses update
        header("Location: master_proyek.php?msg=Data%20berhasil%20diupdate");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($koneksi);
    }
} else {
    // Jika bukan POST, redirect ke halaman utama
    header("Location: master_proyek.php");
    exit();
}
?>
