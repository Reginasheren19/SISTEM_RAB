<?php
// FILE: master_proyek.php (Dengan logika dropdown yang diperbaiki)
include("../config/koneksi_mysql.php");

// Mengatur error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch data for Nama Perumahan
$perumahanResult = mysqli_query($koneksi, "SELECT id_perumahan, nama_perumahan, lokasi FROM master_perumahan ORDER BY nama_perumahan ASC");
$perumahan_options = [];
if($perumahanResult) {
    while ($row = mysqli_fetch_assoc($perumahanResult)) {
        $perumahan_options[] = $row;
    }
}

// Fetch data for Nama Mandor
$mandorResult = mysqli_query($koneksi, "SELECT id_mandor, nama_mandor FROM master_mandor ORDER BY nama_mandor ASC");
$mandor_options = [];
if($mandorResult) {
    while ($row = mysqli_fetch_assoc($mandorResult)) {
        $mandor_options[] = $row;
    }
}


// Ambil data PJ Proyek
$pj_proyek_options = [];
$pjProyekResult = mysqli_query($koneksi, "SELECT id_user, nama_lengkap FROM master_user WHERE role = 'PJ Proyek' ORDER BY nama_lengkap ASC");
if($pjProyekResult){
    while ($row = mysqli_fetch_assoc($pjProyekResult)) {
        $pj_proyek_options[] = $row;
    }
}

// --- Query utama untuk menampilkan tabel ---
$sql = "SELECT 
            mpr.id_proyek, mpr.kavling, mpr.type_proyek,
            mpr.id_perumahan, mpe.nama_perumahan, mpe.lokasi,
            mpr.id_mandor, mm.nama_mandor,
            mpr.id_user_pj, u.nama_lengkap AS nama_pj_proyek
        FROM master_proyek mpr
        LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
        LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user";
$result = mysqli_query($koneksi, $sql);
if (!$result) {
    die("Query gagal dijalankan: " . mysqli_error($koneksi));
}
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Kaiadmin - Bootstrap 5 Admin Dashboard</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="assets/img/kaiadmin/favicon.ico"
      type="image/x-icon"
    />
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" />

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
            <a href="index.html" class="logo">
              <img
                src="assets/img/kaiadmin/logo_light.svg"
                alt="navbar brand"
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
                  <p>Sidebar Layouts</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="sidebarLayouts">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="sidebar-style-2.html">
                        <span class="sub-item">Sidebar Style 2</span>
                      </a>
                    </li>
                    <li>
                      <a href="icon-menu.html">
                        <span class="sub-item">Icon Menu</span>
                      </a>
                    </li>
                  </ul>
                </div>
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
              <a href="../index.html" class="logo">
                <img
                  src="assets/img/kaiadmin/logo_light.svg"
                  alt="navbar brand"
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
              <h3 class="fw-bold mb-3">Mastering</h3>
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
                  <a href="#">Mastering</a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="#">Master Proyek</a>
                </li>
              </ul>
            </div>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex align-items-center">
        <h4 class="card-title">Master Proyek</h4>
        <button
          class="btn btn-primary btn-round ms-auto"
          data-bs-toggle="modal"
          data-bs-target="#addProyekModal"
        >
          <i class="fa fa-plus"></i> Tambah Data
        </button>
      </div>

            <?php if (isset($_GET['msg'])): ?>
        <div class="mb-3">
          <div class="alert alert-success fade show" role="alert">
            <?= htmlspecialchars($_GET['msg']) ?>
          </div>
        </div>
      <?php endif; ?>

      <script>
      window.setTimeout(function() {
        const alert = document.querySelector('.alert');
        if (alert) {
          alert.classList.add('fade');
          alert.classList.remove('show');
          setTimeout(() => alert.remove(), 350);
        }
      }, 3000);

        // Hapus parameter 'msg' dari URL agar tidak muncul lagi saat reload
      if (window.history.replaceState) {
        const url = new URL(window.location);
        if (url.searchParams.has('msg')) {
          url.searchParams.delete('msg');
          window.history.replaceState({}, document.title, url.pathname);
        }
      }
      </script>

