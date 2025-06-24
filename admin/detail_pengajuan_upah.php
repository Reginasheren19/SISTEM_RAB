<?php
// Include file koneksi Anda
include("../config/koneksi_mysql.php");

// Pastikan ID RAB Upah ada dan aman
if (!isset($_GET['id_rab_upah'])) {
    echo "ID RAB Upah tidak diberikan.";
    exit;
}
$id_rab_upah = mysqli_real_escape_string($koneksi, $_GET['id_rab_upah']);

// [PERBAIKAN] Query utama untuk mendapatkan informasi header RAB
// JOIN diubah agar melalui master_proyek terlebih dahulu
$sql_rab = "SELECT 
                tr.id_rab_upah,
                                       tr.tanggal_mulai,
           tr.tanggal_selesai,
                CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS pekerjaan,
                mpr.type_proyek,
                u.nama_lengkap AS pj_proyek,
                mpe.lokasi,
                YEAR(tr.tanggal_mulai) AS tahun,
                mm.nama_mandor
            FROM rab_upah tr
            JOIN master_proyek mpr ON tr.id_proyek = mpr.id_proyek
            LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
            LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
                    LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
            WHERE tr.id_rab_upah = '$id_rab_upah'";

$rab_result = mysqli_query($koneksi, $sql_rab);
if (!$rab_result || mysqli_num_rows($rab_result) == 0) {
    // Jika query gagal atau tidak ada baris yang ditemukan, berikan pesan error yang lebih detail
    die("Data RAB Upah dengan ID '$id_rab_upah' tidak ditemukan atau terjadi error. Query: " . mysqli_error($koneksi));
}
$rab_info = mysqli_fetch_assoc($rab_result);

// [TAMBAHAN] Query untuk menghitung termin ke berapa pengajuan ini
$sql_termin = "SELECT COUNT(id_pengajuan_upah) AS jumlah_sebelumnya FROM pengajuan_upah WHERE id_rab_upah = $id_rab_upah";
$termin_result = mysqli_query($koneksi, $sql_termin);
$termin_data = mysqli_fetch_assoc($termin_result);
$termin_ke = ($termin_data['jumlah_sebelumnya'] ?? 0) + 1; 

// Query detail item pekerjaan pada RAB (sudah benar, tidak perlu diubah)
$sql_detail = "SELECT 
                    d.id_detail_rab_upah, 
                    k.nama_kategori, 
                    mp.uraian_pekerjaan, 
                    d.sub_total
                FROM detail_rab_upah d 
                LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan 
                LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori 
                WHERE d.id_rab_upah = '$id_rab_upah' 
                ORDER BY k.id_kategori, mp.uraian_pekerjaan";
$detail_result = mysqli_query($koneksi, $sql_detail);

// Fungsi getProgressLaluPersen (sudah benar, tidak perlu diubah)
function getProgressLaluPersen($koneksi, $id_detail_rab_upah) {
    $id_detail_rab_upah = (int)$id_detail_rab_upah;
    $query = "SELECT SUM(dp.progress_pekerjaan) AS total_progress 
              FROM detail_pengajuan_upah dp
              JOIN pengajuan_upah pu ON dp.id_pengajuan_upah = pu.id_pengajuan_upah
              WHERE dp.id_detail_rab_upah = $id_detail_rab_upah 
              AND pu.status_pengajuan IN ('diajukan', 'disetujui', 'ditolak', 'dibayar')"; 

    $result = mysqli_query($koneksi, $query);
    if (!$result) {
        echo "<div class='alert alert-danger'><b>Error Query SQL:</b> " . mysqli_error($koneksi) . "</div>";
        return 0;
    }
    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        return (float)($data['total_progress'] ?? 0);
    }
    return 0;
}

