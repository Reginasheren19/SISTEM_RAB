<?php
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
    $total_pengajuan_final = isset($_POST['nominal_pengajuan_final']) ? (int)str_replace('.', '', $_POST['nominal_pengajuan_final']) : 0;
    $keterangan = $_POST['keterangan'] ?? null; // Menambahkan field keterangan
    $all_progress_data = $_POST['progress'] ?? [];

    // Validasi: Pastikan tanggal dan ID RAB upah ada
    if (empty($id_rab_upah) || empty($tanggal_pengajuan)) {
        redirect_with_message("detail_pengajuan_upah.php?id_rab_upah=$id_rab_upah", "Data tidak lengkap. Pastikan ID RAB dan tanggal pengajuan terisi.");
    }
    
    // Validasi: Nilai pengajuan harus lebih besar dari 0
    if ($total_pengajuan_final <= 0) {
        redirect_with_message("detail_pengajuan_upah.php?id_rab_upah=$id_rab_upah", "Nilai pengajuan final harus lebih besar dari nol.");
    }

    // Menyaring array progress, hanya proses yang nilainya diisi dan lebih dari 0
    $filtered_progress = array_filter($all_progress_data, function($value) {
        return is_numeric($value) && (float)$value > 0;
    });

    if (empty($filtered_progress)) {
        redirect_with_message("detail_pengajuan_upah.php?id_rab_upah=$id_rab_upah", "Tidak ada progress pekerjaan yang diisi. Harap isi minimal satu item progress.");
    }

    // Mulai transaksi
    mysqli_begin_transaction($koneksi);

    try {
        // 1. Hitung total nilai progress yang diajukan dari detail
        $total_nilai_progress = 0;
        $detail_to_insert = [];

        foreach ($filtered_progress as $id_detail_rab => $progress_diajukan) {
            $id_detail_rab = (int)$id_detail_rab;
            $progress_diajukan = (float)$progress_diajukan;

            // Ambil sub_total dari database untuk keamanan (menghindari manipulasi dari sisi klien)
            $stmt_subtotal = mysqli_prepare($koneksi, "SELECT sub_total FROM detail_rab_upah WHERE id_detail_rab_upah = ?");
            mysqli_stmt_bind_param($stmt_subtotal, "i", $id_detail_rab);
            mysqli_stmt_execute($stmt_subtotal);
            $result_subtotal = mysqli_stmt_get_result($stmt_subtotal);
            $data_subtotal = mysqli_fetch_assoc($result_subtotal);
            
            if (!$data_subtotal) {
                throw new Exception("Detail RAB dengan ID $id_detail_rab tidak ditemukan.");
            }

            $sub_total_pekerjaan = (float)$data_subtotal['sub_total'];
            $nilai_upah_diajukan = ($progress_diajukan / 100) * $sub_total_pekerjaan;
            $total_nilai_progress += $nilai_upah_diajukan;
            
            // Simpan data detail untuk dimasukkan nanti
            $detail_to_insert[] = [
                'id_detail_rab_upah' => $id_detail_rab,
                'progress_pekerjaan' => $progress_diajukan,
                'nilai_upah_diajukan' => round($nilai_upah_diajukan)
            ];
        }

        // 2. Insert ke tabel pengajuan_upah
        $sql_pengajuan = "INSERT INTO pengajuan_upah (id_rab_upah, tanggal_pengajuan, total_pengajuan, nilai_progress, status_pengajuan, keterangan, created_at, updated_at) VALUES (?, ?, ?, ?, 'diajukan', ?, NOW(), NOW())";
        $stmt_pengajuan = mysqli_prepare($koneksi, $sql_pengajuan);
        mysqli_stmt_bind_param($stmt_pengajuan, "isdis", $id_rab_upah, $tanggal_pengajuan, $total_pengajuan_final, $total_nilai_progress, $keterangan);
        
        if (!mysqli_stmt_execute($stmt_pengajuan)) {
            throw new Exception("Gagal menyimpan data pengajuan utama: " . mysqli_stmt_error($stmt_pengajuan));
        }

        $id_pengajuan_upah = mysqli_insert_id($koneksi); // Dapatkan ID dari pengajuan yang baru saja dibuat

        // 3. Insert ke tabel detail_pengajuan_upah
        $sql_detail = "INSERT INTO detail_pengajuan_upah (id_pengajuan_upah, id_detail_rab_upah, progress_pekerjaan, nilai_upah_diajukan) VALUES (?, ?, ?, ?)";
        $stmt_detail = mysqli_prepare($koneksi, $sql_detail);
        
        foreach ($detail_to_insert as $detail) {
            mysqli_stmt_bind_param(
                $stmt_detail, 
                "iidi", 
                $id_pengajuan_upah, 
                $detail['id_detail_rab_upah'], 
                $detail['progress_pekerjaan'], 
                $detail['nilai_upah_diajukan']
            );
            if (!mysqli_stmt_execute($stmt_detail)) {
                throw new Exception("Gagal menyimpan detail pengajuan: " . mysqli_stmt_error($stmt_detail));
            }
        }
        
        // Jika semua query berhasil, commit transaksi
        mysqli_commit($koneksi);
        
        // Set pesan sukses dan redirect
        redirect_with_message("pengajuan_upah.php", "Pengajuan upah berhasil dikirim.", "success");

    } catch (Exception $e) {
        // Jika terjadi error, rollback transaksi
        mysqli_rollback($koneksi);
        
        // Catat error (opsional, untuk debugging)
        // error_log($e->getMessage());

        // Set pesan error dan redirect kembali ke form
        redirect_with_message("detail_pengajuan_upah.php?id_rab_upah=$id_rab_upah", "Terjadi kesalahan: " . $e->getMessage());
    }

} else {
    // Jika akses bukan via POST, redirect ke halaman utama
    header("Location: index.php");
    exit();
}
?>
