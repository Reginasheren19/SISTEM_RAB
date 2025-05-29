<?php
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id_rab_upah = intval($_POST['id_rab_upah'] ?? 0);
$nama_kategori = trim($_POST['nama_kategori'] ?? '');

if ($id_rab_upah === 0 || $nama_kategori === '') {
    echo json_encode(['success' => false, 'message' => 'Data kategori tidak lengkap']);
    exit;
}

// Insert kategori
$stmt = $koneksi->prepare("INSERT INTO kategori_rab (id_rab_upah, nama_kategori) VALUES (?, ?)");
$stmt->bind_param("is", $id_rab_upah, $nama_kategori);

if ($stmt->execute()) {
    $id_kategori_baru = $stmt->insert_id;
    echo json_encode(['success' => true, 'id_kategori' => $id_kategori_baru]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan kategori']);
}

$stmt->close();
$koneksi->close();
?>
