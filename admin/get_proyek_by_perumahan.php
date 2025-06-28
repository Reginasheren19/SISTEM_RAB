<?php
session_start();
// Sesuaikan path ke koneksi jika perlu
include("../config/koneksi_mysql.php"); 

$id_perumahan = $_GET['id_perumahan'] ?? 0;

if (!$id_perumahan) {
    echo json_encode([]);
    exit();
}

// Query untuk mengambil proyek berdasarkan id_perumahan yang dipilih
// dan HANYA yang sudah memiliki data RAB
$sql = "
    SELECT 
        pro.id_proyek,
        CONCAT('Kavling: ', pro.kavling, ' (Tipe: ', pro.type_proyek, ')') AS nama_proyek_lengkap
    FROM 
        master_proyek pro
    -- [DITAMBAHKAN] JOIN untuk memastikan hanya proyek dengan RAB yang muncul
    JOIN 
        rab_material r ON pro.id_proyek = r.id_proyek
    WHERE 
        pro.id_perumahan = ?
    ORDER BY 
        pro.kavling ASC
";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $id_perumahan);
$stmt->execute();
$result = $stmt->get_result();
$proyek_list = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($proyek_list);

$stmt->close();
$koneksi->close();
?>