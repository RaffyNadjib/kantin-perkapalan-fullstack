<?php
// ==========================================
// KONFIGURASI DATABASE XAMPP
// ==========================================
$host = "localhost"; // Nama host server, default XAMPP adalah localhost
$user = "root";      // Username default dari MySQL di XAMPP
$pass = "";          // Password default kosong (tidak ada)
$db   = "db_kantin";    // Nama database yang sudah dibuat

// ==========================================
// MEMBUAT KONEKSI
// ==========================================
// Menginisialisasi koneksi menggunakan class mysqli
$koneksi = new mysqli($host, $user, $pass, $db);

// ==========================================
// CEK KONEKSI
// ==========================================
// Memeriksa apakah terdapat error saat mencoba terhubung
if ($koneksi->connect_error) {
    // Jika koneksi gagal, hentikan eksekusi halaman (die) dan tampilkan pesan error
    die("Koneksi database gagal: " . $koneksi->connect_error);
}

// Opsional: Hapus atau jadikan komentar baris di bawah ini jika aplikasi sudah berjalan normal,
// ini hanya untuk memastikan koneksi sukses saat pertama kali dibuat.
// echo "Koneksi ke database berhasil!";
?>