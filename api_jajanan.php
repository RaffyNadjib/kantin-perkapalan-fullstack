<?php
// Izinkan akses dari aplikasi mobile (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require 'koneksi.php';

// Ambil data menu jajanan yang stoknya masih ada
$query = $koneksi->query("SELECT * FROM jajanan WHERE stok > 0 ORDER BY nama_jajanan ASC");

$data_kargo = array();
while($row = $query->fetch_assoc()) {
    $data_kargo[] = $row;
}

// Ubah wujud data dari tabel MySQL menjadi format JSON
echo json_encode([
    "success" => true,
    "message" => "Data kargo berhasil diambil",
    "data" => $data_kargo
]);
?>