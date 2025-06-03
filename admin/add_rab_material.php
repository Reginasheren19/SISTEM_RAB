<?php
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_perumahan = mysqli_real_escape_string($koneksi, $_POST['id_perumahan']);
    $id_proyek = mysqli_real_escape_string($koneksi, $_POST['id_proyek']);
    $id_mandor = mysqli_real_escape_string($koneksi, $_POST['id_mandor']);
    $tanggal_mulai_mt = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai_mt']);
    $tanggal_selesai_mt = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai_mt']);

    // Insert the new record
    $sql = "INSERT INTO rab_material (id_perumahan, id_proyek, id_mandor, tanggal_mulai_mt, tanggal_selesai_mt) 
            VALUES ('$id_perumahan', '$id_proyek', '$id_mandor', '$tanggal_mulai_mt', '$tanggal_selesai_mt')";

    if (mysqli_query($koneksi, $sql)) {
    $new_id = mysqli_insert_id($koneksi);
    $tahun = date('Y');
    $formatted_id = 'RABM' . $new_id . $tahun;

    // Jika redirect ke detail:
    header("Location: input_detail_rab_material.php?id_rab_material=" . $new_id);
    exit();
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
} else {
    echo "Metode request tidak valid.";
}
?>
