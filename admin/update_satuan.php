<?php
include("../config/koneksi_mysql.php");

// Pastikan request method POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan sanitasi data dari form
    $id_satuan = mysqli_real_escape_string($koneksi, $_POST['id_satuan']);
    $nama_satuan = mysqli_real_escape_string($koneksi, $_POST['nama_satuan']);
    $keterangan_satuan = mysqli_real_escape_string($koneksi, $_POST['keterangan_satuan']);

    // Query update data satuan (hapus koma sebelum WHERE)
    $sql = "UPDATE master_satuan SET 
            nama_satuan = '$nama_satuan',
            keterangan_satuan = '$keterangan_satuan'
            WHERE id_satuan = '$id_satuan'";

    if (mysqli_query($koneksi, $sql)) {
        // Redirect ke halaman master_satuan dengan tanda sukses update
        header("Location: master_satuan.php?msg=Data%20berhasil%20diupdate");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($koneksi);
    }
} else {
    // Jika bukan POST, redirect ke halaman utama
    header("Location: master_satuan.php");
    exit();
}
?>
