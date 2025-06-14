<?php
session_start();
include("config/koneksi_mysql.php");

// Jika koneksi gagal, hentikan script
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Cek jika form tidak disubmit dengan benar
if (!isset($_POST['username'], $_POST['password'])) {
    header("Location: index.php?pesan=form_tidak_lengkap");
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

// Menggunakan nama tabel 'master_user' sesuai kode Anda
$sql = "SELECT id_user, nama_lengkap, username, password, role, profile_pic FROM master_user WHERE username = ?";
$stmt = $koneksi->prepare($sql);

if (!$stmt) {
    die("Prepare statement gagal: " . $koneksi->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Cek apakah user dengan username tersebut ditemukan
if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();

    // Verifikasi password yang diinput dengan hash di database
    if (password_verify($password, $data['password'])) {
        // Jika password benar, buat session baru yang aman
        session_regenerate_id(true);
        
        // Simpan data ke session
        $_SESSION['id_user'] = $data['id_user'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['profile_pic'] = $data['profile_pic'];

        // [PERBAIKAN UTAMA] Sesuaikan semua 'case' menjadi huruf kecil
        switch (strtolower($data['role'])) {
            case 'super admin': // diubah menjadi huruf kecil
                header("Location: admin/dashboard.php");
                break;
            case 'admin':
                header("Location: admin/dashboard_admin.php");
                break;
            case 'direktur':
                header("Location: admin/dashboard_direktur.php");
                break;
            case 'pj proyek': // diubah menjadi huruf kecil
                header("Location: admin/dashboard_pjproyek.php");
                break;
            case 'divisi teknik': // diubah menjadi huruf kecil
                header("Location: admin/dashboard_teknik.php");
                break;
            // Tambahkan case untuk role lain jika ada (pastikan huruf kecil)
            case 'sekretaris':
                header("Location: admin/dashboard_sekretaris.php");
                break;
            case 'bendahara':
                 header("Location: admin/dashboard_bendahara.php");
                 break;
            default:
                // Jika role tidak terdaftar dalam logika redirect
                header("Location: index.php?pesan=role_tidak_dikenali");
        }
        exit; // Hentikan script setelah redirect

    } else {
        // Jika password salah
        $_SESSION['error_message'] = "Username atau password salah.";
        header("Location: index.php");
        exit;
    }
} else {
    // Jika username tidak ditemukan
    $_SESSION['error_message'] = "Username atau password salah.";
    header("Location: index.php");
    exit;
}

$stmt->close();
$koneksi->close();
?>
