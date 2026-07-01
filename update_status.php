<?php
session_start();
require 'koneksi.php';

if (isset($_GET['id'])) {
    $id_pesanan = $_GET['id'];
    
    // Update status pesanan menjadi selesai
    $stmt = $koneksi->prepare("UPDATE pesanan SET status = 'selesai' WHERE id = ?");
    $stmt->bind_param("i", $id_pesanan);
    
    if ($stmt->execute()) {
        header("Location: index.php");
    } else {
        echo "Gagal mengupdate status!";
    }
}
?>