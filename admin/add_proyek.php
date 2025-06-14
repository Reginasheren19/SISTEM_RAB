<?php
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tangkap dan sanitize input dari form
    $id_perumahan = mysqli_real_escape_string($koneksi, $_POST['id_perumahan']);
    $kavling = mysqli_real_escape_string($koneksi, $_POST['kavling']);
    $type_proyek = mysqli_real_escape_string($koneksi, $_POST['type_proyek']);
    $id_mandor = mysqli_real_escape_string($koneksi, $_POST['id_mandor']);
    $id_user_pj = (int)$_POST['id_user_pj']; // [PERBAIKAN KUNCI] Ambil ID PJ Proyek dari form

    // Validasi sederhana
    if (empty($id_perumahan) || empty($kavling) || empty($type_proyek) || empty($id_mandor)) {
        echo "Semua data wajib diisi!";
        exit;
    }

    // Query insert menggunakan ID (bukan nama)
    $sql = "INSERT INTO master_proyek (id_perumahan, id_mandor, id_user_pj, kavling, type_proyek) 
        VALUES ('$id_perumahan', '$id_mandor', '$id_user_pj', '$kavling', '$type_proyek')";

    if (mysqli_query($koneksi, $sql)) {
        // Redirect dengan pesan sukses
        echo "<script>window.location.href='master_proyek.php?msg=Data%20berhasil%20ditambahkan';</script>";
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>
