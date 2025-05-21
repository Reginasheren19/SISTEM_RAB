<?php
session_start();
include 'config/koneksi_mysql.php'; // sambungkan koneksi database

// Cek jika form signup disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form signup (sesuaikan name input jika diubah)
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        $error = "Semua field wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        // Cek email sudah terdaftar atau belum
        $stmt = $koneksi->prepare("SELECT id_users FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert data user baru, default role = 'Admin' misalnya
            $role = 'Admin';
            $created_at = date('Y-m-d H:i:s');

            $stmt = $koneksi->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $password_hash, $role, $created_at);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Registrasi berhasil, silakan login.";
                header("Location: index.php");
                exit;
            } else {
                $error = "Terjadi kesalahan saat menyimpan data.";
            }
        }
        $stmt->close();
    }
}
?>
