<?php
include("../config/koneksi_mysql.php");


// Mengatur error reporting untuk membantu debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Query ini mengambil SEMUA data untuk ditampilkan di tabel utama.
$sql = "SELECT 
          tr.id_rab_upah,
          tr.id_proyek,
          mpe.nama_perumahan,
          mpr.kavling,
          mpr.type_proyek,
          mpe.lokasi,
          mm.nama_mandor,
          u.nama_lengkap AS pj_proyek, -- Mengambil nama lengkap user sebagai 'pj_proyek'
          tr.tanggal_mulai,
          tr.tanggal_selesai,
          tr.total_rab_upah
        FROM rab_upah tr
        JOIN master_proyek mpr ON tr.id_proyek = mpr.id_proyek
        LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
        LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
        ORDER BY tr.id_rab_upah DESC"; // Mengurutkan berdasarkan data terbaru

$result = mysqli_query($koneksi, $sql);
if (!$result) {
    die("Query Error (rab_upah): " . mysqli_error($koneksi));
}

// Query ini untuk mengisi dropdown 'Nama Perumahan' di dalam modal tambah data.
$perumahanResult = mysqli_query($koneksi, "SELECT id_perumahan, nama_perumahan FROM master_perumahan ORDER BY nama_perumahan ASC");
if (!$perumahanResult) {
    die("Query Error (perumahan): " . mysqli_error($koneksi));
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
              <h3 class="fw-bold mb-3">Rancang RAB</h3>
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
                  <a href="#">Rancang RAB</a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="#">RAB Upah</a>
                </li>
              </ul>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header d-flex align-items-center">
                    <h4 class="card-title">RAB Upah</h4>
                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addRABUpahModal">
                      <i class="fa fa-plus"></i> Tambah Data RAB
                    </button>
                  </div>

                  <div class="card-body">
                    <?php if (isset($_GET['msg'])): ?>
                      <div id="alert-message" class="alert alert-success fade show m-3" role="alert">
                          <?= htmlspecialchars($_GET['msg']) ?>
                      </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                      <table id="basic-datatables" class="display table table-striped table-hover">
                        <thead>
                          <tr>
                            <th>ID RAB</th>
                            <th>Perumahan</th>
                            <th>Kavling</th>
                            <th>Mandor</th>
                            <th>PJ Proyek</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Total</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while ($row = mysqli_fetch_assoc($result)): ?>
                          <?php
                            $tahun = date('Y', strtotime($row['tanggal_mulai']));
                            $bulan = date('m', strtotime($row['tanggal_mulai']));
                            $formatted_id = 'RABP' . substr($tahun, -2) . $bulan . $row['id_proyek'] . $row['id_rab_upah'];
                            $totalFormatted = 'Rp ' . number_format($row['total_rab_upah'], 0, ',', '.');
                          ?>
                          <tr>
                            <td><?= htmlspecialchars($formatted_id) ?></td>
                            <td><?= htmlspecialchars($row['nama_perumahan']) ?></td>
                            <td><?= htmlspecialchars($row['kavling']) ?></td>
                            <td><?= htmlspecialchars($row['nama_mandor']) ?></td>
                            <td><?= htmlspecialchars($row['pj_proyek']) ?></td>
                            <td><?= date('d-m-Y', strtotime($row['tanggal_mulai'])) ?></td>
                            <td><?= date('d-m-Y', strtotime($row['tanggal_selesai'])) ?></td>
                            <td><?= htmlspecialchars($totalFormatted) ?></td>
                            <td>
                              <a href="detail_rab_upah.php?id_rab_upah=<?= urlencode($row['id_rab_upah']) ?>" class="btn btn-info btn-sm">Detail</a>
                              <button class="btn btn-danger btn-sm delete-btn" data-id_rab_upah="<?= htmlspecialchars($row['id_rab_upah']) ?>">Delete</button>
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
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Tambah Data RAB Upah -->
    <div class="modal fade" id="addRABUpahModal" tabindex="-1" aria-labelledby="addRABUpahModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="POST" action="add_rab_upah.php">
            <div class="modal-header">
              <h5 class="modal-title" id="addRABUpahModalLabel">Tambah Data RAB Upah</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="id_perumahan" class="form-label">Nama Perumahan</label>
                <select class="form-select" id="id_perumahan" name="id_perumahan" required>
                  <option value="" disabled selected>Pilih Perumahan</option>
                  <?php 
                    mysqli_data_seek($perumahanResult, 0); // Reset pointer
                    while ($perumahan = mysqli_fetch_assoc($perumahanResult)): 
                  ?>
                    <option value="<?= htmlspecialchars($perumahan['id_perumahan']) ?>"><?= htmlspecialchars($perumahan['nama_perumahan']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="mb-3">
                <label for="id_proyek" class="form-label">Kavling</label>
                <select class="form-select" id="id_proyek" name="id_proyek" required>
                  <option value="" disabled selected>Pilih Kavling</option>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3"><label>Tipe Proyek</label><input type="text" class="form-control" id="type_proyek" readonly /></div>
                <div class="col-md-6 mb-3"><label>Lokasi</label><input type="text" class="form-control" id="lokasi" readonly /></div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3"><label>Mandor</label><input type="text" class="form-control" id="nama_mandor" readonly /></div>
                <div class="col-md-6 mb-3"><label>PJ Proyek</label><input type="text" class="form-control" id="pj_proyek" readonly /></div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3"><label>Tanggal Mulai</label><input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required /></div>
                <div class="col-md-6 mb-3"><label>Tanggal Selesai</label><input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required /></div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary" id="btn-submit-rab" disabled>Lanjut & Buat RAB</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body"><p>Apakah Anda yakin ingin menghapus data ini?</p></div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><a href="#" id="confirmDeleteLink" class="btn btn-danger">Hapus</a></div>
        </div>
      </div>
    </div>

    <!-- PENTING: Urutan script harus benar. jQuery -> Bootstrap -> DataTables -> Script Kustom -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    
    <script>
      $(document).ready(function() {
        // 1. INISIALISASI DATATABLES
        $('#basic-datatables').DataTable();

        // 2. LOGIKA NOTIFIKASI
        if ($('#alert-message').length) {
            setTimeout(function() {
                let bsAlert = new bootstrap.Alert($('#alert-message')[0]);
                bsAlert.close();
                if (window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('msg');
                    window.history.replaceState({ path: url.href }, '', url.href);
                }
            }, 3000);
        }

        // 3. LOGIKA UNTUK MODAL TAMBAH DATA
        const addModal = document.getElementById('addRABUpahModal');
        const idProyekDropdown = $('#id_proyek');
        const submitBtn = $('#btn-submit-rab');

        // Fungsi untuk mereset field di modal
        function resetProyekFields() {
            idProyekDropdown.html('<option value="" disabled selected>Pilih Perumahan Dahulu</option>');
            $('#type_proyek, #lokasi, #nama_mandor, #pj_proyek').val('');
            submitBtn.prop('disabled', true);
        }

        // Ketika dropdown Perumahan berubah
        $('#id_perumahan').on('change', function() {
            const idPerumahan = $(this).val();
            resetProyekFields();
            if (!idPerumahan) return;

            idProyekDropdown.html('<option value="">Memuat...</option>');
            $.ajax({
                url: 'get_kavling.php',
                method: 'POST',
                data: { id_perumahan: idPerumahan },
                dataType: 'json',
                success: function(response) {
                    let options = '<option value="" disabled selected>Pilih Kavling</option>';
                    if (response.length > 0) {
                        response.forEach(function(proyek) {
                            options += `<option value="${proyek.id_proyek}" 
                                        data-type_proyek="${proyek.type_proyek}"
                                        data-lokasi="${proyek.lokasi}"
                                        data-mandor="${proyek.nama_mandor}"
                                        data-pj_proyek="${proyek.pj_proyek}">
                                        ${proyek.kavling}
                                      </option>`;
                        });
                    } else {
                        options = '<option value="" disabled>Tidak ada kavling</option>';
                    }
                    idProyekDropdown.html(options);
                },
                error: function() { alert('Gagal mengambil data kavling.'); resetProyekFields(); }
            });
        });

        // Ketika dropdown Kavling berubah
        idProyekDropdown.on('change', function() {
            const selected = $(this).find(':selected');
            $('#type_proyek').val(selected.data('type_proyek') || '');
            $('#lokasi').val(selected.data('lokasi') || '');
            $('#nama_mandor').val(selected.data('mandor') || '');
            $('#pj_proyek').val(selected.data('pj_proyek') || '');
            submitBtn.prop('disabled', !$(this).val());
        });
        
        // Reset modal saat ditutup
        $(addModal).on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            resetProyekFields();
        });

        // 4. LOGIKA UNTUK TOMBOL DELETE
        $('#basic-datatables tbody').on('click', '.delete-btn', function() {
            const idRabUpah = $(this).data('id_rab_upah');
            $('#confirmDeleteLink').attr('href', 'delete_rab_upah.php?id_rab_upah=' + idRabUpah);
            let deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            deleteModal.show();
        });
      });
    </script>
</body>
</html>
