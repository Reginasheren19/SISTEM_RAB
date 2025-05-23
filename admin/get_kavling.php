<?php
include("../config/koneksi_mysql.php");

if (isset($_POST['id_perumahan'])) {
    $id_perumahan = mysqli_real_escape_string($koneksi, $_POST['id_perumahan']);

    $sql = "SELECT id_proyek, kavling FROM master_proyek WHERE id_perumahan = '$id_perumahan' ORDER BY kavling ASC";
    $result = mysqli_query($koneksi, $sql);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
