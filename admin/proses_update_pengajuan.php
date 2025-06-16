<?php
// FILE: proses_update_pengajuan.php (Final dengan logika update, hapus bukti, dan tambah bukti)
session_start();
include("../config/koneksi_mysql.php");

// Fungsi untuk mengarahkan kembali dengan pesan pop-up
function redirect_with_message($url, $message, $type = 'error') {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
    header("Location: $url");
    exit();
}

// 1. Pastikan request adalah POST dan data utama ada
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_pengajuan_upah'])) {
    die("Akses tidak sah atau data tidak lengkap.");
}

// 2. Ambil semua data dari form dan amankan
$id_pengajuan_upah = (int)$_POST['id_pengajuan_upah'];
$tanggal_pengajuan = $_POST['tanggal_pengajuan'];
$keterangan = $_POST['keterangan'] ?? '';
$nominal_pengajuan_final = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_pengajuan_final'] ?? '0');
$progress_items = $_POST['progress'] ?? [];
$bukti_dihapus_ids = isset($_POST['bukti_dihapus']) && !empty($_POST['bukti_dihapus']) ? explode(',', $_POST['bukti_dihapus']) : [];
$bukti_baru_files = $_FILES['bukti_pekerjaan_baru'] ?? [];

// Validasi dasar
$filtered_progress = array_filter($progress_items, fn($val) => is_numeric($val) && (float)$val > 0);
if (empty($filtered_progress)) {
    redirect_with_message("update_pengajuan_upah.php?id_pengajuan_upah=$id_pengajuan_upah", "Tidak ada progress pekerjaan yang diisi.");
}

// Mulai Transaksi Database
mysqli_begin_transaction($koneksi);

try {
    // 3. Hapus Bukti Lama (jika ada)
    if (!empty($bukti_dihapus_ids)) {
        foreach ($bukti_dihapus_ids as $id_bukti) {
            $id_bukti_safe = (int)$id_bukti;
            // Ambil path file untuk dihapus dari server
            $stmt_path = mysqli_prepare($koneksi, "SELECT path_file FROM bukti_pengajuan_upah WHERE id_bukti = ? AND id_pengajuan_upah = ?");
            mysqli_stmt_bind_param($stmt_path, "ii", $id_bukti_safe, $id_pengajuan_upah);
            mysqli_stmt_execute($stmt_path);
            $result_path = mysqli_stmt_get_result($stmt_path);
            if ($file = mysqli_fetch_assoc($result_path)) {
                $file_path_on_server = '../../' . $file['path_file']; 
                if (file_exists($file_path_on_server)) {
                    unlink($file_path_on_server);
                }
            }
            // Hapus record dari database
            $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM bukti_pengajuan_upah WHERE id_bukti = ?");
            mysqli_stmt_bind_param($stmt_delete, "i", $id_bukti_safe);
            mysqli_stmt_execute($stmt_delete);
        }
    }

    // 4. Proses Upload Bukti Baru (jika ada)
    $upload_dir = '../../uploads/progress_pengajuan/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
    
    if (!empty($bukti_baru_files['name'][0])) {
        $stmt_bukti_baru = mysqli_prepare($koneksi, "INSERT INTO bukti_pengajuan_upah (id_pengajuan_upah, nama_file, path_file) VALUES (?, ?, ?)");
        for ($i = 0; $i < count($bukti_baru_files['name']); $i++) {
            if ($bukti_baru_files['error'][$i] === UPLOAD_ERR_OK) {
                $file_original_name = basename($bukti_baru_files['name'][$i]);
                $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
                $file_new_name = "progress_" . $id_pengajuan_upah . "_" . uniqid() . "." . $file_ext;
                $file_destination = $upload_dir . $file_new_name;

                if (move_uploaded_file($bukti_baru_files['tmp_name'][$i], $file_destination)) {
                    $path_for_db = 'uploads/progress_pengajuan/' . $file_new_name;
                    mysqli_stmt_bind_param($stmt_bukti_baru, "iss", $id_pengajuan_upah, $file_original_name, $path_for_db);
                    mysqli_stmt_execute($stmt_bukti_baru);
                }
            }
        }
    }

    // 5. Update Detail Pengajuan: Hapus yang lama, masukkan yang baru (lebih aman)
    $stmt_delete_detail = mysqli_prepare($koneksi, "DELETE FROM detail_pengajuan_upah WHERE id_pengajuan_upah = ?");
    mysqli_stmt_bind_param($stmt_delete_detail, "i", $id_pengajuan_upah);
    mysqli_stmt_execute($stmt_delete_detail);

    $stmt_insert_detail = mysqli_prepare($koneksi, "INSERT INTO detail_pengajuan_upah (id_pengajuan_upah, id_detail_rab_upah, progress_pekerjaan, nilai_upah_diajukan) VALUES (?, ?, ?, ?)");
    $total_nilai_progress_recalculated = 0;

    foreach ($filtered_progress as $id_detail_rab => $progress_diajukan) {
        $id_detail_rab = (int)$id_detail_rab;
        $progress_diajukan_float = (float)$progress_diajukan;
        
        $res_subtotal = mysqli_query($koneksi, "SELECT sub_total FROM detail_rab_upah WHERE id_detail_rab_upah = $id_detail_rab");
        $sub_total = (float)mysqli_fetch_assoc($res_subtotal)['sub_total'];
        $nilai_upah_diajukan = round(($progress_diajukan_float / 100) * $sub_total);
        $total_nilai_progress_recalculated += $nilai_upah_diajukan;

        mysqli_stmt_bind_param($stmt_insert_detail, "iidd", $id_pengajuan_upah, $id_detail_rab, $progress_diajukan_float, $nilai_upah_diajukan);
        mysqli_stmt_execute($stmt_insert_detail);
    }
    
    // 6. Update Header Pengajuan: status kembali menjadi 'diajukan'
    $sql_update_header = "UPDATE pengajuan_upah SET tanggal_pengajuan = ?, total_pengajuan = ?, keterangan = ?, status_pengajuan = 'diajukan', updated_at = NOW() WHERE id_pengajuan_upah = ?";
    $stmt_header = mysqli_prepare($koneksi, $sql_update_header);
    mysqli_stmt_bind_param($stmt_header, "sdsi", $tanggal_pengajuan, $nominal_pengajuan_final, $keterangan, $id_pengajuan_upah);
    mysqli_stmt_execute($stmt_header);

    // 7. Commit semua perubahan
    mysqli_commit($koneksi);
    redirect_with_message("pengajuan_upah.php", "Pengajuan berhasil diupdate.", "success");

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    redirect_with_message("update_pengajuan_upah.php?id_pengajuan_upah=$id_pengajuan_upah", "Terjadi kesalahan: " . $e->getMessage());
}

?>
