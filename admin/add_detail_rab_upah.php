<?php
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_rab_upah = $_POST['id_rab_upah'] ?? null;
    $id_kategori = $_POST['id_kategori'] ?? null;
    $id_pekerjaan = $_POST['id_pekerjaan'] ?? null;
    $volume = $_POST['volume'] ?? null;
    $harga_satuan = $_POST['harga_satuan'] ?? null;
    $total_rab_upah = $_POST['harga_satuan'] ?? null;


    if (!$id_rab_upah || !$id_kategori || !$id_pekerjaan || !$volume || !$harga_satuan) {
        echo json_encode(['status' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }

    $volume = (int)$volume;
    $harga_satuan = (int)$harga_satuan;

    // Hitung total rab upah
    $subtotal = $volume * $harga_satuan;

    // Query insert
    $stmt = $koneksi->prepare("INSERT INTO detail_rab_upah (id_rab_upah, id_kategori, id_pekerjaan, volume, harga_satuan, total_rab_upah) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiii", $id_rab_upah, $id_kategori, $id_pekerjaan, $volume, $harga_satuan, $total_rab_upah);

    if ($stmt->execute()) {
        echo json_encode(['status' => true, 'message' => 'Detail RAB Upah berhasil disimpan']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Gagal menyimpan data']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => false, 'message' => 'Metode request tidak valid']);
}
?>
