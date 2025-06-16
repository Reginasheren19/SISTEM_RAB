<?php
// FILE: delete_pengajuan_upah.php (Final & Aman)

session_start();
include("../config/koneksi_mysql.php");

// Fungsi untuk mengarahkan kembali dengan pesan pop-up
function redirect_with_message($url, $message, $type = 'error') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
    header("Location: $url");
    exit();
}

// 1. Validasi Input
if (!isset($_GET['id_pengajuan_upah']) || !filter_var($_GET['id_pengajuan_upah'], FILTER_VALIDATE_INT)) {
    redirect_with_message("pengajuan_upah.php", "ID Pengajuan tidak valid.");
}
$id_pengajuan_to_delete = (int)$_GET['id_pengajuan_upah'];

// Mulai transaksi untuk keamanan
mysqli_begin_transaction($koneksi);

try {
    // 2. Ambil info pengajuan yang akan dihapus
    $stmt = mysqli_prepare($koneksi, "SELECT id_rab_upah, status_pengajuan FROM pengajuan_upah WHERE id_pengajuan_upah = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_pengajuan_to_delete);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pengajuan = mysqli_fetch_assoc($result);

    if (!$pengajuan) {
        throw new Exception("Pengajuan tidak ditemukan.");
    }

    $id_rab_upah = $pengajuan['id_rab_upah'];
    $status_pengajuan = $pengajuan['status_pengajuan'];

    // 3. Validasi Status: Hanya boleh hapus jika statusnya 'diajukan' atau 'ditolak'
    if (!in_array($status_pengajuan, ['diajukan', 'ditolak'])) {
        throw new Exception("Pengajuan dengan status '$status_pengajuan' tidak dapat dihapus.");
    }

    // 4. Validasi Urutan: Hanya boleh hapus pengajuan terakhir
    $stmt_last = mysqli_prepare($koneksi, "SELECT MAX(id_pengajuan_upah) AS id_terakhir FROM pengajuan_upah WHERE id_rab_upah = ?");
    mysqli_stmt_bind_param($stmt_last, "i", $id_rab_upah);
    mysqli_stmt_execute($stmt_last);
    $result_last = mysqli_stmt_get_result($stmt_last);
    $last_pengajuan = mysqli_fetch_assoc($result_last);
    
    if ($id_pengajuan_to_delete != $last_pengajuan['id_terakhir']) {
        throw new Exception("Hanya pengajuan termin terakhir yang dapat dihapus.");
    }

    // 5. Hapus file-file bukti dari server
    $stmt_files = mysqli_prepare($koneksi, "SELECT path_file FROM bukti_pengajuan_upah WHERE id_pengajuan_upah = ?");
    mysqli_stmt_bind_param($stmt_files, "i", $id_pengajuan_to_delete);
    mysqli_stmt_execute($stmt_files);
    $result_files = mysqli_stmt_get_result($stmt_files);
    while($file = mysqli_fetch_assoc($result_files)) {
        $file_path_on_server = '../../' . $file['path_file']; 
        if (file_exists($file_path_on_server)) {
            unlink($file_path_on_server);
        }
    }

    // 6. Hapus dari database (dimulai dari tabel anak/child)
    // Hapus dari bukti_pengajuan_upah
    $stmt_delete_bukti = mysqli_prepare($koneksi, "DELETE FROM bukti_pengajuan_upah WHERE id_pengajuan_upah = ?");
    mysqli_stmt_bind_param($stmt_delete_bukti, "i", $id_pengajuan_to_delete);
    mysqli_stmt_execute($stmt_delete_bukti);

    // Hapus dari detail_pengajuan_upah
    $stmt_delete_detail = mysqli_prepare($koneksi, "DELETE FROM detail_pengajuan_upah WHERE id_pengajuan_upah = ?");
    mysqli_stmt_bind_param($stmt_delete_detail, "i", $id_pengajuan_to_delete);
    mysqli_stmt_execute($stmt_delete_detail);

    // Terakhir, hapus dari pengajuan_upah
    $stmt_delete_main = mysqli_prepare($koneksi, "DELETE FROM pengajuan_upah WHERE id_pengajuan_upah = ?");
    mysqli_stmt_bind_param($stmt_delete_main, "i", $id_pengajuan_to_delete);
    mysqli_stmt_execute($stmt_delete_main);

    // Jika semua berhasil, commit transaksi
    mysqli_commit($koneksi);
    redirect_with_message("pengajuan_upah.php", "Pengajuan berhasil dihapus.", "success");

} catch (Exception $e) {
    // Jika ada error, batalkan semua
    mysqli_rollback($koneksi);
    redirect_with_message("pengajuan_upah.php", "Gagal menghapus: " . $e->getMessage());
}
?>