// Fungsi toRoman (sudah benar, tidak perlu diubah)
function toRoman($num) {
    $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $result = '';
    foreach ($map as $roman => $value) {
        while ($num >= $value) {
            $result .= $roman;
            $num -= $value;
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
        <style>
        .upload-card { border: 2px dashed #e0e0e0; border-radius: 0.5rem; transition: all 0.3s ease; background-color: #ffffff; }
        .upload-card.is-dragging { border-color: #0d6efd; background-color: #f0f8ff; }
        .upload-label { display: block; text-align: center; padding: 20px; cursor: pointer; }
        .upload-icon { font-size: 2.5rem; color: #adb5bd; }
        #preview-container { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; }
        .preview-item { position: relative; width: 100px; height: 100px; border-radius: 0.5rem; overflow: hidden; border: 1px solid #dee2e6; }
        .preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .preview-item .remove-btn { position: absolute; top: 5px; right: 5px; width: 22px; height: 22px; background-color: rgba(0, 0, 0, 0.6); color: white; border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity 0.3s ease; font-size: 0.75rem; }
        .preview-item:hover .remove-btn { opacity: 1; }
    </style>
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
              <h3 class="fw-bold mb-3">Form Pengajuan RAB Upah</h3>
            </div>
            
            <form method="POST" action="add_pengajuan.php" enctype="multipart/form-data">
                <input type="hidden" name="id_rab_upah" value="<?= htmlspecialchars($id_rab_upah) ?>">

                <!-- Informasi Proyek & RAB -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Informasi Proyek & RAB</h4>
                        <!-- [DITAMBAHKAN] Info Termin ke berapa -->
                        <span class="badge bg-primary fs-6">Pengajuan Termin Ke-<?= htmlspecialchars($termin_ke) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">Pekerjaan</dt><dd class="col-sm-8">: <?= htmlspecialchars($rab_info['pekerjaan']) ?></dd>
                                    <dt class="col-sm-4">Lokasi</dt><dd class="col-sm-8">: <?= htmlspecialchars($rab_info['lokasi']) ?></dd>
                                    <dt class="col-sm-4">Type Proyek</dt><dd class="col-sm-8">: <?= htmlspecialchars($rab_info['type_proyek']) ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">ID RAB</dt><dd class="col-sm-8">: <?= htmlspecialchars($rab_info['id_rab_upah']) ?></dd>
                                    <dt class="col-sm-4">Mandor</dt><dd class="col-sm-8">: <?= htmlspecialchars($rab_info['nama_mandor']) ?></dd>
                                    <dt class="col-sm-4">PJ Proyek</dt><dd class="col-sm-8">: <?= htmlspecialchars($rab_info['pj_proyek']) ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Input Progress -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light"><h4 class="card-title mb-0">Detail Input Progress Pekerjaan</h4></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-vcenter mb-0" id="tblDetailRAB">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:5%;" class="text-center">No</th>
                                        <th>Uraian Pekerjaan</th>
                                        <th style="width:12%;" class="text-center">Jumlah (Rp)</th>
                                        <th style="width:12%;" class="text-center">Progress Lalu (%)</th>
                                        <th style="width:15%;" class="text-center">Progress Saat Ini (%)</th>
                                        <th style="width:20%;" class="text-center">Nilai Pengajuan (Rp)</th>
                                    </tr>
                                </thead>
                                        <tbody>
                                            <?php
                                            $grandTotalRAB = 0;
                                            if ($detail_result && mysqli_num_rows($detail_result) > 0) {
                                                mysqli_data_seek($detail_result, 0);
                                                $prevKategori = null; $noKategori = 0; $noPekerjaan = 1;
                                                while ($row = mysqli_fetch_assoc($detail_result)) {
                                                    if ($prevKategori !== $row['nama_kategori']) {
                                                        $noKategori++;
                                                        echo "<tr class='table-primary fw-bold'><td class='text-center'>" . toRoman($noKategori) . "</td><td colspan='5'>" . htmlspecialchars($row['nama_kategori']) . "</td></tr>";
                                                        $prevKategori = $row['nama_kategori']; $noPekerjaan = 1;
                                                    }
                                                    $idDetail = $row['id_detail_rab_upah'];
                                                    $progressLalu = getProgressLaluPersen($koneksi, $idDetail);
                                                    $sisaProgress = 100 - $progressLalu;
                                                    $isLunas = $sisaProgress <= 0.001;
                                            ?>
                                                    <tr>
                                                        <td class='text-center'><?= $noPekerjaan ?></td>
                                                        <td><span class='ms-3'><?= htmlspecialchars($row['uraian_pekerjaan']) ?></span></td>
                                                        <td class='text-end'><?= number_format($row['sub_total'], 0, ',', '.') ?></td>
                                                        <td class='text-center'><?= number_format($progressLalu, 2, ',', '.') ?>%</td>
                                                        <!-- [DIUBAH] Menambahkan Checkbox Lunas -->
                                                        <td class="p-1 align-middle">
                                                            <div class="input-group">
                                                                <input type="number" class="form-control form-control-sm progress-input text-center" data-subtotal="<?= $row['sub_total'] ?>" data-id="<?= $idDetail ?>" name="progress[<?= $idDetail ?>]" min="0" max="<?= number_format($sisaProgress, 2, '.', '') ?>" step="0.01" <?= $isLunas ? 'disabled placeholder="Lunas"' : 'placeholder="0.00"' ?>>
                                                                <div class="input-group-text">
                                                                    <input class="form-check-input mt-0 lunas-checkbox" type="checkbox" title="Tandai Lunas (100% Progress)" <?= $isLunas ? 'disabled' : '' ?>>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class='text-end fw-bold nilai-pengajuan' data-id='<?= $idDetail ?>'>Rp 0</td>
                                                    </tr>
                                            <?php
                                                    $noPekerjaan++; $grandTotalRAB += $row['sub_total'];
                                                }
                                            }
                                            ?>
                                        </tbody>
                                <!-- [DIPERBAIKI] Footer Tabel Ditambahkan Kembali -->
                                <tfoot>
                                    <tr class='table-light fw-bolder'>
                                        <td colspan="5" class='text-end'>TOTAL NILAI RAB</td>
                                        <td class='text-end'>Rp <?= number_format($grandTotalRAB, 0, ',', '.') ?></td>
                                    </tr>
                                    <tr class='table-succes fw-bolder'>
                                        <td colspan="5" class='text-end'>TOTAL PENGAJUAN SAAT INI</td>
                                        <td id="total-pengajuan-saat-ini" class='text-end'>Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- [DIUBAH] Ringkasan, Bukti, & Kirim Pengajuan -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light"><h4 class="card-title mb-0">Ringkasan & Kirim Pengajuan</h4></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Upload Bukti Progress Pekerjaan</label>
                                    <div id="upload-card" class="upload-card"><label for="file-input" class="upload-label"><i class="fas fa-cloud-upload-alt upload-icon mb-2"></i><h6 class="fw-bold">Seret & lepas file di sini</h6><p class="text-muted small mb-0">atau klik untuk memilih file</p></label><input type="file" id="file-input" name="bukti_pengajuan[]" multiple accept="image/*,application/pdf" class="d-none"></div>
                                </div>
                                <div id="preview-container"></div>
                            </div>
                            <div class="col-md-5">
                                <!-- [DIUBAH] Tanggal Pengajuan dipindah ke sini -->
                                <div class="mb-3"><label for="tanggal_pengajuan" class="form-label fw-bold">Tanggal Pengajuan</label><input type="date" id="tanggal_pengajuan" name="tanggal_pengajuan" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                                <div class="mb-3"><label for="nominal-pengajuan" class="form-label fw-bold">Nominal Final Diajukan</label><input type="number" class="form-control form-control-lg text-end" id="nominal-pengajuan" name="nominal_pengajuan_final" placeholder="0"><div id="error-nominal" class="form-text text-danger d-none">Nominal tidak boleh melebihi Total Pengajuan Dihitung.</div></div>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end"><a href="pengajuan_upah.php" class="btn btn-secondary">Kembali</a><button type="submit" id="btn-submit" class="btn btn-primary" disabled><i class="fa fa-paper-plane"></i> Kirim Pengajuan</button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </form> 
          </div>
        </div>
      </div>
    </div>

    <!-- Core JS Files -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // ... Seluruh kode JavaScript Anda yang sudah benar tetap di sini ...
            const tableBody = document.querySelector("#tblDetailRAB tbody");
            const totalPengajuanEl = document.querySelectorAll('#total-pengajuan-saat-ini'); // Diubah jadi querySelectorAll
            const nominalPengajuanInput = document.getElementById('nominal-pengajuan');
            const errorNominalEl = document.getElementById('error-nominal');
            const btnSubmit = document.getElementById('btn-submit');
            const uploadCard = document.getElementById('upload-card');
            const fileInput = document.getElementById('file-input');
            const previewContainer = document.getElementById('preview-container');

            function formatRupiah(angka) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka || 0); }

            function calculateTotals() {
                let totalPengajuan = 0;
                document.querySelectorAll('.progress-input').forEach(input => {
                    if (input.disabled) return;
                    const id = input.dataset.id;
                    const subtotal = parseFloat(input.dataset.subtotal);
                    let progressDiajukan = parseFloat(input.value) || 0;
                    const maxProgress = parseFloat(input.max);
                    if (progressDiajukan > maxProgress) { progressDiajukan = maxProgress; input.value = maxProgress.toFixed(2); }
                    if (progressDiajukan < 0) { progressDiajukan = 0; input.value = '0.00'; }
                    const nilaiPengajuan = (progressDiajukan / 100) * subtotal;
                    const nilaiCell = document.querySelector(`.nilai-pengajuan[data-id='${id}']`);
                    if (nilaiCell) { nilaiCell.textContent = formatRupiah(nilaiPengajuan); }
                    totalPengajuan += nilaiPengajuan;
                });
                // Update kedua elemen dengan ID yang sama
                totalPengajuanEl.forEach(el => { el.textContent = formatRupiah(totalPengajuan); });
                nominalPengajuanInput.value = Math.round(totalPengajuan);
                validateNominal();
            }
            
            function validateNominal() {
                const totalPengajuan = parseFloat((totalPengajuanEl[0].textContent || 'Rp 0').replace(/[^0-9]/g, '')) || 0;
                const nominalFinal = parseFloat(nominalPengajuanInput.value) || 0;
                if (nominalFinal > Math.ceil(totalPengajuan)) {
                    errorNominalEl.classList.remove('d-none');
                    btnSubmit.disabled = true;
                } else {
                    errorNominalEl.classList.add('d-none');
                    btnSubmit.disabled = nominalFinal <= 0;
                }
            }

            if (tableBody) {
                tableBody.addEventListener('input', e => { if (e.target.classList.contains('progress-input')) calculateTotals(); });
                
                // [BARU] Logika untuk checkbox lunas
                tableBody.addEventListener('change', function(e) {
                    if (e.target.classList.contains('lunas-checkbox')) {
                        const tr = e.target.closest('tr');
                        const progressInput = tr.querySelector('.progress-input');
                        if (!progressInput) return;

                        const maxProgress = parseFloat(progressInput.max);
                        
                        if (e.target.checked) {
                            progressInput.value = maxProgress.toFixed(2);
                            progressInput.disabled = true;
                        } else {
                            progressInput.value = '';
                            progressInput.disabled = false;
                        }
                        calculateTotals(); // Panggil kalkulasi ulang
                    }
                });
            }            if (nominalPengajuanInput) nominalPengajuanInput.addEventListener('input', validateNominal);
            calculateTotals();

            // Logika Upload File
            if (uploadCard) {
                uploadCard.addEventListener('click', () => fileInput.click());
                uploadCard.addEventListener('dragover', e => { e.preventDefault(); uploadCard.classList.add('is-dragging'); });
                uploadCard.addEventListener('dragleave', () => uploadCard.classList.remove('is-dragging'));
                uploadCard.addEventListener('drop', e => { e.preventDefault(); uploadCard.classList.remove('is-dragging'); fileInput.files = e.dataTransfer.files; handleFiles(fileInput.files); });
                fileInput.addEventListener('change', e => handleFiles(e.target.files));
            }

            function handleFiles(files) {
                previewContainer.innerHTML = '';
                for (const file of files) {
                    if (!file.type.startsWith('image/') && !file.type.includes('pdf')) continue; // Izinkan gambar & pdf
                     // Tambahkan ikon untuk PDF
                    if (file.type.includes('pdf')) {
                         previewContainer.insertAdjacentHTML('beforeend', `<div class="preview-item d-flex flex-column align-items-center justify-content-center bg-light"><i class="fas fa-file-pdf fa-3x text-danger"></i><small class="text-muted mt-2 text-truncate" style="max-width: 90px;">${file.name}</small><button type="button" class="remove-btn" title="Hapus">&times;</button></div>`);
                    } else {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewContainer.insertAdjacentHTML('beforeend', `<div class="preview-item"><img src="${e.target.result}" alt="${file.name}"><button type="button" class="remove-btn" title="Hapus">&times;</button></div>`);
                        }
                        reader.readAsDataURL(file);
                    }
                }
            }
            
            previewContainer.addEventListener('click', function(e){
                if (e.target.closest('.remove-btn')) {
                    const previewItem = e.target.closest('.preview-item');
                    const img = previewItem.querySelector('img');
                    const fileName = img ? img.alt : previewItem.querySelector('small').textContent;
                    previewItem.remove();
                    const dt = new DataTransfer();
                    const files = fileInput.files;
                    for (let i = 0; i < files.length; i++) { if (files[i].name !== fileName) dt.items.add(files[i]); }
                    fileInput.files = dt.files;
                }
            });
        });
    </script>

</body>
</html>