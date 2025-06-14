<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Login | Sistem RAB HBN</title>
    
    <link
      rel="icon"
      href="admin/assets/img/logo/LOGO PT.jpg"
      type="image/x-icon"
    />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
    <!-- Fontawesome CDN Link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
    /* Google Font */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Poppins", sans-serif;
    }
    body {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        /* LATAR BELAKANG HALAMAN - Diubah dari beige ke abu-abu muda yang bersih */
        background: #F0F2F5;
        padding: 30px;
    }
    .container {
        position: relative;
        max-width: 850px;
        width: 100%;
        background: #fff;
        padding: 40px 30px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); /* Shadow dibuat lebih halus */
        perspective: 2700px;
        border-radius: 8px; /* Tambahan: sudut sedikit melengkung agar lebih modern */
    }
    .container .cover {
        position: absolute;
        top: 0;
        left: 50%;
        height: 100%;
        width: 50%;
        z-index: 98;
        transition: all 1s ease;
        transform-origin: left;
        transform-style: preserve-3d;
        backface-visibility: hidden;
    }
    .container #flip:checked ~ .cover {
        transform: rotateY(-180deg);
    }
    .container #flip:checked ~ .forms .login-form {
        pointer-events: none;
    }
    .container .cover .front,
    .container .cover .back {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
    }
    .cover .back {
        transform: rotateY(180deg);
    }
    .container .cover img {
        position: absolute;
        height: 100%;
        width: 100%;
        object-fit: cover;
        z-index: 10;
        border-radius: 0 8px 8px 0; /* Menyesuaikan dengan lengkungan container */
    }
    .container .cover .text {
        position: absolute;
        z-index: 10;
        height: 100%;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    /* OVERLAY GAMBAR - Diubah menjadi gelap agar selaras dengan sidebar */
    .container .cover .text::before {
        content: '';
        position: absolute;
        height: 100%;
        width: 100%;
        opacity: 0.6;
        background: #1A2035; /* Warna biru sangat tua dari sidebarmu */
        border-radius: 0 8px 8px 0;
    }
    .cover .text .text-1,
    .cover .text .text-2 {
        z-index: 20;
        font-size: 26px;
        font-weight: 600;
        color: #fff;
        text-align: center;
    }
    .cover .text .text-2 {
        font-size: 15px;
        font-weight: 500;
    }
    .container .forms {
        height: 100%;
        width: 100%;
        background: #fff;
    }
    .container .form-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .form-content .login-form,
    .form-content .register-form {
        width: calc(100% / 2 - 25px);
    }
    .forms .form-content .title {
        position: relative;
        font-size: 24px;
        font-weight: 500;
        color: #333;
    }
    /* WARNA AKSEN UTAMA - Diubah menjadi biru keunguan */
    .forms .form-content .title:before {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3px;
        width: 25px;
        background: #4C51BF; /* Warna biru keunguan */
    }
    .forms .register-form .title:before {
        width: 20px;
    }
    .forms .form-content .input-boxes {
        margin-top: 30px;
    }
    .forms .form-content .input-box {
        display: flex;
        align-items: center;
        height: 50px;
        width: 100%;
        margin: 10px 0;
        position: relative;
    }
    .form-content .input-box input {
        height: 100%;
        width: 100%;
        outline: none;
        border: none;
        padding: 0 30px;
        font-size: 16px;
        font-weight: 500;
        border-bottom: 2px solid rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }
    /* WARNA AKSEN UTAMA - Border saat input aktif */
    .form-content .input-box input:focus,
    .form-content .input-box input:valid {
        border-color: #4C51BF; /* Warna biru keunguan */
    }
    /* WARNA AKSEN UTAMA - Ikon di dalam input */
    .form-content .input-box i {
        position: absolute;
        color: #4C51BF; /* Warna biru keunguan */
        font-size: 17px;
    }
    .forms .form-content .text {
        font-size: 14px;
        font-weight: 500;
        color: #333;
    }
    .forms .form-content .text a {
        color: #333; 
        text-decoration: none;
    }
    .forms .form-content .text a:hover {
        text-decoration: underline;
    }
    .forms .form-content .button {
        color: #fff;
        margin-top: 40px;
    }
    /* WARNA AKSEN UTAMA - Tombol utama */
    .forms .form-content .button input {
        color: #fff;
        background: #4C51BF; /* Warna biru keunguan */
        border-radius: 6px;
        padding: 0;
        cursor: pointer;
        transition: all 0.4s ease;
    }
    /* WARNA AKSEN UTAMA - Efek hover pada tombol */
    .forms .form-content .button input:hover {
        background: #4347A5; /* Warna biru yang sedikit lebih gelap */
    }
    .forms .form-content label {
        color: #4C51BF; /* Warna biru keunguan */
        cursor: pointer;
    }
    .forms .form-content label:hover {
        text-decoration: underline;
    }
    .forms .form-content .login-text,
    .forms .form-content .sign-up-text {
        text-align: center;
        margin-top: 25px;
    }
    .container #flip {
        display: none;
    }
    @media (max-width: 730px) {
        .container .cover {
            display: none;
        }
        .form-content .login-form,
        .form-content .register-form {
            width: 100%;
        }
        .form-content .register-form {
            display: none;
        }
        .container #flip:checked ~ .forms .register-form {
            display: block;
        }
        .container #flip:checked ~ .forms .login-form {
            display: none;
        }
    }
</style>
  </head>
  <body>
    <div class="container">
      <input type="checkbox" id="flip">
      <div class="cover">
        <div class="front">
          <img src="admin/assets/img/login.jpeg" alt="">
          <div class="text">
            <span class="text-1">Hasta Bangun Nusantara</span>
            <span class="text-2">Solusi Konstruksi untuk Indonesia Maju</span>
          </div>
        </div>
        <div class="back">
          <img src="admin/assets/img/register.jpeg" alt="">
          <div class="text">
            <span class="text-1">Hasta Bangun Nusantara</span>
            <span class="text-2">Solusi Konstruksi untuk Indonesia Maju</span>
          </div>
        </div>
      </div>
      <div class="forms">
        <div class="form-content">

          <div class="login-form">
            <div class="title">Masuk</div>
            <form action="login.php" method="post">
              <div class="input-boxes">
                <div class="input-box">
                  <i class="fas fa-envelope"></i>
                  <input type="text" name="username" placeholder="Masukkan username" required>
                </div>
                  <div class="input-box" style="position: relative;">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                    <i class="far fa-eye" id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color:rgb(187, 160, 101);"></i>
                  </div>
                <div class="text"><a href="register.php">Lupa password?</a></div>
                <div class="button input-box">
                  <input type="submit" value="Kirim">
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

      <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        // Set ikon awal sesuai tipe password
        function setIcon() {
          if (password.type === 'password') {
            togglePassword.classList.add('fa-eye-slash');
            togglePassword.classList.remove('fa-eye');
          } else {
            togglePassword.classList.add('fa-eye');
            togglePassword.classList.remove('fa-eye-slash');
          }
        }

        // Inisialisasi ikon saat halaman pertama load
        setIcon();

        togglePassword.addEventListener('click', function () {
          const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
          password.setAttribute('type', type);

          setIcon();
        });
      </script>

  </body>
</html>
