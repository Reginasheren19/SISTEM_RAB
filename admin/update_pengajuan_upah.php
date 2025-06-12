<?php
// Include file koneksi Anda
include("../config/koneksi_mysql.php");

// Pastikan ID RAB Upah dan ID Pengajuan Upah ada
if (!isset($_POST['id_rab_upah']) || !isset($_POST['id_detail_pengajuan']) || !isset($_POST['progress_pekerjaan']) || !isset($_POST['nilai_upah_diajukan'])) {
    echo "Data tidak lengkap.";
    exit;
}

// Ambil ID RAB Upah, ID Detail Pengajuan, Progress, dan Nilai Upah Diajukan
$id_rab_upah = mysqli_real_escape_string($koneksi, $_POST['id_rab_upah']);
$id_detail_pengajuan = mysqli_real_escape_string($koneksi, $_POST['id_detail_pengajuan']);
$progress_pekerjaan = mysqli_real_escape_string($koneksi, $_POST['progress_pekerjaan']);
$nilai_upah_diajukan = mysqli_real_escape_string($koneksi, $_POST['nilai_upah_diajukan']);

// Cek apakah sudah ada pengajuan dengan ID RAB yang sama
$query_check = "SELECT * FROM detail_pengajuan WHERE id_rab_upah = '$id_rab_upah' AND id_detail_pengajuan = '$id_detail_pengajuan'";
$result_check = mysqli_query($koneksi, $query_check);

if (mysqli_num_rows($result_check) > 0) {
    // Pengajuan sudah ada, lakukan update
    $query_update = "UPDATE detail_pengajuan
                     SET progress_pekerjaan = '$progress_pekerjaan', nilai_upah_diajukan = '$nilai_upah_diajukan'
                     WHERE id_rab_upah = '$id_rab_upah' AND id_detail_pengajuan = '$id_detail_pengajuan'";
    
    if (mysqli_query($koneksi, $query_update)) {
        echo "Pengajuan berhasil diupdate.";
    } else {
        echo "Terjadi kesalahan saat mengupdate pengajuan: " . mysqli_error($koneksi);
    }
} else {
    // Pengajuan belum ada, lakukan insert
    $query_insert = "INSERT INTO detail_pengajuan (id_rab_upah, id_detail_pengajuan, progress_pekerjaan, nilai_upah_diajukan)
                     VALUES ('$id_rab_upah', '$id_detail_pengajuan', '$progress_pekerjaan', '$nilai_upah_diajukan')";
    
    if (mysqli_query($koneksi, $query_insert)) {
        echo "Pengajuan berhasil ditambahkan.";
    } else {
        echo "Terjadi kesalahan saat menambahkan pengajuan: " . mysqli_error($koneksi);
    }
}
?>
