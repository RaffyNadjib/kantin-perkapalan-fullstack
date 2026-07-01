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

// Pastikan hanya kapten (admin) yang bisa mengeksekusi
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Eksekusi query untuk mereset seluruh stok jajanan menjadi 0
$reset_stok = $koneksi->query("UPDATE jajanan SET stok = 0");

if ($reset_stok) {
    // Jika sukses, kembalikan ke halaman palka gudang
    header("Location: master_jajanan.php");
    exit;
} else {
    die("Gagal mengosongkan palka: " . $koneksi->error);
}
?>