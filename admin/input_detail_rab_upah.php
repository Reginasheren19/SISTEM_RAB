<?php
include("../config/koneksi_mysql.php");

if (!isset($_GET['id_rab_upah'])) {
    echo "ID RAB Upah tidak diberikan.";
    exit;
}

$id_rab_upah = mysqli_real_escape_string($koneksi, $_GET['id_rab_upah']);

// Query ambil data RAB Upah beserta data terkait
$sql = "SELECT 
            tr.id_rab_upah,
            CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS pekerjaan,
            mpr.type_proyek,
            mpe.lokasi,
            YEAR(tr.tanggal_mulai) AS tahun,
            mm.nama_mandor
        FROM rab_upah tr
        JOIN master_perumahan mpe ON tr.id_perumahan = mpe.id_perumahan
        JOIN master_proyek mpr ON tr.id_proyek = mpr.id_proyek
        JOIN master_mandor mm ON tr.id_mandor = mm.id_mandor
        WHERE tr.id_rab_upah = '$id_rab_upah'";

$result = mysqli_query($koneksi, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    echo "Data RAB Upah tidak ditemukan.";
    exit;
}

$data = mysqli_fetch_assoc($result);

// Query ambil detail rab upah (pekerjaan dan biaya)
$sql_detail = "SELECT 
                 d.id_detail_rab_upah, 
                 d.id_kategori,
                 d.id_pekerjaan, 
                 mp.uraian_pekerjaan, 
                 k.nama_kategori,
                 ms.nama_satuan,
                 d.volume, 
                 d.harga_satuan, 
                 d.sub_total
               FROM detail_rab_upah d
               LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan
               LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori
               LEFT JOIN master_satuan ms ON mp.id_satuan = ms.id_satuan
               WHERE d.id_rab_upah = '$id_rab_upah'";;

$detail_result = mysqli_query($koneksi, $sql_detail);

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
    <link
  rel="stylesheet"
  href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css"
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


                <div class="card shadow-sm mb-4">
                  <div class="card-header fw-bold">
                    Info RAB Upah
                  </div>
                  <div class="card-body">
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                      <div class="col">
                        <div class="d-flex">
                          <span class="fw-semibold me-2" style="min-width: 120px;">ID RAB</span>
                          <span>: <?= htmlspecialchars($data['id_rab_upah']) ?></span>
                        </div>
                      </div>
                      <div class="col">
                        <div class="d-flex">
                          <span class="fw-semibold me-2" style="min-width: 120px;">Pekerjaan</span>
                          <span>: <?= htmlspecialchars($data['pekerjaan']) ?></span>
                        </div>
                      </div>
                      <div class="col">
                        <div class="d-flex">
                          <span class="fw-semibold me-2" style="min-width: 120px;">Type Proyek</span>
                          <span>: <?= htmlspecialchars($data['type_proyek']) ?></span>
                        </div>
                      </div>
                      <div class="col">
                        <div class="d-flex">
                          <span class="fw-semibold me-2" style="min-width: 120px;">Lokasi</span>
                          <span>: <?= htmlspecialchars($data['lokasi']) ?></span>
                        </div>
                      </div>
                      <div class="col">
                        <div class="d-flex">
                          <span class="fw-semibold me-2" style="min-width: 120px;">Tahun</span>
                          <span>: <?= htmlspecialchars($data['tahun']) ?></span>
                        </div>
                      </div>
                      <div class="col">
                        <div class="d-flex">
                          <span class="fw-semibold me-2" style="min-width: 120px;">Mandor</span>
                          <span>: <?= htmlspecialchars($data['nama_mandor']) ?></span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

