<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Inisialisasi & Pengambilan ID dari URL (Tidak Berubah)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID Distribusi tidak valid.";
    header("Location: distribusi_material.php");
    exit();
}
$id_distribusi = $_GET['id'];

// 2. Query untuk Data Header Distribusi (Tidak Berubah)
$header_sql = "
    SELECT 
        d.id_distribusi, d.tanggal_distribusi, d.keterangan_umum,
        u.nama_lengkap AS nama_pj_distribusi,
        CONCAT(pr.nama_perumahan, ' - Kavling ', p.kavling) AS nama_proyek_lengkap
    FROM distribusi_material d
    LEFT JOIN master_user u ON d.id_user_pj = u.id_user
    LEFT JOIN master_proyek p ON d.id_proyek = p.id_proyek 
    LEFT JOIN master_perumahan pr ON p.id_perumahan = pr.id_perumahan
    WHERE d.id_distribusi = ?
";
$stmt_header = mysqli_prepare($koneksi, $header_sql);
mysqli_stmt_bind_param($stmt_header, "i", $id_distribusi);
mysqli_stmt_execute($stmt_header);
$header_result = mysqli_stmt_get_result($stmt_header);
$distribusi = mysqli_fetch_assoc($header_result);

if (!$distribusi) {
    $_SESSION['error_message'] = "Data Distribusi tidak ditemukan.";
    header("Location: distribusi_material.php");
    exit();
}

// 3. Query untuk Daftar Item yang Sudah Ditambahkan (Tidak Berubah)
$detail_sql = "
    SELECT dd.id_detail, m.nama_material, dd.jumlah_distribusi, s.nama_satuan AS satuan
    FROM detail_distribusi dd
    JOIN master_material m ON dd.id_material = m.id_material
    JOIN master_satuan s ON m.id_satuan = s.id_satuan
    WHERE dd.id_distribusi = ?
    ORDER BY dd.id_detail ASC
";
$stmt_detail = mysqli_prepare($koneksi, $detail_sql);
if ($stmt_detail === false) { die("Query Gagal Disiapkan. Error SQL: " . mysqli_error($koneksi)); }
mysqli_stmt_bind_param($stmt_detail, "i", $id_distribusi);
mysqli_stmt_execute($stmt_detail);
$detail_items = mysqli_stmt_get_result($stmt_detail);


$material_sql = "
    SELECT 
        m.id_material, 
        m.nama_material, 
        s.nama_satuan AS satuan,
        -- Mengambil stok, jika stoknya belum ada (NULL), dianggap 0
        COALESCE(sm.jumlah_stok_tersedia, 0) AS jumlah_stok_tersedia
    FROM 
        master_material m
    LEFT JOIN 
        master_satuan s ON m.id_satuan = s.id_satuan
    LEFT JOIN
        stok_material sm ON m.id_material = sm.id_material
    ORDER BY 
        m.nama_material ASC
