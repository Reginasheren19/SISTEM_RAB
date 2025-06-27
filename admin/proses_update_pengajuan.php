<?php
// FILE: proses_update_pengajuan.php (Final dengan perbaikan)
session_start();
include("../config/koneksi_mysql.php");

// Fungsi untuk mengarahkan kembali dengan pesan pop-up (sudah baik)
function redirect_with_message($url, $message, $type = 'error') {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
    header("Location: $url");
    exit();
}

// 1. Pastikan request adalah POST dan ID utama ada
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_pengajuan_upah'])) {
    die("Akses tidak sah atau data tidak lengkap.");
}

// 2. Ambil semua data dari form dan amankan
$id_pengajuan_upah = (int)$_POST['id_pengajuan_upah'];
$tanggal_pengajuan = $_POST['tanggal_pengajuan'];
$keterangan = trim($_POST['keterangan'] ?? ''); // Gunakan trim untuk membersihkan spasi
$nominal_pengajuan_final = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_pengajuan_final'] ?? '0');
$progress_items = $_POST['progress'] ?? [];

// [PERBAIKAN] Ambil ID bukti yang akan dihapus dari input hidden
$bukti_dihapus_ids = isset($_POST['bukti_dihapus']) && !empty($_POST['bukti_dihapus']) ? explode(',', $_POST['bukti_dihapus']) : [];

// [PERBAIKAN] Cek file yang diupload dengan cara yang lebih andal
$bukti_baru_files = $_FILES['bukti_pengajuan'] ?? null;
$ada_file_baru = $bukti_baru_files && $bukti_baru_files['error'][0] !== UPLOAD_ERR_NO_FILE;


// Validasi dasar (sudah baik)
$filtered_progress = array_filter($progress_items, fn($val) => is_numeric($val) && (float)$val > 0);
if (empty($filtered_progress)) {
    redirect_with_message("update_pengajuan_upah.php?id_pengajuan_upah=$id_pengajuan_upah", "Tidak ada progress pekerjaan yang diisi.");
}

// Mulai Transaksi Database (sudah baik)
mysqli_begin_transaction($koneksi);

