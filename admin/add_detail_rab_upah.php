<?php
include("../config/koneksi_mysql.php");

header('Content-Type: application/json');

$id_rab_upah = $_POST['id_rab_upah'] ?? null;
$detail_json = $_POST['detail'] ?? null;

if (!$id_rab_upah || !$detail_json) {
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
    // Hapus dulu detail lama untuk id_rab_upah ini (jika ingin update penuh)
    $sqlDelete = "DELETE FROM detail_rab_upah WHERE id_rab_upah = ?";
    $stmtDelete = $koneksi->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $id_rab_upah);
    $stmtDelete->execute();
    $stmtDelete->close();

    // Insert detail baru
    $sqlInsert = "INSERT INTO detail_rab_upah (id_rab_upah, id_kategori, id_pekerjaan, volume, harga_satuan) VALUES (?, ?, ?, ?, ?)";
    $stmtInsert = $koneksi->prepare($sqlInsert);

    $total_rab_upah = 0;

    foreach ($details as $item) {
        $id_kategori = intval($item['id_kategori']);
        $id_pekerjaan = intval($item['id_pekerjaan']);
        $volume = intval($item['volume']);
        $harga_satuan = intval($item['harga_satuan']);
        $sub_total = $volume * $harga_satuan;

        $stmtInsert->bind_param("iiiii", $id_rab_upah, $id_kategori, $id_pekerjaan, $volume, $harga_satuan);
        $stmtInsert->execute();

        $total_rab_upah += $sub_total;
    }

    $stmtInsert->close();

    // Update total_rab_upah di tabel rab_upah
    $sqlUpdate = "UPDATE rab_upah SET total_rab_upah = ? WHERE id_rab_upah = ?";
    $stmtUpdate = $koneksi->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ii", $total_rab_upah, $id_rab_upah);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    mysqli_commit($koneksi);

    echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan']);
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
}

?>
