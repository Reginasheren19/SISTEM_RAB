<?php
include("../config/koneksi_mysql.php");

if (!isset($_GET['id_mandor'])) {
    echo json_encode([]);
    exit;
}

$id_mandor = intval($_GET['id_mandor']);

$sql = "SELECT 
          mpr.id_proyek,
          mpe.nama_perumahan,
          mpr.kavling,
          mpe.lokasi
        FROM master_proyek mpr
        JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        WHERE mpr.id_mandor = $id_mandor
        ORDER BY mpe.nama_perumahan, mpr.kavling";

$result = mysqli_query($koneksi, $sql);

$data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

echo json_encode($data);
