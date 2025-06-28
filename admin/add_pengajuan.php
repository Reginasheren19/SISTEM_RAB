<?php
// FILE: add_pengajuan.php (VERSI FINAL & DIPERBAIKI)
session_start();
include("../config/koneksi_mysql.php");

function redirect_with_message($url, $message, $type = 'error') {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
    header("Location: $url");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_rab_upah = isset($_POST['id_rab_upah']) ? (int)$_POST['id_rab_upah'] : 0;
    $tanggal_pengajuan = $_POST['tanggal_pengajuan'] ?? date('Y-m-d');
    $total_pengajuan_final = isset($_POST['nominal_pengajuan_final']) ? (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_pengajuan_final']) : 0;
    $keterangan = $_POST['keterangan'] ?? null;
    $all_progress_data = $_POST['progress'] ?? [];

    if (empty($id_rab_upah) || empty($tanggal_pengajuan) || $total_pengajuan_final <= 0) {
        redirect_with_message("pengajuan_upah.php", "Data tidak lengkap. Pastikan semua data terisi dengan benar.");
    }

    $filtered_progress = array_filter($all_progress_data, fn($v) => is_numeric($v) && (float)$v > 0);
    if (empty($filtered_progress)) {
        redirect_with_message("detail_pengajuan_upah.php?id_rab_upah=$id_rab_upah", "Tidak ada progress pekerjaan yang diisi.");
    }

    mysqli_begin_transaction($koneksi);

    try {
        $id_details = array_keys($filtered_progress);
        $id_placeholders = implode(',', array_fill(0, count($id_details), '?'));
        $types = str_repeat('i', count($id_details));
        $subtotals = [];
        $stmt_get_subtotals = mysqli_prepare($koneksi, "SELECT id_detail_rab_upah, sub_total FROM detail_rab_upah WHERE id_detail_rab_upah IN ($id_placeholders)");
        mysqli_stmt_bind_param($stmt_get_subtotals, $types, ...$id_details);
        mysqli_stmt_execute($stmt_get_subtotals);
        $result_subtotals = mysqli_stmt_get_result($stmt_get_subtotals);
        while ($row = mysqli_fetch_assoc($result_subtotals)) {
            $subtotals[$row['id_detail_rab_upah']] = (float)$row['sub_total'];
        }
        mysqli_stmt_close($stmt_get_subtotals);

        $total_nilai_progress = 0;
        $detail_to_insert = [];
        foreach ($filtered_progress as $id_detail_rab => $progress_diajukan) {
            $id_detail_rab_int = (int)$id_detail_rab;
            $nilai_upah_diajukan = ($progress_diajukan / 100) * $subtotals[$id_detail_rab_int];
            $total_nilai_progress += $nilai_upah_diajukan;
            $detail_to_insert[] = ['id_detail_rab_upah' => $id_detail_rab_int, 'progress_pekerjaan' => (float)$progress_diajukan, 'nilai_upah_diajukan' => round($nilai_upah_diajukan)];
        }

        $sql_pengajuan = "INSERT INTO pengajuan_upah (id_rab_upah, tanggal_pengajuan, total_pengajuan, nilai_progress, status_pengajuan, keterangan) VALUES (?, ?, ?, ?, 'diajukan', ?)";
        $stmt_pengajuan = mysqli_prepare($koneksi, $sql_pengajuan);
        mysqli_stmt_bind_param($stmt_pengajuan, "isdds", $id_rab_upah, $tanggal_pengajuan, $total_pengajuan_final, $total_nilai_progress, $keterangan);
        if (!mysqli_stmt_execute($stmt_pengajuan)) { throw new Exception("Gagal simpan header: " . mysqli_stmt_error($stmt_pengajuan)); }
        $id_pengajuan_upah = mysqli_insert_id($koneksi);

        $sql_detail = "INSERT INTO detail_pengajuan_upah (id_pengajuan_upah, id_detail_rab_upah, progress_pekerjaan, nilai_upah_diajukan) VALUES (?, ?, ?, ?)";
        $stmt_detail = mysqli_prepare($koneksi, $sql_detail);
        foreach ($detail_to_insert as $detail) {
            mysqli_stmt_bind_param($stmt_detail, "iidd", $id_pengajuan_upah, $detail['id_detail_rab_upah'], $detail['progress_pekerjaan'], $detail['nilai_upah_diajukan']);
            if (!mysqli_stmt_execute($stmt_detail)) { throw new Exception("Gagal simpan detail: " . mysqli_stmt_error($stmt_detail)); }
        }
        mysqli_stmt_close($stmt_detail);

        if (isset($_FILES['bukti_pengajuan']) && !empty(array_filter($_FILES['bukti_pengajuan']['name']))) {
            $upload_dir = '../uploads/bukti_pekerjaan/'; // Gunakan folder yang konsisten
            if (!is_dir($upload_dir)) { if (!mkdir($upload_dir, 0775, true)) { throw new Exception("Gagal buat folder upload. Cek izin folder."); } }
            
            // ===============================================================================================
            // INI BAGIAN YANG DIPERBAIKI
            $sql_bukti = "INSERT INTO bukti_pengajuan_upah (id_pengajuan_upah, nama_file, path_file, uploaded_at) VALUES (?, ?, ?, NOW())";
            // ===============================================================================================

            $stmt_bukti = mysqli_prepare($koneksi, $sql_bukti);
            if (!$stmt_bukti) { throw new Exception("Gagal siapkan statement bukti: " . mysqli_error($koneksi)); }

            foreach ($_FILES['bukti_pengajuan']['name'] as $key => $name) {
                if ($_FILES['bukti_pengajuan']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['bukti_pengajuan']['tmp_name'][$key];
                    $file_original_name = basename($name);
                    $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
                    $file_new_name = "bukti_" . $id_pengajuan_upah . "_" . uniqid() . "." . $file_ext;
                    $file_destination = $upload_dir . $file_new_name;
                    $path_for_db = 'uploads/bukti_pekerjaan/' . $file_new_name;

                    if (move_uploaded_file($tmp_name, $file_destination)) {
                        mysqli_stmt_bind_param($stmt_bukti, "iss", $id_pengajuan_upah, $file_original_name, $path_for_db);
                        if (!mysqli_stmt_execute($stmt_bukti)) {
                            unlink($file_destination);
                            throw new Exception("Gagal simpan DB untuk file '$file_original_name'. Error: " . mysqli_stmt_error($stmt_bukti));
                        }
                    } else { throw new Exception("Gagal pindah file '$file_original_name'. Cek izin folder upload."); }
                }
            }
            mysqli_stmt_close($stmt_bukti);
        }
        
        mysqli_commit($koneksi);
        redirect_with_message("pengajuan_upah.php", "Pengajuan upah berhasil dikirim.", "success");

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        redirect_with_message("detail_pengajuan_upah.php?id_rab_upah=$id_rab_upah", "Terjadi kesalahan: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit();
}
?>