<?php
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_proyek = mysqli_real_escape_string($koneksi, $_POST['id_proyek']);
    $tanggal_mulai = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai_mt']);
        $tanggal_selesai = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai_mt']);

    // Insert the new record
    $sql = "INSERT INTO rab_material (id_proyek, tanggal_mulai_mt, tanggal_selesai_mt) 
            VALUES ('$id_proyek', '$tanggal_mulai_mt', '$tanggal_selesai_mt')";

    if (mysqli_query($koneksi, $sql)) {
        // Get the last inserted ID
        $new_id = mysqli_insert_id($koneksi);

        // Redirect to detail page with formatted ID
        header("Location: input_detail_rab_material.php?id_rab_material=" . $new_id);
        exit();
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
} else {
    echo "Metode request tidak valid.";
}
?>
