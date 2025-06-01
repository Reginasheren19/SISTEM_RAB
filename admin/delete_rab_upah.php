<?php
include("../config/koneksi_mysql.php");

if (isset($_GET['id_rab_upah']) && !empty($_GET['id_rab_upah'])) { 
    $hapus_id_rab_upah = mysqli_real_escape_string($koneksi, $_GET['id_rab_upah']);

    // Hapus dulu detail_rab_upah yang terkait
    $sql_delete_detail = "DELETE FROM detail_rab_upah WHERE id_rab_upah = '$hapus_id_rab_upah'";
    $hapus_detail = mysqli_query($koneksi, $sql_delete_detail);
    if (!$hapus_detail) {
        echo "Gagal menghapus detail RAB: " . mysqli_error($koneksi);
        exit;
    }

    // Setelah detail berhasil dihapus, hapus rab_upah
    $sql_delete_rab = "DELETE FROM rab_upah WHERE id_rab_upah = '$hapus_id_rab_upah'";
    $hapus_rab = mysqli_query($koneksi, $sql_delete_rab);
    if ($hapus_rab && mysqli_affected_rows($koneksi) > 0) {
        header("Location: transaksi_rab_upah.php?msg=Data%20berhasil%20dihapus");
        exit;
    } else {
        echo "Error deleting record: " . mysqli_error($koneksi);
    }
} else {
    echo "No RAB Upah specified for deletion.";
}

?>
