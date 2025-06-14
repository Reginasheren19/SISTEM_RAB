<?php
// FILE: proses_update_pengajuan.php (Perbaikan)

// Sertakan file koneksi Anda
include("../config/koneksi_mysql.php");

// Mengatur error reporting untuk development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pastikan request adalah METHOD POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak sah.");
}

// Validasi data yang diterima dari form
if (empty($_POST['id_pengajuan_upah'])) {
    die("Error: ID Pengajuan tidak ditemukan.");
}
if (empty($_POST['nominal_pengajuan_final'])) {
    die("Error: Nominal Final tidak boleh kosong.");
}

// Ambil semua data dari form dan amankan
$id_pengajuan_upah = (int)$_POST['id_pengajuan_upah'];
$tanggal_pengajuan = mysqli_real_escape_string($koneksi, $_POST['tanggal_pengajuan']);
$keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
$progress_items = $_POST['progress']; // Ini adalah array [id_detail_rab_upah => progress_value]

// [PERBAIKAN 1] Bersihkan format ribuan dari nominal final sebelum disimpan
$nominal_pengajuan_final = (int)preg_replace('/[^0-9]/', '', $_POST['nominal_pengajuan_final']);

// Mulai Transaksi Database untuk memastikan data konsisten
mysqli_begin_transaction($koneksi);

try {
    // 1. Loop dan update setiap item di tabel `detail_pengajuan_upah`
    foreach ($progress_items as $id_detail_rab_upah => $progress_diajukan) {
        $id_detail_rab_upah_safe = (int)$id_detail_rab_upah;
        $progress_diajukan_safe = (float)$progress_diajukan;

        // Ambil sub_total untuk menghitung nilai_upah di server
        $res_subtotal = mysqli_query($koneksi, "SELECT sub_total FROM detail_rab_upah WHERE id_detail_rab_upah = $id_detail_rab_upah_safe");
        if(!$res_subtotal) {
             throw new Exception("Query sub_total gagal: " . mysqli_error($koneksi));
        }
        $sub_total = (float)mysqli_fetch_assoc($res_subtotal)['sub_total'];
        $nilai_upah_diajukan = ($progress_diajukan_safe / 100) * $sub_total;

        // Query update untuk detail menggunakan prepared statement
        $sql_update_detail = "UPDATE detail_pengajuan_upah 
                              SET progress_pekerjaan = ?, nilai_upah_diajukan = ?
                              WHERE id_pengajuan_upah = ? AND id_detail_rab_upah = ?";
        
        $stmt_detail = mysqli_prepare($koneksi, $sql_update_detail);
        mysqli_stmt_bind_param($stmt_detail, "ddii", $progress_diajukan_safe, $nilai_upah_diajukan, $id_pengajuan_upah, $id_detail_rab_upah_safe);
        if (!mysqli_stmt_execute($stmt_detail)) {
            throw new Exception("Gagal update detail: " . mysqli_stmt_error($stmt_detail));
        }
        mysqli_stmt_close($stmt_detail);
    }

    // [PERBAIKAN 2] Update data header di tabel `pengajuan_upah` HANYA SEKALI
    // Nilai `total_pengajuan` diambil langsung dari input nominal final yang sudah diedit user.
    // Saat diedit, status kembali menjadi 'diajukan'
    $sql_update_header = "UPDATE pengajuan_upah 
                          SET tanggal_pengajuan = ?, total_pengajuan = ?, keterangan = ?, status_pengajuan = 'diajukan', updated_at = NOW()
                          WHERE id_pengajuan_upah = ?";
    
    $stmt_header = mysqli_prepare($koneksi, $sql_update_header);
    mysqli_stmt_bind_param($stmt_header, "sisi", $tanggal_pengajuan, $nominal_pengajuan_final, $keterangan, $id_pengajuan_upah);
     if (!mysqli_stmt_execute($stmt_header)) {
        throw new Exception("Gagal update header pengajuan: " . mysqli_stmt_error($stmt_header));
    }
    mysqli_stmt_close($stmt_header);
    
    // 3. Commit transaksi jika semuanya berhasil
    mysqli_commit($koneksi);

    // Redirect dengan pesan sukses
    header("Location: pengajuan_upah.php?msg=Pengajuan berhasil diupdate.");
    exit();

} catch (Exception $e) {
    // Jika terjadi error, batalkan semua perubahan
    mysqli_rollback($koneksi);
    die("Terjadi kesalahan saat memproses data: " . $e->getMessage() . ". Transaksi dibatalkan.");
}
?>
