<?php
session_start();
include("../config/koneksi_mysql.php");


// 1. KEAMANAN: Pastikan hanya bisa diakses melalui method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Akses tidak diizinkan.";
    header("Location: distribusi_material.php");
    exit();
}

// 2. AUTENTIKASI: Pastikan user sudah login
if (!isset($_SESSION['id_user'])) {
    $_SESSION['error_message'] = "Sesi Anda telah berakhir. Silakan login kembali.";
    header("Location: login.php");
    exit();
}

// 3. AMBIL DATA DARI FORM DAN SESSION
$id_proyek = $_POST['id_proyek'] ?? ''; // -- BARU -- : Ambil ID Proyek dari form
$tanggal_distribusi = $_POST['tanggal_distribusi'] ?? '';
$keterangan_umum = $_POST['keterangan_umum'] ?? '';
$id_user_pj = $_SESSION['id_user']; // ID user yang login diambil sebagai Penanggung Jawab

// 4. VALIDASI INPUT
// -- BARU -- : Validasi ditambahkan untuk id_proyek
if (empty($id_proyek) || empty($tanggal_distribusi)) {
    $_SESSION['error_message'] = "Proyek Tujuan dan Tanggal Distribusi wajib diisi.";
    header("Location: distribusi_material.php");
    exit();
}

$sql = "INSERT INTO distribusi_material (id_user_pj, id_proyek, tanggal_distribusi, keterangan_umum) VALUES (?, ?, ?, ?)";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    // -- BARU -- : bind_param disesuaikan (iiss) untuk 4 variabel
    // Tipe data: i=integer (id_user_pj), i=integer (id_proyek), s=string, s=string
    mysqli_stmt_bind_param($stmt, "iiss", $id_user_pj, $id_proyek, $tanggal_distribusi, $keterangan_umum);

    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, ambil ID dari record yang baru saja dibuat
        $last_id = mysqli_insert_id($koneksi);
        
        // Buat pesan sukses untuk ditampilkan di halaman berikutnya
        $_SESSION['pesan_sukses'] = "Transaksi distribusi berhasil dibuat. Sekarang, silakan tambahkan detail material.";
        
        // Arahkan ke halaman detail untuk mulai menginput material
        header("Location: input_detail_distribusi.php?id=" . $last_id);
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