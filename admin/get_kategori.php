<?php
include("../config/koneksi_mysql.php");

header('Content-Type: application/json');

$sql = "SELECT id_kategori, nama_kategori FROM master_kategori ORDER BY nama_kategori";
$result = mysqli_query($koneksi, $sql);

$kategori = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $kategori[] = [
          'id_kategori' => (int)$row['id_kategori'],
          'nama_kategori' => $row['nama_kategori']
        ];
    }
}

echo json_encode($kategori);
