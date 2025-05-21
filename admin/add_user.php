<?php
include("../config/koneksi_mysql.php");

// Proses saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
      // Debugging: Periksa apakah data POST diterima
    echo '<pre>';
    print_r($_POST);
    print_r($_FILES);  // Memeriksa file yang di-upload
    echo '</pre>';

    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];  // Password dari form (belum di-hash)
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Cek jika ada foto profil yang di-upload
    $profilePic = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        // Foto profil di-upload
        $profilePic = "uploads/" . basename($_FILES['profile_pic']['name']);
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $profilePic); // Simpan foto ke folder "uploads"
    }

    // Query untuk menyimpan data ke database
    $sql = "INSERT INTO users (username, email, password, role, profile_pic) 
            VALUES ('$username', '$email', '$hashed_password', '$role', '$profilePic')";

    if (mysqli_query($koneksi, $sql)) {
        echo "<script>window.location.href='master_user.php?msg=Data%20berhasil%20ditambahkan';</script>";

    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
    echo '<pre>';
print_r($_FILES['profile_pic']);
echo '</pre>';

}
?>
