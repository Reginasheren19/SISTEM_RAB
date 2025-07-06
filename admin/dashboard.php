<?php
session_start();

// Periksa apakah pengguna sudah login dan memiliki role
if (!isset($_SESSION['login_status']) || !isset($_SESSION['role'])) {
    // Jika tidak, tendang kembali ke halaman login
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

// Ambil role pengguna dari session dan ubah ke huruf kecil untuk konsistensi
$user_role = strtolower($_SESSION['role']);

// Gunakan switch case untuk mengarahkan ke dashboard yang sesuai
switch ($user_role) {
    case 'admin':
        //header("Location: dashboard_admin.php");
        header("Location: dashboard_admin_m.php");

        break;
    
    case 'direktur':
        //header("Location: dashboard_direktur.php");
        header("Location: dashboard_direk_m.php");
        break;
        
    case 'pj proyek':
        // Pastikan nama file ini sesuai dengan yang Anda gunakan
        header("Location: dashboard_pjproyek.php"); 
        // header("Location: dashboard_pj_m.php"); 
        break;
        
    case 'divisi teknik':
        // header("Location: dashboard_teknik.php");
        header("Location: dashboard_divtek_m.php");
        break;
        
        
    default:
        // Sebagai fallback jika role tidak dikenali, arahkan ke halaman login
        // dengan pesan error untuk keamanan.
        header("Location: ../index.php?pesan=role_tidak_dikenali");
        break;
}

// Penting untuk menghentikan eksekusi script setelah redirect
exit();
?>
