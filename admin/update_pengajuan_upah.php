<?php
// FILE: update_pengajuan_upah.php (Hanya untuk menampilkan form)

// Sertakan file koneksi Anda
include("../config/koneksi_mysql.php");

// Mengatur error reporting untuk development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pastikan ID Pengajuan Upah ada dari GET request
if (!isset($_GET['id_pengajuan_upah'])) {
    die("Akses tidak sah. ID Pengajuan Upah tidak diberikan.");
}

// Ambil ID dari GET dan amankan
$id_pengajuan_upah = (int)$_GET['id_pengajuan_upah'];

//=============================================
// BLOK UNTUK MENAMPILKAN DATA (METHOD GET)
//=============================================
// Query utama untuk mengambil data header pengajuan
$sql_pengajuan = "SELECT pu.*, CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS pekerjaan, mpr.type_proyek, mpe.lokasi, mm.nama_mandor 
                  FROM pengajuan_upah pu
                  JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah
                  JOIN master_perumahan mpe ON ru.id_perumahan = mpe.id_perumahan
                  JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
                  JOIN master_mandor mm ON ru.id_mandor = mm.id_mandor
                  WHERE pu.id_pengajuan_upah = '$id_pengajuan_upah'";

$pengajuan_result = mysqli_query($koneksi, $sql_pengajuan);
if (!$pengajuan_result || mysqli_num_rows($pengajuan_result) == 0) {
    die("Data Pengajuan Upah tidak ditemukan.");
}
$pengajuan_info = mysqli_fetch_assoc($pengajuan_result);
$id_rab_upah = $pengajuan_info['id_rab_upah'];

// Hanya izinkan update jika status 'diajukan' atau 'ditolak'
if (!in_array($pengajuan_info['status_pengajuan'], ['diajukan', 'ditolak'])) {
    die("Pengajuan dengan status '" . $pengajuan_info['status_pengajuan'] . "' tidak dapat diupdate lagi.");
}

// Query untuk mendapatkan semua item dari RAB asli (untuk membangun struktur tabel)
$sql_rab_items = "SELECT d.id_detail_rab_upah, k.nama_kategori, mp.uraian_pekerjaan, d.sub_total 
                  FROM detail_rab_upah d 
                  LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan 
                  LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori 
                  WHERE d.id_rab_upah = '$id_rab_upah' 
                  ORDER BY k.id_kategori, d.id_detail_rab_upah";
$rab_items_result = mysqli_query($koneksi, $sql_rab_items);

// Ambil data progress yang sudah ada untuk pengajuan ini ke dalam array agar mudah diakses di dalam loop
$existing_progress = [];
$sql_detail_pengajuan = "SELECT id_detail_rab_upah, progress_pekerjaan FROM detail_pengajuan_upah WHERE id_pengajuan_upah = '$id_pengajuan_upah'";
$detail_pengajuan_result = mysqli_query($koneksi, $sql_detail_pengajuan);
while($row = mysqli_fetch_assoc($detail_pengajuan_result)) {
    $existing_progress[$row['id_detail_rab_upah']] = $row['progress_pekerjaan'];
}

// Fungsi untuk mengambil akumulasi progress dari pengajuan lain yang sudah disetujui/dibayar
function getProgressLalu($koneksi, $id_detail_rab_upah, $id_pengajuan_to_exclude) {
    $query = "SELECT SUM(dpu.progress_pekerjaan) AS total_progress 
              FROM detail_pengajuan_upah dpu
              JOIN pengajuan_upah pu ON dpu.id_pengajuan_upah = pu.id_pengajuan_upah
              WHERE dpu.id_detail_rab_upah = ".(int)$id_detail_rab_upah." 
                AND pu.id_pengajuan_upah != ".(int)$id_pengajuan_to_exclude."
                AND pu.status_pengajuan IN ('disetujui', 'dibayar')";
    $result = mysqli_query($koneksi, $query);
    $data = mysqli_fetch_assoc($result);
    return (float)($data['total_progress'] ?? 0);
}

