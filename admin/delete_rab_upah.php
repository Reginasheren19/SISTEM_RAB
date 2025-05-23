<?php
include("../config/koneksi_mysql.php");

// Debugging $_GET
echo "Parameter GET: ";
print_r($_GET);
echo "<br>";

if (isset($_GET['id_rab_upah']) && !empty($_GET['id_rab_upah'])) { 
    // Ambil ID rab_upah dari parameter URL dan sanitasi
    $hapus_id_rab_upah = mysqli_real_escape_string($koneksi, $_GET['id_rab_upah']);
    echo "ID RAB Upah yang akan dihapus: " . $hapus_id_rab_upah . "<br>"; // Debugging

    // Jalankan query untuk menghapus data rab_upah berdasarkan ID
    $sql = mysqli_query($koneksi, "DELETE FROM rab_upah WHERE id_rab_upah = '$hapus_id_rab_upah'");

    // Cek apakah query berhasil dieksekusi dan ada baris yang terhapus
    if ($sql && mysqli_affected_rows($koneksi) > 0) {
        header("Location: transaksi_rab_upah.php?msg=Data%20berhasil%20dihapus");
        exit; // Hentikan eksekusi setelah redirect
    } else {
        echo "Error deleting record: " . mysqli_error($koneksi);
    }
} else {
    echo "No RAB Upah specified for deletion.";
}
?>
