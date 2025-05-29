<?php
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id_rab_upah = intval($_POST['id_rab_upah'] ?? 0);
$id_kategori = intval($_POST['id_kategori'] ?? 0);
$id_pekerjaan = intval($_POST['id_pekerjaan'] ?? 0);
$volume = floatval($_POST['volume'] ?? 0);
$harga_satuan = floatval($_POST['harga_satuan'] ?? 0);

if ($id_rab_upah === 0 || $id_kategori === 0 || $id_pekerjaan === 0 || $volume <= 0 || $harga_satuan <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data pekerjaan tidak lengkap atau tidak valid']);
    exit;
}

$sub_total = $volume * $harga_satuan;

$stmt = $koneksi->prepare("INSERT INTO detail_rab_upah (id_rab_upah, id_kategori, id_pekerjaan, volume, harga_satuan, sub_total) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiiidd", $id_rab_upah, $id_kategori, $id_pekerjaan, $volume, $harga_satuan, $sub_total);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id_detail' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pekerjaan']);
}

$stmt->close();
$koneksi->close();
?>
