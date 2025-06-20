<?php
// Include file koneksi
include("../config/koneksi_mysql.php");

// Pastikan ID Pengajuan Upah ada
if (!isset($_GET['id_pengajuan_upah'])) {
    die("ID Pengajuan Upah tidak diberikan."); // Gunakan die() untuk menghentikan eksekusi
}
$id_pengajuan_upah = (int)$_GET['id_pengajuan_upah']; // Casting ke integer untuk keamanan

// Query utama untuk mengambil data pengajuan dan info RAB terkait
$sql_pengajuan_info = "SELECT 
                        pu.tanggal_pengajuan,
                        pu.total_pengajuan,
                        pu.status_pengajuan,
                        pu.keterangan,
                        tr.id_rab_upah,
                        tr.total_rab_upah,
                        tr.tanggal_mulai,
                        tr.tanggal_selesai,
                        CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS pekerjaan,
                        mpr.type_proyek,
                        mpe.lokasi,
                        mm.nama_mandor,
                        u.nama_lengkap AS pj_proyek -- <-- Sekarang ini bisa diambil
                    FROM pengajuan_upah pu
                    LEFT JOIN rab_upah tr ON pu.id_rab_upah = tr.id_rab_upah
                    LEFT JOIN master_proyek mpr ON tr.id_proyek = mpr.id_proyek
                    LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
                    LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
                    LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user -- <-- DITAMBAHKAN JOIN INI
                    WHERE pu.id_pengajuan_upah = $id_pengajuan_upah";

$pengajuan_result = mysqli_query($koneksi, $sql_pengajuan_info);
// Periksa jika query info pengajuan gagal
if (!$pengajuan_result) {
    die("Error query data pengajuan: " . mysqli_error($koneksi));
}
if (mysqli_num_rows($pengajuan_result) == 0) {
    die("Data Pengajuan Upah tidak ditemukan.");
}
$pengajuan_info = mysqli_fetch_assoc($pengajuan_result);


// Query detail pengajuan upah
// =================================== KESALAHAN DI SINI ===================================
// Nama tabel yang benar adalah 'detail_pengajuan_upah', bukan 'detail_pengajuan'
$sql_detail = "SELECT
                    dp.id_detail_rab_upah,
                    dp.progress_pekerjaan, 
                    dp.nilai_upah_diajukan, 
                    k.nama_kategori, 
                    mp.uraian_pekerjaan, 
                    d.sub_total 
                FROM detail_pengajuan_upah dp 
                LEFT JOIN detail_rab_upah d ON dp.id_detail_rab_upah = d.id_detail_rab_upah
                LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan 
                LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori 
                WHERE dp.id_pengajuan_upah = '$id_pengajuan_upah'
                ORDER BY k.id_kategori, mp.uraian_pekerjaan";
// =========================================================================================

$detail_result = mysqli_query($koneksi, $sql_detail);
// Tambahkan pengecekan error SETELAH query dijalankan
if (!$detail_result) {
    // Jika query gagal, hentikan eksekusi dan tampilkan pesan error dari MySQL
    die("Error query detail pengajuan: " . mysqli_error($koneksi));
}

// Query untuk mengambil bukti pekerjaan dari tabel bukti_pengajuan_upah
$sql_bukti_pekerjaan = "SELECT nama_file, path_file FROM bukti_pengajuan_upah WHERE id_pengajuan_upah = $id_pengajuan_upah";
$bukti_pekerjaan_result = mysqli_query($koneksi, $sql_bukti_pekerjaan);

// Query untuk menghitung termin pengajuan
$id_rab_upah = $pengajuan_info['id_rab_upah'];
$sql_termin = "SELECT COUNT(id_pengajuan_upah) AS termin_ke 
               FROM pengajuan_upah 
               WHERE id_rab_upah = $id_rab_upah AND id_pengajuan_upah <= $id_pengajuan_upah";
$termin_result = mysqli_query($koneksi, $sql_termin);
$termin_data = mysqli_fetch_assoc($termin_result);
$termin_ke = $termin_data['termin_ke'];

