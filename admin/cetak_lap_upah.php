<?php
session_start();
include("../config/koneksi_mysql.php");

// Ambil data session pengguna
$logged_in_user_id = $_SESSION['id_user'] ?? 0;
$user_role = strtolower($_SESSION['role'] ?? 'guest');

if ($logged_in_user_id === 0) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// Ambil semua parameter filter dari URL
$jenis_laporan = $_GET['laporan'] ?? 'tidak_dikenal';
$status_filter = $_GET['status'] ?? 'semua';
$proyek_filter = $_GET['proyek'] ?? 'semua';
$mandor_filter = $_GET['mandor'] ?? 'semua';
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');
// GANTI FUNGSI LAMA DI cetak_lap_upah.php DENGAN YANG INI

// Fungsi untuk menampilkan header detail proyek
function tampilkan_header_detail($koneksi, $id_proyek) {
    // Query ini sudah benar dan mengambil semua info yang dibutuhkan
    $query_proyek = "SELECT 
                        mpe.nama_perumahan, mpr.kavling, mm.nama_mandor, 
                        u.nama_lengkap AS pj_proyek, ru.total_rab_upah
                    FROM master_proyek mpr
                    LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
                    LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
                    LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
                    LEFT JOIN rab_upah ru ON mpr.id_proyek = ru.id_proyek
                    WHERE mpr.id_proyek = " . (int)$id_proyek;

    $result_proyek = mysqli_query($koneksi, $query_proyek);
    $info_proyek = mysqli_fetch_assoc($result_proyek);

    if ($info_proyek) {
        // JUDUL UTAMA LAPORAN
        echo "<h2 class='report-main-title'>REKAPITULASI PROYEK</h2>";

        // STRUKTUR UTAMA DUA KOLOM MENGGUNAKAN TABEL TAK TERLIHAT
        echo "<table class='header-container'><tbody><tr>";

        // === KOLOM KIRI ===
        echo "<td class='header-column'>";
            echo "<table>";
            echo "<tr><td class='key'>Nama Perumahan</td><td class='colon'>:</td><td class='value'>" . htmlspecialchars($info_proyek['nama_perumahan']) . "</td></tr>";
            echo "<tr><td class='key'>Kavling / Blok</td><td class='colon'>:</td><td class='value'>" . htmlspecialchars($info_proyek['kavling']) . "</td></tr>";
            echo "<tr><td class='key'>PJ Proyek</td><td class='colon'>:</td><td class='value'>" . htmlspecialchars($info_proyek['pj_proyek']) . "</td></tr>";
            echo "</table>";
        echo "</td>";

        // === KOLOM KANAN ===
        echo "<td class='header-column'>";
            echo "<table>";
            echo "<tr><td class='key'>Tanggal Cetak</td><td class='colon'>:</td><td class='value'>" . strftime('%d %B %Y') . "</td></tr>";
            echo "<tr><td class='key'>Mandor</td><td class='colon'>:</td><td class='value'>" . htmlspecialchars($info_proyek['nama_mandor']) . "</td></tr>";
            echo "<tr><td class='key'>Total Anggaran</td><td class='colon'>:</td><td class='value'>Rp " . number_format($info_proyek['total_rab_upah'] ?? 0, 0, ',', '.') . "</td></tr>";
            echo "</table>";
        echo "</td>";

        echo "</tr></tbody></table>";;
    }
    $GLOBALS['data_proyek_info'] = $info_proyek;

}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12px; color: #000; }
        .container { max-width: 800px; margin: auto; }
        .kop-surat { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
        .kop-surat img { width: 100px; height: auto; margin-right: 20px; }
        .kop-surat .kop-text { text-align: center; flex-grow: 1; }
        .kop-surat h3, .kop-surat p { margin: 0; }
        .kop-surat h3 { font-size: 24px; font-weight: bold; }
        .kop-surat p { font-size: 14px; }
        
    /* [BARU] Pengaturan Header Informasi Proyek */
    .report-main-title { text-align: center; font-size: 14pt; font-weight: bold; text-decoration: underline; margin: 20px 0; }
    .header-container, .header-container tr, .header-container td {
        border: none !important; /* WAJIB! Menghapus semua border */
        padding: 0;
        vertical-align: top;
    }
    .header-column table {
        border: none !important; /* Menghapus border tabel di dalam kolom */
        width: 100%;
    }
    .header-column table td {
        border: none !important; /* Menghapus border sel */
        padding: 2px;
    }
    td.key { font-weight: bold; width: 120px; }
    td.colon { width: 10px; }
    td.value { width: auto; }
    .separator-line { border: 0; border-top: 1px solid #000; margin: 15px 0; }
        .filter-info { text-align: center; font-size: 12px; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 6px; text-align: left; vertical-align: middle; }
        th { background-color: #e9ecef !important; text-align: center; }
        
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .signature-section { margin-top: 40px; }
        .signature-section p { margin-bottom: 60px; }
        .signature-section .name { font-weight: bold; text-decoration: underline; }
        .clearfix { clear: both; }

        @media print {
            body { -webkit-print-color-adjust: exact; } /* Memastikan warna background tercetak */
            .no-print { display: none; }

            /* [BARU] Mengatur halaman menjadi A4 Portrait */
            @page {
                size: A4 portrait;
                margin: 20mm;
            }
        }
    </style>
</head>
<body>

<div class="container my-4">
        <div class="kop-surat">
            <img src="assets/img/logo/LOGO PT.jpg" alt="Logo Perusahaan" onerror="this.style.display='none'">
            <div class="kop-text">
                <h3>PT. HASTA BANGUN NUSANTARA</h3>
                <p>Jalan Cokroaminoto 63414 Ponorogo Jawa Timur</p>
                <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
            </div>
        </div>
    </header>

    <main>
        <?php
        $judul_laporan = "";
        $sql = "";
        $result = null;

        switch ($jenis_laporan) {
            case 'pengajuan_upah':
                $judul_laporan = "Laporan Pengajuan Upah";
                $sql = "SELECT pu.id_pengajuan_upah, pu.tanggal_pengajuan, pu.total_pengajuan, pu.status_pengajuan, CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek, mm.nama_mandor FROM pengajuan_upah pu LEFT JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah INNER JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor WHERE 1=1";
                if ($status_filter !== 'semua') $sql .= " AND pu.status_pengajuan = '" . mysqli_real_escape_string($koneksi, $status_filter) . "'";
                if ($proyek_filter !== 'semua') $sql .= " AND ru.id_proyek = " . (int)$proyek_filter;
                if ($mandor_filter !== 'semua') $sql .= " AND mpr.id_mandor = " . (int)$mandor_filter;
                if (!empty($tanggal_mulai)) $sql .= " AND pu.tanggal_pengajuan >= '" . mysqli_real_escape_string($koneksi, $tanggal_mulai) . "'";
                if (!empty($tanggal_selesai)) $sql .= " AND pu.tanggal_pengajuan <= '" . mysqli_real_escape_string($koneksi, $tanggal_selesai) . "'";
                $sql .= " ORDER BY pu.tanggal_pengajuan DESC";
                $result = mysqli_query($koneksi, $sql);
                echo "<h2 class='report-title'>$judul_laporan</h2>";
                if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) { echo "<p class='filter-info'>Periode: " . date('d M Y', strtotime($tanggal_mulai)) . " s/d " . date('d M Y', strtotime($tanggal_selesai)) . "</p>"; }
                echo "<table><thead><tr><th>No</th><th>ID</th><th>Proyek</th><th>Mandor</th><th>Tanggal</th><th class='text-end'>Total</th><th>Status</th></tr></thead><tbody>";
                if ($result && mysqli_num_rows($result) > 0) {
                    $no = 1; $total_semua = 0;
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr><td class='text-center'>{$no}</td><td>" . htmlspecialchars($row['id_pengajuan_upah']) . "</td><td>" . htmlspecialchars($row['nama_proyek']) . "</td><td>" . htmlspecialchars($row['nama_mandor']) . "</td><td class='text-center'>" . date("d-m-Y", strtotime($row['tanggal_pengajuan'])) . "</td><td class='text-end'>Rp " . number_format($row['total_pengajuan'], 0, ',', '.') . "</td><td class='text-center'>" . htmlspecialchars(ucwords($row['status_pengajuan'])) . "</td></tr>";
                        $no++; $total_semua += $row['total_pengajuan'];
                    }
                    echo "<tr class='fw-bold'><td colspan='5' class='text-center'>TOTAL KESELURUHAN</td><td class='text-end'>Rp " . number_format($total_semua, 0, ',', '.') . "</td><td></td></tr>";
                } else { echo "<tr><td colspan='7' class='text-center'>Tidak ada data.</td></tr>"; }
                echo "</tbody></table>";
                break;

            case 'realisasi_anggaran':
                $judul_laporan = "Laporan Realisasi Anggaran Upah";
                    // [PERBAIKAN UTAMA DI SINI] Menggunakan INNER JOIN agar sama dengan halaman web
                    $sql = "SELECT CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek, ru.total_rab_upah, (SELECT SUM(pu.total_pengajuan) FROM pengajuan_upah pu WHERE pu.id_rab_upah = ru.id_rab_upah AND pu.status_pengajuan = 'dibayar' " . (!empty($tanggal_mulai) ? "AND pu.tanggal_pengajuan >= '" . mysqli_real_escape_string($koneksi, $tanggal_mulai) . "'" : "") . " " . (!empty($tanggal_selesai) ? "AND pu.tanggal_pengajuan <= '" . mysqli_real_escape_string($koneksi, $tanggal_selesai) . "'" : "") . ") AS total_terbayar 
                            FROM master_proyek mpr 
                            LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan 
                            INNER JOIN rab_upah ru ON mpr.id_proyek = ru.id_proyek"; // <-- PERUBAHAN KUNCI
                    
                    $where_conditions = [];
                    if ($proyek_filter !== 'semua') {
                        $where_conditions[] = "mpr.id_proyek = " . (int)$proyek_filter;
                    }
                    if ($mandor_filter !== 'semua') {
                        $where_conditions[] = "mpr.id_mandor = " . (int)$mandor_filter;
                    }

                    if (!empty($where_conditions)) {
                        $sql .= " WHERE " . implode(' AND ', $where_conditions);
                    }
                    
                    $sql .= " GROUP BY mpr.id_proyek ORDER BY nama_proyek ASC";
                    $result = mysqli_query($koneksi, $sql);
                    
                    echo "<h2 class='report-title'>Laporan Realisasi Anggaran Upah</h2>";
                    if (!empty($tanggal_mulai)) { echo "<p class='filter-info'>Periode Pembayaran: " . date('d M Y', strtotime($tanggal_mulai)) . " s/d " . date('d M Y', strtotime($tanggal_selesai)) . "</p>"; }

                    echo "<table class='report-data'><thead><tr><th>No</th><th>Nama Proyek</th><th class='text-center'>Anggaran</th><th class='text-center'>Terbayar</th><th class='text-c'>Sisa</th><th class='text-center'>Realisasi (%)</th></tr></thead><tbody>";
                    if ($result && mysqli_num_rows($result) > 0) {
                        $no = 1; $total_rab_semua = 0; $total_terbayar_semua = 0; $total_sisa_semua = 0;
                        while ($row = mysqli_fetch_assoc($result)) {
                            $total_rab = (float)($row['total_rab_upah'] ?? 0);
                            $total_terbayar = (float)($row['total_terbayar'] ?? 0);
                            $sisa_anggaran = $total_rab - $total_terbayar;
                            $realisasi_persen = ($total_rab > 0) ? ($total_terbayar / $total_rab) * 100 : 0;
                            echo "<tr><td class='text-center'>{$no}</td><td>" . htmlspecialchars($row['nama_proyek']) . "</td><td class='text-end'>Rp " . number_format($total_rab, 0, ',', '.') . "</td><td class='text-end'>Rp " . number_format($total_terbayar, 0, ',', '.') . "</td><td class='text-end'>Rp " . number_format($sisa_anggaran, 0, ',', '.') . "</td><td class='text-center'>" . number_format($realisasi_persen, 2) . "%</td></tr>";
                            $no++; $total_rab_semua += $total_rab; $total_terbayar_semua += $total_terbayar; $total_sisa_semua += $sisa_anggaran;
                        }
                        echo "<tr class='fw-bold'><td colspan='2' class='text-center'>TOTAL</td><td class='text-end'>Rp " . number_format($total_rab_semua, 0, ',', '.') . "</td><td class='text-end'>Rp " . number_format($total_terbayar_semua, 0, ',', '.') . "</td><td class='text-end'>Rp " . number_format($total_sisa_semua, 0, ',', '.') . "</td><td></td></tr>";
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>Tidak ada data yang sesuai.</td></tr>";
                    }
                    echo "</tbody></table>";
                    break;

            case 'rekapitulasi_proyek':
                if (empty($proyek_filter)) { die("Pilih proyek terlebih dahulu."); }
                $safe_proyek_filter = (int)$proyek_filter;

                // Memanggil fungsi header yang cerdas dan lengkap
                tampilkan_header_detail($koneksi, $safe_proyek_filter);

                $laporan_sql = "SELECT pu.tanggal_pengajuan, pu.total_pengajuan, pu.status_pengajuan, pu.updated_at, (SELECT COUNT(*) + 1 FROM pengajuan_upah p2 WHERE p2.id_rab_upah = ru.id_rab_upah AND p2.id_pengajuan_upah < pu.id_pengajuan_upah) AS termin_ke FROM pengajuan_upah pu JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah WHERE ru.id_proyek = $safe_proyek_filter ORDER BY pu.tanggal_pengajuan ASC";
                $result = mysqli_query($koneksi, $laporan_sql);

                echo "<table><thead><tr><th>No</th><th class='text-center'>Termin Ke-</th><th>Tanggal Pengajuan</th><th class='text-end'>Total Pengajuan</th><th class='text-center'>Status</th><th class='text-center'>Tanggal Dibayar</th></tr></thead><tbody>";
                if ($result && mysqli_num_rows($result) > 0) {
                    $no = 1; $total_dibayar = 0;
                    while ($row = mysqli_fetch_assoc($result)) {
                        $tanggal_dibayar = (strtolower($row['status_pengajuan']) == 'dibayar' && !empty($row['updated_at'])) ? date("d-m-Y", strtotime($row['updated_at'])) : "-";
                        echo "<tr><td class='text-center'>{$no}</td><td class='text-center'>" . htmlspecialchars($row['termin_ke']) . "</td><td class='text-center'>" . date("d-m-Y", strtotime($row['tanggal_pengajuan'])) . "</td><td class='text-end'>Rp " . number_format($row['total_pengajuan'], 0, ',', '.') . "</td><td class='text-center'>" . htmlspecialchars(ucwords($row['status_pengajuan'])) . "</td><td class='text-center'>{$tanggal_dibayar}</td></tr>";
                        $no++;
                        if (strtolower($row['status_pengajuan']) == 'dibayar') { $total_dibayar += $row['total_pengajuan']; }
                    }
                    echo "<tr class='fw-bold'><td colspan='3' class='text-center'>TOTAL DIBAYARKAN</td><td class='text-end'>Rp " . number_format($total_dibayar, 0, ',', '.') . "</td><td colspan='2'></td></tr>";
                } else {
                    echo "<tr><td colspan='6' class='text-center'>Tidak ada data pengajuan untuk proyek ini.</td></tr>";
                }
                echo "</tbody></table>";
                ?>
                <?php
                break;

            default:
                echo "<h2>Jenis Laporan Tidak Dikenal</h2>";
                break;
        }

if ($result) {
    $sql_direktur = "SELECT nama_lengkap FROM master_user WHERE role = 'direktur' LIMIT 1";
    $nama_direktur = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_direktur))['nama_lengkap'] ?? '..................';

    // Ambil info PJ Proyek jika laporan rekapitulasi
    $pj_proyek = ($jenis_laporan === 'rekapitulasi_proyek' && isset($GLOBALS['data_proyek_info']['pj_proyek']))
        ? $GLOBALS['data_proyek_info']['pj_proyek']
        : null;
?>
    <div class="signature-section">
        <!-- Tanda tangan Direktur -->
        <div style="width: 25%; float: right; text-align: center;">
            <p style="margin-bottom: 5px;">Ponorogo, <?= strftime('%d %B %Y') ?></p>
            <p style="margin-bottom: 40px;">Mengetahui,</p>
            <br><br>
            <p class="name" style="margin-bottom: 2px;">Ir. <?= htmlspecialchars($nama_direktur) ?></p>
            <p style="margin-top: 0;">Direktur Utama</p>
        </div>

        <?php if ($pj_proyek): ?>
        <!-- Tanda tangan PJ Proyek -->
        <div style="width: 25%; float: left; text-align: center;">
            <p style="margin-bottom: 5px;">&nbsp;</p>
            <p style="margin-bottom: 40px;">Dibuat Oleh,</p>
            <br><br>
            <p class="name" style="margin-bottom: 2px;"><?= htmlspecialchars($pj_proyek) ?></p>
            <p style="margin-top: 0;">PJ Proyek</p>
        </div>
        <?php endif; ?>

        <div class="clearfix"></div>
    </div>
<?php
}

        if ($koneksi) mysqli_close($koneksi);
        ?>
    </main>
</div>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>