<div class="card-body">
    <div class="table-responsive">
        <table id="basic-datatables" class="display table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID Proyek</th>
                    <th>Perumahan</th>
                    <th>Kavling</th>
                    <th>Type Proyek</th>
                    <th>Mandor</th>
                    <th>PJ Proyek</th> <!-- Menambahkan kolom PJ Proyek -->
                    <th>Lokasi</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id_proyek']) ?></td>
                        <td><?= htmlspecialchars($row['nama_perumahan']) ?></td>
                        <td><?= htmlspecialchars($row['kavling']) ?></td>
                        <td><?= htmlspecialchars($row['type_proyek']) ?></td>
                        <td><?= htmlspecialchars($row['nama_mandor']) ?></td>
                        <td><?= htmlspecialchars($row['nama_pj_proyek'] ?? 'N/A') ?></td> <!-- Menampilkan PJ Proyek -->
                        <td><?= htmlspecialchars($row['lokasi']) ?></td>
                        <td>
                            <!-- Update Button -->
                            <button class="btn btn-warning btn-sm btn-update" 
                                    data-id_proyek='<?= $row['id_proyek'] ?>' 
                                    data-id_perumahan='<?= $row['id_perumahan'] ?>' 
                                    data-kavling='<?= $row['kavling'] ?>' 
                                    data-type_proyek='<?= $row['type_proyek'] ?>' 
                                    data-id_mandor='<?= $row['id_mandor'] ?>'
                                    data-id_user_pj='<?= $row['id_user_pj'] ?>'
                                    data-bs-toggle="modal" 
                                    data-bs-target="#updateProyekModal">
                                <i class="fa fa-edit"></i> Update
                            </button>

                            <!-- Delete Button -->
                            <button class="btn btn-danger btn-sm btn-delete" 
                                    data-id_proyek='<?= $row['id_proyek'] ?>'
                                    data-bs-toggle="modal" 
                                    data-bs-target="#confirmDeleteModal">
                                <i class="fa fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

    </div>
  </div>
</div>

<!-- Modal Tambah Data Pekerjaan -->
<div class="modal fade" id="addProyekModal" tabindex="-1" aria-labelledby="addProyekModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="add_proyek.php">
        <input type="hidden" name="action" value="add" />
        <div class="modal-header">
          <h5 class="modal-title" id="addProyekModalLabel">Tambah Data Proyek</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

