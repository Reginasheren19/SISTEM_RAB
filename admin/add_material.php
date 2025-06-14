<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nama_material = mysqli_real_escape_string($koneksi, $_POST['nama_material']);
    $id_satuan = (int)$_POST['id_satuan']; 
    $keterangan_material = mysqli_real_escape_string($koneksi, $_POST['keterangan_material']);

    if (empty($nama_material) || empty($id_satuan)) {
        $_SESSION['error_message'] = "Nama material dan satuan tidak boleh kosong.";
        header("Location: master_material.php");
        exit();
    }
    $koneksi->begin_transaction();

    try {
        // 2. Simpan data ke tabel master_material menggunakan Prepared Statement (lebih aman)
        $sql_master = "INSERT INTO master_material (nama_material, id_satuan, keterangan_material) VALUES (?, ?, ?)";
        $stmt_master = $koneksi->prepare($sql_master);
        $stmt_master->bind_param("sis", $nama_material, $id_satuan, $keterangan_material);
        
        // Jika gagal, hentikan proses dan batalkan
        if (!$stmt_master->execute()) {
            throw new Exception("Gagal menyimpan data material: " . $stmt_master->error);
        }

        // 3. Ambil ID dari material yang BARU saja kita buat
        $last_id_material = $koneksi->insert_id;
        $stmt_master->close();

        // 4. Buat catatan stok awal (nilai 0) untuk material baru ini di tabel stok_material
        $sql_stok = "INSERT INTO stok_material (id_material, jumlah_stok_tersedia) VALUES (?, 0.00)";
        $stmt_stok = $koneksi->prepare($sql_stok);
        $stmt_stok->bind_param("i", $last_id_material);

        // Jika gagal, hentikan proses dan batalkan
        if (!$stmt_stok->execute()) {
            throw new Exception("Gagal membuat catatan stok awal: " . $stmt_stok->error);
        }
        $stmt_stok->close();

        // 5. Jika semua proses di atas berhasil, simpan perubahan secara permanen
        $koneksi->commit();
        $_SESSION['pesan_sukses'] = "Material baru \"".htmlspecialchars($nama_material)."\" berhasil ditambahkan.";

    } catch (Exception $e) {
        // 6. Jika ada SATU saja error di dalam blok 'try', batalkan SEMUA proses yang sudah berjalan
        $koneksi->rollback();
        $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    }

} else {
    // Jika halaman ini diakses secara langsung tanpa melalui form
    $_SESSION['error_message'] = "Akses tidak sah.";
}

// 7. Redirect (arahkan) kembali ke halaman master material
$koneksi->close();
header("Location: master_material.php");
exit();

?>