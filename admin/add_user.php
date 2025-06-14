<?php
session_start();
include("../config/koneksi_mysql.php");

// Pastikan request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Ambil semua data dari form, sesuaikan dengan database-mu
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password']; // Kita akan hash password ini
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);

    // Variabel untuk menyimpan nama file gambar, defaultnya NULL (kosong)
    $nama_file_foto = NULL;

    // 2. Proses Upload Foto Profil (Jika ada file yang diunggah)
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $nama_asli_file = $_FILES['profile_pic']['name'];
        $ukuran_file = $_FILES['profile_pic']['size'];
        
        // Tentukan folder tujuan upload
        $upload_dir = "../uploads/user_photos/";

        // Pastikan folder untuk menyimpan gambar sudah ada, jika belum buat
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Validasi tipe file (hanya izinkan gambar)
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($nama_asli_file, PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            $_SESSION['error_message'] = "Tipe file tidak diizinkan. Harap unggah file gambar (jpg, png, gif).";
            header("Location: master_user.php");
            exit();
        }

        // Validasi ukuran file (misalnya, maks 2MB)
        if ($ukuran_file > 2097152) { // 2 * 1024 * 1024 bytes
            $_SESSION['error_message'] = "Ukuran file terlalu besar. Maksimal 2MB.";
            header("Location: master_user.php");
            exit();
        }

        // Buat nama file yang unik untuk menghindari file tertimpa
        $nama_file_baru = "user_" . time() . "_" . uniqid() . "." . $file_ext;
        $tujuan_upload = $upload_dir . $nama_file_baru;

        // Pindahkan file dari temporary location ke folder tujuan
        if (move_uploaded_file($file_tmp, $tujuan_upload)) {
            $nama_file_foto = $nama_file_baru; // Jika berhasil, simpan nama file baru ini ke database
        } else {
            $_SESSION['error_message'] = "Gagal memindahkan file yang diunggah.";
            header("Location: master_user.php");
            exit();
        }
    }

    // 3. Hash password untuk keamanan
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 4. Siapkan & Eksekusi Query dengan PREPARED STATEMENT (Lebih Aman)
    // Query disesuaikan dengan struktur tabelmu: master_user
    $sql = "INSERT INTO master_user (nama_lengkap, username, password, role, profile_pic) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($stmt) {
        // Bind parameter ke query: s = string
        $stmt->bind_param("sssss", $nama_lengkap, $username, $hashed_password, $role, $nama_file_foto);

        if ($stmt->execute()) {
            $_SESSION['pesan_sukses'] = "User baru berhasil ditambahkan.";
        } else {
            $_SESSION['error_message'] = "Gagal menambahkan user: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Gagal menyiapkan query: " . $koneksi->error;
    }

} else {
    // Jika halaman diakses tanpa metode POST
    $_SESSION['error_message'] = "Akses tidak sah.";
}

// 5. Redirect kembali ke halaman master user
$koneksi->close();
header("Location: master_user.php");
exit();
?>