<div class="card">
  <div class="card-header">
    Detail RAB
  </div>
      <div class="card-body"> 

        <button
          id="btnTambahKategori"
          class="btn btn-primary btn-round ms-auto mb-3">
          <i class="fa fa-plus"></i> Tambah Kategori
        </button>
        
  <div class="table-responsive">
    <table class="table table-bordered" id="tblKategori">
      <thead>
        <tr>
          <th scope="col" style="width:5%;">No</th>
          <th scope="col">Uraian Pekerjaan</th>
          <th scope="col" style="width:10%;">Satuan</th>
          <th scope="col" style="width:10%;">Volume</th>
          <th scope="col" style="width:15%;">Harga Satuan</th>
          <th scope="col" style="width:15%;">Jumlah</th>
          <th scope="col" style="width:15%;">Aksi</th>
        </tr>
      </thead>
      
      <tbody>
        <?php
        if ($detail_result && mysqli_num_rows($detail_result) > 0) {
            $no = 1;
            $grand_total = 0;
            while ($row = mysqli_fetch_assoc($detail_result)) {
                $grand_total += $row['sub_total'];
                echo "<tr>
                        <td>" . $no++ . "</td>
                        <td>" . htmlspecialchars($row['uraian_pekerjaan']) . "</td>
                        <td>" . htmlspecialchars($row['nama_satuan']) . "</td>
                        <td>" . htmlspecialchars($row['volume']) . "</td>
                        <td>Rp " . number_format($row['harga_satuan'], 0, ',', '.') . "</td>
                        <td>Rp " . number_format($row['sub_total'], 0, ',', '.') . "</td>  
                                                                      
                        <td class='text-center'>
                        </td>
                      </tr>";
            }
            echo "<tr>
                    <td colspan='5' class='text-end fw-bold'>Total</td>
                    <td class='fw-bold'>Rp " . number_format($grand_total, 0, ',', '.') . "</td>
                  </tr>";
        } 
        ?>
        </tbody>
      </table>
      
    </div>
    <div class="mt-3 text-end">
  <button id="btnSimpanSemua" class="btn btn-success">
    <i class="fa fa-save"></i> Simpan RAB
  </button>
</div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  <script src="/SISTEM_RAB/assets/js/bootstrap.bundle.min.js"></script>