// Fungsi untuk mengubah angka menjadi Angka Romawi
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
                <h3 class="fw-bold mb-3">Detail Pengajuan Upah</h3>
                <div class="ms-auto">
                    <a href="pengajuan_upah.php" class="btn btn-secondary btn-round">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>

            <!-- [DIUBAH] Bagian Informasi Header yang lebih rapi -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Informasi Pengajuan</h4>
                    <span class="badge bg-primary fs-6">Pengajuan Termin Ke-<?= htmlspecialchars($termin_ke) ?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Kolom Kiri -->
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">ID Pengajuan</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($id_pengajuan_upah) ?></dd>

                                <dt class="col-sm-4">Pekerjaan</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['pekerjaan']) ?></dd>
                                
                                <dt class="col-sm-4">Type Proyek</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['type_proyek']) ?></dd>

                                <dt class="col-sm-4">Lokasi</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['lokasi']) ?></dd>
                                
                                <dt class="col-sm-4">Keterangan</dt>
                                <dd class="col-sm-8">: <?= !empty($pengajuan_info['keterangan']) ? htmlspecialchars($pengajuan_info['keterangan']) : '-' ?></dd>
                            </dl>
                        </div>
                        <!-- Kolom Kanan -->
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Tanggal Pengajuan</dt>
                                <dd class="col-sm-8">: <?= date('d F Y', strtotime($pengajuan_info['tanggal_pengajuan'])) ?></dd>
                                
                                <dt class="col-sm-4">Status</dt>
                                <dd class="col-sm-8">: <span class="badge bg-info"><?= ucwords(htmlspecialchars($pengajuan_info['status_pengajuan'])) ?></span></dd>
                                
                                <dt class="col-sm-4">Mandor</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['nama_mandor']) ?></dd>
                                
                                <dt class="col-sm-4">PJ Proyek</dt>
                                <dd class="col-sm-8">: <?= htmlspecialchars($pengajuan_info['pj_proyek']) ?></dd>
                                
                                <dt class="col-sm-4">Total RAB Upah</dt>
                                <dd class="col-sm-8">: <strong class="text-success">Rp <?= number_format($pengajuan_info['total_rab_upah'], 0, ',', '.') ?></strong></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Detail Pekerjaan -->
            <div class="card shadow-sm">
                <div class="card-header bg-light"><h4 class="card-title mb-0">Rincian Pekerjaan yang Diajukan</h4></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-vcenter mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%;" class="text-center">No</th>
                                    <th>Uraian Pekerjaan</th>
                                    <th style="width:15%;" class="text-center">Jumlah RAB (Rp)</th>
                                    <th style="width:15%;" class="text-center">Progress Diajukan (%)</th>
                                    <th style="width:15%;" class="text-center">Nilai Diajukan (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($detail_result && mysqli_num_rows($detail_result) > 0) {
                                    $prevKategori = null;
                                    $noKategori = 0;
                                    $noPekerjaan = 1;
                                    while ($row = mysqli_fetch_assoc($detail_result)) {
                                        if ($prevKategori !== $row['nama_kategori']) {
                                            $noKategori++;
                                            echo "<tr class='table-primary fw-bold'><td class='text-center'>" . toRoman($noKategori) . "</td><td colspan='4'>" . htmlspecialchars($row['nama_kategori']) . "</td></tr>";
                                            $prevKategori = $row['nama_kategori'];
                                            $noPekerjaan = 1;
                                        }
                                        echo "<tr>
                                                <td class='text-center'>" . $noPekerjaan++ . "</td>
                                                <td><span class='ms-3'>" . htmlspecialchars($row['uraian_pekerjaan']) . "</span></td>
                                                <td class='text-end'>" . number_format($row['sub_total'], 0, ',', '.') . "</td>
                                                <td class='text-center'>" . number_format($row['progress_pekerjaan'], 2, ',', '.') . "%</td>
                                                <td class='text-end fw-bold'>" . number_format($row['nilai_upah_diajukan'], 0, ',', '.') . "</td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>Tidak ada rincian pekerjaan untuk pengajuan ini.</td></tr>";
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr class='table-success fw-bolder'>
                                    <td colspan="4" class='text-end'>TOTAL PENGAJUAN</td>
                                    <td class='text-end'>Rp <?= number_format($pengajuan_info['total_pengajuan'], 0, ',', '.') ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
              </div>
                <!-- [DIUBAH] Layout Bukti Pembayaran & Bukti Pekerjaan -->
                <div class="row">
                <!-- Layout galeri bukti -->
                <div class="row">
                    <!-- Kolom Bukti Pembayaran -->
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4" style="min-height: 250px;">
                            <div class="card-header bg-light"><h4 class="card-title mb-0">Bukti Pembayaran</h4></div>
                            <div class="card-body">
                                <?php if (!empty($pengajuan_info['bukti_bayar'])):
                                    $path_bayar = "../" . htmlspecialchars($pengajuan_info['bukti_bayar']);
                                ?>
                                    <div class="col-12">
                                        <a href="../<?= $path_bayar ?>" target="_blank" title="Lihat Bukti Pembayaran">
                                            <img src="../<?= $path_bayar ?>" class="img-thumbnail" style="width: 100%; height: auto; max-height: 240px; object-fit: contain;" onerror="this.onerror=null;this.src='https://placehold.co/600x400/EEE/31343C?text=File Tidak Ditemukan';">
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">Tidak ada bukti pembayaran yang dilampirkan.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Kolom Bukti Pembayaran -->
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4" style="min-height: 250px;">
                            <div class="card-header bg-light"><h4 class="card-title mb-0">Bukti Pembayaran</h4></div>
                            <div class="card-body">
                                <?php if (!empty($pengajuan_info['bukti_bayar'])):
                                    // [FIXED] Path dibangun dengan benar sekali saja.
                                    $path_bayar = "../" . htmlspecialchars($pengajuan_info['bukti_bayar']);
                                ?>
                                    <div class="col-12">
                                        <a href="<?= $path_bayar ?>" target="_blank" title="Lihat Bukti Pembayaran">
                                            <img src="<?= $path_bayar ?>" class="img-thumbnail" style="width: 100%; height: auto; max-height: 240px; object-fit: contain;" onerror="this.onerror=null;this.src='https://placehold.co/600x400/EEE/31343C?text=File';">
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">Tidak ada bukti pembayaran yang dilampirkan.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

          </div>
        </div>
      </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</body>
</html>