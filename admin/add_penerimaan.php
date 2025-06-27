<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Akses tidak sah.";
    header("Location: penerimaan_material.php");
    exit();
}

$id_pembelian = $_POST['id_pembelian'] ?? null;
$ids_detail_pembelian = $_POST['id_detail_pembelian'] ?? [];
$ids_material = $_POST['id_material'] ?? [];
$jumlah_diterima_arr = $_POST['jumlah_diterima'] ?? [];
$jumlah_rusak_arr = $_POST['jumlah_rusak'] ?? [];
$catatan_arr = $_POST['catatan'] ?? [];

if (empty($id_pembelian) || empty($ids_detail_pembelian)) {
    $_SESSION['error_message'] = "Data tidak lengkap atau tidak ada item yang dikonfirmasi.";
    header("Location: penerimaan_material.php");
    exit();
}

$koneksi->begin_transaction();

$stmt_log = null;
$stmt_stok = null;
$stmt_get_sisa = null;
$stmt_cek_harga = null; // --- [BARU] --- Statement untuk mengecek harga item
$stmt_update_header = null;

try {
    $sql_log = "INSERT INTO log_penerimaan_material (id_pembelian, id_detail_pembelian, id_material, jumlah_diterima, jumlah_rusak, catatan, tanggal_penerimaan, jenis_penerimaan) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
    $stmt_log = $koneksi->prepare($sql_log);

    $sql_stok = "UPDATE stok_material SET jumlah_stok_tersedia = jumlah_stok_tersedia + ? WHERE id_material = ?";
    $stmt_stok = $koneksi->prepare($sql_stok);

    $sql_get_sisa = "
        SELECT (dp.quantity - COALESCE(SUM(log.jumlah_diterima), 0) - COALESCE(SUM(log.jumlah_rusak), 0)) as sisa
        FROM detail_pencatatan_pembelian dp
        LEFT JOIN log_penerimaan_material log ON dp.id_detail_pembelian = log.id_detail_pembelian
        WHERE dp.id_detail_pembelian = ?
        GROUP BY dp.id_detail_pembelian
    ";
    $stmt_get_sisa = $koneksi->prepare($sql_get_sisa);
    
    // --- [BARU] --- Query untuk mengecek harga item, guna menentukan jenis penerimaan
    $sql_cek_harga = "SELECT harga_satuan_pp FROM detail_pencatatan_pembelian WHERE id_detail_pembelian = ?";
    $stmt_cek_harga = $koneksi->prepare($sql_cek_harga);


    for ($i = 0; $i < count($ids_detail_pembelian); $i++) {
        if (!isset($jumlah_diterima_arr[$i]) || !isset($jumlah_rusak_arr[$i])) {
            continue;
        }

        $detail_id = $ids_detail_pembelian[$i];
        $material_id = $ids_material[$i];
        $catatan = $catatan_arr[$i];
        
        $stmt_get_sisa->bind_param("i", $detail_id);
        $stmt_get_sisa->execute();
        $sisa_sebenarnya = (float)($stmt_get_sisa->get_result()->fetch_assoc()['sisa'] ?? 0);

        $diterima_dari_form = (float)$jumlah_diterima_arr[$i];
        $rusak_dari_form = (float)$jumlah_rusak_arr[$i];
        
        if(($diterima_dari_form + $rusak_dari_form) > ($sisa_sebenarnya + 0.01)) {
             throw new Exception("Data tidak valid untuk material ID #$material_id. Jumlah diterima melebihi sisa.");
        }

        $diterima_baik_final = $diterima_dari_form;
        $rusak_final = $rusak_dari_form;

        if ($diterima_baik_final > 0 || $rusak_final > 0) {
            
            // --- [DIHAPUS] --- Logika lama untuk cek jenis penerimaan
            // $stmt_cek_jenis = $koneksi->prepare("SELECT COUNT(*) as hitung FROM ...");

            // --- [BARU] --- Logika baru yang lebih cerdas untuk menentukan Jenis Penerimaan
            $stmt_cek_harga->bind_param("i", $detail_id);
            $stmt_cek_harga->execute();
            $harga_item = (float)($stmt_cek_harga->get_result()->fetch_assoc()['harga_satuan_pp'] ?? 0);

            if ($harga_item > 0) {
                $jenis_penerimaan = 'Penerimaan Awal';
            } else {
                $jenis_penerimaan = 'Penerimaan Pengganti';
            }

            // Masukkan data ke log dengan jenis penerimaan yang sudah benar
            $stmt_log->bind_param("iiiddss", $id_pembelian, $detail_id, $material_id, $diterima_baik_final, $rusak_final, $catatan, $jenis_penerimaan);
            $stmt_log->execute();

            // Update stok
            if ($diterima_baik_final > 0) {
                $stmt_stok->bind_param("di", $diterima_baik_final, $material_id);
                $stmt_stok->execute();
            }
        }
    }
    
    // Logika Update Status Header Pembelian (jika sudah lunas) - Tetap sama
    // ... (tidak ada perubahan di blok ini) ...

    $koneksi->commit();
    $_SESSION['pesan_sukses'] = "Penerimaan untuk Pembelian ID #{$id_pembelian} berhasil dicatat.";

} catch (Exception $exception) {
    $koneksi->rollback();
    $_SESSION['error_message'] = "Terjadi kesalahan. Semua perubahan dibatalkan. Error: " . $exception->getMessage();

} finally {
    if ($stmt_log) $stmt_log->close();
    if ($stmt_stok) $stmt_stok->close();
    if ($stmt_get_sisa) $stmt_get_sisa->close();
    if ($stmt_cek_harga) $stmt_cek_harga->close(); // --- [BARU] --- Tutup statement baru
    if ($stmt_update_header) $stmt_update_header->close();
    
    header("Location: penerimaan_material.php");
    exit();
}
?>