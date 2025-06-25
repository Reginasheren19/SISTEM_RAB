<?php
include("../config/koneksi_mysql.php");
header('Content-Type: application/json'); // Penting: memberitahu browser bahwa ini adalah data JSON

$id_pembelian = $_GET['id_pembelian'] ?? 0;
$items = [];

if ($id_pembelian > 0) {
    $sql = "
        SELECT 
            dp.id_detail_pembelian,
            dp.id_material,
            m.nama_material,
            s.nama_satuan,
            dp.quantity AS jumlah_dipesan
        FROM detail_pencatatan_pembelian dp
        JOIN master_material m ON dp.id_material = m.id_material
        LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan
        WHERE dp.id_pembelian = ?
    ";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_pembelian);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
}

// Kembalikan data dalam format JSON
echo json_encode($items);
?>