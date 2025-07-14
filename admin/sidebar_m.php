<?php
// Pastikan session sudah dimulai di halaman utama yang meng-include file ini
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ambil role dan nama file saat ini untuk menandai menu yang aktif
$user_role = strtolower($_SESSION['role'] ?? 'guest');
$current_page = basename($_SERVER['PHP_SELF']);

// Fungsi untuk mengecek apakah sebuah menu harus aktif
// Ini akan membuat menu yang sedang dibuka menjadi berwarna biru
function is_active($pages, $current_page) {
    if (in_array($current_page, $pages)) {
        return 'active';
    }
    return '';
}
?>
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo"> 
        <div class="logo-header" data-background-color="dark">
            <a href="dashboard.php" class="logo">
                <img src="assets/img/logo/LOGO PT.jpg" alt="Logo PT" class="navbar-brand" height="30" />
            </a>
            <div class="nav-toggle">

                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
    </div>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">

                <!-- ======================================================= -->
                <!-- 1. Menu Dashboard (Bisa dilihat semua role)             -->
                <!-- ======================================================= -->
                <li class="nav-item <?= is_active(['dashboard.php', 'dashboard_direk_m.php', 'dashboard_admin_m.php', 'dashboard_divtek_m.php', 'dashboard_pj_m.php'], $current_page) ?>">
                    <a href="dashboard.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- ======================================================= -->
                <!-- 2. GRUP MENU TRANSAKSI                                  -->
                <!-- ======================================================= -->
                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">Transaksi</h4>
                </li>
                
                <!-- Sub-menu Rancang RAB (Hanya untuk Divisi Teknik & Admin) -->
                <?php if (in_array($user_role, ['divisi teknik', 'admin', 'super admin', 'direktur'])): ?>
                <li class="nav-item <?= is_active(['transaksi_rab_material.php', 'detail_rab_material.php', 'input_detail_rab_material.php'], $current_page) ?>">
                    <a href="transaksi_rab_material.php">
                        <i class="fas fa-clipboard-list"></i>
                        <p>Rancang Anggaran </p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array($user_role, [ 'admin', 'direktur', 'super admin'])): ?>
                <li class="nav-item <?= is_active(['pencatatan_pembelian.php', 'detail_pembelian.php', 'input_detail_pembelian.php'], $current_page) ?>">
                    <a href="pencatatan_pembelian.php">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <p>Pencatatan Pembelian</p>
                    </a>
                </li>
                <?php endif; ?>                                
                <?php if (in_array($user_role, [ 'pj proyek', 'super admin'])): ?>
                <li class="nav-item <?= is_active(['penerimaan_material.php'], $current_page) ?>">
                    <a href="penerimaan_material.php">
                        <i class="fas fa-box-open"></i>
                        <p>Penerimaan Gudang</p>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array($user_role, [ 'pj proyek', 'super admin'])): ?>
                <li class="nav-item <?= is_active(['distribusi_material.php','detail_distribusi.php', 'input_detail_distribusi.php' ], $current_page) ?>">
                    <a href="distribusi_material.php">
                        <i class="fas fa-truck"></i>
                        <p>Distribusi ke Proyek</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php 
                // Tampilkan seluruh blok Laporan HANYA untuk role yang diizinkan
                if (in_array($user_role, ['direktur', 'admin', 'super admin', 'pj proyek'])): 
                ?>
                    <li class="nav-section">
                        <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                        <h4 class="text-section">Laporan</h4>
                    </li>
                    
                    <?php if (in_array($user_role, ['direktur', 'admin', 'super admin'])): ?>
                    <li class="nav-item <?= is_active(['laporan_pembelian.php'], $current_page) ?>">
                        <a href="laporan_pembelian.php">
                            <i class="fas fa-book"></i>
                            <p>Laporan Pembelian</p>
                        </a>
                    </li>
                    <li class="nav-item <?= is_active(['laporan_distribusi.php'], $current_page) ?>">
                        <a href="laporan_distribusi.php">
                            <i class="fas fa-book"></i>
                            <p>Laporan Distribusi</p>
                        </a>
                    </li>
                    <li class="nav-item <?= is_active(['lap_realisasi_anggaran_m.php'], $current_page) ?>">
                        <a href="lap_realisasi_anggaran_m.php">
                            <i class="fas fa-chart-line"></i>
                            <p>RAB VS Realisasi</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (in_array($user_role, ['direktur', 'admin', 'pj proyek','super admin'])): ?>
                    <li class="nav-item <?= is_active(['kartu_stok.php'], $current_page) ?>">
                        <a href="kartu_stok.php"><i class="fas fa-clipboard-check"></i><p>Kartu Stok</p></a>
                    </li>
                    <?php endif; ?>

                <?php endif; // Akhir dari blok Laporan ?>

<!-- 4. GRUP MENU MASTER DATA -->
    <li class="nav-section">
        <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
        <h4 class="text-section">Master Data</h4>
    </li>
    
    <!-- Master Data Umum (Hanya Admin & Direktur) -->
    <?php if (in_array($user_role, ['admin', 'direktur'])): ?>
        <li class="nav-item <?= is_active(['master_perumahan.php'], $current_page) ?>">
            <a href="master_perumahan.php"><i class="fas fa-map-marked-alt"></i><p>Master Perumahan</p></a>
        </li>
        <li class="nav-item <?= is_active(['master_mandor.php'], $current_page) ?>">
            <a href="master_mandor.php"><i class="fas fa-user"></i><p>Master Mandor</p></a>
        </li>
        <li class="nav-item <?= is_active(['master_user.php'], $current_page) ?>">
            <a href="master_user.php"><i class="fas fa-users-cog"></i><p>Master User</p></a>
        </li>
        
    <?php endif; ?>

    <!-- Master Material (Hanya Admin & Direktur, Pj) -->
    <?php if (in_array($user_role, ['admin', 'direktur','pj proyek'])): ?>
        <li class="nav-item <?= is_active(['master_material.php'], $current_page) ?>">
            <a href="master_material.php"><i class="fas fa-boxes"></i><p>Master Material</p></a>
        </li>
    <?php endif; ?>

    
    <!-- Master Data Teknis (Bisa diakses oleh Admin, Divisi Teknik, dan Direktur) -->
    <?php if (in_array($user_role, ['admin', 'direktur', 'divisi teknik'])): ?>
    <li class="nav-item <?= is_active(['master_kategori.php'], $current_page) ?>">
        <a href="master_kategori.php"><i class="fas fa-tags"></i><p>Master Kategori</p></a>
    </li>
    <li class="nav-item <?= is_active(['master_pekerjaan.php'], $current_page) ?>">
        <a href="master_pekerjaan.php"><i class="fas fa-briefcase"></i><p>Master Pekerjaan</p></a>
    </li>
    <li class="nav-item <?= is_active(['master_satuan.php'], $current_page) ?>">
        <a href="master_satuan.php"><i class="fas fa-ruler-combined"></i><p>Master Satuan</p></a>
    </li>
            <li class="nav-item <?= is_active(['master_proyek.php'], $current_page) ?>">
            <a href="master_proyek.php"><i class="fas fa-building"></i><p>Master Proyek</p></a>
        </li>
<?php endif; ?>
</ul>

        </div>
    </div>
</div>