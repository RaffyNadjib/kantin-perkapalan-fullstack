<?php
session_start();
require 'koneksi.php';

// 1. Cek apakah sudah login
if (!isset($_SESSION['username'])) { 
    header("Location: login.php"); 
    exit; 
}

// 2. Cek apakah dia Admin (JIKA BUKAN, TENDANG KELUAR!)
if ($_SESSION['role'] != 'admin') {
    echo "<script>
            alert('AKSES DITOLAK! Hanya Kapten (Admin) yang memiliki kunci ke ruangan ini.');
            window.location='index.php';
          </script>";
    exit;
}
// ... sisa kode di bawahnya biarkan sama ...

// Pastikan hanya admin yang bisa melakukan aksi ini
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Ambil tanggal hari ini
$hari_ini = date('Y-m-d');

// Query untuk menghapus semua pesanan yang terjadi pada HARI INI saja
// Berkat ON DELETE CASCADE, tabel detail_pesanan untuk hari ini juga otomatis bersih!
$hapus = $koneksi->query("DELETE FROM pesanan WHERE DATE(tanggal_pesanan) = '$hari_ini'");

if ($hapus) {
    // Jika berhasil, lempar kembali ke halaman Logbook
    header("Location: data_transaksi.php");
    exit;
} else {
    // Tampilkan pesan error jika query gagal
    die("Gagal mereset data: " . $koneksi->error);
}
?>