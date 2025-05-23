<?php
include("../config/koneksi_mysql.php");

// Debugging $_GET
echo "Parameter GET: ";
print_r($_GET);
echo "<br>";

if (isset($_GET['proyek']) && !empty($_GET['proyek'])) { // Periksa 'user' di URL
    // Ambil ID user dari parameter URL dan sanitasi
    $hapus_id_proyek = mysqli_real_escape_string($koneksi, $_GET['proyek']);
    echo "ID proyek yang akan dihapus: " . $hapus_id_proyek . "<br>"; // Debugging

    // Jalankan query untuk menghapus user berdasarkan ID
    $sql = mysqli_query($koneksi, "DELETE FROM master_proyek WHERE id_proyek = '$hapus_id_proyek'");

    // Cek apakah query berhasil dieksekusi
    if ($sql && mysqli_affected_rows($koneksi) > 0) { // Pastikan ada baris yang terhapus
        header("location: master_proyek.php?msg=Data%20berhasil%20dihapus");         exit; // Pastikan untuk menghentikan eksekusi skrip setelah header
    } else {
        echo "Error deleting record: " . mysqli_error($koneksi);
    }
} else {
    echo "No user specified for deletion.";
}
?>
