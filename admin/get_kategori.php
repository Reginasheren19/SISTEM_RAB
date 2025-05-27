<?php
include("../config/koneksi_mysql.php");

$term = $_GET['term'] ?? '';
$term = mysqli_real_escape_string($koneksi, $term);

$sql = "SELECT nama_kategori FROM master_kategori WHERE nama_kategori LIKE '%$term%' ORDER BY nama_kategori LIMIT 10";
$result = mysqli_query($koneksi, $sql);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Format objek dengan label dan value agar lebih kompatibel
    $data[] = ['label' => $row['nama_kategori'], 'value' => $row['nama_kategori']];
}

header('Content-Type: application/json');
echo json_encode($data);
