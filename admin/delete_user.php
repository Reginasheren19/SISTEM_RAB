<?php
include("../config/koneksi_mysql.php");

// Debugging $_GET
echo "Parameter GET: ";
print_r($_GET);
echo "<br>";

if (isset($_GET['id_user']) && !empty($_GET['id_user'])) { // Periksa 'id_user' di URL
    // Ambil ID user dari parameter URL dan sanitasi
    $hapus_id_user = mysqli_real_escape_string($koneksi, $_GET['id_user']);
    echo "ID User yang akan dihapus: " . $hapus_id_user . "<br>"; // Debugging

    // Jalankan query untuk menghapus user berdasarkan ID
    $sql = mysqli_query($koneksi, "DELETE FROM master_user WHERE id_user = '$hapus_id_user'");

    // Cek apakah query berhasil dieksekusi
    if ($sql && mysqli_affected_rows($koneksi) > 0) { // Pastikan ada baris yang terhapus
        header("location: master_user.php?msg=Data%20berhasil%20dihapus"); // Redirect dengan pesan sukses
        exit;
    } else {
        echo "Error deleting record: " . mysqli_error($koneksi);
    }
} else {
    echo "No user specified for deletion.";
}
?>
