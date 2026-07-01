<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require 'koneksi.php';

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);

switch ($action) {
    case 'get_dashboard':
        $queryStok = mysqli_query($koneksi, "SELECT SUM(stok) as total_stok FROM jajanan");
        $dataStok = mysqli_fetch_assoc($queryStok);
        $totalStok = $dataStok['total_stok'] ?? 0;

        $queryMenu = mysqli_query($koneksi, "SELECT nama_jajanan, harga, stok FROM jajanan ORDER BY id ASC");
        $menu = [];
        while($row = mysqli_fetch_assoc($queryMenu)) { $menu[] = $row; }

        $queryPending = mysqli_query($koneksi, "SELECT COUNT(*) as jml FROM pesanan WHERE status = 'pending'");
        $dataPending = mysqli_fetch_assoc($queryPending);
        $totalPending = $dataPending['jml'] ?? 0;

        $queryAntrean = mysqli_query($koneksi, "SELECT id, nama_pelanggan, status, tanggal_pesanan FROM pesanan ORDER BY id DESC LIMIT 5");
        $antrean = [];
        if($queryAntrean) {
            while($p = mysqli_fetch_assoc($queryAntrean)) {
                $pesanan_id = $p['id'];
                $queryDetail = mysqli_query($koneksi, "SELECT d.id, j.nama_jajanan, d.jumlah FROM detail_pesanan d JOIN jajanan j ON d.jajanan_id = j.id WHERE d.pesanan_id = '$pesanan_id'");
                $details = [];
                if($queryDetail) { while($d = mysqli_fetch_assoc($queryDetail)) { $details[] = $d; } }
                $p['details'] = $details;
                $antrean[] = $p;
            }
        }
        echo json_encode(["success" => true, "total_stok" => (int)$totalStok, "menu" => $menu, "antrean" => $antrean, "pending" => (int)$totalPending]);
        break;

    case 'update_status':
        $pesanan_id = (int)$data['id'];
        $status_baru = mysqli_real_escape_string($koneksi, $data['status']);
        $query = mysqli_query($koneksi, "UPDATE pesanan SET status = '$status_baru' WHERE id = '$pesanan_id'");
        echo json_encode(["success" => $query]);
        break;

    case 'login':
        $username = mysqli_real_escape_string($koneksi, $data['username']);
        $password = mysqli_real_escape_string($koneksi, $data['password']);
        $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' AND password='$password'");
        if (mysqli_num_rows($query) > 0) {
            $user = mysqli_fetch_assoc($query);
            echo json_encode(["success" => true, "role" => $user['role']]);
        } else {
            echo json_encode(["success" => false, "message" => "Username salah!"]);
        }
        break;

    case 'checkout':
        $nama_pembeli = mysqli_real_escape_string($koneksi, $data['nama_pembeli']);
        $total_harga = (int)$data['total_harga'];
        $uang_pembeli = (int)$data['uang_pembeli'];
        $kembalian = (int)$data['kembalian'];
        $metode_pembayaran = mysqli_real_escape_string($koneksi, $data['metode_pembayaran']);
        $keranjang = $data['keranjang'];

        $queryPesanan = "INSERT INTO pesanan (nama_pelanggan, tanggal_pesanan, total_harga, uang_pembeli, kembalian, metode_pembayaran, status) VALUES ('$nama_pembeli', NOW(), '$total_harga', '$uang_pembeli', '$kembalian', '$metode_pembayaran', 'pending')";
        if (mysqli_query($koneksi, $queryPesanan)) {
            $pesanan_id = mysqli_insert_id($koneksi);
            $status_semua = true;
            foreach($keranjang as $item) {
                $jajanan_id = (int)$item['id'];
                $jumlah = (int)$item['qty'];
                $subtotal = (int)$item['subtotal'];
                $insertDetail = mysqli_query($koneksi, "INSERT INTO detail_pesanan (pesanan_id, jajanan_id, jumlah, subtotal) VALUES ('$pesanan_id', '$jajanan_id', '$jumlah', '$subtotal')");
                $updateStok = mysqli_query($koneksi, "UPDATE jajanan SET stok = stok - $jumlah WHERE id='$jajanan_id'");
                if(!$insertDetail || !$updateStok) { $status_semua = false; }
            }
            echo json_encode(["success" => $status_semua]);
        } else { echo json_encode(["success" => false, "message" => "Gagal membuat pesanan."]); }
        break;

    case 'get_menu':
        $query = mysqli_query($koneksi, "SELECT * FROM jajanan ORDER BY id DESC");
        $result = [];
        while($row = mysqli_fetch_assoc($query)) { $result[] = $row; }
        echo json_encode(["success" => true, "data" => $result]);
        break;

    case 'tambah_menu':
        $nama = mysqli_real_escape_string($koneksi, $data['nama_jajanan']);
        $harga_modal = (int)$data['harga_modal'];
        $harga = (int)$data['harga'];
        $stok = (int)$data['stok'];
        $query = mysqli_query($koneksi, "INSERT INTO jajanan (nama_jajanan, harga_modal, harga, stok) VALUES ('$nama', '$harga_modal', '$harga', '$stok')");
        echo json_encode(["success" => $query]);
        break;

    case 'edit_menu':
        $id = (int)$data['id'];
        $nama = mysqli_real_escape_string($koneksi, $data['nama_jajanan']);
        $harga_modal = (int)$data['harga_modal'];
        $harga = (int)$data['harga'];
        $stok = (int)$data['stok'];
        $query = mysqli_query($koneksi, "UPDATE jajanan SET nama_jajanan='$nama', harga_modal='$harga_modal', harga='$harga', stok='$stok' WHERE id='$id'");
        echo json_encode(["success" => $query]);
        break;

    case 'kosongkan_stok':
        $query = mysqli_query($koneksi, "UPDATE jajanan SET stok = 0");
        echo json_encode(["success" => $query]);
        break;

    case 'hapus_menu':
        $id = (int)$data['id'];
        $query = mysqli_query($koneksi, "DELETE FROM jajanan WHERE id='$id'");
        echo json_encode(["success" => $query]);
        break;

    // ==========================================
    // API KHUSUS LOGBOOK (Riwayat & Keuangan)
    // ==========================================
    case 'get_logbook':
        // 1. Pendapatan Kotor Hari Ini (Hanya status 'selesai')
        $qKotor = mysqli_query($koneksi, "SELECT SUM(total_harga) as kotor FROM pesanan WHERE DATE(tanggal_pesanan) = CURDATE() AND status = 'selesai'");
        $kotor = mysqli_fetch_assoc($qKotor)['kotor'] ?? 0;

        // 2. Laba Bersih Hari Ini (Harga Jual - Harga Modal)
        $qBersih = mysqli_query($koneksi, "SELECT SUM(d.subtotal - (j.harga_modal * d.jumlah)) as bersih FROM detail_pesanan d JOIN pesanan p ON d.pesanan_id = p.id JOIN jajanan j ON d.jajanan_id = j.id WHERE DATE(p.tanggal_pesanan) = CURDATE() AND p.status = 'selesai'");
        $bersih = mysqli_fetch_assoc($qBersih)['bersih'] ?? 0;

        // 3. Tarik Semua History Transaksi (Tabel)
        $qHistory = mysqli_query($koneksi, "SELECT * FROM pesanan ORDER BY id DESC");
        $history = [];
        if($qHistory){
            while($p = mysqli_fetch_assoc($qHistory)){
                $pid = $p['id'];
                // Detail untuk Struk Nota
                $qDet = mysqli_query($koneksi, "SELECT d.jumlah, j.nama_jajanan, j.harga, d.subtotal FROM detail_pesanan d JOIN jajanan j ON d.jajanan_id = j.id WHERE d.pesanan_id = '$pid'");
                $details = [];
                if($qDet){
                    while($d = mysqli_fetch_assoc($qDet)){ $details[] = $d; }
                }
                $p['details'] = $details;
                $history[] = $p;
            }
        }
        echo json_encode(["success" => true, "pendapatan_kotor" => (int)$kotor, "laba_bersih" => (int)$bersih, "history" => $history]);
        break;

    case 'reset_logbook':
        // Kosongkan riwayat pesanan (Hati-hati!)
        mysqli_query($koneksi, "DELETE FROM detail_pesanan");
        $query = mysqli_query($koneksi, "DELETE FROM pesanan");
        echo json_encode(["success" => $query]);
        break;

    default:
        echo json_encode(["error" => "Perintah tidak ditemukan"]);
        break;
}
?>