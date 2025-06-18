<?php
session_start();
include("../config/koneksi_mysql.php");

// -----------------------------------------------------------------------------
// FILE: add_detail_distribusi.php
// FUNGSI: Menerima data borongan (batch) dan menyimpannya ke database.
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Akses tidak diizinkan.";
    header("Location: distribusi_material.php");
    exit();
}

// Ambil data utama dari form
$id_distribusi = $_POST['id_distribusi'] ?? null;
$item_ids = $_POST['id_material'] ?? [];
$item_jumlahs = $_POST['jumlah_distribusi'] ?? [];

// Validasi dasar: Pastikan ada item yang dikirim
if (empty($id_distribusi) || empty($item_ids)) {
    $_SESSION['error_message'] = "Tidak ada item yang ditambahkan atau ID transaksi tidak valid.";
    // Arahkan kembali ke halaman detail yang spesifik
    header("Location: input_detail_distribusi.php?id=" . $id_distribusi);
    exit();
}

// Gunakan Transaksi Database untuk memastikan semua data aman (All or Nothing)
mysqli_begin_transaction($koneksi);

try {
    // Siapkan statement di luar loop untuk efisiensi
    $sql_insert = "INSERT INTO detail_distribusi (id_distribusi, id_material, jumlah_distribusi) VALUES (?, ?, ?)";
    $stmt_insert = mysqli_prepare($koneksi, $sql_insert);

    $sql_update_stok = "UPDATE stok_material SET jumlah_stok_tersedia = jumlah_stok_tersedia - ? WHERE id_material = ?";
    $stmt_update_stok = mysqli_prepare($koneksi, $sql_update_stok);

    // Loop sebanyak item yang dikirim dari form
    for ($i = 0; $i < count($item_ids); $i++) {
        $id_material = $item_ids[$i];
        $jumlah = $item_jumlahs[$i];

        // 1. Insert ke tabel detail_distribusi
        mysqli_stmt_bind_param($stmt_insert, "iid", $id_distribusi, $id_material, $jumlah);
        mysqli_stmt_execute($stmt_insert);

        // 2. Update (kurangi) stok material
        mysqli_stmt_bind_param($stmt_update_stok, "di", $jumlah, $id_material);
        mysqli_stmt_execute($stmt_update_stok);
    }

    // Jika semua query di dalam loop berhasil, simpan semua perubahan secara permanen
    mysqli_commit($koneksi);

    $_SESSION['pesan_sukses'] = "Semua item distribusi berhasil disimpan.";
    // Arahkan ke halaman daftar utama setelah semua selesai
    header("Location: distribusi_material.php");
    exit();

} catch (mysqli_sql_exception $exception) {
    // Jika ada satu saja query yang gagal, batalkan SEMUA perubahan yang sudah terjadi
    mysqli_rollback($koneksi);
    
    $_SESSION['error_message'] = "Terjadi kesalahan saat menyimpan data. Semua perubahan dibatalkan. Error: " . $exception->getMessage();
    // Arahkan kembali ke halaman detail agar user bisa mencoba lagi
    header("Location: input_detail_distribusi.php?id=" . $id_distribusi);
    exit();
}
?>