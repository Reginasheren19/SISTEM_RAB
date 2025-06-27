<?php
session_start();
include("../config/koneksi_mysql.php");

// --- [DIUBAH TOTAL] --- Query diperbaiki dengan Subquery agar 100% akurat dan anti-salah hitung
$sql = "
    SELECT 
        p.id_pembelian,
        p.tanggal_pembelian,
        p.keterangan_pembelian,
        p.bukti_pembayaran,
        p.total_biaya,
        COALESCE(pesanan.total_dipesan, 0) AS total_dipesan,
        COALESCE(diproses.total_diproses, 0) AS total_diproses
    FROM 
        pencatatan_pembelian p
    -- Subquery (1): Menghitung total kuantitas yang dipesan (asli + pengganti)
    LEFT JOIN (
        SELECT id_pembelian, SUM(quantity) as total_dipesan
        FROM detail_pencatatan_pembelian
        GROUP BY id_pembelian
    ) AS pesanan ON p.id_pembelian = pesanan.id_pembelian
    -- Subquery (2): Menghitung total item yang sudah diproses (diterima baik + rusak)
    LEFT JOIN (
        SELECT id_pembelian, SUM(jumlah_diterima + jumlah_rusak) as total_diproses
        FROM log_penerimaan_material
        GROUP BY id_pembelian
    ) AS diproses ON p.id_pembelian = diproses.id_pembelian
    ORDER BY 
        p.tanggal_pembelian DESC, p.id_pembelian DESC
";

