<?php
include("../config/koneksi_mysql.php");

header('Content-Type: application/json');

$id_rab_material = $_POST['id_rab_material'] ?? null;
$detail_json = $_POST['detail'] ?? null;

if (!$id_rab_material || !$detail_json) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit;
}

$details = json_decode($detail_json, true);
if (!is_array($details)) {
    echo json_encode(['status' => 'error', 'message' => 'Format data salah']);
    exit;
}

// Mulai transaksi agar konsisten
mysqli_begin_transaction($koneksi);

try {
    // Hapus dulu detail lama untuk id_rab_material ini (jika ingin update penuh)
    $sqlDelete = "DELETE FROM detail_rab_material WHERE id_rab_material = ?";
    $stmtDelete = $koneksi->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $id_rab_material);
    $stmtDelete->execute();
    $stmtDelete->close();

    // Insert detail baru
    $sqlInsert = "INSERT INTO detail_rab_material (id_rab_material, id_kategori, id_pekerjaan, volume, harga_satuan) VALUES (?, ?, ?, ?, ?)";
    $stmtInsert = $koneksi->prepare($sqlInsert);

    $total_rab_material = 0;

    foreach ($details as $item) {
        $id_kategori = intval($item['id_kategori']);
        $id_pekerjaan = intval($item['id_pekerjaan']);
        $volume = intval($item['volume']);
        $harga_satuan = intval($item['harga_satuan']);
        $sub_total = $volume * $harga_satuan;

        $stmtInsert->bind_param("iiiii", $id_rab_material, $id_kategori, $id_pekerjaan, $volume, $harga_satuan);
        $stmtInsert->execute();

        $total_rab_material += $sub_total;
    }

    $stmtInsert->close();

    // Update total_rab_material di tabel rab_material
    $sqlUpdate = "UPDATE rab_material SET total_rab_material = ? WHERE id_rab_material = ?";
    $stmtUpdate = $koneksi->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ii", $total_rab_material, $id_rab_material);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    mysqli_commit($koneksi);

    echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan']);
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
}

?>
