<?php

include("../config/koneksi_mysql.php");

// Mengatur error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fungsi helper untuk menentukan kelas warna dropdown
function getStatusClass($status) {
    // Menggunakan strtolower untuk memastikan konsistensi
    switch (strtolower(trim($status))) {
        case 'disetujui':
            return 'bg-success text-white';
        case 'dibayar':
            return 'bg-primary text-white';
        case 'ditolak':
            return 'bg-danger text-white';
        case 'diajukan':
            return 'bg-warning text-dark';
        default:
            return 'bg-secondary text-white';
    }
}

// Mengambil data rab_upah yang bergabung dengan master_perumahan, master_proyek, dan master_mandor
$sql = "SELECT 
    pu.id_pengajuan_upah,
    pu.tanggal_pengajuan,
    pu.total_pengajuan,
    pu.status_pengajuan,
    pu.keterangan,
    ru.id_rab_upah,
    mpe.nama_perumahan,
    mpr.kavling,
    mm.nama_mandor  -- Pastikan nama_mandor ada di sini
FROM pengajuan_upah pu
JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah
JOIN master_perumahan mpe ON ru.id_perumahan = mpe.id_perumahan
JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
JOIN master_mandor mm ON ru.id_mandor = mm.id_mandor
";


$pengajuanresult = mysqli_query($koneksi, $sql);
if (!$pengajuanresult) {
    die("Query Error: " . mysqli_error($koneksi));
}

// Jika membutuhkan data master perumahan, proyek, atau mandor, bisa dipanggil secara terpisah (jika ada kebutuhan lebih lanjut)
$perumahanResult = mysqli_query($koneksi, "SELECT id_perumahan, nama_perumahan, lokasi FROM master_perumahan ORDER BY nama_perumahan ASC");
if (!$perumahanResult) {
    die("Query Error (perumahan): " . mysqli_error($koneksi));
}

$kavlingResult = mysqli_query($koneksi, "SELECT id_proyek, kavling, type_proyek FROM master_proyek ORDER BY type_proyek ASC");
if (!$kavlingResult) {
    die("Query Error (proyek): " . mysqli_error($koneksi));
}

