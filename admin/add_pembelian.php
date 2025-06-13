<?php
session_start();
include("../config/koneksi_mysql.php");

// Aktifkan error reporting untuk development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validasi: Pastikan request method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Akses tidak sah.";
    header("Location: pencatatan_pembelian.php");
    exit();
}

// Ambil dan validasi data input
$tanggal_pembelian = $_POST['tanggal_pembelian'] ?? '';
$keterangan_pembelian = $_POST['keterangan_pembelian'] ?? '';
$bukti_pembayaran = $_FILES['bukti_pembayaran'] ?? null;

// Validasi jika data tidak ada
if (empty($tanggal_pembelian) || empty($keterangan_pembelian)) {
    $_SESSION['error_message'] = "Tanggal dan keterangan pembelian wajib diisi!";
    header("Location: pencatatan_pembelian.php");
    exit();
}

// Validasi dan proses upload file bukti pembayaran jika ada
$bukti_pembayaran_file = '';
if ($bukti_pembayaran && $bukti_pembayaran['error'] === UPLOAD_ERR_OK) {
    // Tentukan direktori tujuan
    $upload_dir = "../uploads/bukti_pembayaran/";
    $file_name = basename($bukti_pembayaran['name']);
    $target_file = $upload_dir . $file_name;

    // Cek apakah file adalah gambar
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
        if (move_uploaded_file($bukti_pembayaran['tmp_name'], $target_file)) {
            $bukti_pembayaran_file = $file_name; // Simpan nama file untuk database
        } else {
            $_SESSION['error_message'] = "Gagal meng-upload bukti pembayaran.";
            header("Location: pencatatan_pembelian.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "File yang di-upload bukan gambar.";
        header("Location: pencatatan_pembelian.php");
        exit();
    }
}

// Menyusun query untuk menyimpan data pembelian
$sql = "INSERT INTO pencatatan_pembelian (tanggal_pembelian, keterangan_pembelian, bukti_pembayaran, total_biaya) 
        VALUES (?, ?, ?, 0)"; // total_biaya di-set ke 0 terlebih dahulu
$stmt = mysqli_prepare($koneksi, $sql);

// Periksa apakah query berhasil disiapkan
if ($stmt) {
    // Bind parameter ke statement
    mysqli_stmt_bind_param($stmt, "sss", $tanggal_pembelian, $keterangan_pembelian, $bukti_pembayaran_file);

    // Eksekusi query
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, ambil ID pembelian yang baru saja dibuat
        $last_id = mysqli_insert_id($koneksi);
        $_SESSION['pesan_sukses'] = "Pembelian berhasil ditambahkan.";

        // Redirect ke halaman detail untuk menambahkan item
        header("Location: input_detail_pembelian.php?id=" . $last_id);
        exit();
    } else {
        // Jika eksekusi gagal
        $_SESSION['error_message'] = "Gagal menyimpan data ke database: " . mysqli_stmt_error($stmt);
        header("Location: pencatatan_pembelian.php");
        exit();
    }
} else {
    // Jika query gagal disiapkan
    $_SESSION['error_message'] = "Terjadi kesalahan pada query database.";
    header("Location: pencatatan_pembelian.php");
    exit();
}
?>
