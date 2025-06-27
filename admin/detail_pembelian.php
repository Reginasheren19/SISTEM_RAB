<?php
// Selalu mulai dengan session_start()
session_start();
include("../config/koneksi_mysql.php");

// 1. VALIDASI (Tetap Sama)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID Pembelian tidak valid atau tidak ditemukan.";
    header("Location: pencatatan_pembelian.php");
    exit();
}
$pembelian_id = (int)$_GET['id'];

// 2. AMBIL DATA INDUK & DETAIL (Query tetap sama, kita olah datanya setelah ini)
$stmt_master = $koneksi->prepare("SELECT * FROM pencatatan_pembelian WHERE id_pembelian = ?");
$stmt_master->bind_param("i", $pembelian_id);
$stmt_master->execute();
$result_master = $stmt_master->get_result();
$pembelian = $result_master->fetch_assoc();

if (!$pembelian) { /* ... handling error ... */ }

$sql_detail = "
    SELECT dp.id_detail_pembelian, dp.id_material, dp.quantity, dp.harga_satuan_pp, dp.sub_total_pp,
           m.nama_material, s.nama_satuan
    FROM detail_pencatatan_pembelian dp
    JOIN master_material m ON dp.id_material = m.id_material
    LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan
    WHERE dp.id_pembelian = ? ORDER BY dp.harga_satuan_pp DESC
";
$stmt_detail = $koneksi->prepare($sql_detail);
$stmt_detail->bind_param("i", $pembelian_id);
$stmt_detail->execute();
$detail_items = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. AMBIL SEMUA DATA LOG & PENERIMAAN (Query tetap sama)
$sql_log = "
    SELECT log.jumlah_diterima, log.jumlah_rusak, log.catatan, log.tanggal_penerimaan,
           log.jenis_penerimaan, m.nama_material, s.nama_satuan
    FROM log_penerimaan_material log
    JOIN master_material m ON log.id_material = m.id_material
    LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan
    WHERE log.id_pembelian = ? ORDER BY log.tanggal_penerimaan DESC
";
$stmt_log = $koneksi->prepare($sql_log);
$stmt_log->bind_param("i", $pembelian_id);
$stmt_log->execute();
$result_log = $stmt_log->get_result();

$penerimaan_per_item = [];
$stmt_sum_item = $koneksi->prepare("SELECT id_detail_pembelian, SUM(jumlah_diterima) as diterima FROM log_penerimaan_material WHERE id_pembelian = ? GROUP BY id_detail_pembelian");
$stmt_sum_item->bind_param("i", $pembelian_id);
$stmt_sum_item->execute();
$result_sum_item = $stmt_sum_item->get_result();
while ($row = $result_sum_item->fetch_assoc()) {
    $penerimaan_per_item[$row['id_detail_pembelian']] = $row['diterima'];
}

// BAGIAN PERSIAPAN DATA UNTUK TABEL RINCIAN
$grouped_items = [];
foreach ($detail_items as $item) {
    $material_name = $item['nama_material'];
    if (!isset($grouped_items[$material_name])) {
        $grouped_items[$material_name] = [
            'nama_material' => $material_name, 'nama_satuan' => $item['nama_satuan'],
            'total_dipesan_asli' => 0, 'total_sub_total_asli' => 0,
            'total_diterima' => 0, 'detail_ids' => []
        ];
    }
    $grouped_items[$material_name]['detail_ids'][] = $item['id_detail_pembelian'];
    if ((float)$item['harga_satuan_pp'] > 0) {
        $grouped_items[$material_name]['total_dipesan_asli'] += $item['quantity'];
        $grouped_items[$material_name]['total_sub_total_asli'] += $item['sub_total_pp'];
    }
}
foreach ($grouped_items as $material_name => &$group_data) {
    $total_diterima_grup = 0;
    foreach ($group_data['detail_ids'] as $detail_id) {
        $total_diterima_grup += $penerimaan_per_item[$detail_id] ?? 0;
    }
    $group_data['total_diterima'] = $total_diterima_grup;
}
unset($group_data);

// --- [BARU] --- HITUNG ULANG GRAND TOTAL UNTUK STATUS HEADER ---
$total_dipesan = 0;
$total_diterima = 0;
// Kita hitung total pesanan dari SEMUA item (asli + pengganti)
$stmt_total_pesanan_all = $koneksi->prepare("SELECT SUM(quantity) as total FROM detail_pencatatan_pembelian WHERE id_pembelian = ?");
$stmt_total_pesanan_all->bind_param("i", $pembelian_id);
$stmt_total_pesanan_all->execute();
$total_dipesan = $stmt_total_pesanan_all->get_result()->fetch_assoc()['total'] ?? 0;
// Kita hitung total diterima dari SEMUA log
foreach ($grouped_items as $group) {
    $total_diterima += $group['total_diterima'];
}


