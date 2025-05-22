<?php
include("../config/koneksi_mysql.php");

// Pastikan request method POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan sanitasi data dari form
    $id_pekerjaan = mysqli_real_escape_string($koneksi, $_POST['id_pekerjaan']);
    $uraian_pekerjaan = mysqli_real_escape_string($koneksi, $_POST['uraian_pekerjaan']);
    $id_satuan = mysqli_real_escape_string($koneksi, $_POST['id_satuan']);

    // Query update data mandor
    $sql = "UPDATE 
                master_pekerjaan 
            SET 
                uraian_pekerjaan = '$uraian_pekerjaan',
                id_satuan = '$id_satuan'
            WHERE 
                id_pekerjaan = '$id_pekerjaan'";

    if (mysqli_query($koneksi, $sql)) {
        // Redirect ke halaman master_mandor dengan tanda sukses update
        header("Location: master_pekerjaan.php?msg=Data%20berhasil%20diupdate");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($koneksi);
    }
} else {
    // Jika bukan POST, redirect ke halaman utama
    header("Location: master_pekerjaan.php");
    exit();
}
?>
