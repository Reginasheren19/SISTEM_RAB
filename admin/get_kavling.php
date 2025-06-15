<?php
// get_kavling.php
include("../config/koneksi_mysql.php");

if (isset($_POST['id_perumahan'])) {
    $id_perumahan = mysqli_real_escape_string($koneksi, $_POST['id_perumahan']);

    $sql = "SELECT 
                mpr.id_proyek, 
                mpr.kavling, 
                mpr.type_proyek, 
                mpe.lokasi,
                mm.nama_mandor,
                u.nama_lengkap AS pj_proyek
            FROM master_proyek mpr
            LEFT JOIN master_perumahan mpe ON mpr.id_perumahan = mpe.id_perumahan
            LEFT JOIN master_mandor mm ON mpr.id_mandor = mm.id_mandor
            LEFT JOIN master_user u ON mpr.id_user_pj = u.id_user
            WHERE mpr.id_perumahan = '$id_perumahan'
            ORDER BY mpr.kavling ASC";
    $result = mysqli_query($koneksi, $sql);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

?>