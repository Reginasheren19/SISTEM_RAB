<?php
include("../config/koneksi_mysql.php");

$query = "SELECT id_pekerjaan, uraian_pekerjaan, (SELECT nama_satuan FROM master_satuan WHERE id_satuan = mp.id_satuan) AS nama_satuan
          FROM master_pekerjaan mp ORDER BY uraian_pekerjaan ASC";
$result = mysqli_query($koneksi, $query);

$pekerjaan_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pekerjaan_list[] = $row;
}

header('Content-Type: application/json');
echo json_encode($pekerjaan_list);
