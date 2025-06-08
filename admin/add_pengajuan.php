<?php
session_start();
include("../config/koneksi_mysql.php");

// Fungsi untuk mendapatkan progress terakhir dari sebuah item pekerjaan
function getProgressLaluPersen($koneksi, $id_detail_rab_upah) {
    $id_detail_rab_upah = (int)$id_detail_rab_upah;
    // [FIXED] Nama tabel diubah menjadi detail_pengajuan_upah
    $query = "SELECT MAX(progress_pekerjaan) AS progress_terakhir FROM detail_pengajuan_upah WHERE id_detail_rab_upah = ?";
    
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_detail_rab_upah);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        return (float)($data['progress_terakhir'] ?? 0);
    }
    return 0;
}

// Hanya proses jika metode request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Validasi dan Sanitasi Input Utama
    if (!isset($_POST['id_rab_upah']) || !isset($_POST['nominal_pengajuan_final']) || !isset($_POST['progress'])) {
        $_SESSION['error_message'] = "Data yang dikirim tidak lengkap.";
        header("Location: " . $_SERVER['HTTP_REFERER']); // Kembali ke halaman sebelumnya
        exit;
    }

    $id_rab_upah = (int)$_POST['id_rab_upah'];
    $total_pengajuan_final = (int)filter_var($_POST['nominal_pengajuan_final'], FILTER_SANITIZE_NUMBER_INT);
    $progress_array = $_POST['progress'];

    if ($total_pengajuan_final <= 0) {
        $_SESSION['error_message'] = "Nominal pengajuan final harus lebih besar dari 0.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    // Filter array progress untuk hanya memproses yang diisi (nilai > 0)
    $progress_to_process = array_filter($progress_array, function($value) {
        return is_numeric($value) && $value > 0;
    });

    if (empty($progress_to_process)) {
        $_SESSION['error_message'] = "Tidak ada progress pekerjaan yang diajukan.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // 2. Ambil semua sub_total yang relevan dalam satu query untuk efisiensi
    $sub_totals = [];
    $detail_ids = array_keys($progress_to_process);
    $placeholders = implode(',', array_fill(0, count($detail_ids), '?'));
    $types = str_repeat('i', count($detail_ids));

    $sql_subtotal = "SELECT id_detail_rab_upah, sub_total FROM detail_rab_upah WHERE id_detail_rab_upah IN ($placeholders)";
    $stmt_subtotal = mysqli_prepare($koneksi, $sql_subtotal);
    mysqli_stmt_bind_param($stmt_subtotal, $types, ...$detail_ids);
    mysqli_stmt_execute($stmt_subtotal);
    $result_subtotal = mysqli_stmt_get_result($stmt_subtotal);
    while ($row = mysqli_fetch_assoc($result_subtotal)) {
        $sub_totals[$row['id_detail_rab_upah']] = $row['sub_total'];
    }

    // 3. Mulai Transaksi Database
    mysqli_begin_transaction($koneksi);

    try {
        // 4. Insert data ke tabel master `pengajuan_upah`
        $tanggal_pengajuan = date("Y-m-d");
        $sql_pengajuan = "INSERT INTO pengajuan_upah (id_rab_upah, tanggal_pengajuan, total_pengajuan, nilai_progress, status_pengajuan) VALUES (?, ?, ?, ?, 'diajukan')";
        
        $stmt_pengajuan = mysqli_prepare($koneksi, $sql_pengajuan);
        if ($stmt_pengajuan === false) {
             throw new Exception("Gagal mempersiapkan query pengajuan utama: " . mysqli_error($koneksi));
        }
        
        mysqli_stmt_bind_param($stmt_pengajuan, "isii", $id_rab_upah, $tanggal_pengajuan, $total_pengajuan_final, $total_pengajuan_final);
        
        if (!mysqli_stmt_execute($stmt_pengajuan)) {
            throw new Exception("Gagal menyimpan data pengajuan utama.");
        }
        
        // Ambil ID dari pengajuan yang baru saja dibuat
        $id_pengajuan_upah = mysqli_insert_id($koneksi);

        // 5. Loop dan insert data ke tabel `detail_pengajuan_upah`
        // [FIXED] Nama tabel diubah menjadi detail_pengajuan_upah
        $sql_detail = "INSERT INTO detail_pengajuan_upah (id_pengajuan_upah, id_detail_rab_upah, progress_pekerjaan, nilai_upah_diajukan) VALUES (?, ?, ?, ?)";
        $stmt_detail = mysqli_prepare($koneksi, $sql_detail);
        if ($stmt_detail === false) {
             throw new Exception("Gagal mempersiapkan query detail pengajuan: " . mysqli_error($koneksi));
        }

        foreach ($progress_to_process as $id_detail_rab => $progress_diajukan) {
            $id_detail_rab = (int)$id_detail_rab;
            $progress_diajukan = (float)$progress_diajukan;
            
            // Dapatkan progress lalu dari database
            $progress_lalu = getProgressLaluPersen($koneksi, $id_detail_rab);
            
            // Hitung progress kumulatif (lalu + baru)
            $progress_kumulatif = $progress_lalu + $progress_diajukan;

            // Hitung nilai upah yang diajukan untuk item ini saja
            $sub_total_item = $sub_totals[$id_detail_rab] ?? 0;
            $nilai_upah_diajukan = round(($progress_diajukan / 100) * $sub_total_item);

            mysqli_stmt_bind_param($stmt_detail, "iidi", $id_pengajuan_upah, $id_detail_rab, $progress_kumulatif, $nilai_upah_diajukan);
            
            if (!mysqli_stmt_execute($stmt_detail)) {
                throw new Exception("Gagal menyimpan detail pengajuan untuk item ID: " . $id_detail_rab);
            }
        }

        // 6. Jika semua query berhasil, commit transaksi
        mysqli_commit($koneksi);
        
        $_SESSION['success_message'] = "Pengajuan progress berhasil dikirim.";
        header("Location: pengajuan_upah.php"); // Redirect ke halaman daftar pengajuan
        exit;

    } catch (Exception $e) {
        // 7. Jika terjadi kesalahan, rollback transaksi
        mysqli_rollback($koneksi);
        
        $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
        error_log("Gagal memproses pengajuan: " . $e->getMessage()); // Log error untuk admin
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

} else {
    // Jika halaman diakses tanpa metode POST
    header("Location: pengajuan_upah.php");
    exit;
}
?>
