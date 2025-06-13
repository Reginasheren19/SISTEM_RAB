<?php
// FILE: proses_update_pengajuan.php
// TUGAS: MENERIMA DATA DARI FORM DAN MENYIMPAN KE DATABASE

include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak sah.");
}

// Ambil semua data yang dikirim dari form dan bersihkan
$id_pengajuan_upah = (int)$_POST['id_pengajuan_upah'];
$tanggal_pengajuan = mysqli_real_escape_string($koneksi, $_POST['tanggal_pengajuan']);
$keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
$nominal_pengajuan_final = (float)$_POST['nominal_pengajuan_final'];
$progress_items = $_POST['progress']; // Ini adalah array [id_detail_rab_upah => progress_value]

// Mulai Transaksi Database untuk memastikan data konsisten
mysqli_begin_transaction($koneksi);

try {
    // 1. Update data header di tabel `pengajuan_upah`
    // Saat diedit, status kembali menjadi 'diajukan'
    $sql_update_header = "UPDATE pengajuan_upah 
                          SET tanggal_pengajuan = ?, total_pengajuan = ?, keterangan = ?, status_pengajuan = 'diajukan'
                          WHERE id_pengajuan_upah = ?";
    
    $stmt_header = mysqli_prepare($koneksi, $sql_update_header);
    mysqli_stmt_bind_param($stmt_header, "sdsi", $tanggal_pengajuan, $nominal_pengajuan_final, $keterangan, $id_pengajuan_upah);
    mysqli_stmt_execute($stmt_header);
    mysqli_stmt_close($stmt_header);

    // 2. Loop dan update setiap item di tabel `detail_pengajuan_upah`
    foreach ($progress_items as $id_detail_rab_upah => $progress_diajukan) {
        $id_detail_rab_upah = (int)$id_detail_rab_upah;
        $progress_diajukan = (float)$progress_diajukan;

        // Ambil sub_total untuk menghitung nilai_upah
        $res_subtotal = mysqli_query($koneksi, "SELECT sub_total FROM detail_rab_upah WHERE id_detail_rab_upah = $id_detail_rab_upah");
        $sub_total = (float)mysqli_fetch_assoc($res_subtotal)['sub_total'];
        $nilai_upah_diajukan = ($progress_diajukan / 100) * $sub_total;

        // Query update untuk detail
        $sql_update_detail = "UPDATE detail_pengajuan_upah 
                              SET progress_pekerjaan = ?, nilai_upah_diajukan = ?
                              WHERE id_pengajuan_upah = ? AND id_detail_rab_upah = ?";
        
        $stmt_detail = mysqli_prepare($koneksi, $sql_update_detail);
        mysqli_stmt_bind_param($stmt_detail, "ddii", $progress_diajukan, $nilai_upah_diajukan, $id_pengajuan_upah, $id_detail_rab_upah);
        mysqli_stmt_execute($stmt_detail);
        mysqli_stmt_close($stmt_detail);
    }

    // Jika semua query berhasil, commit transaksi
    mysqli_commit($koneksi);
    
    // Redirect dengan pesan sukses
    header("Location: pengajuan_upah.php?status=update_sukses");
    exit();

} catch (Exception $e) {
    // Jika terjadi error, batalkan semua perubahan
    mysqli_rollback($koneksi);
    die("Terjadi kesalahan saat memproses data: " . $e->getMessage());
}
?>