<script>
  let kategoriList = [];
  let pekerjaanList = [];

  $(function() {
    // Konversi angka ke angka romawi
    function toRoman(num) {
      const romans = [
        ["M",1000], ["CM",900], ["D",500], ["CD",400],
        ["C",100], ["XC",90], ["L",50], ["XL",40],
        ["X",10], ["IX",9], ["V",5], ["IV",4], ["I",1]
      ];
      let result = '';
      for (let [letter, value] of romans) {
        while (num >= value) {
          result += letter;
          num -= value;
        }
      }
      return result;
    }

    // Load data kategori dari server (harus berupa objek dengan id_kategori & nama_kategori)
    function loadKategori() {
      return $.ajax({
        url: 'get_kategori.php',
        dataType: 'json',
        success: data => kategoriList = data,
        error: () => kategoriList = []
      });
    }

    // Load data pekerjaan & satuan (harus berupa objek dengan id_pekerjaan, uraian_pekerjaan, nama_satuan)
    function loadPekerjaanSatuan() {
      return $.ajax({
        url: 'get_pekerjaan.php',
        dataType: 'json',
        success: data => pekerjaanList = data,
        error: () => pekerjaanList = []
      });
    }

    // Autocomplete input kategori
    function bindAutocompleteKategori(input) {
      input.autocomplete({
        source: kategoriList.map(k => k.nama_kategori),
        minLength: 0,
        delay: 100,
      }).focus(function() {
        $(this).autocomplete("search", "");
      });
    }

    // Autocomplete input pekerjaan
    function bindAutocompletePekerjaan(input) {
      input.autocomplete({
        source: pekerjaanList.map(p => p.uraian_pekerjaan),
        minLength: 0,
        delay: 100,
        select: function(event, ui) {
          let pekerjaan = pekerjaanList.find(p => p.uraian_pekerjaan === ui.item.value);
          if (pekerjaan) {
            $(this).closest('tr').find('input.satuan').val(pekerjaan.nama_satuan);
          }
        }
      }).focus(function() {
        $(this).autocomplete("search", "");
      });
    }

    // Update nomor kategori, pekerjaan, subtotal kategori, dan total keseluruhan
    function updateRowNumber() {
      let kategoriCount = 0;
      $('#tblKategori tbody tr').each(function() {
        if ($(this).hasClass('kategori') || $(this).hasClass('input-kategori')) {
          kategoriCount++;
          $(this).find('td:first').text(toRoman(kategoriCount));

          let pekerjaanCount = 0;
          let totalKategori = 0;
          let nextRow = $(this).next();

          while (nextRow.length && (nextRow.hasClass('pekerjaan') || nextRow.hasClass('input-pekerjaan') || nextRow.hasClass('sub-total'))) {
            if (nextRow.hasClass('pekerjaan') || nextRow.hasClass('input-pekerjaan')) {
              pekerjaanCount++;
              nextRow.find('td:first').text(pekerjaanCount);

              let jumlahText = nextRow.find('td').eq(5).text().replace(/[^\d]/g, '');
              let jumlahVal = parseInt(jumlahText) || 0;
              totalKategori += jumlahVal;
            }
            nextRow = nextRow.next();
          }
          updateSubTotalRow($(this), totalKategori);
        }
      });
      updateTotalKeseluruhan();
    }

    // Update atau buat baris subtotal kategori
    function updateSubTotalRow(kategoriRow, totalKategori) {
      if (totalKategori === 0) {
        kategoriRow.nextUntil('tr.kategori').filter('.sub-total').remove();
        return;
      }

      let subTotalRow = kategoriRow.nextUntil('tr.kategori').filter('.sub-total').first();

      if (subTotalRow.length) {
        subTotalRow.find('td').eq(1).text('Sub Total');
        subTotalRow.find('td').eq(5).text('Rp ' + totalKategori.toLocaleString('id-ID'));
      } else {
        let insertAfter = kategoriRow;
        let nextRow = kategoriRow.next();

        while(nextRow.length && (nextRow.hasClass('pekerjaan') || nextRow.hasClass('input-pekerjaan'))) {
          insertAfter = nextRow;
          nextRow = nextRow.next();
        }

        const subTotalHtml = $(` 
          <tr class="sub-total">
            <td></td>
            <td class="fw-bold">Sub Total</td>
            <td></td>
            <td></td>
            <td></td>
            <td class="fw-bold">Rp ${totalKategori.toLocaleString('id-ID')}</td>
            <td></td>
          </tr>
        `);

        insertAfter.after(subTotalHtml);
      }
    }

    // Update atau buat baris total keseluruhan
    function updateTotalKeseluruhan() {
      let totalKeseluruhan = 0;
      $('#tblKategori tbody tr.sub-total').each(function() {
        let subtotalText = $(this).find('td').eq(5).text().replace(/[^\d]/g, '');
        let subtotalVal = parseInt(subtotalText) || 0;
        totalKeseluruhan += subtotalVal;
      });

      $('#tblKategori tbody tr.total-keseluruhan').remove();

      if (totalKeseluruhan === 0) return;

      const totalRowHtml = $(`
        <tr class="table-success total-keseluruhan">
          <td></td>
          <td class="fw-bold">Total Keseluruhan</td>
          <td></td>
          <td></td>
          <td></td>
          <td class="fw-bold">Rp ${totalKeseluruhan.toLocaleString('id-ID')}</td>
          <td></td>
        </tr>
      `);

      $('#tblKategori tbody').append(totalRowHtml);
    }

    // Tambah baris kategori baru
    function tambahBarisKategori() {
      $('#tblKategori tbody tr.no-data').remove();
      const newRow = $(`
        <tr class="input-kategori" data-kategori-id="${Date.now()}">
          <td></td>
          <td colspan="5"><input type="text" class="form-control kategori-autocomplete" placeholder="Ketik kategori" autocomplete="off" /></td>
          <td class="text-center">
            <button type="button" class="btn btn-success btn-sm btn-simpan"><i class="fa fa-check"></i></button>
            <button type="button" class="btn btn-danger btn-sm btn-batal"><i class="fa fa-times"></i></button>
          </td>
        </tr>
      `);
      $('#tblKategori tbody').append(newRow);
      bindAutocompleteKategori(newRow.find('input.kategori-autocomplete'));
      updateRowNumber();
    }

    // Event tombol tambah kategori
    $('#btnTambahKategori').on('click', function() {
      if (kategoriList.length === 0) {
        $.when(loadKategori()).done(tambahBarisKategori);
      } else {
        tambahBarisKategori();
      }
    });

    // Event simpan kategori baru
    $('#tblKategori').on('click', '.btn-simpan', function() {
      const row = $(this).closest('tr');
      const val = row.find('input.kategori-autocomplete').val().trim();

      if (!val) {
        alert('Kategori tidak boleh kosong!');
        return;
      }

      const kategoriId = row.data('kategori-id') || Date.now();
      row.removeClass('input-kategori').addClass('kategori').attr('data-kategori-id', kategoriId);
      row.html(`
        <td></td>
        <td>
          ${val}
          <button type="button" class="btn btn-outline-secondary btn-sm btn-toggle-pekerjaan ms-2" title="Tampilkan / Sembunyikan Pekerjaan" style="padding: 2px 6px; font-size: 0.75rem;">
            <i class="fa fa-chevron-up"></i>
          </button>
        </td>
        <td colspan="4"></td>
        <td class="text-center">
          <button class="btn btn-primary btn-sm btn-tambah-pekerjaan" title="Tambah Pekerjaan" style="border-radius:50%;padding:6px 9px;">
            <i class="fa fa-plus"></i>
          </button>
          <button class="btn btn-danger btn-sm btn-batal ms-1" title="Hapus Kategori">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      `);
      row.addClass('table-primary mt-4');
      updateRowNumber();
    });

    // Event batal input kategori
    $('#tblKategori').on('click', '.btn-batal', function() {
      const row = $(this).closest('tr');
      row.remove();

      if ($('#tblKategori tbody tr').length === 0) {
        $('#tblKategori tbody').append('<tr class="no-data"><td colspan="7" class="text-center">Tidak ada detail pekerjaan</td></tr>');
      }

      updateRowNumber();
    });

    // Event tambah pekerjaan pada kategori
    $('#tblKategori').on('click', '.btn-tambah-pekerjaan', function() {
      const kategoriRow = $(this).closest('tr');
      const kategoriId = kategoriRow.data('kategori-id');

      let maxNomor = 0;
      kategoriRow.nextAll('tr.pekerjaan, tr.input-pekerjaan').each(function() {
        if ($(this).data('parent-kategori-id') === kategoriId) {
          let nomor = parseInt($(this).find('td:first').text());
          if (!isNaN(nomor) && nomor > maxNomor) maxNomor = nomor;
        } else {
          return false;
        }
      });

      if (kategoriRow.next().hasClass('input-pekerjaan')) return;

      const pekerjaanRow = $(`
        <tr class="input-pekerjaan" data-parent-kategori-id="${kategoriId}">
          <td>${maxNomor + 1}</td>
          <td><input type="text" class="form-control uraian-pekerjaan" placeholder="Ketik uraian pekerjaan" autocomplete="off" /></td>
          <td><input type="text" class="form-control satuan" placeholder="Satuan" readonly /></td>
          <td><input type="number" class="form-control volume" placeholder="Volume" min="0" /></td>
          <td><input type="number" class="form-control harga-satuan" placeholder="Harga Satuan" min="0" /></td>
          <td><input type="text" class="form-control jumlah" placeholder="Jumlah" readonly /></td>
          <td class="text-center">
            <button type="button" class="btn btn-success btn-sm btn-simpan-pekerjaan"><i class="fa fa-check"></i></button>
            <button type="button" class="btn btn-danger btn-sm btn-batal-pekerjaan"><i class="fa fa-times"></i></button>
          </td>
        </tr>
      `);

      let lastPekerjaan = null;
      kategoriRow.nextAll('tr.pekerjaan, tr.input-pekerjaan').each(function() {
        if ($(this).data('parent-kategori-id') === kategoriId) {
          lastPekerjaan = $(this);
        } else {
          return false;
        }
      });

      if (lastPekerjaan) lastPekerjaan.after(pekerjaanRow);
      else kategoriRow.after(pekerjaanRow);

      bindAutocompletePekerjaan(pekerjaanRow.find('input.uraian-pekerjaan'));
    });

    // Event simpan pekerjaan
    $('#tblKategori').on('click', '.btn-simpan-pekerjaan', function() {
      const row = $(this).closest('tr');
      const uraian = row.find('input.uraian-pekerjaan').val().trim();
      const satuan = row.find('input.satuan').val().trim();
      const volume = parseFloat(row.find('input.volume').val());
      const hargaSatuan = parseFloat(row.find('input.harga-satuan').val());

      if (!uraian) { alert('Uraian pekerjaan tidak boleh kosong!'); return; }
      if (!satuan) { alert('Satuan tidak boleh kosong!'); return; }
      if (isNaN(volume) || volume <= 0) { alert('Volume harus lebih dari 0!'); return; }
      if (isNaN(hargaSatuan) || hargaSatuan <= 0) { alert('Harga satuan harus lebih dari 0!'); return; }

      const total = volume * hargaSatuan;
      const nomor = row.find('td:first').text();

      row.removeClass('input-pekerjaan').addClass('pekerjaan');
      row.html(`
        <td>${nomor}</td>
        <td>${uraian}</td>
        <td>${satuan}</td>
        <td>${volume}</td>
        <td>Rp ${hargaSatuan.toLocaleString('id-ID')}</td>
        <td>Rp ${total.toLocaleString('id-ID')}</td>
        <td class="text-center">
          <button class="btn btn-danger btn-sm btn-batal-pekerjaan" title="Hapus Pekerjaan">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      `);

      updateRowNumber();
    });

    // Event batal pekerjaan
    $('#tblKategori').on('click', '.btn-batal-pekerjaan', function() {
      $(this).closest('tr').remove();
      updateRowNumber();
    });

    // Toggle pekerjaan show/hide di kategori
    $('#tblKategori').on('click', '.btn-toggle-pekerjaan', function() {
      const kategoriRow = $(this).closest('tr.kategori, tr.input-kategori');
      if (!kategoriRow.length) return;
      const kategoriId = kategoriRow.data('kategori-id');
      if (!kategoriId) return;

      const pekerjaanRows = $(`#tblKategori tbody tr.pekerjaan[data-parent-kategori-id='${kategoriId}'], #tblKategori tbody tr.input-pekerjaan[data-parent-kategori-id='${kategoriId}']`);

      if (pekerjaanRows.is(':visible')) {
        pekerjaanRows.hide();
        $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
      } else {
        pekerjaanRows.show();
        $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
      }
    });

    // Load data kategori & pekerjaan, bind autocomplete, update nomor
    $.when(loadKategori(), loadPekerjaanSatuan()).done(function() {
      bindAutocompleteKategori($('input.kategori-autocomplete'));
      bindAutocompletePekerjaan($('input.uraian-pekerjaan'));
      updateRowNumber();
    });

    // Simpan semua data ke server via AJAX
    $('#btnSimpanSemua').on('click', function() {
      const id_rab_upah = <?= json_encode($id_rab_upah) ?>;

      let dataToSend = [];

      $('#tblKategori tbody tr.kategori').each(function() {
        const kategoriId = $(this).data('kategori-id');
        const kategoriNama = $(this).find('td').eq(1).text().trim();

        $(this).nextAll('tr.pekerjaan').each(function() {
          if ($(this).data('parent-kategori-id') !== kategoriId) return false;

          const uraian = $(this).find('td').eq(1).text().trim();
          const satuan = $(this).find('td').eq(2).text().trim();
          const volume = parseInt($(this).find('td').eq(3).text());
          const harga_satuan_text = $(this).find('td').eq(4).text().replace(/[^\d]/g, '');
          const harga_satuan = parseInt(harga_satuan_text);
          const sub_total_text = $(this).find('td').eq(5).text().replace(/[^\d]/g, '');
          const sub_total = parseInt(sub_total_text);

          const pekerjaanObj = pekerjaanList.find(p => p.uraian_pekerjaan === uraian);
          const id_pekerjaan = pekerjaanObj ? pekerjaanObj.id_pekerjaan : null;

          const kategoriObj = kategoriList.find(k => k.nama_kategori.trim().toLowerCase() === kategoriNama.trim().toLowerCase());
          const id_kategori = kategoriObj ? kategoriObj.id_kategori : null;

          if (!id_pekerjaan || !id_kategori) {
            console.warn('Data id_pekerjaan atau id_kategori tidak ditemukan:', { id_pekerjaan, id_kategori, uraian, kategoriNama });
            return;
          }

          dataToSend.push({
            id_kategori,
            id_pekerjaan,
            volume,
            harga_satuan,
            sub_total
          });
        });
      });

      console.log('Data yang dikirim:', dataToSend);

      if (dataToSend.length === 0) {
        alert('Tidak ada data yang disimpan. Pastikan Anda telah menambahkan pekerjaan.');
        return;
      }

      $.ajax({
        url: 'add_detail_rab_upah.php',
        method: 'POST',
        data: {
          id_rab_upah,
          detail: JSON.stringify(dataToSend)
        },
        success: function(res) {
          alert(res.message || 'Data berhasil disimpan');
                window.location.href = 'transaksi_rab_upah.php';  // Redirect setelah simpan sukses
          // Kalau perlu, reload halaman atau update UI di sini
        },
        error: function() {
          alert('Gagal menyimpan data');
        }
      });
    });

  });
</script>
</body>
</html>
