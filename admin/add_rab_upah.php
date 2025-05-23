<?php
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_perumahan = mysqli_real_escape_string($koneksi, $_POST['id_perumahan']);
    $id_proyek = mysqli_real_escape_string($koneksi, $_POST['id_proyek']);
    $id_mandor = mysqli_real_escape_string($koneksi, $_POST['id_mandor']);
    $tanggal_mulai = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai']);

    $sql = "INSERT INTO rab_upah (id_perumahan, id_proyek, id_mandor, tanggal_mulai) 
            VALUES ('$id_perumahan', '$id_proyek', '$id_mandor', '$tanggal_mulai')";

    if (mysqli_query($koneksi, $sql)) {
        // Ambil id_rab_upah yang baru saja disimpan
        $new_id = mysqli_insert_id($koneksi);

        // Redirect ke halaman detail dengan id_rab_upah sebagai parameter
        header("Location: detail_rab_upah.php?id_rab_upah=" . $new_id);
        exit();
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
} else {
    echo "Metode request tidak valid.";
}
?>
