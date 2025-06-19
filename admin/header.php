<?php
// SELALU MULAI SESSION DI BARIS PALING ATAS
session_start();

// =====================================================================
//      INI BAGIAN YANG DIPERBAIKI
// Kita cek apakah 'id_user' ada di session, bukan 'is_logged_in' lagi.
// =====================================================================
if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
    // Jika tidak ada id_user, berarti belum login. Tendang ke halaman login.
    header("Location: ../login.php"); 
    exit();
}

// Sertakan file koneksi. Gunakan `require_once` agar lebih aman.
require_once("../config/koneksi_mysql.php");

// Ambil data user dari session untuk ditampilkan dan untuk cek role
$user_role = $_SESSION['role'] ?? 'tamu';
$nama_user = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$username = $_SESSION['username'] ?? 'guest';
$profile_pic = $_SESSION['profile_pic'] ?? 'default.jpg';

// Definisi fungsi can_access()
if (!function_exists('can_access')) {
    function can_access($allowed_roles, $current_role) {
        if (!is_array($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }
        return in_array($current_role, $allowed_roles);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Aplikasi RAB</title> 
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/LOGO PT.jpg" type="image/x-icon"/>
    
    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons",
          ],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
</head>
<body>
<div class="wrapper">
<!-- Kode pembuka HTML -->