<!-- Dropdown for Nama Perumahan -->
<div class="mb-3">
    <label for="id_perumahan" class="form-label">Nama Perumahan</label>
    <select class="form-select" id="id_perumahan" name="id_perumahan" required>
        <option value="" disabled selected>Pilih Nama Perumahan</option>
        <?php foreach ($perumahan_options as $perumahan): ?>
            <option value="<?= htmlspecialchars($perumahan['id_perumahan']) ?>" data-lokasi="<?= htmlspecialchars($perumahan['lokasi']) ?>">
                <?= htmlspecialchars($perumahan['nama_perumahan']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

          <div class="mb-3">
            <label for="kavling" class="form-label">Kavling</label>
            <input type="text" class="form-control" id="kavling" name="kavling" placeholder="Masukkan kavling" required />
          </div>

          <div class="mb-3">
            <label for="type_proyek" class="form-label">Type Proyek</label>
            <input type="text" class="form-control" id="type_proyek" name="type_proyek" placeholder="Masukkan type proyek" required />
          </div>

<!-- Dropdown for Nama Mandor -->
<div class="mb-3">
    <label for="id_mandor" class="form-label">Nama Mandor</label>
    <select class="form-select" id="id_mandor" name="id_mandor" required>
        <option value="" disabled selected>Pilih Nama Mandor</option>
        <?php foreach ($mandor_options as $mandor): ?>
            <option value="<?= htmlspecialchars($mandor['id_mandor']) ?>">
                <?= htmlspecialchars($mandor['nama_mandor']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


        <!-- [DITAMBAHKAN] Dropdown untuk memilih PJ Proyek -->
        <div class="mb-3">
            <label class="form-label">PJ Proyek</label>
            <select class="form-select" name="id_user_pj" required>
                <option value="">Pilih PJ Proyek</option>
                <?php foreach ($pj_proyek_options as $opt): ?>
                    <option value="<?= $opt['id_user'] ?>"><?= htmlspecialchars($opt['nama_lengkap']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

          <!-- Lokasi (read-only) -->
          <div class="mb-3">
            <label for="lokasi" class="form-label">Lokasi</label>
            <input type="text" class="form-control" id="lokasi" name="lokasi" readonly placeholder="Lokasi akan muncul otomatis" />
          </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
</div>

<!-- Modal Update Data Proyek -->
<div class="modal fade" id="updateProyekModal" tabindex="-1" aria-labelledby="updateProyekModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="update_proyek.php">
                <input type="hidden" name="id_proyek" id="update_id_proyek" />
                <div class="modal-header">
                    <h5 class="modal-title" id="updateProyekModalLabel">Update Data Proyek</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                     <div class="mb-3">
                            <label class="form-label">Nama Perumahan</label>
                            <select class="form-select" name="id_perumahan" id="update_id_perumahan" required>
                                <?php foreach ($perumahan_options as $opt): ?><option value="<?= $opt['id_perumahan'] ?>" data-lokasi="<?= htmlspecialchars($opt['lokasi']) ?>"><?= htmlspecialchars($opt['nama_perumahan']) ?></option><?php endforeach; ?>
                            </select>
                        </div>

          <div class="mb-3">
            <label for="update_kavling" class="form-label">Kavling</label>
            <input type="text" class="form-control" id="update_kavling" name="kavling" value="<?= htmlspecialchars($row['kavling']) ?>" placeholder="Ubah kavling" required />
          </div>

          <div class="mb-3">
            <label for="update_type_proyek" class="form-label">Type Proyek</label>
            <input type="text" class="form-control" id="update_type_proyek" name="type_proyek" value="<?= htmlspecialchars($row['type_proyek']) ?>" placeholder="Ubah type proyek" required />
          </div>

          <div class="mb-3">
              <label class="form-label">Mandor</label>
              <select class="form-select" name="id_mandor" id="update_id_mandor" required>
                  <?php foreach ($mandor_options as $opt): ?><option value="<?= $opt['id_mandor'] ?>"><?= htmlspecialchars($opt['nama_mandor']) ?></option><?php endforeach; ?>
              </select>
          </div>
          <div class="mb-3">
              <label class="form-label">PJ Proyek</label>
              <select class="form-select" name="id_user_pj" id="update_id_user_pj" required>
                  <?php foreach ($pj_proyek_options as $opt): ?><option value="<?= $opt['id_user'] ?>"><?= htmlspecialchars($opt['nama_lengkap']) ?></option><?php endforeach; ?>
              </select>
          </div>

          <div class="mb-3">
            <label for="update_lokasi" class="form-label">Lokasi</label>
            <input type="text" class="form-control" id="update_lokasi" name="lokasi" readonly value="<?= htmlspecialchars($row['lokasi'] ?? '') ?>" />
          </div>

        </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
      </form>
    </div>
  </div>
</div>


  <!-- Modal Delete Confirmation -->
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this user?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <a href="master_proyek.php" id="confirmDeleteLink" class="btn btn-danger">Delete</a>
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
    // Handle dropdown change for "id_perumahan" to update location
    $('#id_perumahan').on('change', function() {
        const lokasi = $(this).find(':selected').data('lokasi') || ''; // Get location from selected option
        $('#lokasi').val(lokasi); // Set the location input field value
    });

    // Handle update button click event to populate the modal
    document.querySelectorAll('.btn-update').forEach(button => {
        button.addEventListener('click', function() {
            // Get data attributes and set modal inputs
            const idProyek = this.dataset.id_proyek;
            const idPerumahan = this.dataset.id_perumahan;
            const kavling = this.dataset.kavling;
            const typeProyek = this.dataset.type_proyek;
            const idMandor = this.dataset.id_mandor;
            const idUserPj = this.dataset.id_user_pj;

            // Fill the form fields inside the modal
            document.getElementById('update_id_proyek').value = idProyek;
            document.getElementById('update_id_perumahan').value = idPerumahan;
            document.getElementById('update_kavling').value = kavling;
            document.getElementById('update_type_proyek').value = typeProyek;
            document.getElementById('update_id_mandor').value = idMandor;

            // Update the location field based on selected "id_perumahan"
            const perumahanSelect = document.getElementById('update_id_perumahan');
            const selectedOption = perumahanSelect.querySelector(`option[value="${idPerumahan}"]`);
            const lokasi = selectedOption ? selectedOption.getAttribute('data-lokasi') : '';
            document.getElementById('update_lokasi').value = lokasi;

            // Show the update modal
            const updateModal = new bootstrap.Modal(document.getElementById('updateProyekModal'));
            updateModal.show();
        });
    });
});

</script>

    <script>
    $(document).ready(function() {
        $('#basic-datatables').DataTable();
        
        const updateModal = new bootstrap.Modal(document.getElementById('updateProyekModal'));
        
        function updateLokasi(selectElement, targetInput) {
            const selectedOption = $(selectElement).find('option:selected');
            const lokasi = selectedOption.data('lokasi') || '';
            $(targetInput).val(lokasi);
        }

        $('#add_id_perumahan').on('change', function() { updateLokasi(this, '#add_lokasi'); });
        $('#update_id_perumahan').on('change', function() { updateLokasi(this, '#update_lokasi'); });

        // Event delegation untuk tombol di dalam tabel
        $('#basic-datatables').on('click', '.btn-update', function() {
            // Ambil data dari atribut data-* tombol yang diklik
            $('#update_id_proyek').val($(this).data('id_proyek'));
            $('#update_id_perumahan').val($(this).data('id_perumahan'));
            $('#update_kavling').val($(this).data('kavling'));
            $('#update_type_proyek').val($(this).data('type_proyek'));
            $('#update_id_mandor').val($(this).data('id_mandor'));

            // [PERBAIKAN KUNCI] Cek jika data PJ Proyek ada
            const pjId = $(this).data('id_user_pj');
            if (pjId && pjId !== 0) {
                $('#update_id_user_pj').val(pjId);
            } else {
                // Jika tidak ada, set ke nilai kosong agar placeholder "-- Pilih PJ Proyek --" yang tampil
                $('#update_id_user_pj').val('');
            }
            
            updateLokasi('#update_id_perumahan', '#update_lokasi');
            updateModal.show();
        });

        // ... (logika delete bisa ditambahkan di sini) ...
    });
</script>

<script>
$(document).ready(function() {
    // Handle delete button click event to confirm deletion
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const proyekId = this.dataset.id_proyek;
            const deleteLink = document.getElementById('confirmDeleteLink');
            deleteLink.href = 'delete_proyek.php?proyek=' + proyekId; // Update the link with the correct project ID
            const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            deleteModal.show(); // Show the delete confirmation modal
        });
    });
});

</script>


</body>
</html>
