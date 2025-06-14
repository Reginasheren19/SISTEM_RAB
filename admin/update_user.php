<?php
session_start();
include("../config/koneksi_mysql.php");

// Pastikan request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Ambil semua data dari form update
    // Validasi sederhana untuk ID User
    if (!isset($_POST['id_user']) || !is_numeric($_POST['id_user'])) {
        $_SESSION['error_message'] = "ID User tidak valid.";
        header("Location: master_user.php");
        exit();
    }

    $id_user = (int)$_POST['id_user'];
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    $password_baru = $_POST['password']; // Bisa kosong

    // 2. Ambil data LAMA dari database untuk perbandingan
    $stmt_old = $koneksi->prepare("SELECT password, profile_pic FROM master_user WHERE id_user = ?");
    $stmt_old->bind_param("i", $id_user);
    $stmt_old->execute();
    $result_old = $stmt_old->get_result();
    $user_lama = $result_old->fetch_assoc();
    $stmt_old->close();
    
    if (!$user_lama) {
        $_SESSION['error_message'] = "User tidak ditemukan.";
        header("Location: master_user.php");
        exit();
    }

    // 3. Logika untuk Password: Cek apakah user mengisi password baru
    if (!empty($password_baru)) {
        // Jika ya, hash password baru
        $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
    } else {
        // Jika tidak, gunakan password lama yang sudah ada di database
        $hashed_password = $user_lama['password'];
    }

    // 4. Logika untuk Foto Profil: Cek apakah ada file foto baru yang diunggah
    $nama_file_foto = $user_lama['profile_pic']; // Defaultnya, pakai foto lama

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        
        // Jika ada file baru, lakukan validasi dan proses upload seperti di add_user.php
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $nama_asli_file = $_FILES['profile_pic']['name'];
        $ukuran_file = $_FILES['profile_pic']['size'];
        
        $upload_dir = "../uploads/user_photos/";
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($nama_asli_file, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_types) || $ukuran_file > 2097152) {
            $_SESSION['error_message'] = "Upload gagal. Pastikan file adalah gambar (jpg/png/gif) dan ukuran maks 2MB.";
            header("Location: master_user.php");
            exit();
        }

        // Hapus foto lama jika ada (dan bukan foto default)
        if (!empty($user_lama['profile_pic']) && file_exists($upload_dir . $user_lama['profile_pic']) && $user_lama['profile_pic'] != 'default.jpg') {
            unlink($upload_dir . $user_lama['profile_pic']);
        }
        
        // Buat nama file baru yang unik
        $nama_file_baru = "user_" . time() . "_" . uniqid() . "." . $file_ext;
        $tujuan_upload = $upload_dir . $nama_file_baru;

        if (move_uploaded_file($file_tmp, $tujuan_upload)) {
            $nama_file_foto = $nama_file_baru; // Ganti nama file foto dengan yang baru
        } else {
             $_SESSION['error_message'] = "Gagal memindahkan file yang diunggah.";
             header("Location: master_user.php");
             exit();
        }
    }

    // 5. Siapkan query UPDATE dengan PREPARED STATEMENT
    $sql = "UPDATE master_user SET 
                nama_lengkap = ?, 
                username = ?, 
                password = ?, 
                role = ?, 
                profile_pic = ? 
            WHERE id_user = ?";
    
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        // 6. Bind semua parameter dan eksekusi
        $stmt->bind_param("sssssi", 
            $nama_lengkap, 
            $username, 
            $hashed_password, 
            $role, 
            $nama_file_foto,
            $id_user
        );

        if ($stmt->execute()) {
            // Redirect ke halaman master_user dengan pesan sukses
            $_SESSION['pesan_sukses'] = "Data user berhasil di-update.";
            header("Location: master_user.php?msg=Data%20berhasil%20diupdate");
            exit();
        } else {
            $_SESSION['error_message'] = "Gagal meng-update user: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Gagal menyiapkan query update: " . $koneksi->error;
    }

} else {
    $_SESSION['error_message'] = "Akses tidak sah.";
}

// Redirect kembali ke halaman master user jika tidak ada POST
$koneksi->close();
header("Location: master_user.php");
exit();
?>
