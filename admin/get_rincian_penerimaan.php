<?php
session_start();
include("../config/koneksi_mysql.php");

header('Content-Type: application/json');

if (!isset($_GET['id_pembelian']) || !is_numeric($_GET['id_pembelian'])) {
    echo json_encode([]);
    exit();
}
$pembelian_id = (int)$_GET['id_pembelian'];

// Query untuk mengambil detail item, menghitung yang sudah diterima, dan mencari sisanya.
$sql = "
    SELECT
        dp.id_detail_pembelian,
        dp.id_material,
        m.nama_material,
        s.nama_satuan,
        dp.quantity as total_dipesan,
        COALESCE(SUM(log.jumlah_diterima), 0) + COALESCE(SUM(log.jumlah_rusak), 0) as total_sudah_diproses,
        (dp.quantity - (COALESCE(SUM(log.jumlah_diterima), 0) + COALESCE(SUM(log.jumlah_rusak), 0))) as sisa_dipesan
    FROM
        detail_pencatatan_pembelian dp
    JOIN
        master_material m ON dp.id_material = m.id_material
    LEFT JOIN
        master_satuan s ON m.id_satuan = s.id_satuan
    LEFT JOIN
        log_penerimaan_material log ON dp.id_detail_pembelian = log.id_detail_pembelian
    WHERE
        dp.id_pembelian = ?
    GROUP BY
        dp.id_detail_pembelian, m.nama_material, s.nama_satuan, dp.quantity
    HAVING 
        sisa_dipesan > 0
";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $pembelian_id);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($items);

$stmt->close();
$koneksi->close();