<?php
session_start();
include("../config/koneksi_mysql.php");

if (!isset($_GET['id_rab_material'])) {
    echo "ID RAB Material tidak diberikan.";
    exit;
}

$id_rab_material = mysqli_real_escape_string($koneksi, $_GET['id_rab_material']);

// Ambil data RAB Material
$sql = "SELECT 
            CONCAT(mpe.nama_perumahan, ' - ', mpr.kavling) AS pekerjaan,
            mpe.nama_perumahan,
            mpe.lokasi,
            mpr.kavling,
            mpr.type_proyek,
            YEAR(tr.tanggal_mulai_mt) AS tahun
        FROM rab_material tr
        JOIN master_proyek mpr ON tr.id_proyek = mpr.id_proyek
        LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
        WHERE tr.id_rab_material = '$id_rab_material'";

$result = mysqli_query($koneksi, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    echo "Data RAB Material tidak ditemukan.";
    exit;
}
$data = mysqli_fetch_assoc($result);

// Ambil data detail pekerjaan
$sql_detail = "SELECT 
                 d.id_kategori,
                 d.id_pekerjaan, 
                 mp.uraian_pekerjaan, 
                 k.nama_kategori,
                 ms.nama_satuan,
                 d.volume, 
                 d.harga_satuan, 
                 d.sub_total
               FROM detail_rab_material d
               LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan
               LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori
               LEFT JOIN master_satuan ms ON mp.id_satuan = ms.id_satuan
               WHERE d.id_rab_material = '$id_rab_material'
               ORDER BY k.id_kategori, mp.uraian_pekerjaan";

$detail_result = mysqli_query($koneksi, $sql_detail);

function toRoman($num) {
    $map = [
        'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
        'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
        'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
    ];
    $result = '';
    foreach ($map as $roman => $value) {
        while ($num >= $value) {
            $result .= $roman;
            $num -= $value;
        }
    }
    return $result;
}

$path_logo = 'assets/img/logo/LOGO PT.jpg';
$type_logo = pathinfo($path_logo, PATHINFO_EXTENSION);
$data_logo = file_get_contents($path_logo);
$logo_base64 = 'data:image/' . $type_logo . ';base64,' . base64_encode($data_logo);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak RAB Material</title>
    <style>
        body { font-family: Tahoma, sans-serif; font-size: 12px; color: #000; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: auto; }
        .kop-surat { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 15px; }
        .kop-surat img { width: 100px; height: auto; margin-left: 40px; }
        .kop-surat .kop-text { text-align: center; flex-grow: 1; }
        .kop-surat h3 { font-size: 22px; font-weight: bold; margin: 0; }
        .kop-surat h2 { font-size: 18px; font-weight: bold; margin: 0; }
        .kop-surat p { font-size: 13px; margin: 0; }
        .title { text-align: center; font-size: 14px; font-weight: bold; text-decoration: underline; margin-bottom: 5px; }
        .info-proyek p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid black; padding: 5px; text-align: left; vertical-align: middle; }
        th { background-color: #e9ecef; text-align: center; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .signature-section { margin-top: 50px; width: 30%; float: right; text-align: center; }
        .signature-section .name { font-weight: bold; text-decoration: underline; margin-top: 60px; }
        .clearfix::after { content: ""; clear: both; display: table; }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="container my-4">
    <div class="kop-surat">
        <img src="<?= $logo_base64 ?>" alt="Logo Perusahaan" onerror="this.style.display='none'">
        <div class="kop-text">
            <h3>PT. HASTA BANGUN NUSANTARA</h3>
            <h2 class="tagline">General Contractor & Developer</h2>
            <p>Jalan Cakraninggrat, Kauman, Kabupaten Ponorogo, Jawa Timur 63414</p>
            <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
        </div>
    </div>
    <p class="title">RENCANA ANGGARAN BIAYA MATERIAL</p>
    <div class="info-proyek">
        <p><strong>Nama Proyek</strong> : <?= htmlspecialchars($data['nama_perumahan']) ?></p>
        <p><strong>Lokasi</strong> : <?= htmlspecialchars($data['lokasi']) ?></p>
        <p><strong>Kavling</strong> : <?= htmlspecialchars($data['kavling']) ?></p>
        <p><strong>Type</strong> : <?= htmlspecialchars($data['type_proyek']) ?></p>
        <p><strong>Tahun</strong> : <?= htmlspecialchars($data['tahun']) ?></p>
    </div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Uraian Pekerjaan</th>
                <th>Satuan</th>
                <th>Volume</th>
                <th>Harga Satuan</th>
                <th>Sub Total</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($detail_result && mysqli_num_rows($detail_result) > 0) {
            $prevKategori = null;
            $subTotal = 0;
            $grandTotal = 0;
            $noKategori = 0;
            $noPekerjaan = 1;
            while ($row = mysqli_fetch_assoc($detail_result)) {
                if ($prevKategori !== $row['nama_kategori']) {
                    if ($prevKategori !== null) {
                        echo "<tr class='fw-bold'><td colspan='5' class='text-end'>Sub Total " . htmlspecialchars($prevKategori) . "</td><td class='text-end'>Rp " . number_format($subTotal, 0, ',', '.') . "</td></tr>";
                    }
                    $noKategori++;
                    echo "<tr class='fw-bold'><td>" . toRoman($noKategori) . "</td><td colspan='5'>" . htmlspecialchars($row['nama_kategori']) . "</td></tr>";
                    $subTotal = 0;
                    $noPekerjaan = 1;
                }
                echo "<tr>
                        <td class='text-center'>" . $noPekerjaan++ . "</td>
                        <td>" . htmlspecialchars($row['uraian_pekerjaan']) . "</td>
                        <td class='text-center'>" . htmlspecialchars($row['nama_satuan']) . "</td>
                        <td class='text-end'>" . number_format($row['volume'], 2, ',', '.') . "</td>
                        <td class='text-end'>Rp " . number_format($row['harga_satuan'], 0, ',', '.') . "</td>
                        <td class='text-end'>Rp " . number_format($row['sub_total'], 0, ',', '.') . "</td>
                    </tr>";
                $subTotal += $row['sub_total'];
                $grandTotal += $row['sub_total'];
                $prevKategori = $row['nama_kategori'];
            }
            echo "<tr class='fw-bold'><td colspan='5' class='text-end'>Sub Total " . htmlspecialchars($prevKategori) . "</td><td class='text-end'>Rp " . number_format($subTotal, 0, ',', '.') . "</td></tr>";
        } else {
            echo "<tr><td colspan='6' class='text-center'>Tidak ada data pekerjaan</td></tr>";
        }
        ?>
        </tbody>
        <tfoot>
            <tr class="fw-bold">
                <td colspan="5" class="text-end">TOTAL</td>
                <td class="text-end">Rp <?= number_format($grandTotal ?? 0, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
    <div class="signature-section">
        <p>Ponorogo, <?= strftime('%d %B %Y') ?></p>
        <p>Mengetahui,</p>
        <br><br><br> <p class="name">( Ir.Purwo Hermanto )</p>
        <span>Direktur</span>
    </div>
    <div class="clearfix"></div>

</div>
<script>
    window.onload = function() {
        window.print();
    }
</script>
</body>
</html>
