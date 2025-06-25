<?php
session_start();
include("../config/koneksi_mysql.php");

// Proteksi halaman dan validasi input
if (!isset($_SESSION['id_user'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}
if (!isset($_GET['id_rab_upah'])) {
    die("ID RAB Upah tidak valid.");
}
$id_rab_upah = (int)$_GET['id_rab_upah'];

// =================================================================================
// [FUNGSI BARU] - FUNGSI UNTUK KONVERSI ANGKA MENJADI TERBILANG
// =================================================================================
function terbilang($nilai) {
    $nilai = abs($nilai);
    $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    $temp = "";
    if ($nilai < 12) {
        $temp = " " . $huruf[$nilai];
    } else if ($nilai < 20) {
        $temp = terbilang($nilai - 10) . " Belas";
    } else if ($nilai < 100) {
        $temp = terbilang($nilai / 10) . " Puluh" . terbilang($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " Seratus" . terbilang($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = terbilang($nilai / 100) . " Ratus" . terbilang($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " Seribu" . terbilang($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = terbilang($nilai / 1000) . " Ribu" . terbilang($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = terbilang($nilai / 1000000) . " Juta" . terbilang($nilai % 1000000);
    } else if ($nilai < 1000000000000) {
        $temp = terbilang($nilai / 1000000000) . " Milyar" . terbilang($nilai % 1000000000);
    } else if ($nilai < 1000000000000000) {
        $temp = terbilang($nilai / 1000000000000) . " Triliun" . terbilang($nilai % 1000000000000);
    }
    return $temp;
}

function rupiah_terbilang($nilai) {
    if ($nilai < 0) {
        $hasil = "Minus " . trim(terbilang($nilai));
    } else {
        $hasil = trim(terbilang($nilai));
    }
    return $hasil . " Rupiah";
}


// =================================================================================
// 1. PENGAMBILAN DATA
// =================================================================================

// Query Info Header (menambahkan 'type_proyek' dan 'lokasi')
$sql_rab_info = "
    SELECT 
        ru.id_rab_upah, ru.id_proyek, ru.tanggal_mulai, ru.tanggal_selesai, ru.total_rab_upah,
        mpe.nama_perumahan, mpr.kavling, mpr.type_proyek, mpe.lokasi,
        mm.nama_mandor, u.nama_lengkap AS pj_proyek
    FROM rab_upah ru
    JOIN master_proyek mpr ON ru.id_proyek = mpr.id_proyek
    LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
    LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
    LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
    WHERE ru.id_rab_upah = $id_rab_upah
";
$rab_result = mysqli_query($koneksi, $sql_rab_info);
if (!$rab_result || mysqli_num_rows($rab_result) == 0) die("Data RAB Upah tidak ditemukan.");
$rab_info = mysqli_fetch_assoc($rab_result);

// Query Detail Pekerjaan
$sql_detail = "
    SELECT 
        d.id_detail_rab_upah, k.nama_kategori, k.id_kategori, mp.uraian_pekerjaan, d.volume, 
        ms.nama_satuan, d.harga_satuan, d.sub_total
    FROM detail_rab_upah d 
    LEFT JOIN master_pekerjaan mp ON d.id_pekerjaan = mp.id_pekerjaan 
    LEFT JOIN master_kategori k ON d.id_kategori = k.id_kategori 
    LEFT JOIN master_satuan ms ON mp.id_satuan = ms.id_satuan
    WHERE d.id_rab_upah = $id_rab_upah
    ORDER BY k.id_kategori, mp.uraian_pekerjaan
";
$detail_result = mysqli_query($koneksi, $sql_detail);
if (!$detail_result) die("Gagal mengambil detail pekerjaan: " . mysqli_error($koneksi));

// =================================================================================
// 2. PROSES DATA UNTUK RINGKASAN DAN DETAIL
// =================================================================================
$rekap_data = [];
$detail_data = [];
$grand_total = 0;

if (mysqli_num_rows($detail_result) > 0) {
    while ($row = mysqli_fetch_assoc($detail_result)) {
        // Simpan semua detail untuk Halaman 2
        $detail_data[] = $row;
        
        // Proses data untuk rekapitulasi (Halaman 1)
        if (!isset($rekap_data[$row['id_kategori']])) {
            $rekap_data[$row['id_kategori']] = [
                'nama_kategori' => $row['nama_kategori'],
                'total' => 0
            ];
        }
        $rekap_data[$row['id_kategori']]['total'] += $row['sub_total'];
        $grand_total += $row['sub_total'];
    }
}

// Ambil Nama Direktur untuk TTD
$sql_direktur = "SELECT nama_lengkap FROM master_user WHERE role = 'direktur' LIMIT 1";
$nama_direktur = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_direktur))['nama_lengkap'] ?? '.....................';

// Fungsi konversi Angka Romawi
function toRoman($num) {
    $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $result = '';
    foreach ($map as $roman => $value) { while ($num >= $value) { $result .= $roman; $num -= $value; } }
    return $result;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak RAB Upah #<?= $id_rab_upah ?></title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background-color: #fff; color: #000; font-size: 11px; }
        .page { width: 190mm; min-height: 270mm; padding: 10mm; margin: 5mm auto; border: 1px solid #FFF; }
.kop-surat {
    display: flex;
    align-items: center;
    border-bottom: 3px double #000;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.kop-surat img {
    width: 100px;
    height: auto;
    margin-left: 40px; /* Geser logo ke kanan */
}

.kop-surat .kop-text {
    text-align: center;
    flex-grow: 1;
}

.kop-surat h3 {
    font-size: 22px;
    font-weight: bold;
    margin: 0;
}

.kop-surat h2 {
    font-size: 18px;
    font-weight: bold;
    margin: 0;
}

.kop-surat p {
    font-size: 14px;
    margin: 0;
}
        
        h4.report-title { text-align: center; margin-bottom: 15px; font-weight: bold; text-decoration: underline; font-size: 14px;}
        
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { border: none !important; padding: 1px 0; font-size: 12px; vertical-align: top; }
        .info-table td:nth-child(1) { width: 120px; font-weight: bold;}
        .info-table td:nth-child(2) { width: 15px; text-align: center; }

        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td { border: 1px solid black; padding: 4px; vertical-align: middle; }
        .data-table th { background-color: #E8E8E8; text-align: center; font-weight: bold; }
        .data-table .text-end { text-align: right; } 
        .data-table .text-center { text-align: center; }
        .data-table .text-start { text-align: left; }
        .data-table .fw-bold { font-weight: bold; }

        /* [PERUBAHAN] Memberi warna pada baris kategori */
        .category-row td {
            background-color:rgb(247, 255, 22); /* Warna kuning muda */
            font-weight: bold;
        }
        .total-row { background-color: #E8E8E8; font-weight: bold; }

        .terbilang-box { margin-top: 15px; font-style: italic; }
        .signature-section { margin-top: 30px; width: 100%; }
        .signature-box { text-align: center; width: 250px; float: right; }
        .signature-box .jabatan { margin-bottom: 60px; }
        .signature-box .nama { font-weight: bold; text-decoration: underline; }
        
        .clearfix { clear: both; }
                .tagline {
    font-style: italic;
}

        @media print { 
            body { margin: 0; }
            .page { border: none; margin: 0; box-shadow: none; }
            .page-break { page-break-after: always; }
            .no-print { display: none; } 
            @page { size: A4 portrait; margin: 0; } 
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="position:fixed; top:10px; right:10px; padding: 8px 12px;">Cetak</button>

    <div class="page">
<div class="kop-surat">
    <img src="assets/img/logo/LOGO PT.jpg" alt="Logo Perusahaan" onerror="this.style.display='none'">
    <div class="kop-text">
        <h3>PT. HASTA BANGUN NUSANTARA</h3>
        <h2 class ="tagline">General Contractor & Developer</h2>
                <p>Jalan Cakraninggrat, Kauman, Kabupaten Ponorogo, Jawa Timur 63414</p>
                <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
    </div>
</div>

        <h4 class="report-title">REKAPITULASI RENCANA ANGGARAN BIAYA UPAH</h4>

<table class="info-table">
    <tr>
        <td>Pekerjaan</td>
        <td>:</td>
        <td>PEMBANGUNAN UNIT PERUMAHAN <?= strtoupper(htmlspecialchars($rab_info['nama_perumahan'])) ?></td>
    </tr>
    <tr>
        <td>Lokasi</td>
        <td>:</td>
        <td><?= htmlspecialchars($rab_info['lokasi']) ?></td>
    </tr>
    <tr>
        <td>Kavling</td>
        <td>:</td>
        <td><?= htmlspecialchars($rab_info['kavling']) ?></td>
    </tr>
    <tr>
        <td>Type</td>
        <td>:</td>
        <td><?= htmlspecialchars($rab_info['type_proyek']) ?></td>
    </tr>
    <tr>
        <td>Tahun</td>
        <td>:</td>
        <td><?= date('Y', strtotime($rab_info['tanggal_mulai'])) ?></td>
    </tr>
</table>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th>Jenis Pekerjaan</th>
                    <th style="width: 30%;">Jumlah Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php $no_rekap = 0; foreach($rekap_data as $id_kat => $data): $no_rekap++; ?>
                <tr>
                    <td class="text-center"><?= toRoman($no_rekap) ?></td>
                    <td><?= htmlspecialchars($data['nama_kategori']) ?></td>
                    <td class="text-end">Rp <?= number_format($data['total'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                 <?php if (count($rekap_data) == 0): ?>
                    <tr><td colspan="3" class="text-center">Tidak ada data.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="text-end">JUMLAH</td>
                    <td class="text-end">Rp <?= number_format($grand_total, 2, ',', '.') ?></td>
                </tr>
                 <tr class="total-row">
                    <td colspan="2" class="text-end">DIBULATKAN</td>
                    <td class="text-end">Rp <?= number_format($grand_total, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="terbilang-box">
            <strong>Terbilang :</strong> <?= rupiah_terbilang($grand_total); ?>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <p>Ponorogo, <?= date('d F Y') ?></p>
                <p class="jabatan">Direktur Utama</p>
                <p class="nama"><?=(htmlspecialchars($nama_direktur)) ?></p>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>

    <div class="page-break"></div>

    <div class="page">
<div class="kop-surat">
    <img src="assets/img/logo/LOGO PT.jpg" alt="Logo Perusahaan" onerror="this.style.display='none'">
    <div class="kop-text">
        <h3>PT. HASTA BANGUN NUSANTARA</h3>
        <h2 class ="tagline">General Contractor & Developer</h2>
                <p>Jalan Cakraninggrat, Kauman, Kabupaten Ponorogo, Jawa Timur 63414</p>
                <p>Telp: (0352) 123-456 | Email: kontak@hastabangun.co.id</p>
    </div>
</div>

        <h4 class="report-title">RINCIAN RENCANA ANGGARAN BIAYA UPAH</h4>
        
<table class="info-table">
    <tr>
        <td>Pekerjaan</td>
        <td>:</td>
        <td>PEMBANGUNAN UNIT PERUMAHAN <?= strtoupper(htmlspecialchars($rab_info['nama_perumahan'])) ?></td>
    </tr>
    <tr>
        <td>Lokasi</td>
        <td>:</td>
        <td><?= htmlspecialchars($rab_info['lokasi']) ?></td>
    </tr>
    <tr>
        <td>Kavling</td>
        <td>:</td>
        <td><?= htmlspecialchars($rab_info['kavling']) ?></td>
    </tr>
    <tr>
        <td>Type</td>
        <td>:</td>
        <td><?= htmlspecialchars($rab_info['type_proyek']) ?></td>
    </tr>
    <tr>
        <td>Tahun</td>
        <td>:</td>
        <td><?= date('Y', strtotime($rab_info['tanggal_mulai'])) ?></td>
    </tr>
</table>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">NO</th>
                    <th>URAIAN</th>
                    <th style="width: 8%;">SAT</th>
                    <th style="width: 10%;">VOL</th>
                    <th style="width: 20%;">HARGA SAT</th>
                    <th style="width: 22%;">JUMLAH</th>
                </tr>
                 <tr>
                    <th>1</th>
                    <th>2</th>
                    <th>3</th>
                    <th>4</th>
                    <th>5</th>
                    <th>6</th>
                </tr>
            </thead>
<tbody>
    <?php
    // Memulai pengecekan utama: apakah ada data detail?
    if (count($detail_data) > 0):
        
        // Inisialisasi variabel untuk looping
        $prevKategori = null;
        $noKategori = 0;

        // Mulai looping untuk setiap baris data
        foreach ($detail_data as $row):

            // Logika untuk menampilkan header kategori jika berbeda dari sebelumnya
            if ($prevKategori !== $row['id_kategori']):
                if ($prevKategori !== null):
                    // Cetak sub total untuk kategori sebelumnya
                    echo "<tr class='fw-bold'><td colspan='5' class='text-end'>Sub Total</td><td class='text-end'>Rp " . number_format($rekap_data[$prevKategori]['total'], 2, ',', '.') . "</td></tr>";
                endif;
                
                // Siapkan untuk kategori baru
                $noKategori++;
                $noPekerjaan = 1;
                echo "<tr class='category-row fw-bold'><td class='text-center'>" . toRoman($noKategori) . "</td><td colspan='5'>" . htmlspecialchars($row['nama_kategori']) . "</td></tr>";
                $prevKategori = $row['id_kategori'];
            endif;
    ?>
            <tr>
                <td class="text-center"><?= $noPekerjaan++ ?></td>
                <td class="text-start"><?= htmlspecialchars($row['uraian_pekerjaan']) ?></td>
                <td class="text-center"><?= htmlspecialchars($row['nama_satuan']) ?></td>
                <td class="text-end"><?= number_format($row['volume'], 2, ',', '.') ?></td>
                <td class="text-end">Rp <?= number_format($row['harga_satuan'], 2, ',', '.') ?></td>
                <td class="text-end">Rp <?= number_format($row['sub_total'], 2, ',', '.') ?></td>
            </tr>
    <?php
        // Akhir dari looping foreach
        endforeach;

        // Setelah looping selesai, cetak sub total untuk kategori yang paling terakhir
        if ($prevKategori !== null):
            echo "<tr class='fw-bold'><td colspan='5' class='text-end'>Sub Total</td><td class='text-end'>Rp " . number_format($rekap_data[$prevKategori]['total'], 2, ',', '.') . "</td></tr>";
        endif;

    // Bagian ELSE: ini hanya akan dieksekusi jika 'if (count($detail_data) > 0)' bernilai salah
    else:
        echo "<tr><td colspan='6' class='text-center'>Belum ada detail pekerjaan.</td></tr>";
    
    // Akhir dari struktur if-else utama
    endif;
    ?>
</tbody>
            <tfoot>
                 <tr class="total-row">
                    <td colspan="5" class="text-end">TOTAL KESELURUHAN</td>
                    <td class="text-end">Rp <?= number_format($grand_total, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

         <div class="signature-section">
            <div class="signature-box" style="float:right; text-align:center; width: 33%;">
                 <p>Ponorogo, <?= date('d F Y') ?></p>
                <p class="jabatan">Disetujui Oleh,</p>
                <p class="nama"> <?=(htmlspecialchars($nama_direktur)) ?></p>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>

    <script>
    window.onload = function() {
        window.print();
    }
</script>
</body>
</html>