";
$materials_result = mysqli_query($koneksi, $material_sql);

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Input Detail Distribusi Material</title>
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
                <a href="distribusi_material.php">
                  <i class="fas fa-truck"></i>
                  <p>Distribusi Material</p>
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
                <h3 class="fw-bold mb-3">Distribusi Material</h3>
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
                        <a href="#">Distribusi Material</a>
                    </li>
                    <li class="separator">
                        <i class="icon-arrow-right"></i>
                    </li>
                    <li class="nav-item">
                        <a href="#">Input Detail Distribusi Material</a>
                    </li>
                </ul>
            </div>

                    <div class="card mb-4">
                        <div class="card-header"><h4 class="card-title">Informasi Transaksi</h4></div>
                          <div class="card-body">
                              <div class="row">
                                  <div class="col-md-6">
                                      <p><strong>ID Distribusi:</strong> DIST<?= htmlspecialchars($distribusi['id_distribusi']) . date('Y', strtotime($distribusi['tanggal_distribusi'])) ?></p>
                                      <p><strong>Proyek Tujuan:</strong> <?= htmlspecialchars($distribusi['nama_proyek_lengkap']) ?></p>
                                  </div>
                                  <div class="col-md-6">
                                      <p><strong>Tanggal:</strong> <?= date("d F Y", strtotime($distribusi['tanggal_distribusi'])) ?></p>
                                      <p><strong>Didistribusikan Oleh:</strong> <?= htmlspecialchars($distribusi['nama_pj_distribusi']) ?></p>
                                  </div>
                                  <div class="col-12 mt-2">
                                      <p><strong>Keterangan:</strong> <?= nl2br(htmlspecialchars($distribusi['keterangan_umum'])) ?></p>
                                  </div>
                              </div>
                          </div>
                    </div>
                        <div class="card mb-4">
                        <div class="card-header"><h4 class="card-title">Tambah Material</h4></div>
                        <div class="card-body">
                            <div class="row align-items-end">
                                <div class="col-md-4 mb-3">
                                    <label for="id_material" class="form-label">Material</label>
                                    <select class="form-select" id="id_material">
                                        <option value="" data-stok="" data-satuan="">-- Pilih Material --</option>
                                        <?php while($material = mysqli_fetch_assoc($materials_result)): ?>
                                            <option value="<?= $material['id_material'] ?>" data-stok="<?= $material['jumlah_stok_tersedia'] ?>" data-satuan="<?= $material['satuan'] ?>" data-nama="<?= htmlspecialchars($material['nama_material']) ?>">
                                                <?= htmlspecialchars($material['nama_material']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="stok_saat_ini" class="form-label">Stok</label>
                                    <input type="text" id="stok_saat_ini" class="form-control" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="jumlah_distribusi" class="form-label">Jumlah</label>
                                    <input type="number" step="0.01" class="form-control" id="jumlah_distribusi">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="button" id="btn-tambah-item" class="btn btn-primary w-100">Tambahkan</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="add_detail_distribusi.php" method="POST">
                        <input type="hidden" name="id_distribusi" value="<?= $id_distribusi ?>">

                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Daftar Material Didistribusikan</h4></div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Nama Material</th>
                                            <th>Jumlah</th>
                                            <th>Satuan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="daftar-item-body">
                                        </tbody>
                                </table>
                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-success">Selesaikan Transaksi & Simpan</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script>
    $(document).ready(function() {
        var nomorUrut = 1;

        // 1. Update stok saat material dipilih
        $('#id_material').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var stok = selectedOption.data('stok');
            var satuan = selectedOption.data('satuan');
            if (stok !== '') {
                $('#stok_saat_ini').val(stok + ' ' + satuan);
            } else {
                $('#stok_saat_ini').val('');
            }
        });

        // 2. Logika saat tombol "Tambahkan" diklik
        $('#btn-tambah-item').on('click', function() {
            var materialSelect = $('#id_material');
            var selectedOption = materialSelect.find('option:selected');
            var jumlahInput = $('#jumlah_distribusi');

            var idMaterial = materialSelect.val();
            var namaMaterial = selectedOption.data('nama');
            var stok = parseFloat(selectedOption.data('stok'));
            var satuan = selectedOption.data('satuan');
            var jumlah = parseFloat(jumlahInput.val());

            // Validasi
            if (!idMaterial) {
                alert('Silakan pilih material terlebih dahulu.');
                return;
            }
            if (isNaN(jumlah) || jumlah <= 0) {
                alert('Jumlah harus diisi dengan angka lebih dari 0.');
                return;
            }
            if (jumlah > stok) {
                alert('Jumlah distribusi tidak boleh melebihi stok yang tersedia!');
                return;
            }

            // Buat baris tabel baru
            var barisBaru = `
                <tr data-id="${idMaterial}">
                    <td>${nomorUrut}</td>
                    <td>
                        ${namaMaterial}
                        <input type="hidden" name="id_material[]" value="${idMaterial}">
                        <input type="hidden" name="jumlah_distribusi[]" value="${jumlah}">
                    </td>
                    <td>${jumlah}</td>
                    <td>${satuan}</td>
                    <td><button type="button" class="btn btn-danger btn-sm btn-hapus-item">Hapus</button></td>
                </tr>
            `;

            // Tambahkan baris baru ke tabel
            $('#daftar-item-body').append(barisBaru);
            nomorUrut++;

            // Reset form input
            materialSelect.val('').trigger('change');
            jumlahInput.val('');
        });

        // 3. Logika saat tombol "Hapus" di dalam baris tabel diklik
        $('#daftar-item-body').on('click', '.btn-hapus-item', function() {
            $(this).closest('tr').remove();
            
            // Atur ulang nomor urut
            nomorUrut = 1;
            $('#daftar-item-body tr').each(function() {
                $(this).find('td:first').text(nomorUrut);
                nomorUrut++;
            });
        });
    });
    </script>
</body>
</html>