<?php
// FILE: proses_update_pengajuan.php (VERSI FINAL - 100% LENGKAP & AMAN)
session_start();
include("../config/koneksi_mysql.php");

// Fungsi helper untuk redirect dengan pesan
function redirect_with_message($url, $message, $type = 'error') {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
    header("Location: $url");
    exit();
}

// Hanya proses jika request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak sah.");
}

// 1. Ambil semua data dari form dengan aman
$id_pengajuan_upah = isset($_POST['id_pengajuan_upah']) ? (int)$_POST['id_pengajuan_upah'] : 0;
if ($id_pengajuan_upah === 0) { die("ID Pengajuan tidak valid."); }

$id_rab_upah_query = mysqli_query($koneksi, "SELECT id_rab_upah FROM pengajuan_upah WHERE id_pengajuan_upah = $id_pengajuan_upah");
$id_rab_upah = (int)mysqli_fetch_assoc($id_rab_upah_query)['id_rab_upah'];

$tanggal_pengajuan = $_POST['tanggal_pengajuan'] ?? date('Y-m-d');
$keterangan = $_POST['keterangan'] ?? '';
$nominal_pengajuan_final = isset($_POST['nominal_pengajuan_final']) ? (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_pengajuan_final']) : 0;
$progress_items = $_POST['progress'] ?? [];
$bukti_dihapus_ids_str = $_POST['bukti_dihapus'] ?? '';
$bukti_dihapus_ids = !empty($bukti_dihapus_ids_str) ? array_map('intval', explode(',', $bukti_dihapus_ids_str)) : [];
$bukti_baru_files = $_FILES['bukti_baru'] ?? null;

// Validasi dasar
if ($nominal_pengajuan_final <= 0) {
    redirect_with_message("update_pengajuan_upah.php?id_pengajuan_upah=$id_pengajuan_upah", "Nominal Final tidak boleh nol.");
}

mysqli_begin_transaction($koneksi);

try {
    // TAHAP 1: HAPUS BUKTI LAMA (JIKA ADA)
    if (!empty($bukti_dihapus_ids)) {
        $ids_placeholder = implode(',', array_fill(0, count($bukti_dihapus_ids), '?'));
        $types = str_repeat('i', count($bukti_dihapus_ids));
        
        $stmt_path = mysqli_prepare($koneksi, "SELECT path_file FROM bukti_pengajuan_upah WHERE id_bukti IN ($ids_placeholder)");
        mysqli_stmt_bind_param($stmt_path, $types, ...$bukti_dihapus_ids);
        mysqli_stmt_execute($stmt_path);
        $result_paths = mysqli_stmt_get_result($stmt_path);
        while($file = mysqli_fetch_assoc($result_paths)) {
            if (!empty($file['path_file']) && file_exists('../' . $file['path_file'])) { unlink('../' . $file['path_file']); }
        }
        mysqli_stmt_close($stmt_path);

        $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM bukti_pengajuan_upah WHERE id_bukti IN ($ids_placeholder)");
        mysqli_stmt_bind_param($stmt_delete, $types, ...$bukti_dihapus_ids);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
    }

    // TAHAP 2: PROSES UPLOAD BUKTI BARU (JIKA ADA)
    if ($bukti_baru_files && $bukti_baru_files['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $upload_dir = '../uploads/bukti_pekerjaan/';
        if (!is_dir($upload_dir)) { if (!mkdir($upload_dir, 0775, true)) { throw new Exception("Gagal membuat folder upload."); } }
        
        $sql_bukti = "INSERT INTO bukti_pengajuan_upah (id_pengajuan_upah, nama_file, path_file, uploaded_at) VALUES (?, ?, ?, NOW())";
        $stmt_bukti_baru = mysqli_prepare($koneksi, $sql_bukti);
        if(!$stmt_bukti_baru) { throw new Exception("Gagal siapkan statement bukti."); }

        foreach ($bukti_baru_files['name'] as $key => $name) {
            if ($bukti_baru_files['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $bukti_baru_files['tmp_name'][$key];
                $file_original_name = basename($name);
                $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
                $file_new_name = "bukti_update_" . $id_pengajuan_upah . "_" . uniqid() . "." . $file_ext;
                $file_destination = $upload_dir . $file_new_name;
                $path_for_db = 'uploads/bukti_pekerjaan/' . $file_new_name;

                if (move_uploaded_file($tmp_name, $file_destination)) {
                    mysqli_stmt_bind_param($stmt_bukti_baru, "iss", $id_pengajuan_upah, $file_original_name, $path_for_db);
                    if(!mysqli_stmt_execute($stmt_bukti_baru)) { unlink($file_destination); throw new Exception("Gagal simpan DB untuk file: ".mysqli_stmt_error($stmt_bukti_baru)); }
                } else { throw new Exception("Gagal memindahkan file '$file_original_name'."); }
            }
        }
        mysqli_stmt_close($stmt_bukti_baru);
    }

    // TAHAP 3: UPDATE DETAIL PENGAJUAN (HAPUS LAMA, INSERT BARU)
    $stmt_delete_detail = mysqli_prepare($koneksi, "DELETE FROM detail_pengajuan_upah WHERE id_pengajuan_upah = ?");
    mysqli_stmt_bind_param($stmt_delete_detail, "i", $id_pengajuan_upah);
    mysqli_stmt_execute($stmt_delete_detail);
    mysqli_stmt_close($stmt_delete_detail);
    
    $filtered_progress = array_filter($progress_items, fn($val) => is_numeric($val) && (float)$val > 0);
    if (!empty($filtered_progress)) {
        $sql_detail = "INSERT INTO detail_pengajuan_upah (id_pengajuan_upah, id_detail_rab_upah, progress_pekerjaan, nilai_upah_diajukan) VALUES (?, ?, ?, ?)";
        $stmt_detail = mysqli_prepare($koneksi, $sql_detail);
        foreach ($filtered_progress as $id_detail_rab => $progress_diajukan) {
            $sub_total_query = mysqli_query($koneksi, "SELECT sub_total FROM detail_rab_upah WHERE id_detail_rab_upah = ".(int)$id_detail_rab);
            $sub_total = (float)mysqli_fetch_assoc($sub_total_query)['sub_total'];
            $nilai_upah_diajukan = round(((float)$progress_diajukan / 100) * $sub_total);
            mysqli_stmt_bind_param($stmt_detail, "iidd", $id_pengajuan_upah, $id_detail_rab, $progress_diajukan, $nilai_upah_diajukan);
            mysqli_stmt_execute($stmt_detail);
        }
        mysqli_stmt_close($stmt_detail);
    }
    
    // TAHAP 4: UPDATE HEADER PENGAJUAN
    $sql_update_header = "UPDATE pengajuan_upah SET tanggal_pengajuan = ?, total_pengajuan = ?, keterangan = ?, status_pengajuan = 'diajukan', updated_at = NOW() WHERE id_pengajuan_upah = ?";
    $stmt_header = mysqli_prepare($koneksi, $sql_update_header);
    mysqli_stmt_bind_param($stmt_header, "sdsi", $tanggal_pengajuan, $nominal_pengajuan_final, $keterangan, $id_pengajuan_upah);
    mysqli_stmt_execute($stmt_header);
    mysqli_stmt_close($stmt_header);

    // TAHAP 5: FINAL - COMMIT & SELESAI
    mysqli_commit($koneksi);
    redirect_with_message("pengajuan_upah.php", "Pengajuan berhasil diupdate.", "success");

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    redirect_with_message("update_pengajuan_upah.php?id_pengajuan_upah=$id_pengajuan_upah", "Terjadi kesalahan: " . $e->getMessage());
}
?>