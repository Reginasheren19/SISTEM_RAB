<?php
include("../config/koneksi_mysql.php");

// Debugging $_GET
echo "Parameter GET: ";
print_r($_GET);
echo "<br>";

if (isset($_GET['perumahan']) && !empty($_GET['perumahan'])) { // Periksa 'user' di URL
    // Ambil ID user dari parameter URL dan sanitasi
    $hapus_id_perumahan = mysqli_real_escape_string($koneksi, $_GET['perumahan']);
    echo "ID Perumahan yang akan dihapus: " . $hapus_id_perumahan . "<br>"; // Debugging

    // Jalankan query untuk menghapus user berdasarkan ID
    $sql = mysqli_query($koneksi, "DELETE FROM master_perumahan WHERE id_perumahan = '$hapus_id_perumahan'");

    // Cek apakah query berhasil dieksekusi
    if ($sql && mysqli_affected_rows($koneksi) > 0) { // Pastikan ada baris yang terhapus
        header("location: master_perumahan.php?msg=Data%20berhasil%20dihapus");         exit; // Pastikan untuk menghentikan eksekusi skrip setelah header
    } else {
        echo "Error deleting record: " . mysqli_error($koneksi);
    }
} else {
    echo "No user specified for deletion.";
}
?>
