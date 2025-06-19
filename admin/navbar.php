<!-- ============================================== -->
<!--      KODE INI ADALAH ISI DARI templates/navbar.php      -->
<!-- ============================================== -->
<div class="main-panel">
    <div class="main-header">
        <div class="main-header-logo">
            <div class="logo-header" data-background-color="dark">
                <a href="dashboard.php" class="logo">
                    <img src="assets/img/logo/LOGO PT.jpg" alt="Logo PT" class="navbar-brand" height="30" />
                </a>
                <div class="nav-toggle">
                    <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                    <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                </div>
                <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
            </div>
        </div>
        <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
            <div class="container-fluid">
                <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                    <li class="nav-item topbar-user dropdown hidden-caret">
                        <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                            <div class="avatar-sm"><img src="../uploads/user_photos/<?php echo htmlspecialchars($profile_pic); ?>" alt="Foto Profil" class="avatar-img rounded-circle" onerror="this.onerror=null; this.src='assets/img/profile.jpg';"></div>
                            <span class="profile-username"><span class="op-7">Hi,</span> <span class="fw-bold"><?php echo htmlspecialchars($nama_user); ?></span></span>
                        </a>
                        <ul class="dropdown-menu dropdown-user animated fadeIn">
                            <div class="dropdown-user-scroll scrollbar-outer">
                                <li>
                                    <div class="user-box">
                                        <div class="avatar-lg"><img src="../uploads/user_photos/<?php echo htmlspecialchars($profile_pic); ?>" alt="Foto Profil" class="avatar-img rounded" onerror="this.onerror=null; this.src='assets/img/profile.jpg';"></div>
                                        <div class="u-text">
                                            <h4><?php echo htmlspecialchars($nama_user); ?></h4>
                                            <p class="text-muted"><?php echo htmlspecialchars($username); ?></p>
                                            <a href="profile.php" class="btn btn-xs btn-secondary btn-sm">Lihat Profil</a>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="profile.php">Pengaturan Akun</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="../logout.php">Logout</a>
                                </li>
                            </div>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
    <!-- End Navbar -->
