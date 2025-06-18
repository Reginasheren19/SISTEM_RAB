<?php
// FILE: proses_update_status.php
session_start();
include("../config/koneksi_mysql.php");

// Set header ke JSON karena output kita adalah JSON
header('Content-Type: application/json');

// Fungsi untuk mengirim response JSON dan menghentikan script
function send_json_response($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit();
}

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(false, 'Metode request tidak diizinkan.');
}

// Validasi input dasar
if (!isset($_POST['id_pengajuan_upah']) || !isset($_POST['new_status'])) {
    send_json_response(false, 'Data tidak lengkap (ID atau Status tidak ada).');
}

// Amankan input
$id_pengajuan_upah = (int)$_POST['id_pengajuan_upah'];
$new_status = mysqli_real_escape_string($koneksi, trim($_POST['new_status']));
$keterangan = isset($_POST['keterangan']) ? trim(mysqli_real_escape_string($koneksi, $_POST['keterangan'])) : '';
$bukti_bayar_path = null;

// Validasi status yang diizinkan
$allowed_statuses = ['diajukan', 'disetujui', 'ditolak', 'dibayar'];
if (!in_array($new_status, $allowed_statuses)) {
    send_json_response(false, 'Status tidak valid.');
}

// Mulai Transaksi Database
mysqli_begin_transaction($koneksi);

try {
    // Penanganan khusus jika status adalah 'dibayar' (memproses file upload)
    if ($new_status === 'dibayar') {
        if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Anda harus meng-upload file bukti pembayaran.");
        }

        $file = $_FILES['bukti_bayar'];
        $upload_dir = '../uploads/bukti_bayar/'; // Path relatif dari folder 'pages'
        
        // Buat direktori jika belum ada
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true)) {
            throw new Exception("Gagal membuat folder upload. Periksa izin folder.");
        }

        $file_ext = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception("Format file tidak diizinkan. Hanya JPG, PNG, atau PDF.");
        }

        // Buat nama file unik untuk menghindari tumpukan nama
        $file_new_name = "bayar_" . $id_pengajuan_upah . "_" . uniqid() . "." . $file_ext;
        $file_destination = $upload_dir . $file_new_name;

        if (!move_uploaded_file($file['tmp_name'], $file_destination)) {
            throw new Exception("Gagal memindahkan file yang di-upload.");
        }
        
        // Simpan path relatif dari root proyek untuk disimpan ke database
        $bukti_bayar_path = 'uploads/bukti_bayar/' . $file_new_name;
    }

    // Ambil keterangan lama untuk membuat riwayat/log (Metode lebih kompatibel)
    $old_keterangan = '';
    $stmt_get = mysqli_prepare($koneksi, "SELECT keterangan FROM pengajuan_upah WHERE id_pengajuan_upah = ?");
    if ($stmt_get) {
        mysqli_stmt_bind_param($stmt_get, "i", $id_pengajuan_upah);
        mysqli_stmt_execute($stmt_get);
        mysqli_stmt_bind_result($stmt_get, $keterangan_db);
        if (mysqli_stmt_fetch($stmt_get)) {
             $old_keterangan = $keterangan_db;
        }
        mysqli_stmt_close($stmt_get);
    } else {
        throw new Exception("Gagal menyiapkan statement SQL untuk mengambil data lama.");
    }
    $old_keterangan = $old_keterangan ?? ''; // Pastikan tidak null
    
    // Buat entri log baru
    $log_entry = "[" . strtoupper($new_status) . " pada " . date('d-m-Y H:i') . "]";
    if (!empty($keterangan)) {
        $log_entry .= ": " . $keterangan;
    }

    // Gabungkan keterangan lama dengan log baru
    $final_keterangan = !empty($old_keterangan) ? $old_keterangan . "\n" . $log_entry : $log_entry;

    // Query UPDATE dinamis berdasarkan status
    if ($new_status === 'dibayar') {
        // Jika 'dibayar', update status, keterangan, dan path bukti bayar
        $sql = "UPDATE pengajuan_upah SET status_pengajuan = ?, keterangan = ?, bukti_bayar = ?, updated_at = NOW() WHERE id_pengajuan_upah = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "sssi", $new_status, $final_keterangan, $bukti_bayar_path, $id_pengajuan_upah);
    } else {
        // Untuk status lain, hanya update status dan keterangan
        $sql = "UPDATE pengajuan_upah SET status_pengajuan = ?, keterangan = ?, updated_at = NOW() WHERE id_pengajuan_upah = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $new_status, $final_keterangan, $id_pengajuan_upah);
    }

    if (!mysqli_stmt_execute($stmt)) {
        // Jika gagal, hapus file yang sudah terlanjur di-upload (jika ada)
        if ($bukti_bayar_path && file_exists('../../' . $bukti_bayar_path)) {
            unlink('../../' . $bukti_bayar_path);
        }
        throw new Exception("Gagal mengupdate status di database: " . mysqli_stmt_error($stmt));
    }
    
    // Jika semua berhasil, commit transaksi
    mysqli_commit($koneksi);
    
    // Kirim pesan sukses via session untuk ditampilkan di halaman setelah reload
    $_SESSION['flash_message'] = ['message' => 'Status pengajuan berhasil diupdate.', 'type' => 'success'];
    send_json_response(true, "Status pengajuan berhasil diupdate.");

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    // [FIXED] Menambahkan detail error ke dalam response JSON untuk debugging
    $error_details = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    send_json_response(false, "Terjadi kesalahan pada server.", ['debug' => $error_details]);
}
?>
