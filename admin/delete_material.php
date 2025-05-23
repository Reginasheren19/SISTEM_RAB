<?php
include("../config/koneksi_mysql.php");

// Debugging $_GET
echo "Parameter GET: ";
print_r($_GET);
echo "<br>";

if (isset($_GET['material']) && !empty($_GET['material'])) { // Periksa 'material' di URL
    // Ambil ID material dari parameter URL dan sanitasi
    $hapus_id_material = mysqli_real_escape_string($koneksi, $_GET['material']);
    echo "ID Material yang akan dihapus: " . $hapus_id_material . "<br>"; // Debugging

    // Jalankan query untuk menghapus material berdasarkan ID
    $sql = mysqli_query($koneksi, "DELETE FROM master_material WHERE id_material = '$hapus_id_material'");

    // Cek apakah query berhasil dieksekusi
    if ($sql && mysqli_affected_rows($koneksi) > 0) { // Pastikan ada baris yang terhapus
        header("location: master_material.php?msg=Data%20berhasil%20dihapus");         exit; // Pastikan untuk menghentikan eksekusi skrip setelah header
    } else {
        echo "Error deleting record: " . mysqli_error($koneksi);
    }
} else {
    echo "No user specified for deletion.";
}
?>
