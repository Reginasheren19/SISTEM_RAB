<?php
include("../config/koneksi_mysql.php");

// Debugging $_GET
echo "Parameter GET: ";
print_r($_GET);
echo "<br>";

if (isset($_GET['satuan']) && !empty($_GET['satuan'])) { // Periksa 'user' di URL
    // Ambil ID user dari parameter URL dan sanitasi
    $hapus_id_satuan = mysqli_real_escape_string($koneksi, $_GET['satuan']);
    echo "ID Satuan yang akan dihapus: " . $hapus_id_satuan . "<br>"; // Debugging

    // Jalankan query untuk menghapus user berdasarkan ID
    $sql = mysqli_query($koneksi, "DELETE FROM master_satuan WHERE id_satuan = '$hapus_id_satuan'");

    // Cek apakah query berhasil dieksekusi
    if ($sql && mysqli_affected_rows($koneksi) > 0) { // Pastikan ada baris yang terhapus
        header("location: master_satuan.php?msg=Data%20berhasil%20dihapus");         exit; // Pastikan untuk menghentikan eksekusi skrip setelah header
    } else {
        echo "Error deleting record: " . mysqli_error($koneksi);
    }
} else {
    echo "No user specified for deletion.";
}
?>
