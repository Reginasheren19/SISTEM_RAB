<?php
session_start();
include("config/koneksi_mysql.php");

// Jika koneksi gagal, hentikan script
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Cek jika form tidak disubmit dengan benar
if (empty($_POST['username']) || empty($_POST['password'])) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Username dan password harus diisi.'];
    header("Location: index.php");
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

// Menggunakan prepared statement untuk keamanan
$sql = "SELECT id_user, nama_lengkap, username, password, role, profile_pic FROM master_user WHERE username = ?";
$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    die("Prepare statement gagal: " . $koneksi->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();

    // Verifikasi password
    if (password_verify($password, $data['password'])) {
        // Jika berhasil, buat session baru
        session_regenerate_id(true);
        
        $_SESSION['id_user'] = $data['id_user'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['profile_pic'] = $data['profile_pic'];
        $_SESSION['login_status'] = true; // Tandai sudah login

        // Arahkan berdasarkan role
        switch (strtolower($data['role'])) {
            case 'admin':
                header("Location: admin/dashboard_admin.php");
                // header("Location: admin/dashboard_admin_m.php");                
                break;
            case 'direktur':
                header("Location: admin/dashboard_direktur.php");
                // header("Location: admin/dashboard_direk_m.php");        
                break;
            case 'pj proyek':
                header("Location: admin/dashboard_pjproyek.php");
                // header("Location: admin/dashboard_pj_m.php");
                break;
            case 'divisi teknik':
                // header("Location: admin/dashboard_teknik.php");
                header("Location: admin/dashboard_divtek_m.php");
                break;
            default:
                // Jika role tidak dikenal, arahkan ke dashboard umum
                header("Location: admin/dashboard.php"); 
        }
        exit;

    } else {
        // Jika password salah
// Jika password salah
$_SESSION['error_message'] = "Username atau password salah.";
header("Location: index.php");
exit;
    }
} else {
    // Jika username tidak ditemukan
    $_SESSION['error_message'] = 'Username tidak terdaftar. Silakan periksa kembali.'; // Pesan baru yang spesifik
    header("Location: index.php");
    exit;
}

$stmt->close();
$koneksi->close();
?>
