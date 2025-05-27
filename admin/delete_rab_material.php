<?php
include("../config/koneksi_mysql.php");

// Debugging $_GET
echo "Parameter GET: ";
print_r($_GET);
echo "<br>";

if (isset($_GET['id_rab_material']) && !empty($_GET['id_rab_material'])) { 
    // Ambil ID rab_material dari parameter URL dan sanitasi
    $hapus_id_rab_material = mysqli_real_escape_string($koneksi, $_GET['id_rab_material']);
    echo "ID RAB Material yang akan dihapus: " . $hapus_id_rab_material . "<br>"; // Debugging

    // Jalankan query untuk menghapus data rab_material berdasarkan ID
    $sql = mysqli_query($koneksi, "DELETE FROM transaksi_rab_material WHERE id_rab_material = '$hapus_id_rab_material'");

    // Cek apakah query berhasil dieksekusi dan ada baris yang terhapus
    if ($sql && mysqli_affected_rows($koneksi) > 0) {
        header("Location: transaksi_rab_material.php?msg=Data%20berhasil%20dihapus");
        exit; // Hentikan eksekusi setelah redirect
    } else {
        echo "Error deleting record: " . mysqli_error($koneksi);
    }
} else {
    echo "No RAB Upah specified for deletion.";
}
?>
