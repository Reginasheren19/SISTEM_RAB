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
        header("Location: dashboard_admin.php");
        break;
    
    case 'direktur':
        header("Location: dashboard_direktur.php");
        break;
        
    case 'pj proyek':
        // Pastikan nama file ini sesuai dengan yang Anda gunakan
        header("Location: dashboard_pjproyek.php"); 
        break;
        
    case 'divisi teknik':
        header("Location: dashboard_teknik.php");
        break;
        
    // Anda bisa menambahkan case untuk role lain jika ada di masa depan
    // case 'sekretaris':
    //     header("Location: dashboard_sekretaris.php");
    //     break;
        
    default:
        // Sebagai fallback jika role tidak dikenali, arahkan ke halaman login
        // dengan pesan error untuk keamanan.
        header("Location: ../index.php?pesan=role_tidak_dikenali");
        break;
}

// Penting untuk menghentikan eksekusi script setelah redirect
exit();
?>