$mandorResult = mysqli_query($koneksi, "SELECT id_mandor, nama_mandor FROM master_mandor ORDER BY nama_mandor ASC");
if (!$mandorResult) {
    die("Query Error (mandor): " . mysqli_error($koneksi));
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
        <style>
        /* [BARU] CSS untuk membuat dropdown berwarna terlihat lebih baik */
        .status-select {
            border-radius: 0.25rem; /* Menyamakan dengan badge */
            font-weight: 500;
            border: none;
            padding-right: 2rem; /* Memberi ruang untuk panah dropdown */
        }
        .status-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); /* Menambahkan outline saat fokus */
        }
        
        .modal-header.bg-success .modal-title,
        .modal-header.bg-danger .modal-title,
        .modal-header.bg-primary .modal-title,
        .modal-header.bg-info .modal-title,
        .modal-header.bg-secondary .modal-title,
        .modal-header.bg-warning .modal-title,
        .modal-header.bg-success .btn-close,
        .modal-header.bg-danger .btn-close,
        .modal-header.bg-primary .btn-close,
        .modal-header.bg-info .btn-close,
        .modal-header.bg-secondary .btn-close,
        .modal-header.bg-warning .btn-close {
            color: white;
        }
        .modal-header.bg-warning .modal-title,
        .modal-header.bg-warning .btn-close {
             color: black;
        }
    </style>

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
                  <p>Rancang RAB</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="sidebarLayouts">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="sidebar-style-2.html">
                        <span class="sub-item">RAB Upah</span>
                      </a>
                    </li>
                    <li>
                      <a href="icon-menu.html">
                        <span class="sub-item">RAB Material</span>
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
              <h3 class="fw-bold mb-3">Pengajuan Upah RAB</h3>
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
                  <a href="#">Pengajuan Upah RAB</a>
                </li>
              </ul>
            </div>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex align-items-center">
        <h4 class="card-title">Pengajuan Upah RAB</h4>
        <button
          class="btn btn-primary btn-round ms-auto"
          data-bs-toggle="modal"
          data-bs-target="#selectProyekModal"
        >
          <i class="fa fa-plus"></i> Buat Pengajuan Baru
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
          <th>ID Pengajuan</th>
          <th>Proyek</th>
          <th>Mandor</th>
          <th>Tanggal Pengajuan</th>
          <th>Total Pengajuan</th>
          <th>Status</th>
          <th>Keterangan</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($pengajuanresult)): ?>
          <?php
            // Format Tanggal dan Total Pengajuan
            $tanggalFormatted = date('d-m-Y', strtotime($row['tanggal_pengajuan']));
            $totalFormatted = number_format($row['total_pengajuan'], 0, ',', '.');

            // Tentukan kelas badge berdasarkan status pengajuan
            $statusPengajuan = $row['status_pengajuan'];
            switch ($statusPengajuan) {
              case 'diajukan':
                $badgeClass = 'badge-black';
                $statusLabel = 'Diajukan';
                break;
              case 'disetujui':
                $badgeClass = 'badge-primary';
                $statusLabel = 'Disetujui';
                break;
              case 'ditolak':
                $badgeClass = 'badge-danger';
                $statusLabel = 'Ditolak';
                break;
              case 'dibayar':
                $badgeClass = 'badge-success';
                $statusLabel = 'Dibayar';
                break;
            }
          ?>
          <tr>
            <td class="text-center"><?= htmlspecialchars($row['id_pengajuan_upah']) ?></td>
            <td><?= htmlspecialchars($row['nama_perumahan']) . ' - ' . htmlspecialchars($row['kavling']) ?></td> <!-- Display formatted Proyek -->
            <td><?= htmlspecialchars($row['nama_mandor']) ?></td>
            <td class="text-center"><?= $tanggalFormatted ?></td>
            <td class="text-center"><?= $totalFormatted ?></td>
            <td>
              <!-- [DIUBAH] Menambahkan class warna dari fungsi PHP -->
              <select class="form-select status-select <?= getStatusClass($row['status_pengajuan']) ?>" data-id="<?= htmlspecialchars($row['id_pengajuan_upah']) ?>">
                  <option value="diajukan" <?= ($row['status_pengajuan'] == 'diajukan') ? 'selected' : '' ?>>Diajukan</option>
                  <option value="disetujui" <?= ($row['status_pengajuan'] == 'disetujui') ? 'selected' : '' ?>>Disetujui</option>
                  <option value="ditolak" <?= ($row['status_pengajuan'] == 'ditolak') ? 'selected' : '' ?>>Ditolak</option>
                  <option value="dibayar" <?= ($row['status_pengajuan'] == 'dibayar') ? 'selected' : '' ?>>Dibayar</option>
              </select>
            </td>
            <td><?= htmlspecialchars($row['keterangan']) ?></td>
      <td>
        <!-- Tombol Detail -->
        <a href="get_pengajuan_upah.php?id_pengajuan_upah=<?= urlencode($row['id_pengajuan_upah']) ?>" class="btn btn-info btn-sm">Detail</a>

        <!-- Tombol Update hanya jika statusnya 'Diajukan' atau 'Ditolak' -->
        <?php if ($row['status_pengajuan'] == 'diajukan' || $row['status_pengajuan'] == 'ditolak'): ?>
          <a href="update_pengajuan_upah.php?id_pengajuan_upah=<?= urlencode($row['id_pengajuan_upah']) ?>" class="btn btn-warning btn-sm">Update</a>
        <?php endif; ?>

        <!-- Tombol Delete hanya muncul jika statusnya 'diajukan' atau 'ditolak' -->
        <?php if (in_array($row['status_pengajuan'], ['diajukan', 'ditolak'])): ?>
          <button class="btn btn-danger btn-sm delete-btn" data-id="<?= htmlspecialchars($row['id_pengajuan_upah']) ?>" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
            Delete
          </button>
        <?php endif; ?>
      </td>
    </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>


