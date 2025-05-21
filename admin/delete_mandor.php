<?php
include("../config/koneksi_mysql.php");

// Debugging $_GET
echo "Parameter GET: ";
print_r($_GET);
echo "<br>";

if (isset($_GET['mandor']) && !empty($_GET['mandor'])) { // Periksa 'user' di URL
    // Ambil ID user dari parameter URL dan sanitasi
    $hapus_id_mandor = mysqli_real_escape_string($koneksi, $_GET['mandor']);
    echo "ID Mandor yang akan dihapus: " . $hapus_id_mandor . "<br>"; // Debugging

    // Jalankan query untuk menghapus user berdasarkan ID
    $sql = mysqli_query($koneksi, "DELETE FROM master_mandor WHERE id_mandor = '$hapus_id_mandor'");

    // Cek apakah query berhasil dieksekusi
    if ($sql && mysqli_affected_rows($koneksi) > 0) { // Pastikan ada baris yang terhapus
        header("location: master_mandor.php?msg=Data%20berhasil%20dihapus");         exit; // Pastikan untuk menghentikan eksekusi skrip setelah header
    } else {
        echo "Error deleting record: " . mysqli_error($koneksi);
    }
} else {
    echo "No user specified for deletion.";
}
?>
