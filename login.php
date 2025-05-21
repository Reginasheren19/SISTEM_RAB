<?php
session_start();
include("config/koneksi_mysql.php");

// Cek koneksi
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Debug cek data post (boleh dihapus setelah berfungsi)
if (!isset($_POST['username'], $_POST['password'])) {
    header("Location: index.php?pesan=gagal");
    exit;
}

$username = $_POST['username'];
$password = $_POST['password'];

$stmt = $koneksi->prepare("SELECT id_users, username, password, role FROM users WHERE username = ?");
if (!$stmt) {
    die("Prepare failed: " . $koneksi->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();

    if (password_verify($password, $data['password'])) {
        $_SESSION['id_users'] = $data['id_users'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];

        switch ($data['role']) {
            case 'Admin':
                header("Location: dashboard.php");
                break;
            case 'Direktur':
                header("Location: direktur/dashboard_direktur.php");
                break;
            case 'PJ Proyek':
                header("Location: pj_proyek/dashboard_pjproyek.php");
                break;
            case 'Divisi Teknik':
                header("Location: divisi_teknik/dashboard_teknik.php");
                break;
            default:
                header("Location: index.php?pesan=role_tidak_terdaftar");
        }
        exit;
    } else {
        // Password salah
        header("Location: index.php?pesan=gagal");
        exit;
    }
} else {
    // Username tidak ditemukan
    header("Location: index.php?pesan=gagal");
    exit;
}
?>