<!-- Modal for selecting Proyek RAB -->
<div class="modal fade" id="selectProyekModal" tabindex="-1" aria-labelledby="selectProyekModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="add_pengajuan_upah.php">
        <input type="hidden" name="action" value="add" />
        <div class="modal-header">
          <h5 class="modal-title" id="selectProyekModalLabel">Pilih Proyek RAB</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <!-- Table for RAB Projects -->
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>ID RAB</th>
                  <th>Nama Perumahan</th>
                  <th>Kavling</th>
                  <th>Mandor</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Query to get existing RAB projects
                $rabUpahResult = mysqli_query($koneksi, "SELECT 
                    ru.id_rab_upah, 
                    ru.id_proyek, 
                    mpe.nama_perumahan, 
                    mpr.kavling, 
                    mm.nama_mandor 
                  FROM rab_upah ru
                  JOIN master_perumahan mpe ON ru.id_perumahan = mpe.id_perumahan
                  JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
                  JOIN master_mandor mm ON ru.id_mandor = mm.id_mandor");

                // Format the ID
                $tahun = date('Y');
                $bulan = date('m');
                while ($rab = mysqli_fetch_assoc($rabUpahResult)) {
                  // Format the ID to match the 'RABPYYYYMMXXX' format
                $tahun_2digit = substr($tahun, -2);
                $id_proyek = $rab['id_proyek'];
                $id_rab_upah = $rab['id_rab_upah'];

                  $formatted_id = 'RABP' . $tahun_2digit . $bulan . $id_proyek . $id_rab_upah;
                ?>
                  <tr>
                    <td><?= htmlspecialchars($formatted_id) ?></td> <!-- Display formatted ID -->
                    <td><?= htmlspecialchars($rab['nama_perumahan']) ?></td>
                    <td><?= htmlspecialchars($rab['kavling']) ?></td>
                    <td><?= htmlspecialchars($rab['nama_mandor']) ?></td>
                    <td>
                      <!-- Button to select this project -->
                      <button type="button" class="btn btn-success btn-sm selectProyekBtn" data-id="<?= htmlspecialchars($rab['id_rab_upah']) ?>" data-nama_perumahan="<?= htmlspecialchars($rab['nama_perumahan']) ?>" data-kavling="<?= htmlspecialchars($rab['kavling']) ?>">
                        Pilih
                      </button>
                    </td>
                  </tr>
                <?php
                } // End of while loop
                ?>
              </tbody>
            </table>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal to confirm deletion -->
<div class="modal" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this record?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="confirmDeleteLink" href="#" class="btn btn-danger">Confirm Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Konfirmasi Hapus -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Konfirmasi Penghapusan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">Apakah Anda yakin ingin menghapus data ini?</div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><a id="confirmDeleteLink" href="#" class="btn btn-danger">Ya, Hapus</a></div>
        </div>
    </div>
</div>

<!-- [DIUBAH] Modal Universal untuk Ubah Status -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Perubahan Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Anda akan mengubah status menjadi <strong id="new-status-text-modal" class="text-primary"></strong>.</p>
                <div class="mb-3">
                    <label for="updateKeteranganText" class="form-label">Catatan / Alasan (Opsional):</label>
                    <textarea class="form-control" id="updateKeteranganText" rows="3" placeholder="Jika status 'ditolak', alasan wajib diisi..."></textarea>
                    <div id="keteranganError" class="text-danger mt-2 d-none">Alasan penolakan tidak boleh kosong.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="submitUpdateBtn">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Alasan Penolakan -->
<div class="modal fade" id="rejectionReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alasan Penolakan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="rejectionReasonText" class="form-label">Harap masukkan alasan penolakan:</label>
                    <textarea class="form-control" id="rejectionReasonText" rows="3" placeholder="Contoh: Perhitungan tidak sesuai, perlu revisi..."></textarea>
                    <div id="rejectionError" class="text-danger mt-2 d-none">Alasan penolakan tidak boleh kosong.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelRejectionBtn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="submitRejectionBtn">Tolak Pengajuan</button>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Inisialisasi DataTable
    $('#basic-datatables').DataTable();

    // 2. Inisialisasi semua modal
    const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    const statusChangeModal = new bootstrap.Modal(document.getElementById('confirmStatusChangeModal'));
    const rejectionModal = new bootstrap.Modal(document.getElementById('rejectionReasonModal'));

    // Variabel global untuk menyimpan state dropdown
    let currentSelect, originalValue;

        function getJsStatusClass(status) {
        switch (status) {
            case 'disetujui': return { bg: 'bg-success', text: 'text-white' };
            case 'dibayar':   return { bg: 'bg-primary', text: 'text-white' };
            case 'ditolak':   return { bg: 'bg-danger',  text: 'text-white' };
            case 'diajukan':  return { bg: 'bg-warning', text: 'text-dark'  };
            default:          return { bg: 'bg-secondary', text: 'text-white' };
        }
    }

        // [BARU] 3. Logika untuk tombol PILIH PROYEK di dalam modal
    // Menggunakan event delegation karena tombol ada di dalam modal
    $('#selectProyekModal').on('click', '.selectProyekBtn', function () {
        const idRabUpah = $(this).data('id');
        if (idRabUpah) {
            window.location.href = 'detail_pengajuan_upah.php?id_rab_upah=' + idRabUpah;
        }
    });

    // 3. Logika untuk tombol HAPUS
    $('#basic-datatables').on('click', '.delete-btn', function() {
        const pengajuanId = $(this).data('id');
        $('#confirmDeleteLink').attr('href', `delete_pengajuan_upah.php?id_pengajuan_upah=${pengajuanId}`);
        deleteModal.show();
    });

    // 5. Logika untuk DROPDOWN STATUS
    $('#basic-datatables').on('focus', '.status-select', function() {
        $(this).data('original-value', $(this).val());
    });

    $('#basic-datatables').on('change', '.status-select', function() {
        currentSelect = $(this);
        const newStatus = $(this).val();
        
        $(this).val($(this).data('original-value'));

        $(modalElement).data('pengajuan-id', $(this).data('id'));
        $(modalElement).data('new-status', newStatus);

        $('#new-status-text-modal').text(`"${newStatus}"`);
        // ... Logika warna modal ...
        statusUpdateModal.show();
    });

    // Saat dropdown diubah, siapkan modal
    $('#basic-datatables').on('change', '.status-select', function() {
        const select = $(this);
        const newStatus = select.val();
        const originalValue = select.data('original-value');
        const pengajuanId = select.data('id');

        // Kembalikan dropdown ke nilai asli secara visual sambil menunggu konfirmasi modal
        select.val(originalValue);

        // Simpan konteks/data yang diperlukan ke elemen modal itu sendiri
        $(modalElement).data('pengajuan-id', pengajuanId);
        $(modalElement).data('new-status', newStatus);

        // Siapkan tampilan modal universal
        $('#new-status-text-modal').text(`"${newStatus}"`).removeClass('text-danger text-primary');
        if (newStatus === 'ditolak') {
            $('#new-status-text-modal').addClass('text-danger');
        } else {
            $('#new-status-text-modal').addClass('text-primary');
        }
        $('#updateKeteranganText').val('');
        $('#keteranganError').addClass('d-none');
        statusUpdateModal.show();
    });
    
    // Fungsi umum untuk mengirim data AJAX
    function submitStatusChange(data) {
        $.ajax({
            url: 'update_status_pengajuan.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error! ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan pada server.');
                console.error("AJAX Error:", xhr.responseText);
            }
        });
    }

    // Handler untuk tombol "Simpan Perubahan" di modal universal
    $('#submitUpdateBtn').on('click', function() {
        const pengajuanId = $(modalElement).data('pengajuan-id');
        const newStatus = $(modalElement).data('new-status');

        if (!pengajuanId || !newStatus) return; // Pengaman jika data tidak ada

        const keterangan = $('#updateKeteranganText').val().trim();

        // Validasi: jika status 'ditolak', keterangan wajib diisi
        if (newStatus === 'ditolak' && keterangan === '') {
            $('#keteranganError').removeClass('d-none');
            return;
        }

        submitStatusChange({
            id_pengajuan_upah: pengajuanId,
            new_status: newStatus,
            keterangan: keterangan
        });
        statusUpdateModal.hide();
    });

    // Handler untuk tombol "Tolak Pengajuan" di modal penolakan
    $('#submitRejectionBtn').on('click', function() {
        if (!currentSelect) return;
        const reason = $('#rejectionReasonText').val().trim();
        if (reason === '') {
            $('#rejectionError').removeClass('d-none');
            return;
        }
        
        submitStatusChange({
            id_pengajuan_upah: currentSelect.data('id'),
            new_status: 'ditolak',
            keterangan: reason
        });
        rejectionModal.hide();
    });
});
</script>

</body>
</html>