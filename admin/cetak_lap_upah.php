<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Panggil autoloader dari Composer (pastikan path ini benar)
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['id_user'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// 2. Ambil semua parameter filter
$jenis_laporan = $_GET['laporan'] ?? 'tidak_dikenal';
$status_filter = $_GET['status'] ?? 'semua';
$proyek_filter = $_GET['proyek'] ?? 'semua';
$mandor_filter = $_GET['mandor'] ?? 'semua';
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');

// 3. Mulai "Output Buffering" untuk menampung output HTML
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11px; color: #000; }
        .container { width: 100%; margin: auto; }
        .kop-surat { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 10px; }
        .kop-surat .logo { width: 80px; height: auto; margin-right: 20px; }
        .kop-surat .kop-text { flex-grow: 1; text-align: center; }
        .kop-surat h1 { font-size: 22px; font-weight: bold; margin: 0; }
        .kop-surat p { font-size: 12px; margin: 2px 0 0 0; }
        .info-header { margin-bottom: 15px; font-size: 11px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; background-color: #f9f9f9; }
        .info-header-col { float: left; width: 50%; }
        .info-header::after { content: ""; display: table; clear: both; }
        h2.report-title { text-align: center; font-size: 16px; margin-bottom: 5px; text-transform: uppercase; text-decoration: underline; font-weight: bold; margin-top: 10px; }
        .filter-info { text-align: center; font-size: 11px; margin-bottom: 15px; font-style: italic; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 5px; text-align: left; vertical-align: middle; }
        th { background-color: #e9ecef; text-align: center; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .signature-section { margin-top: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <header class="kop-surat">
            <img src="../assets/img/logo/LOGO PT.jpg" alt="Logo" class="logo">
            <div class="kop-text">
                <h1>PT. HASTA BANGUN NUSANTARA</h1>
                <p>Jalan Cokroaminoto 63414 Ponorogo Jawa Timur</p>
                <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
            </div>
        </header>

        <main>
            <?php
            // Semua logika SWITCH CASE diletakkan di sini
            switch ($jenis_laporan) {
                case 'pengajuan_upah':
                    $judul_laporan = "Laporan Pengajuan Upah";
                    $sql = "SELECT pu.id_pengajuan_upah, pu.tanggal_pengajuan, pu.total_pengajuan, pu.status_pengajuan,
                                   CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek, mm.nama_mandor
                            FROM pengajuan_upah pu
                            LEFT JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah
                            LEFT JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
                            LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
                            LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
                            WHERE 1=1";
                    if ($status_filter !== 'semua') $sql .= " AND pu.status_pengajuan = '" . mysqli_real_escape_string($koneksi, $status_filter) . "'";
                    if ($proyek_filter !== 'semua') $sql .= " AND ru.id_proyek = " . (int)$proyek_filter;
                    if ($mandor_filter !== 'semua') $sql .= " AND mpr.id_mandor = " . (int)$mandor_filter;
                    if (!empty($tanggal_mulai)) $sql .= " AND pu.tanggal_pengajuan >= '" . mysqli_real_escape_string($koneksi, $tanggal_mulai) . "'";
                    if (!empty($tanggal_selesai)) $sql .= " AND pu.tanggal_pengajuan <= '" . mysqli_real_escape_string($koneksi, $tanggal_selesai) . "'";
                    $sql .= " ORDER BY pu.tanggal_pengajuan DESC";
                    $result = mysqli_query($koneksi, $sql);

                    echo "<h2 class='report-title'>$judul_laporan</h2>";
                    if (!empty($tanggal_mulai)) { echo "<p class='filter-info'>Periode: " . date('d M Y', strtotime($tanggal_mulai)) . " s/d " . date('d M Y', strtotime($tanggal_selesai)) . "</p>"; }
                    
                    echo "<table><thead><tr><th>No</th><th>ID</th><th>Proyek</th><th>Mandor</th><th>Tanggal</th><th class='text-end'>Total</th><th>Status</th></tr></thead><tbody>";
                    if ($result && mysqli_num_rows($result) > 0) {
                        $no = 1; $total_semua = 0;
                        mysqli_data_seek($result, 0); // Kembali ke awal result set
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr><td class='text-center'>{$no}</td><td class='text-center'>" . htmlspecialchars($row['id_pengajuan_upah']) . "</td><td>" . htmlspecialchars($row['nama_proyek']) . "</td><td>" . htmlspecialchars($row['nama_mandor']) . "</td><td class='text-center'>" . date("d-m-Y", strtotime($row['tanggal_pengajuan'])) . "</td><td class='text-end'>Rp " . number_format($row['total_pengajuan'], 0, ',', '.') . "</td><td class='text-center'>" . htmlspecialchars(ucwords($row['status_pengajuan'])) . "</td></tr>";
                            $no++; $total_semua += $row['total_pengajuan'];
                        }
                        echo "<tr class='fw-bold'><td colspan='5' class='text-center'>TOTAL KESELURUHAN</td><td class='text-end'>Rp " . number_format($total_semua, 0, ',', '.') . "</td><td></td></tr>";
                    } else { echo "<tr><td colspan='7' class='text-center'>Tidak ada data.</td></tr>"; }
                    echo "</tbody></table>";
                    break;

                case 'realisasi_anggaran':
                    $judul_laporan = "Laporan Realisasi Anggaran Upah";
                    $sql = "SELECT CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek, ru.total_rab_upah, (SELECT SUM(pu.total_pengajuan) FROM pengajuan_upah pu WHERE pu.id_rab_upah = ru.id_rab_upah AND pu.status_pengajuan = 'dibayar' " . (!empty($tanggal_mulai) ? "AND pu.tanggal_pengajuan >= '" . mysqli_real_escape_string($koneksi, $tanggal_mulai) . "'" : "") . " " . (!empty($tanggal_selesai) ? "AND pu.tanggal_pengajuan <= '" . mysqli_real_escape_string($koneksi, $tanggal_selesai) . "'" : "") . ") AS total_terbayar FROM master_proyek mpr LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan LEFT JOIN rab_upah ru ON mpr.id_proyek = ru.id_proyek";
                    $where_conditions = [];
                    if ($proyek_filter !== 'semua') $where_conditions[] = "mpr.id_proyek = " . (int)$proyek_filter;
                    if ($mandor_filter !== 'semua') $where_conditions[] = "mpr.id_mandor = " . (int)$mandor_filter;
                    if (!empty($where_conditions)) { $sql .= " WHERE " . implode(' AND ', $where_conditions); }
                    $sql .= " GROUP BY mpr.id_proyek ORDER BY nama_proyek ASC";
                    $result = mysqli_query($koneksi, $sql);
                    
                    echo "<h2 class='report-title'>$judul_laporan</h2>";
                    if (!empty($tanggal_mulai)) { echo "<p class='filter-info'>Periode Pembayaran: " . date('d M Y', strtotime($tanggal_mulai)) . " s/d " . date('d M Y', strtotime($tanggal_selesai)) . "</p>"; }

                    echo "<table><thead><tr><th>No</th><th>Nama Proyek</th><th class='text-end'>Anggaran</th><th class='text-end'>Terbayar</th><th class='text-end'>Sisa</th><th class='text-center'>Realisasi (%)</th></tr></thead><tbody>";
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
                    } else { echo "<tr><td colspan='6' class='text-center'>Tidak ada data.</td></tr>"; }
                    echo "</tbody></table>";
                    break;

                case 'rekapitulasi_proyek':
                    $judul_laporan = "Laporan Rekapitulasi Proyek";
                    if (empty($proyek_filter) || $proyek_filter == 'semua') die("Pilih proyek terlebih dahulu untuk laporan ini.");
                    
                    $safe_proyek_filter = (int)$proyek_filter;
                    $info_sql = "SELECT CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS nama_proyek, mm.nama_mandor FROM master_proyek mpr LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor WHERE mpr.id_proyek = $safe_proyek_filter";
                    $proyek_info = mysqli_fetch_assoc(mysqli_query($koneksi, $info_sql));

                    echo "<div class='info-header'><div class='info-header-col'><b>Proyek:</b> " . htmlspecialchars($proyek_info['nama_proyek']) . "</div><div class='info-header-col'><b>Mandor:</b> " . htmlspecialchars($proyek_info['nama_mandor']) . "</div></div>";
                    echo "<h2 class='report-title'>$judul_laporan</h2>";

                    $laporan_sql = "SELECT pu.tanggal_pengajuan, pu.total_pengajuan, pu.status_pengajuan, pu.updated_at, (SELECT COUNT(*) FROM pengajuan_upah p2 WHERE p2.id_rab_upah = ru.id_rab_upah AND p2.id_pengajuan_upah <= pu.id_pengajuan_upah) AS termin_ke FROM pengajuan_upah pu JOIN rab_upah ru ON pu.id_rab_upah = ru.id_rab_upah WHERE ru.id_proyek = $safe_proyek_filter ORDER BY pu.tanggal_pengajuan ASC";
                    $result = mysqli_query($koneksi, $laporan_sql);

                    echo "<table><thead><tr><th>No</th><th>Tgl Pengajuan</th><th class='text-center'>Termin</th><th class='text-end'>Total Diajukan</th><th class='text-center'>Status</th><th class='text-center'>Tgl Dibayar</th></tr></thead><tbody>";
                    if ($result && mysqli_num_rows($result) > 0) {
                        $no = 1; $total_semua = 0;
                        while ($row = mysqli_fetch_assoc($result)) {
                            $tanggal_dibayar = (strtolower($row['status_pengajuan']) == 'dibayar' && !empty($row['updated_at'])) ? date("d-m-Y", strtotime($row['updated_at'])) : "-";
                            echo "<tr><td class='text-center'>{$no}</td><td class='text-center'>" . date("d-m-Y", strtotime($row['tanggal_pengajuan'])) . "</td><td class='text-center'>" . $row['termin_ke'] . "</td><td class='text-end'>Rp " . number_format($row['total_pengajuan'], 0, ',', '.') . "</td><td class='text-center'>" . htmlspecialchars(ucwords($row['status_pengajuan'])) . "</td><td class='text-center'>{$tanggal_dibayar}</td></tr>";
                            $no++;
                            if (strtolower($row['status_pengajuan']) == 'dibayar') {
                                $total_semua += $row['total_pengajuan'];
                            }
                        }
                        echo "<tr class='fw-bold'><td colspan='3' class='text-center'>TOTAL DIBAYARKAN</td><td class='text-end'>Rp " . number_format($total_semua, 0, ',', '.') . "</td><td colspan='2'></td></tr>";
                    } else { echo "<tr><td colspan='6' class='text-center'>Tidak ada riwayat pengajuan.</td></tr>"; }
                    echo "</tbody></table>";
                    break;

                default:
                    echo "<h2>Jenis Laporan Tidak Dikenal</h2>";
                    break;
            }

            if (isset($result) && $result) {
                $sql_direktur = "SELECT nama_lengkap FROM master_user WHERE role = 'direktur' LIMIT 1";
                $nama_direktur = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_direktur))['nama_lengkap'] ?? '..................';
                echo '<div class="signature-section" style="width: 30%; float: right; text-align: center;">';
                echo '<p>Ponorogo, ' . strftime('%d %B %Y') . '</p>';
                echo '<p>Mengetahui,</p>';
                echo '<br><br><br>';
                echo '<p class="name">' . htmlspecialchars($nama_direktur) . '</p>';
                echo '<p style="margin-top:-60px;">Direktur</p>';
                echo '</div><div style="clear: both;"></div>';
            }
            if ($koneksi) mysqli_close($koneksi);
            ?>
        </main>
    </div>
</body>
</html>
<?php
// 4. Ambil output HTML ke variabel
$html = ob_get_contents();
ob_end_clean();

try {
    // 5. Buat instance mpdf
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-P', // A4 Portrait
        'tempDir' => __DIR__ . '/../temp' // Pastikan folder 'temp' ada dan writable
    ]);
    
    // Nama file dinamis
    $nama_file = 'Laporan-' . str_replace('_', '-', $jenis_laporan) . '-' . date('d-m-Y') . '.pdf';

    // Tulis HTML ke PDF
    $mpdf->WriteHTML($html);

    // 6. Paksa unduh PDF
    $mpdf->Output($nama_file, 'D');

} catch (\Mpdf\MpdfException $e) {
    die("Gagal membuat PDF: " . $e->getMessage());
}

exit;
?>