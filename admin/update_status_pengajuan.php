<?php
// FILE: update_status_pengajuan.php (Dengan logika riwayat/log keterangan)

header('Content-Type: application/json');
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

// Validasi input
if (!isset($_POST['id_pengajuan_upah']) || !isset($_POST['new_status'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

// Amankan input
$id_pengajuan_upah = (int)$_POST['id_pengajuan_upah'];
$new_status = mysqli_real_escape_string($koneksi, $_POST['new_status']);
// Ini adalah keterangan untuk status SAAT INI (misal: alasan penolakan atau catatan lain)
$current_keterangan = isset($_POST['keterangan']) ? trim(mysqli_real_escape_string($koneksi, $_POST['keterangan'])) : '';

// [LOGIKA BARU] Membuat riwayat keterangan
// 1. Ambil keterangan yang sudah ada di database
$sql_get_old_keterangan = "SELECT keterangan FROM pengajuan_upah WHERE id_pengajuan_upah = ?";
$stmt_get = mysqli_prepare($koneksi, $sql_get_old_keterangan);
mysqli_stmt_bind_param($stmt_get, "i", $id_pengajuan_upah);
mysqli_stmt_execute($stmt_get);
$result = mysqli_stmt_get_result($stmt_get);
$row = mysqli_fetch_assoc($result);
$old_keterangan = isset($row['keterangan']) ? trim($row['keterangan']) : '';
mysqli_stmt_close($stmt_get);

// 2. Buat entri log baru untuk perubahan status ini
// Format: [STATUS] atau [STATUS]: Keterangan baru
$log_entry = "[" . strtoupper($new_status) . "]";
if (!empty($current_keterangan)) {
    // Tambahkan keterangan hanya jika diisi.
    $log_entry .= ": " . $current_keterangan;
}

// 3. Gabungkan keterangan lama dengan entri log baru, dipisahkan oleh baris baru
$final_keterangan = $old_keterangan;
if (!empty($old_keterangan)) {
    // Tambahkan baris baru jika sudah ada keterangan sebelumnya
    $final_keterangan .= "\n" . $log_entry;
} else {
    // Jika ini adalah keterangan pertama
    $final_keterangan = $log_entry;
}


// Siapkan query UPDATE menggunakan prepared statement
$sql = "UPDATE pengajuan_upah SET status_pengajuan = ?, keterangan = ?, updated_at = NOW() WHERE id_pengajuan_upah = ?";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ssi", $new_status, $final_keterangan, $id_pengajuan_upah);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate status di database.']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query.']);
}

mysqli_close($koneksi);
?>