$result = mysqli_query($koneksi, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Pencatatan Pembelian</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="assets/img/logo/LOGO PT.jpg"
      type="image/x-icon"
    />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular",
            "Font Awesome 5 Brands",
            "simple-line-icons",
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

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="assets/css/demo.css" />
  </head>
  <body>
    <div class="wrapper">
      <!-- Sidebar -->
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="dark">
            <a href="" class="logo">
              <img
                src="assets/img/logo/LOGO PT.jpg"
                alt="Logo PT"
                class="navbar-brand"
                height="20"
              />
            </a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar">
                <i class="gg-menu-right"></i>
              </button>
              <button class="btn btn-toggle sidenav-toggler">
                <i class="gg-menu-left"></i>
              </button>
            </div>
            <button class="topbar-toggler more">
              <i class="gg-more-vertical-alt"></i>
            </button>
          </div>
          <!-- End Logo Header -->
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item active">
                <a
                  data-bs-toggle="collapse"
                  href="#dashboard"
                  class="collapsed"
                  aria-expanded="false"
                >
                  <i class="fas fa-home"></i>
                  <p>Dashboard</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="dashboard">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="../demo1/index.html">
                        <span class="sub-item">Dashboard 1</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Components</h4>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#base">
                  <i class="fas fa-layer-group"></i>
                  <p>Base</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="base">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="components/avatars.html">
                        <span class="sub-item">Avatars</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/buttons.html">
                        <span class="sub-item">Buttons</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/gridsystem.html">
                        <span class="sub-item">Grid System</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/panels.html">
                        <span class="sub-item">Panels</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/notifications.html">
                        <span class="sub-item">Notifications</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/sweetalert.html">
                        <span class="sub-item">Sweet Alert</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/font-awesome-icons.html">
                        <span class="sub-item">Font Awesome Icons</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/simple-line-icons.html">
                        <span class="sub-item">Simple Line Icons</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/typography.html">
                        <span class="sub-item">Typography</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#sidebarLayouts">
                  <i class="fas fa-th-list"></i>
                  <p>Rancang RAB</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="sidebarLayouts">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="transaksi_rab_upah.php">
                        <span class="sub-item">RAB Upah</span>
                      </a>
                    </li>
                    <li>
                      <a href="transaksi_rab_material.php">
                        <span class="sub-item">RAB Material</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a href="pengajuan_upah.php">
                  <i class="fas fa-pen-square"></i>
                  <p>Pengajuah Upah</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="pencatatan_pembelian.php">
                  <i class="fas fa-pen-square"></i>
                  <p>Pencatatan Pembelian</p>
                </a>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#forms">
                  <i class="fas fa-pen-square"></i>
                  <p>Forms</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="forms">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="forms/forms.html">
                        <span class="sub-item">Basic Form</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#tables">
                  <i class="fas fa-table"></i>
                  <p>Tables</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="tables">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="tables/tables.html">
                        <span class="sub-item">Basic Table</span>
                      </a>
                    </li>
                    <li>
                      <a href="tables/datatables.html">
                        <span class="sub-item">Datatables</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#maps">
                  <i class="fas fa-map-marker-alt"></i>
                  <p>Maps</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="maps">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="maps/googlemaps.html">
                        <span class="sub-item">Google Maps</span>
                      </a>
                    </li>
                    <li>
                      <a href="maps/jsvectormap.html">
                        <span class="sub-item">Jsvectormap</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#charts">
                  <i class="far fa-chart-bar"></i>
                  <p>Charts</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="charts">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="charts/charts.html">
                        <span class="sub-item">Chart Js</span>
                      </a>
                    </li>
                    <li>
                      <a href="charts/sparkline.html">
                        <span class="sub-item">Sparkline</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a href="widgets.html">
                  <i class="fas fa-desktop"></i>
                  <p>Widgets</p>
                  <span class="badge badge-success">4</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="../../documentation/index.html">
                  <i class="fas fa-file"></i>
                  <p>Documentation</p>
                  <span class="badge badge-secondary">1</span>
                </a>
              </li>

              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#submenu">
                  <i class="fas fa-bars"></i>
                  <p>Mastering</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="submenu">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="master_perumahan.php">
                        <span class="sub-item">Master Perumahan</span>
                      </a>
                    </li>
                    <li>
                      <a href="master_proyek.php">
                        <span class="sub-item">Master Proyek</span>
                      </a>
                    </li>
                    <li>
                      <a href="master_mandor.php">
                        <span class="sub-item">Master Mandor</span>
                      </a>
                    </li>
                    <li>
                      <a href="master_kategori.php">
                        <span class="sub-item">Master Kategori</span>
                      </a>
                    </li>
                    <li>
                      <a href="master_satuan.php">
                        <span class="sub-item">Master Satuan</span>
                      </a>
                    </li>
                    <li>
                      <a href="master_pekerjaan.php">
                        <span class="sub-item">Master Pekerjaan</span>
                      </a>
                    </li>
                    <li>
                      <a href="master_material.php">
                        <span class="sub-item">Master Material</span>
                      </a>
                    </li>
                    <li>
                      <a href="master_user.php">
                        <span class="sub-item">Master User</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>

            </ul>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->

      <div class="main-panel">
        <div class="main-header">
          <div class="main-header-logo">
            <!-- Logo Header -->
            <div class="logo-header" data-background-color="dark">
              <a href="index.html" class="logo">
                <img
                  src="assets/img/logo/LOGO PT.jpg"
                  alt="Logo PT"
                  class="navbar-brand"
                  height="20"
              />
              </a>
              <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                  <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                  <i class="gg-menu-left"></i>
                </button>
              </div>
              <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
              </button>
            </div>
            <!-- End Logo Header -->
          </div>
          <!-- Navbar Header -->
          <nav
            class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom"
          >
            <div class="container-fluid">
              <nav
                class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex"
              >
                <div class="input-group">
                  <div class="input-group-prepend">
                    <button type="submit" class="btn btn-search pe-1">
                      <i class="fa fa-search search-icon"></i>
                    </button>
                  </div>
                  <input
                    type="text"
                    placeholder="Search ..."
                    class="form-control"
                  />
                </div>
              </nav>

              <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <li
                  class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none"
                >
                  <a
                    class="nav-link dropdown-toggle"
                    data-bs-toggle="dropdown"
                    href="#"
                    role="button"
                    aria-expanded="false"
                    aria-haspopup="true"
                  >
                    <i class="fa fa-search"></i>
                  </a>
                  <ul class="dropdown-menu dropdown-search animated fadeIn">
                    <form class="navbar-left navbar-form nav-search">
                      <div class="input-group">
                        <input
                          type="text"
                          placeholder="Search ..."
                          class="form-control"
                        />
                      </div>
                    </form>
                  </ul>
                </li>
                <li class="nav-item topbar-icon dropdown hidden-caret">
                  <a
                    class="nav-link dropdown-toggle"
                    href="#"
                    id="messageDropdown"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i class="fa fa-envelope"></i>
                  </a>
                  <ul
                    class="dropdown-menu messages-notif-box animated fadeIn"
                    aria-labelledby="messageDropdown"
                  >
                    <li>
                      <div
                        class="dropdown-title d-flex justify-content-between align-items-center"
                      >
                        Messages
                        <a href="#" class="small">Mark all as read</a>
                      </div>
                    </li>
                    <li>
                      <div class="message-notif-scroll scrollbar-outer">
                        <div class="notif-center">
                          <a href="#">
                            <div class="notif-img">
                              <img
                                src="assets/img/jm_denis.jpg"
                                alt="Img Profile"
                              />
                            </div>
                            <div class="notif-content">
                              <span class="subject">Jimmy Denis</span>
                              <span class="block"> How are you ? </span>
                              <span class="time">5 minutes ago</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-img">
                              <img
                                src="assets/img/chadengle.jpg"
                                alt="Img Profile"
                              />
                            </div>
                            <div class="notif-content">
                              <span class="subject">Chad</span>
                              <span class="block"> Ok, Thanks ! </span>
                              <span class="time">12 minutes ago</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-img">
                              <img
                                src="assets/img/mlane.jpg"
                                alt="Img Profile"
                              />
                            </div>
                            <div class="notif-content">
                              <span class="subject">Jhon Doe</span>
                              <span class="block">
                                Ready for the meeting today...
                              </span>
                              <span class="time">12 minutes ago</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-img">
                              <img
                                src="assets/img/talha.jpg"
                                alt="Img Profile"
                              />
                            </div>
                            <div class="notif-content">
                              <span class="subject">Talha</span>
                              <span class="block"> Hi, Apa Kabar ? </span>
                              <span class="time">17 minutes ago</span>
                            </div>
                          </a>
                        </div>
                      </div>
                    </li>
                    <li>
                      <a class="see-all" href="javascript:void(0);"
                        >See all messages<i class="fa fa-angle-right"></i>
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="nav-item topbar-icon dropdown hidden-caret">
                  <a
                    class="nav-link dropdown-toggle"
                    href="#"
                    id="notifDropdown"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i class="fa fa-bell"></i>
                    <span class="notification">4</span>
                  </a>
                  <ul
                    class="dropdown-menu notif-box animated fadeIn"
                    aria-labelledby="notifDropdown"
                  >
                    <li>
                      <div class="dropdown-title">
                        You have 4 new notification
                      </div>
                    </li>
                    <li>
                      <div class="notif-scroll scrollbar-outer">
                        <div class="notif-center">
                          <a href="#">
                            <div class="notif-icon notif-primary">
                              <i class="fa fa-user-plus"></i>
                            </div>
                            <div class="notif-content">
                              <span class="block"> New user registered </span>
                              <span class="time">5 minutes ago</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-icon notif-success">
                              <i class="fa fa-comment"></i>
                            </div>
                            <div class="notif-content">
                              <span class="block">
                                Rahmad commented on Admin
                              </span>
                              <span class="time">12 minutes ago</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-img">
                              <img
                                src="assets/img/profile2.jpg"
                                alt="Img Profile"
                              />
                            </div>
                            <div class="notif-content">
                              <span class="block">
                                Reza send messages to you
                              </span>
                              <span class="time">12 minutes ago</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-icon notif-danger">
                              <i class="fa fa-heart"></i>
                            </div>
                            <div class="notif-content">
                              <span class="block"> Farrah liked Admin </span>
                              <span class="time">17 minutes ago</span>
                            </div>
                          </a>
                        </div>
                      </div>
                    </li>
                    <li>
                      <a class="see-all" href="javascript:void(0);"
                        >See all notifications<i class="fa fa-angle-right"></i>
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="nav-item topbar-icon dropdown hidden-caret">
                  <a
                    class="nav-link"
                    data-bs-toggle="dropdown"
                    href="#"
                    aria-expanded="false"
                  >
                    <i class="fas fa-layer-group"></i>
                  </a>
                  <div class="dropdown-menu quick-actions animated fadeIn">
                    <div class="quick-actions-header">
                      <span class="title mb-1">Quick Actions</span>
                      <span class="subtitle op-7">Shortcuts</span>
                    </div>
                    <div class="quick-actions-scroll scrollbar-outer">
                      <div class="quick-actions-items">
                        <div class="row m-0">
                          <a class="col-6 col-md-4 p-0" href="#">
                            <div class="quick-actions-item">
                              <div class="avatar-item bg-danger rounded-circle">
                                <i class="far fa-calendar-alt"></i>
                              </div>
                              <span class="text">Calendar</span>
                            </div>
                          </a>
                          <a class="col-6 col-md-4 p-0" href="#">
                            <div class="quick-actions-item">
                              <div
                                class="avatar-item bg-warning rounded-circle"
                              >
                                <i class="fas fa-map"></i>
                              </div>
                              <span class="text">Maps</span>
                            </div>
                          </a>
                          <a class="col-6 col-md-4 p-0" href="#">
                            <div class="quick-actions-item">
                              <div class="avatar-item bg-info rounded-circle">
                                <i class="fas fa-file-excel"></i>
                              </div>
                              <span class="text">Reports</span>
                            </div>
                          </a>
                          <a class="col-6 col-md-4 p-0" href="#">
                            <div class="quick-actions-item">
                              <div
                                class="avatar-item bg-success rounded-circle"
                              >
                                <i class="fas fa-envelope"></i>
                              </div>
                              <span class="text">Emails</span>
                            </div>
                          </a>
                          <a class="col-6 col-md-4 p-0" href="#">
                            <div class="quick-actions-item">
                              <div
                                class="avatar-item bg-primary rounded-circle"
                              >
                                <i class="fas fa-file-invoice-dollar"></i>
                              </div>
                              <span class="text">Invoice</span>
                            </div>
                          </a>
                          <a class="col-6 col-md-4 p-0" href="#">
                            <div class="quick-actions-item">
                              <div
                                class="avatar-item bg-secondary rounded-circle"
                              >
                                <i class="fas fa-credit-card"></i>
                              </div>
                              <span class="text">Payments</span>
                            </div>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </li>

                <li class="nav-item topbar-user dropdown hidden-caret">
                  <a
                    class="dropdown-toggle profile-pic"
                    data-bs-toggle="dropdown"
                    href="#"
                    aria-expanded="false"
                  >
                    <div class="avatar-sm">
                      <img
                        src="assets/img/profile.jpg"
                        alt="..."
                        class="avatar-img rounded-circle"
                      />
                    </div>
                    <span class="profile-username">
                      <span class="op-7">Hi,</span>
                      <span class="fw-bold">Hizrian</span>
                    </span>
                  </a>
                  <ul class="dropdown-menu dropdown-user animated fadeIn">
                    <div class="dropdown-user-scroll scrollbar-outer">
                      <li>
                        <div class="user-box">
                          <div class="avatar-lg">
                            <img
                              src="assets/img/profile.jpg"
                              alt="image profile"
                              class="avatar-img rounded"
                            />
                          </div>
                          <div class="u-text">
                            <h4>Hizrian</h4>
                            <p class="text-muted">hello@example.com</p>
                            <a
                              href="profile.html"
                              class="btn btn-xs btn-secondary btn-sm"
                              >View Profile</a
                            >
                          </div>
                        </div>
                      </li>
                      <li>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">My Profile</a>
                        <a class="dropdown-item" href="#">My Balance</a>
                        <a class="dropdown-item" href="#">Inbox</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">Account Setting</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">Logout</a>
                      </li>
                    </div>
                  </ul>
                </li>
              </ul>
            </div>
          </nav>
          <!-- End Navbar -->
        </div>

        
    <div class="container">
        <div class="page-inner">
            <div class="page-header">
                <h3 class="fw-bold mb-3">Pencatatan Pembelian</h3>
                <ul class="breadcrumbs mb-3">
                    <li class="nav-home">
                        <a href="dashboard.php">
                            <i class="icon-home"></i>
                        </a>
                    </li>
                    <li class="separator">
                        <i class="icon-arrow-right"></i>
                    </li>
                    <li class="nav-item">
                        <a href="#">Pencatatan Pembelian</a>
                    </li>
                </ul>
            </div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h4 class="card-title">Daftar Transaksi Pembelian</h4>
                <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addPembelianModal">
                    <i class="fa fa-plus"></i> Tambah Pembelian
                </button>
            </div>
            <div class="card-body">
                <?php /* ... Blok Notifikasi ... */ ?>
                <div class="table-responsive">
                    <table id="tabelPembelian" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>ID Pembelian</th>
                                <th>Tanggal Pembelian</th>
                                <th>Keterangan</th>
                                <th>Total Biaya</th>
                                <th>Bukti Pembelian</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (isset($result) && $result && mysqli_num_rows($result) > 0): 
                                $nomor = 1; 
                                while ($row = mysqli_fetch_assoc($result)): 
                                    $tahun_pembelian = date('Y', strtotime($row['tanggal_pembelian']));
                                    $formatted_id = 'PB' . $row['id_pembelian'] . $tahun_pembelian;
                                    
                                    // --- [DISEMPURNAKAN] --- Logika Status Final yang Sesuai Permintaan
                                    $total_dipesan = $row['total_dipesan'];
                                    $total_diproses = $row['total_diproses'];
                                    
                                    $status_text = 'Baru';
                                    $badge_class = 'bg-secondary';

                                    if ($total_dipesan == 0) {
                                        $status_text = 'Kosong';
                                        $badge_class = 'bg-dark';
                                    } elseif ($total_diproses >= $total_dipesan) {
                                        // Jika semua sudah diproses (baik+rusak) >= total pesanan (asli+pengganti)
                                        // Maka statusnya final: Selesai. Tidak ada lagi "(Ada Retur)".
                                        $status_text = 'Selesai';
                                        $badge_class = 'bg-success';
                                    } elseif ($total_diproses > 0 && $total_diproses < $total_dipesan) {
                                        $status_text = 'Diterima Sebagian';
                                        $badge_class = 'bg-info text-dark';
                                    } else { // ($total_diproses <= 0 && $total_dipesan > 0)
                                        $status_text = 'Menunggu Penerimaan';
                                        $badge_class = 'bg-warning text-dark';
                                    }
                            ?>
                                    <tr>
                                        <td><?= $nomor++ ?></td>
                                        <td><?= htmlspecialchars($formatted_id) ?></td>
                                        <td><?= date("d F Y", strtotime($row['tanggal_pembelian'])) ?></td>
                                        <td><?= htmlspecialchars($row['keterangan_pembelian']) ?></td>
                                        <td><?= 'Rp ' . number_format($row['total_biaya'] ?? 0, 0, ',', '.') ?></td>
                                        <td>
                                            <?php if (!empty($row['bukti_pembayaran'])): ?>
                                                <a href="../uploads/bukti_pembayaran/<?= htmlspecialchars($row['bukti_pembayaran']) ?>" target="_blank">Lihat Bukti</a>
                                            <?php else: ?>
                                                <span>-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($status_text) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="detail_pembelian.php?id=<?= urlencode($row['id_pembelian']) ?>" class="btn btn-info btn-sm" title="Lihat Detail">Detail</a>
                                        </td>
                                    </tr>
                            <?php 
                                endwhile; 
                            else:
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center">Belum ada data pembelian.</td>
                                </tr>
                            <?php 
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" id="addPembelianModal" tabindex="-1" aria-labelledby="addPembelianModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="add_pembelian.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPembelianModalLabel">Tambah Pembelian Material</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tanggal_pembelian" class="form-label">Tanggal Pembelian</label>
                            <input type="date" class="form-control" id="tanggal_pembelian" name="tanggal_pembelian" required>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan_pembelian" class="form-label">Keterangan</label>
                            <input type="text" class="form-control" id="keterangan_pembelian" name="keterangan_pembelian" required>
                        </div>
                        <div class="mb-3">
                            <label for="bukti_pembayaran" class="form-label">Upload Nota Pembelian</label>
                            <input type="file" class="form-control" id="bukti_pembayaran" name="bukti_pembayaran" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Lanjut</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Apakah Anda yakin ingin menghapus data pembelian ini?</p>
            <p class="text-danger small">Semua detail material yang terkait juga akan ikut terhapus.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <a href="#" id="confirmDeleteLink" class="btn btn-danger">Hapus</a>
          </div>
        </div>
      </div>
    </div>

    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

    <script>
    $(document).ready(function() {

        // Inisialisasi DataTable tanpa properti 'order'.
        // Ini akan menjaga urutan asli dari HTML (yang sudah diurutkan dari server/PHP).
        $('#tabelPembelian').DataTable();

        // Bagian untuk modal hapus
        $('#tabelPembelian').on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const deleteUrl = `delete_pembelian.php?id=${id}`;
            $('#confirmDeleteLink').attr('href', deleteUrl);
        });

        // Bagian notifikasi otomatis hilang
        const alertBox = $('.alert');
        if (alertBox.length) {
            setTimeout(function() {
                alertBox.fadeOut('slow');
            }, 5000);
        }
        
    });
    </script>
</body>
</html>