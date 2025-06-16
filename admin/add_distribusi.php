<?php
// Selalu mulai session di awal
session_start();

// Sertakan file koneksi database
include("../config/koneksi_mysql.php");

// -----------------------------------------------------------------------------
// FILE: add_distribusi.php
// FUNGSI: Menerima data dari form modal dan membuat record distribusi baru.
// -----------------------------------------------------------------------------

// 1. KEAMANAN: Pastikan hanya bisa diakses melalui method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Jika diakses langsung, tendang kembali
    $_SESSION['error_message'] = "Akses tidak diizinkan.";
    header("Location: distribusi_material.php");
    exit();
}

// 2. AUTENTIKASI: Pastikan user sudah login
// Saya asumsikan ID user yang login disimpan di $_SESSION['id_user']
// Sesuaikan 'id_user' jika nama session Anda berbeda
if (!isset($_SESSION['id_user'])) {
    $_SESSION['error_message'] = "Sesi Anda telah berakhir. Silakan login kembali.";
    header("Location: login.php"); // Arahkan ke halaman login
    exit();
}

// 3. AMBIL DATA DARI FORM DAN SESSION
$tanggal_distribusi = $_POST['tanggal_distribusi'] ?? '';
$keterangan_umum = $_POST['keterangan_umum'] ?? '';
$id_user_pj = $_SESSION['id_user']; // ID user yang login diambil sebagai Penanggung Jawab

// 4. VALIDASI INPUT
// Tanggal adalah satu-satunya yang wajib diisi berdasarkan form
if (empty($tanggal_distribusi)) {
    $_SESSION['error_message'] = "Tanggal distribusi wajib diisi.";
    header("Location: distribusi_material.php");
    exit();
}

// 5. PERSIAPKAN DAN EKSEKUSI QUERY INSERT
// Menggunakan prepared statements untuk mencegah SQL Injection
$sql = "INSERT INTO distribusi_material (id_user_pj, tanggal_distribusi, keterangan_umum) VALUES (?, ?, ?)";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    // Bind parameter ke statement. Tipe data: i = integer, s = string
    mysqli_stmt_bind_param($stmt, "iss", $id_user_pj, $tanggal_distribusi, $keterangan_umum);

    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, ambil ID dari record yang baru saja dibuat
        $last_id = mysqli_insert_id($koneksi);
        
        // Buat pesan sukses untuk ditampilkan di halaman berikutnya
        $_SESSION['pesan_sukses'] = "Transaksi distribusi berhasil dibuat. Sekarang, silakan tambahkan detail material.";
        
        // Arahkan ke halaman detail untuk mulai menginput material
        header("Location: detail_distribusi.php?id=" . $last_id);
        exit();

    } else {
        // Jika eksekusi gagal
        $_SESSION['error_message'] = "Gagal menyimpan data ke database. Error: " . mysqli_stmt_error($stmt);
        header("Location: distribusi_material.php");
        exit();
    }
} else {
    // Jika persiapan query gagal
    $_SESSION['error_message'] = "Terjadi kesalahan pada server. Query tidak bisa disiapkan.";
    header("Location: distribusi_material.php");
    exit();
}

?>