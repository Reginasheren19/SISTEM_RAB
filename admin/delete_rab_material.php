<?php
include("../config/koneksi_mysql.php");

if (isset($_GET['id_rab_material']) && !empty($_GET['id_rab_material'])) { 
    $hapus_id_rab_material = mysqli_real_escape_string($koneksi, $_GET['id_rab_material']);

    // Hapus dulu detail_rab_material yang terkait
    $sql_delete_detail = "DELETE FROM detail_rab_material WHERE id_rab_material = '$hapus_id_rab_material'";
    $hapus_detail = mysqli_query($koneksi, $sql_delete_detail);
    if (!$hapus_detail) {
        echo "Gagal menghapus detail RAB Material: " . mysqli_error($koneksi);
        exit;
    }

    // Setelah detail berhasil dihapus, hapus rab_material
    $sql_delete_rab = "DELETE FROM rab_material WHERE id_rab_material = '$hapus_id_rab_material'";
    $hapus_rab = mysqli_query($koneksi, $sql_delete_rab);
    if ($hapus_rab && mysqli_affected_rows($koneksi) > 0) {
        header("Location: transaksi_rab_material.php?msg=Data%20berhasil%20dihapus");
        exit;
    } else {
        echo "Error deleting record: " . mysqli_error($koneksi);
    }
} else {
    echo "No RAB Material specified for deletion.";
}
?>
