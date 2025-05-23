<?php
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_perumahan = mysqli_real_escape_string($koneksi, $_POST['id_perumahan']);
    $id_proyek = mysqli_real_escape_string($koneksi, $_POST['id_proyek']);
    $id_mandor = mysqli_real_escape_string($koneksi, $_POST['id_mandor']);
    $tanggal_mulai = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai']);

        // Format YYYY dan MM dari tanggal_mulai
    $tahun = date('Y', strtotime($tanggal_mulai));
    $bulan = date('m', strtotime($tanggal_mulai));
    // Format id_proyek jadi 3 digit, misal 1 -> 001
    $id_proyek_3digit = str_pad($id_proyek, 3, '0', STR_PAD_LEFT);

    // Generate id_rab_upah sendiri di PHP
    $id_rab_upah = intval($tahun . $bulan . $id_proyek_3digit); 

    $sql = "INSERT INTO rab_upah (id_rab_upah, id_perumahan, id_proyek, id_mandor, tanggal_mulai) 
            VALUES ('$id_rab_upah', '$id_perumahan', '$id_proyek', '$id_mandor', '$tanggal_mulai')";

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