// Logika untuk tombol retur (tetap sama)
$stmt_total_rusak = $koneksi->prepare("SELECT SUM(jumlah_rusak) as total FROM log_penerimaan_material WHERE id_pembelian = ?");
$stmt_total_rusak->bind_param("i", $pembelian_id);
$stmt_total_rusak->execute();
$total_rusak_dilaporkan = $stmt_total_rusak->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_total_retur = $koneksi->prepare("SELECT SUM(quantity) as total FROM detail_pencatatan_pembelian WHERE id_pembelian = ? AND harga_satuan_pp = 0");
$stmt_total_retur->bind_param("i", $pembelian_id);
$stmt_total_retur->execute();
$total_sudah_diretur = $stmt_total_retur->get_result()->fetch_assoc()['total'] ?? 0;
$perlu_proses_retur = $total_rusak_dilaporkan > $total_sudah_diretur;

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Detail Pembelian</title>
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
            <h3 class="fw-bold mb-3">Detail Pembelian</h3>
            <ul class="breadcrumbs mb-3">
                <li class="nav-home"><a href="dashboard.php"><i class="icon-home"></i></a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="#">Detail Pembelian</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="">Pencatatan Pembelian Material</a></li>
            </ul>
        </div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Informasi Transaksi</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>ID Pembelian:</strong> <?= 'PB' . htmlspecialchars($pembelian['id_pembelian']) . date('Y', strtotime($pembelian['tanggal_pembelian'])) ?></p>
                <p><strong>Tanggal:</strong> <?= date("d F Y", strtotime(htmlspecialchars($pembelian['tanggal_pembelian']))) ?></p>
                <p><strong>Keterangan:</strong> <?= htmlspecialchars($pembelian['keterangan_pembelian']) ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> 
                    <?php
                        // Sekarang variabel $total_dipesan dan $total_diterima sudah ada lagi
                        $status_text = 'Baru';
                        $badge_class = 'bg-info';
                        if ($total_diterima <= 0 && $total_dipesan > 0) {
                            $status_text = 'Menunggu Penerimaan';
                            $badge_class = 'bg-warning';
                        } elseif ($total_diterima < $total_dipesan) {
                            $status_text = 'Diterima Sebagian';
                            $badge_class = 'bg-primary';
                        } elseif ($total_diterima >= $total_dipesan) {
                            $status_text = 'Selesai';
                            $badge_class = 'bg-success';
                        }
                    ?>
                    <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                </p>
                <p><strong>Bukti Pembayaran:</strong> 
                    <?php if (!empty($pembelian['bukti_pembayaran'])): ?>
                        <a href="../uploads/bukti_pembayaran/<?= htmlspecialchars($pembelian['bukti_pembayaran']) ?>" target="_blank">Lihat Bukti</a>
                    <?php else: ?>
                        <span class="text-muted"><em>Tidak ada</em></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($perlu_proses_retur): ?>
        <div class="alert alert-warning mt-3">
            <h5 class="alert-heading">Tindakan Diperlukan!</h5>
            <p>Terdapat barang yang dilaporkan rusak atau tidak sesuai oleh PJ Proyek. Silakan proses retur untuk memesan barang pengganti.</p>
            <hr>
            <a href="add_retur.php?id=<?= $pembelian_id ?>" class="btn btn-warning" onclick="return confirm('Anda yakin ingin memproses retur dan membuat pesanan pengganti untuk item yang rusak?')">
                <i class="fa fa-sync-alt"></i> Proses Retur & Pesan Pengganti
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title">Rincian Material Dipesan</h4></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Material</th>
                        <th class="text-end">Jumlah Dipesan (Asli)</th>
                        <th class="text-end">Total Diterima Baik</th>
                        <th class="text-end">Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $nomor = 1;
                    if (!empty($grouped_items)):
                        foreach ($grouped_items as $item):
                    ?>
                        <tr>
                            <td><?= $nomor++ ?></td>
                            <td><?= htmlspecialchars($item['nama_material']) ?></td>
                            <td class="text-end"><?= number_format($item['total_dipesan_asli'], 2, ',', '.') ?> <?= htmlspecialchars($item['nama_satuan']) ?></td>
                            <td class="text-end fw-bold <?= ($item['total_diterima'] < $item['total_dipesan_asli']) ? 'text-warning' : 'text-success' ?>">
                                <?= number_format($item['total_diterima'], 2, ',', '.') ?>
                            </td>
                            <td class="text-end">Rp <?= number_format($item['total_sub_total_asli'], 0, ',', '.') ?></td>
                        </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <tr><td colspan="5" class="text-center">Belum ada detail material.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end fw-bold">Grand Total Pembelian</th>
                        <th class="text-end fw-bold">Rp <?= number_format($pembelian['total_biaya'], 0, ',', '.') ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title">Riwayat Penerimaan Barang (Log)</h4></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Material</th>
                        <th>Jenis</th>
                        <th class="text-end">Diterima Baik</th>
                        <th class="text-end">Rusak</th>
                        <th>Catatan dari PJ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_log->num_rows > 0):
                        $result_log->data_seek(0);
                        while ($log_entry = $result_log->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= date("d M Y, H:i", strtotime($log_entry['tanggal_penerimaan'])) ?></td>
                            <td><?= htmlspecialchars($log_entry['nama_material']) ?></td>
                            <td>
                                <?php 
                                    $jenis_badge = $log_entry['jenis_penerimaan'] == 'Penerimaan Awal' ? 'bg-info' : 'bg-secondary';
                                ?>
                                <span class="badge <?= $jenis_badge ?>"><?= htmlspecialchars($log_entry['jenis_penerimaan']) ?></span>
                            </td>
                            <td class="text-end text-success"><?= number_format($log_entry['jumlah_diterima'], 2, ',', '.') ?> <?= htmlspecialchars($log_entry['nama_satuan']) ?></td>
                            <td class="text-end text-danger"><?= number_format($log_entry['jumlah_rusak'], 2, ',', '.') ?> <?= htmlspecialchars($log_entry['nama_satuan']) ?></td>
                            <td><?= htmlspecialchars($log_entry['catatan']) ?></td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr><td colspan="6" class="text-center"><em>Belum ada riwayat penerimaan barang untuk pembelian ini.</em></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="text-end mt-3 mb-3">
    <a href="pencatatan_pembelian.php" class="btn btn-secondary">Kembali ke Daftar</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

</body>
</html>