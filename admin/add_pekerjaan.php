<?php
include("../config/koneksi_mysql.php");

// Proses saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uraian_pekerjaan = mysqli_real_escape_string($koneksi, $_POST['uraian_pekerjaan']);
    $id_satuan = mysqli_real_escape_string($koneksi, $_POST['id_satuan']);
    
        // Validasi sederhana (pastikan tidak kosong)
    if (empty($uraian) || $id_satuan <= 0) {
        echo "Data uraian dan satuan wajib diisi!";
        exit;
    }

    // Query untuk menyimpan data ke database
    $sql = "INSERT INTO master_pekerjaan (uraian_pekerjaan, id_satuan) 
            VALUES ('$uraian_pekerjaan', '$id_satuan')";
    if (mysqli_query($koneksi, $sql)) {
        echo "<script>window.location.href='master_pekerjaan.php?msg=Data%20berhasil%20ditambahkan';</script>";

    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>
