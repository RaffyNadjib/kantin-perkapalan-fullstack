<?php
session_start();
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_pelanggan = $_POST['nama_pelanggan'];
    
    // Cek apakah ada jajanan yang dipilih
    if (!isset($_POST['jajanan_id']) || empty($_POST['jajanan_id'])) {
        die("Pilih minimal 1 jajanan! <a href='kasir.php'>Kembali</a>");
    }

    $jajanan_dipilih = $_POST['jajanan_id']; 
    $jumlah_dibeli   = $_POST['jumlah'];     

    // 1. Hitung Total Harga Keseluruhan
    $total_harga_semua = 0;
    foreach ($jajanan_dipilih as $id_jajanan) {
        $qty = $jumlah_dibeli[$id_jajanan];
        $query = $koneksi->query("SELECT harga FROM jajanan WHERE id = '$id_jajanan'");
        $harga = $query->fetch_assoc()['harga'];
        $total_harga_semua += ($harga * $qty);
    }

    // Tangkap data pembayaran baru
    $uang_pembeli = $_POST['uang_pembeli'] ?? 0;
    $metode_pembayaran = $_POST['metode_pembayaran'] ?? 'Cash';
    $kembalian = $uang_pembeli - $total_harga_semua;

    // 2. Insert ke Tabel Pesanan
    $stmt = $koneksi->prepare("INSERT INTO pesanan (nama_pelanggan, total_harga, uang_pembeli, kembalian, metode_pembayaran, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("sddds", $nama_pelanggan, $total_harga_semua, $uang_pembeli, $kembalian, $metode_pembayaran);
    $stmt->execute();
    
    $pesanan_id = $koneksi->insert_id; 

    // 3. Insert ke Tabel detail_pesanan dan Update Stok Jajanan
    foreach ($jajanan_dipilih as $id_jajanan) {
        $qty = $jumlah_dibeli[$id_jajanan];
        
        $query_harga = $koneksi->query("SELECT harga FROM jajanan WHERE id = '$id_jajanan'");
        $harga_satuan = $query_harga->fetch_assoc()['harga'];
        $subtotal = $harga_satuan * $qty;

        $stmt_detail = $koneksi->prepare("INSERT INTO detail_pesanan (pesanan_id, jajanan_id, jumlah, subtotal) VALUES (?, ?, ?, ?)");
        $stmt_detail->bind_param("iiid", $pesanan_id, $id_jajanan, $qty, $subtotal);
        $stmt_detail->execute();

        $koneksi->query("UPDATE jajanan SET stok = stok - $qty WHERE id = '$id_jajanan'");
    }

    header("Location: index.php");
    exit;
}
?>