function toRoman($num) {
    $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $result = '';
    foreach ($map as $roman => $value) {
        while ($num >= $value) {
            $result .= $roman; $num -= $value;
        }
    }
    return $result;
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
              <h3 class="fw-bold mb-3">Form Detail Pengajuan RAB Upah</h3>
            </div>

          <div class="container mt-4">

          <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h4>Detail dan Pengajuan Progress RAB</h4>
            </div>
<div class="card-body">
    <form method="POST" action="proses_update_pengajuan.php">
      <input type="hidden" name="id_pengajuan_upah" value="<?= htmlspecialchars($id_pengajuan_upah) ?>">
        <div class="row row-cols-1 row-cols-md-2 g-3">
            <!-- ID RAB -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">ID RAB</span>
                    <span>: <?= htmlspecialchars($pengajuan_info['id_rab_upah']) ?></span>
                </div>
            </div>

            <!-- Pekerjaan -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">Pekerjaan</span>
                    <span>: <?= htmlspecialchars($pengajuan_info['pekerjaan']) ?></span>
                </div>
            </div>

            <!-- Type Proyek -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">Type Proyek</span>
                    <span>: <?= htmlspecialchars($pengajuan_info['type_proyek']) ?></span>
                </div>
            </div>

            <!-- Lokasi -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">Lokasi</span>
                    <span>: <?= htmlspecialchars($pengajuan_info['lokasi']) ?></span>
                </div>
            </div>

<!-- Input untuk Tanggal Pengajuan -->
<div class="col-md-6">
    <div class="form-group">
        <label for="tanggal_pengajuan" class="fw-semibold">Tanggal Pengajuan</label>
        <!-- Tampilkan tanggal pengajuan yang ada pada database, tetapi user bisa menggantinya -->
        <input type="date" id="tanggal_pengajuan" name="tanggal_pengajuan" class="form-control" 
               value="<?= date('Y-m-d', strtotime($pengajuan_info['tanggal_pengajuan'])) ?>" required>
    </div>
</div>




            <!-- Mandor -->
            <div class="col">
                <div class="d-flex">
                    <span class="fw-semibold me-2" style="min-width: 120px;">Mandor</span>
                    <span>: <?= htmlspecialchars($pengajuan_info['nama_mandor']) ?></span>
                </div>
            </div>
        </div>


                                <div class="table-responsive">
                                    <table class="table table-bordered" id="tblDetailRAB">
                                        <thead>
                                            <tr>
                                                <th style="width:5%;">No</th>
                                                <th>Uraian Pekerjaan</th>
                                                <th style="width:12%;">Jumlah (Rp)</th>
                                                <th style="width:12%;">Progress Lalu (%)</th>
                                                <th style="width:10%;">Progress Diajukan (%)</th>
                                                <th style="width:12%;">Nilai Pengajuan (Rp)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            <?php
                            while ($row_rab = mysqli_fetch_assoc($rab_items_result)) {
                                $idDetailRab = $row_rab['id_detail_rab_upah'];
                                $progressLalu = getProgressLalu($koneksi, $idDetailRab, $id_pengajuan_upah);
                                $sisaProgress = 100 - $progressLalu;
                                $progressSaatIni = (float)($existing_progress[$idDetailRab] ?? 0);
                            ?>
                                <tr>
                                    <!-- Isi dengan kolom-kolom tabel seperti sebelumnya -->
                                    <td class="text-center"><?= $idDetailRab ?></td> <!-- Contoh -->
                                    <td><?= htmlspecialchars($row_rab['uraian_pekerjaan']) ?></td>
                                    <td class="text-end"><?= number_format($row_rab['sub_total'], 0, ',', '.') ?></td>
                                    <td class="text-center"><?= number_format($progressLalu, 2, ',', '.') ?>%</td>
                                    <td class="text-center">
                                        <input type="number" class="form-control form-control-sm progress-input mx-auto" style="width:90px;"
                                               data-subtotal="<?= $row_rab['sub_total'] ?>"
                                               data-id="<?= $idDetailRab ?>"
                                               name="progress[<?= $idDetailRab ?>]"
                                               min="0" max="<?= number_format($sisaProgress, 2, '.', '') ?>" step="0.01"
                                               value="<?= number_format($progressSaatIni, 2, '.', '') ?>">
                                    </td>
                                    <td class="text-end fw-bold nilai-pengajuan" data-id="<?= $idDetailRab ?>">Rp 0</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                         <tfoot>
                           <tr class='table-info fw-bold'>
                                <td colspan="5" class='text-end'>TOTAL PENGAJUAN SAAT INI</td>
                                <td id="total-pengajuan-saat-ini" class='text-end'>Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Input Nominal & Keterangan -->
                <div class="row justify-content-end mt-4">
                    <div class="col-md-4">
                        <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="2"><?= htmlspecialchars($pengajuan_info['keterangan']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="nominal-pengajuan" class="form-label fw-bold">Nominal Final yang Diajukan</label>
                        <input type="text" class="form-control form-control-lg text-end" id="nominal-pengajuan" name="nominal_pengajuan_final" 
                               value="<?= number_format($pengajuan_info['total_pengajuan'], 0, ',', '.') ?>">
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end mt-4">
                    <a href="pengajuan_upah.php" class="btn btn-secondary me-2">Kembali</a>
                    <button type="submit" id="btn-submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Update Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const tableBody = document.querySelector("#tblDetailRAB tbody");
    const totalPengajuanEl = document.getElementById('total-pengajuan-saat-ini');
    const nominalPengajuanInput = document.getElementById('nominal-pengajuan');
    const btnSubmit = document.getElementById('btn-submit');
    
    // Fungsi untuk format angka ke Rupiah
    function formatRupiah(angka, prefix = 'Rp ') {
        let number_string = Math.round(angka).toString().replace(/[^,\d]/g, ''),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return prefix + (rupiah || '0');
    }

    // Fungsi untuk mengubah format Rupiah kembali ke angka
    function unformatRupiah(rupiahStr) {
        if (!rupiahStr) return 0;
        return parseInt(String(rupiahStr).replace(/[^0-9]/g, '')) || 0;
    }

    /**
     * Menghitung nilai per baris dan total.
     * @param {boolean} updateFinalInput - Jika true, akan mengupdate juga input "Nominal Final".
     */
    function calculateTotals(updateFinalInput = false) {
        let totalPengajuan = 0;
        document.querySelectorAll('.progress-input').forEach(input => {
            const subtotal = parseFloat(input.dataset.subtotal) || 0;
            let progress = parseFloat(input.value) || 0;
            const maxProgress = parseFloat(input.max) || 100;

            if (progress > maxProgress) {
                progress = maxProgress;
                input.value = maxProgress.toFixed(2);
            }
            if (progress < 0) {
                progress = 0;
                input.value = '0.00';
            }
            
            const nilaiPengajuan = (progress / 100) * subtotal;
            const nilaiCell = document.querySelector(`.nilai-pengajuan[data-id='${input.dataset.id}']`);
            if (nilaiCell) {
                nilaiCell.textContent = formatRupiah(nilaiPengajuan);
            }
            totalPengajuan += nilaiPengajuan;
        });
        
        if(totalPengajuanEl) totalPengajuanEl.textContent = formatRupiah(totalPengajuan);
        
        if (updateFinalInput) {
            nominalPengajuanInput.value = formatRupiah(totalPengajuan, ''); // Format tanpa "Rp"
        }
        
        validateNominal();
    }
    
    function validateNominal() {
        const nominalFinal = unformatRupiah(nominalPengajuanInput.value);
        if(btnSubmit) {
            btnSubmit.disabled = nominalFinal <= 0;
        }
    }

    if(tableBody) {
        tableBody.addEventListener('input', function(event) {
            if (event.target.classList.contains('progress-input')) {
                calculateTotals(true);
            }
        });
    }

    if(nominalPengajuanInput) {
        nominalPengajuanInput.addEventListener('input', function(e) {
            const cursorPos = e.target.selectionStart;
            const originalLength = e.target.value.length;
            
            let value = unformatRupiah(e.target.value);
            e.target.value = formatRupiah(value, '');
            
            const newLength = e.target.value.length;
            e.target.setSelectionRange(cursorPos + (newLength - originalLength), cursorPos + (newLength - originalLength));
            
            validateNominal();
        });
    }

    // Panggil saat halaman dimuat: Kalkulasi nilai per baris, tapi JANGAN update nominal final (false).
    calculateTotals(false);
    
    // Jalankan validasi untuk nominal yang sudah ada dari database.
    validateNominal();
});
</script>
</body>
</html>

