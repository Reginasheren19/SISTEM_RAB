<!-- ============================================== -->
<!--      KODE INI ADALAH ISI DARI sidebar.php     -->
<!-- ============================================== -->
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
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
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <li class="nav-item">
                    <a href="dashboard.php"><i class="fas fa-home"></i><p>Dashboard</p></a>
                </li>

                <?php if (can_access(['Super Admin', 'Admin', 'PJ Proyek', 'Direktur'], $user_role)): ?>
                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">Transaksi RAB Upah</h4>
                </li>
                <li class="nav-item"><a href="transaksi_rab_upah.php"><i class="fas fa-pen-square"></i><p>Rancang RAB Upah</p></a></li>
                <li class="nav-item"><a href="pengajuan_upah.php"><i class="fas fa-pen-square"></i><p>Pengajuan Upah</p></a></li>
                <?php endif; ?>

                <?php if (can_access(['Super Admin', 'Admin', 'PJ Proyek', 'Direktur', 'Divisi Teknik'], $user_role)): ?>
                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">Transaksi RAB Material</h4>
                </li>
                <li class="nav-item"><a href="transaksi_rab_material.php"><i class="fas fa-pen-square"></i><p>Rancang RAB Material</p></a></li>
                <li class="nav-item"><a href="pencatatan_pembelian.php"><i class="fas fa-pen-square"></i><p>Pencatatan Pembelian</p></a></li>
                <li class="nav-item"><a href="distribusi_material.php"><i class="fas fa-truck"></i><p>Distribusi Material</p></a></li>
                <?php endif; ?>

                <?php if (can_access(['Super Admin', 'Direktur'], $user_role)): ?>
                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">Laporan</h4>
                </li>
                <li class="nav-item"><a href="#"><i class="fas fa-file"></i><p>Laporan RAB Upah</p></a></li>
                <?php endif; ?>

                <?php if (can_access(['Super Admin', 'Admin', 'Divisi Teknik'], $user_role)): ?>
                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">Mastering Data</h4>
                </li>
                <li class="nav-item"><a href="master_perumahan.php"><i class="fas fa-database"></i><p>Master Perumahan</p></a></li>
                <li class="nav-item"><a href="master_proyek.php"><i class="fas fa-database"></i><p>Master Proyek</p></a></li>
                <li class="nav-item"><a href="master_mandor.php"><i class="fas fa-database"></i><p>Master Mandor</p></a></li>
                <li class="nav-item"><a href="master_kategori.php"><i class="fas fa-database"></i><p>Master Kategori</p></a></li>
                <li class="nav-item"><a href="master_satuan.php"><i class="fas fa-database"></i><p>Master Satuan</p></a></li>
                <li class="nav-item"><a href="master_pekerjaan.php"><i class="fas fa-database"></i><p>Master Pekerjaan</p></a></li>
                <li class="nav-item"><a href="master_material.php"><i class="fas fa-database"></i><p>Master Material</p></a></li>
                    <?php if (can_access(['Super Admin'], $user_role)): ?>
                    <li class="nav-item">
                        <a href="master_user.php"><i class="fas fa-database"></i><p>Master User</p></a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
<!-- End Sidebar -->