<?php
session_start();
include("../config/koneksi_mysql.php");

// =============================================================================
// FILE: add_retur.php
// FUNGSI: Membuat pesanan pengganti SECARA MANUAL untuk semua item rusak
//         yang belum pernah dibuatkan pesanannya.
// =============================================================================

// 1. Validasi awal: Pastikan ada ID dan merupakan angka
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID Pembelian tidak valid.";
    header("Location: pencatatan_pembelian.php");
    exit();
}
$id_pembelian = (int)$_GET['id'];


// 2. --- [LOGIKA UTAMA] --- 
// Query cerdas untuk mencari tahu item apa saja dan berapa banyak yang perlu dibuatkan pesanan penggantinya.
$sql_cek_retur = "
    SELECT 
        rusak.id_material,
        (rusak.total_rusak - COALESCE(pengganti.total_pengganti, 0)) as perlu_diganti
    FROM 
        -- Subquery (A): Hitung total barang rusak per material untuk pembelian ini
        (SELECT id_material, SUM(jumlah_rusak) as total_rusak
         FROM log_penerimaan_material
         WHERE id_pembelian = ? AND jumlah_rusak > 0
         GROUP BY id_material) AS rusak
    LEFT JOIN
        -- Subquery (B): Hitung total barang pengganti yang sudah pernah dibuat per material
        (SELECT id_material, SUM(quantity) as total_pengganti
         FROM detail_pencatatan_pembelian
         WHERE id_pembelian = ? AND harga_satuan_pp = 0
         GROUP BY id_material) AS pengganti ON rusak.id_material = pengganti.id_material
    -- Hanya ambil yang selisihnya (perlu diganti) lebih dari 0
    HAVING perlu_diganti > 0
";

$stmt_cek = $koneksi->prepare($sql_cek_retur);
$stmt_cek->bind_param("ii", $id_pembelian, $id_pembelian);
$stmt_cek->execute();
$items_to_replace = $stmt_cek->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cek->close();

// Jika tidak ada item yang perlu diganti, kembalikan dengan pesan
if (empty($items_to_replace)) {
    $_SESSION['error_message'] = "Tidak ada item rusak yang perlu diproses untuk retur pada pembelian ini.";
    header("Location: detail_pembelian.php?id=" . $id_pembelian);
    exit();
}


// 3. Proses pembuatan pesanan pengganti dalam transaksi
$koneksi->begin_transaction();
$stmt_insert = null;

try {
    // Siapkan query untuk INSERT pesanan pengganti
    $sql_insert = "INSERT INTO detail_pencatatan_pembelian (id_pembelian, id_material, quantity, harga_satuan_pp, sub_total_pp) VALUES (?, ?, ?, 0, 0)";
    $stmt_insert = $koneksi->prepare($sql_insert);

    $total_item_dibuat = 0;
    // Loop sebanyak item yang perlu dibuatkan pesanannya
    foreach ($items_to_replace as $item) {
        $id_material = $item['id_material'];
        $quantity_pengganti = $item['perlu_diganti'];

        $stmt_insert->bind_param("iid", $id_pembelian, $id_material, $quantity_pengganti);
        $stmt_insert->execute();
        $total_item_dibuat++;
    }

    // Jika semua berhasil, simpan
    $koneksi->commit();
    $_SESSION['pesan_sukses'] = "Berhasil membuat {$total_item_dibuat} pesanan pengganti. Silakan konfirmasi penerimaannya jika barang sudah datang.";

} catch (mysqli_sql_exception $exception) {
    // Jika gagal, batalkan semua
    $koneksi->rollback();
    $_SESSION['error_message'] = "Gagal membuat pesanan pengganti. Error: " . $exception->getMessage();
} finally {
    if ($stmt_insert) $stmt_insert->close();
}


// 4. Arahkan kembali ke halaman detail untuk melihat hasilnya
$koneksi->close();
header("Location: detail_pembelian.php?id=" . $id_pembelian);
exit();
?>