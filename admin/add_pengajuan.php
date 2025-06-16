<?php
// FILE: add_pengajuan.php (Telah diperbaiki dengan logika upload file)

session_start();
include("../config/koneksi_mysql.php");

// Fungsi untuk mengarahkan kembali dengan pesan
function redirect_with_message($url, $message, $type = 'error') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit();
}

// Hanya proses jika metode request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari form dan lakukan sanitasi dasar
    $id_rab_upah = isset($_POST['id_rab_upah']) ? (int)$_POST['id_rab_upah'] : 0;
    $tanggal_pengajuan = $_POST['tanggal_pengajuan'] ?? null;
    $total_pengajuan_final = isset($_POST['nominal_pengajuan_final']) ? (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_pengajuan_final']) : 0;
    $keterangan = $_POST['keterangan'] ?? null;
    $all_progress_data = $_POST['progress'] ?? [];

    // Validasi data dasar
    if (empty($id_rab_upah) || empty($tanggal_pengajuan) || $total_pengajuan_final <= 0) {
        redirect_with_message("detail_pengajuan_upah.php?id_rab_upah=$id_rab_upah", "Data tidak lengkap. Pastikan semua data terisi dengan benar.");
    }
    
    $filtered_progress = array_filter($all_progress_data, function($value) {
        return is_numeric($value) && (float)$value > 0;
    });

    if (empty($filtered_progress)) {
        redirect_with_message("detail_pengajuan_upah.php?id_rab_upah=$id_rab_upah", "Tidak ada progress pekerjaan yang diisi.");
    }

    // Mulai transaksi
    mysqli_begin_transaction($koneksi);

    try {
        // 1. Hitung total nilai progress dari detail (Kode Anda sudah bagus, saya pertahankan)
        $total_nilai_progress = 0;
        $detail_to_insert = [];

        foreach ($filtered_progress as $id_detail_rab => $progress_diajukan) {
            $id_detail_rab = (int)$id_detail_rab;
            $progress_diajukan_float = (float)$progress_diajukan;

            $stmt_subtotal = mysqli_prepare($koneksi, "SELECT sub_total FROM detail_rab_upah WHERE id_detail_rab_upah = ?");
            mysqli_stmt_bind_param($stmt_subtotal, "i", $id_detail_rab);
            mysqli_stmt_execute($stmt_subtotal);
            $result_subtotal = mysqli_stmt_get_result($stmt_subtotal);
            $data_subtotal = mysqli_fetch_assoc($result_subtotal);
            
            if (!$data_subtotal) {
                throw new Exception("Detail RAB dengan ID $id_detail_rab tidak ditemukan.");
            }

            $sub_total_pekerjaan = (float)$data_subtotal['sub_total'];
            $nilai_upah_diajukan = ($progress_diajukan_float / 100) * $sub_total_pekerjaan;
            $total_nilai_progress += $nilai_upah_diajukan;
            
            $detail_to_insert[] = [
                'id_detail_rab_upah' => $id_detail_rab,
                'progress_pekerjaan' => $progress_diajukan_float,
                'nilai_upah_diajukan' => round($nilai_upah_diajukan)
            ];
        }

        // 2. Insert ke tabel pengajuan_upah
        $sql_pengajuan = "INSERT INTO pengajuan_upah (id_rab_upah, tanggal_pengajuan, total_pengajuan, nilai_progress, status_pengajuan, keterangan) VALUES (?, ?, ?, ?, 'diajukan', ?)";
        $stmt_pengajuan = mysqli_prepare($koneksi, $sql_pengajuan);
        mysqli_stmt_bind_param($stmt_pengajuan, "isdds", $id_rab_upah, $tanggal_pengajuan, $total_pengajuan_final, $total_nilai_progress, $keterangan);
        
        if (!mysqli_stmt_execute($stmt_pengajuan)) {
            throw new Exception("Gagal menyimpan data pengajuan utama: " . mysqli_stmt_error($stmt_pengajuan));
        }
        $id_pengajuan_upah = mysqli_insert_id($koneksi);

        // 3. Insert ke tabel detail_pengajuan_upah
        $sql_detail = "INSERT INTO detail_pengajuan_upah (id_pengajuan_upah, id_detail_rab_upah, progress_pekerjaan, nilai_upah_diajukan) VALUES (?, ?, ?, ?)";
        $stmt_detail = mysqli_prepare($koneksi, $sql_detail);
        
        foreach ($detail_to_insert as $detail) {
            mysqli_stmt_bind_param($stmt_detail, "iidd", $id_pengajuan_upah, $detail['id_detail_rab_upah'], $detail['progress_pekerjaan'], $detail['nilai_upah_diajukan']);
            if (!mysqli_stmt_execute($stmt_detail)) {
                throw new Exception("Gagal menyimpan detail pengajuan: " . mysqli_stmt_error($stmt_detail));
            }
        }
        
        // 4. [DITAMBAHKAN] Proses upload dan simpan file bukti
        $upload_dir = '../uploads/bukti_pengajuan/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0775, true)) {
                throw new Exception("Gagal membuat folder upload. Pastikan folder `uploads/` dapat ditulis oleh server.");
            }
        }
        
        if (isset($_FILES['bukti_pengajuan']) && count(array_filter($_FILES['bukti_pengajuan']['name'])) > 0) {
            $stmt_bukti = mysqli_prepare($koneksi, "INSERT INTO bukti_pengajuan_upah (id_pengajuan_upah, nama_file, path_file) VALUES (?, ?, ?)");
            
            $files = $_FILES['bukti_pengajuan'];
            $file_count = count($files['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file_tmp_name = $files['tmp_name'][$i];
                    $file_original_name = basename($files['name'][$i]);
                    $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
                    
                    // Buat nama file unik dan aman
                    $file_new_name = "bukti_" . $id_pengajuan_upah . "_" . uniqid() . "." . $file_ext;
                    $file_destination = $upload_dir . $file_new_name;

                    if (move_uploaded_file($file_tmp_name, $file_destination)) {
                        $path_for_db = '../uploads/bukti_pengajuan/' . $file_new_name;
                        mysqli_stmt_bind_param($stmt_bukti, "iss", $id_pengajuan_upah, $file_original_name, $path_for_db);
                        if (!mysqli_stmt_execute($stmt_bukti)) {
                            unlink($file_destination); // Hapus file jika gagal simpan ke DB
                            throw new Exception("Gagal menyimpan data file '$file_original_name' ke database.");
                        }
                    } else {
                        throw new Exception("Gagal memindahkan file '$file_original_name'.");
                    }
                }
            }
        }
        
        // 5. Jika semua query dan upload berhasil, commit transaksi
        mysqli_commit($koneksi);
        redirect_with_message("pengajuan_upah.php", "Pengajuan upah berhasil dikirim.", "success");

    } catch (Exception $e) {
        // Jika terjadi error, rollback semua perubahan database
        mysqli_rollback($koneksi);
        redirect_with_message("detail_pengajuan_upah.php?id_rab_upah=$id_rab_upah", "Terjadi kesalahan: " . $e->getMessage());
    }

} else {
    header("Location: index.php");
    exit();
}
?>