try {
    // 3. Hapus Bukti Lama (jika ada) - Logika Anda sudah benar
    if (!empty($bukti_dihapus_ids)) {
        foreach ($bukti_dihapus_ids as $id_bukti) {
            $id_bukti_safe = (int)$id_bukti;
            
            // Ambil path file untuk dihapus dari server
            $stmt_path = mysqli_prepare($koneksi, "SELECT path_file FROM bukti_pengajuan_upah WHERE id_bukti = ? AND id_pengajuan_upah = ?");
            mysqli_stmt_bind_param($stmt_path, "ii", $id_bukti_safe, $id_pengajuan_upah);
            mysqli_stmt_execute($stmt_path);
            $result_path = mysqli_stmt_get_result($stmt_path);
            
            if ($file = mysqli_fetch_assoc($result_path)) {
                $file_path_on_server = '../' . $file['path_file'];
                if (file_exists($file_path_on_server)) {
                    unlink($file_path_on_server);
                }
            }
            mysqli_stmt_close($stmt_path);

            // Hapus record dari database
            $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM bukti_pengajuan_upah WHERE id_bukti = ?");
            mysqli_stmt_bind_param($stmt_delete, "i", $id_bukti_safe);
            mysqli_stmt_execute($stmt_delete);
            mysqli_stmt_close($stmt_delete);
        }
    }

    // 4. Proses Upload Bukti Baru (jika ada) - [LOGIKA INI DIPERBAIKI TOTAL]
    if ($ada_file_baru) {
        $upload_dir_server = '../uploads/bukti_pengajuan/';
        if (!is_dir($upload_dir_server)) {
            mkdir($upload_dir_server, 0775, true);
        }

        $stmt_bukti_baru = mysqli_prepare($koneksi, "INSERT INTO bukti_pengajuan_upah (id_pengajuan_upah, nama_file, path_file, uploaded_at) VALUES (?, ?, ?, NOW())");
        if ($stmt_bukti_baru === false) {
            throw new Exception("Gagal menyiapkan statement SQL untuk bukti baru: " . mysqli_error($koneksi));
        }

        foreach ($bukti_baru_files['name'] as $i => $nama_file) {
            // Cek jika ada error pada file individual
            if ($bukti_baru_files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $bukti_baru_files['tmp_name'][$i];
                $file_original_name = basename($nama_file);
                $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
                
                // Validasi ekstensi file (contoh)
                $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];
                if (!in_array($file_ext, $allowed_exts)) {
                   throw new Exception("Format file '{$file_original_name}' tidak diizinkan.");
                }

                $file_new_name = "bukti_" . $id_pengajuan_upah . "_" . uniqid() . "." . $file_ext;
                $file_destination = $upload_dir_server . $file_new_name;

                if (move_uploaded_file($tmp_name, $file_destination)) {
                    $path_for_db = 'uploads/bukti_pengajuan/' . $file_new_name;
                    
                    mysqli_stmt_bind_param($stmt_bukti_baru, "iss", $id_pengajuan_upah, $file_original_name, $path_for_db);
                    if (!mysqli_stmt_execute($stmt_bukti_baru)) {
                        throw new Exception("Gagal menyimpan data file '{$file_original_name}' ke database: " . mysqli_stmt_error($stmt_bukti_baru));
                    }
                } else {
                    throw new Exception("Gagal memindahkan file '{$file_original_name}'. Cek izin folder server.");
                }
            } elseif ($bukti_baru_files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                // Beri pesan error jika upload gagal karena alasan selain "tidak ada file"
                throw new Exception("Terjadi error saat mengupload file '{$nama_file}'. Kode error: " . $bukti_baru_files['error'][$i]);
            }
        }
        mysqli_stmt_close($stmt_bukti_baru);
    }


    // 5. Update Detail Pengajuan: Hapus yang lama, masukkan yang baru (sudah baik)
    $stmt_delete_detail = mysqli_prepare($koneksi, "DELETE FROM detail_pengajuan_upah WHERE id_pengajuan_upah = ?");
    mysqli_stmt_bind_param($stmt_delete_detail, "i", $id_pengajuan_upah);
    mysqli_stmt_execute($stmt_delete_detail);
    mysqli_stmt_close($stmt_delete_detail);

    $stmt_insert_detail = mysqli_prepare($koneksi, "INSERT INTO detail_pengajuan_upah (id_pengajuan_upah, id_detail_rab_upah, progress_pekerjaan, nilai_upah_diajukan) VALUES (?, ?, ?, ?)");
    
    foreach ($filtered_progress as $id_detail_rab => $progress_diajukan) {
        $id_detail_rab_int = (int)$id_detail_rab;
        $progress_diajukan_float = (float)$progress_diajukan;
        
        // Menggunakan prepared statement untuk mengambil sub_total agar lebih aman
        $stmt_subtotal = mysqli_prepare($koneksi, "SELECT sub_total FROM detail_rab_upah WHERE id_detail_rab_upah = ?");
        mysqli_stmt_bind_param($stmt_subtotal, "i", $id_detail_rab_int);
        mysqli_stmt_execute($stmt_subtotal);
        $res_subtotal = mysqli_stmt_get_result($stmt_subtotal);
        $sub_total = (float)mysqli_fetch_assoc($res_subtotal)['sub_total'];
        mysqli_stmt_close($stmt_subtotal);

        $nilai_upah_diajukan = round(($progress_diajukan_float / 100) * $sub_total);

        mysqli_stmt_bind_param($stmt_insert_detail, "iidd", $id_pengajuan_upah, $id_detail_rab_int, $progress_diajukan_float, $nilai_upah_diajukan);
        mysqli_stmt_execute($stmt_insert_detail);
    }
    mysqli_stmt_close($stmt_insert_detail);
    
    // 6. Update Header Pengajuan: status kembali menjadi 'diajukan' (sudah baik)
    $sql_update_header = "UPDATE pengajuan_upah SET tanggal_pengajuan = ?, total_pengajuan = ?, keterangan = ?, status_pengajuan = 'diajukan', updated_at = NOW() WHERE id_pengajuan_upah = ?";
    $stmt_header = mysqli_prepare($koneksi, $sql_update_header);
    // [PERBAIKAN] Pastikan total pengajuan yang disimpan adalah nominal final dari form, bukan hasil kalkulasi ulang
    mysqli_stmt_bind_param($stmt_header, "sdsi", $tanggal_pengajuan, $nominal_pengajuan_final, $keterangan, $id_pengajuan_upah);
    mysqli_stmt_execute($stmt_header);
    mysqli_stmt_close($stmt_header);

    // 7. Commit semua perubahan dan arahkan (sudah baik)
    mysqli_commit($koneksi);
    redirect_with_message("pengajuan_upah.php", "Pengajuan berhasil diupdate.", "success");

} catch (Exception $e) {
    mysqli_rollback($koneksi); // Batalkan semua jika ada error
    // Kirim pesan error yang lebih informatif
    redirect_with_message("update_pengajuan_upah.php?id_pengajuan_upah=$id_pengajuan_upah", "Terjadi kesalahan: " . $e->getMessage());
}
?>