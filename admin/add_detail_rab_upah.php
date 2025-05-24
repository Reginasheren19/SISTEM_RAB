<?php
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_perumahan = mysqli_real_escape_string($koneksi, $_POST['id_perumahan']);
    $id_proyek = mysqli_real_escape_string($koneksi, $_POST['id_proyek']);
    $id_mandor = mysqli_real_escape_string($koneksi, $_POST['id_mandor']);
    $tanggal_mulai = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai']);

    // Insert the new record
    $sql = "INSERT INTO rab_upah (id_perumahan, id_proyek, id_mandor, tanggal_mulai) 
            VALUES ('$id_perumahan', '$id_proyek', '$id_mandor', '$tanggal_mulai')";

    if (mysqli_query($koneksi, $sql)) {
        // Redirect dengan pesan sukses
        echo "<script>window.location.href='transaksi_rab_upah.php?msg=Data%20berhasil%20ditambahkan';</script>";
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>
