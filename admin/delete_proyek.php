<?php
include("../config/koneksi_mysql.php");

// Debugging $_GET
echo "Parameter GET: ";
print_r($_GET);
echo "<br>";

// Pastikan parameter 'proyek' ada dan valid
if (isset($_GET['proyek']) && !empty($_GET['proyek'])) {
    // Ambil ID proyek dari parameter URL dan sanitasi
    $hapus_id_proyek = mysqli_real_escape_string($koneksi, $_GET['proyek']);
    echo "ID proyek yang akan dihapus: " . $hapus_id_proyek . "<br>"; // Debugging

    // Menggunakan prepared statement untuk menghapus data
    $stmt = $koneksi->prepare("DELETE FROM master_proyek WHERE id_proyek = ?");
    
    // Mengikat parameter
    $stmt->bind_param("i", $hapus_id_proyek); // "i" untuk integer

    // Menjalankan query
    if ($stmt->execute()) {
        // Jika berhasil, alihkan dengan pesan sukses
        header("Location: master_proyek.php?msg=Data%20berhasil%20dihapus");
        exit; // Pastikan untuk menghentikan eksekusi skrip setelah header
    } else {
        // Jika gagal, tampilkan error
        echo "Error deleting record: " . $stmt->error;
    }

    // Menutup statement
    $stmt->close();
} else {
    echo "No proyek specified for deletion.";
}
?>
