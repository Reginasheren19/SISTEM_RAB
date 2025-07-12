<?php
include("../config/koneksi_mysql.php");

// Debugging $_GET
// echo "Parameter GET: ";
// print_r($_GET);
// echo "<br>";

if (isset($_GET['id']) && !empty($_GET['id'])) { // cek 'id', bukan 'pekerjaan'
    // Ambil ID pekerjaan dari parameter URL dan sanitasi
    $hapus_id_pekerjaan = mysqli_real_escape_string($koneksi, $_GET['id']);
    // echo "ID Pekerjaan yang akan dihapus: " . $hapus_id_pekerjaan . "<br>"; // Debugging

    // Jalankan query untuk menghapus pekerjaan berdasarkan ID
    $sql = mysqli_query($koneksi, "DELETE FROM master_pekerjaan WHERE id_pekerjaan = '$hapus_id_pekerjaan'");

    // Cek apakah query berhasil dieksekusi
    if ($sql && mysqli_affected_rows($koneksi) > 0) { // Pastikan ada baris yang terhapus
        header("location: master_pekerjaan.php?msg=Data%20berhasil%20dihapus");
        exit;
    } else {
        echo "Error deleting record: " . mysqli_error($koneksi);
    }
} else {
    echo "No pekerjaan specified for deletion.";
}
?>
