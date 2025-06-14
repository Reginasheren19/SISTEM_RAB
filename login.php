<?php
session_start();
include("config/koneksi_mysql.php");

// Jika koneksi gagal, hentikan script
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Cek jika form tidak disubmit dengan benar
if (!isset($_POST['username'], $_POST['password'])) {
    // Redirect dengan pesan error yang lebih spesifik jika perlu
    header("Location: index.php?pesan=form_tidak_lengkap");
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

// FIX 1 & 2: Ganti nama tabel menjadi 'master_user' dan sesuaikan nama kolom
// Tambahkan juga nama_lengkap dan profile_pic agar bisa dipakai di dashboard
$sql = "SELECT id_user, nama_lengkap, username, password, role, profile_pic FROM master_user WHERE username = ?";
$stmt = $koneksi->prepare($sql);

if (!$stmt) {
    // Error jika query gagal disiapkan
    die("Prepare statement gagal: " . $koneksi->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Cek apakah user dengan username tersebut ditemukan (tepat 1 baris)
if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();

    // Verifikasi password yang diinput dengan hash di database
    if (password_verify($password, $data['password'])) {
        // Jika password benar, buat session baru yang aman
        session_regenerate_id(true);
        
        // FIX 2: Simpan data ke session dengan nama kolom yang benar
        $_SESSION['id_user'] = $data['id_user'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['profile_pic'] = $data['profile_pic']; // Simpan foto untuk ditampilkan nanti

        // FIX 3: Arahkan ke dashboard sesuai role (sesuaikan case dengan spasi)
        switch (strtolower($data['role'])) {
            case 'super admin':
                header("Location: admin/dashboard.php");
                break;

            case 'admin':
                header("Location: admin/dashboard_admin.php");
                break;
            case 'direktur':
                header("Location: direktur/dashboard_direktur.php");
                break;
            case 'pj proyek': // Menggunakan spasi
                header("Location: pj_proyek/dashboard_pjproyek.php");
                break;
            case 'divisi teknik': // Menggunakan spasi
                header("Location: divisi_teknik/dashboard_teknik.php");
                break;
            // Tambahkan case untuk role lain jika ada
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

// Tutup statement dan koneksi
$stmt->close();
$koneksi->close();
?>