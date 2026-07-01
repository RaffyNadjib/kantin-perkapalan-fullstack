<?php
session_start();
require 'koneksi.php';

// Jika sudah login, langsung tendang ke halaman dashboard (index)
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$error_msg = '';

// Proses jika tombol Masuk ditekan
if (isset($_POST['login'])) {
    // Tangkap input dari form
    $username = $koneksi->real_escape_string($_POST['username']);
    $password = $_POST['password']; // Jika password Anda di database menggunakan MD5, ubah jadi: md5($_POST['password'])

    // Cek kecocokan di database tabel users
    $cek_user = $koneksi->query("SELECT * FROM users WHERE username = '$username' AND password = '$password'");

    if ($cek_user->num_rows > 0) {
        // Jika cocok, ambil datanya
        $data = $cek_user->fetch_assoc();
        
        // Simpan sesi penting!
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role']; // Sesi hak akses (admin/kasir) disimpan di sini
        
        // Arahkan ke dashboard
        header("Location: index.php");
        exit;
    } else {
        // Jika salah, munculkan pesan error
        $error_msg = "Username atau Password Anda salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KantinKita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #cbd5c0; /* Warna hijau sage sesuai desain Anda */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background-color: #8c9c81; /* Warna hijau header card */
            color: #0A1D37; /* Warna teks gelap */
            text-align: center;
            padding: 25px 20px;
        }
        .login-header h3 {
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .login-header small {
            font-weight: 700;
            opacity: 0.8;
        }
        .btn-login {
            background-color: #0A1D37; /* Warna navy sesuai tombol Masuk Anda */
            color: white;
            font-weight: bold;
            padding: 10px;
            border-radius: 6px;
            transition: 0.3s;
        }
        .btn-login:hover {
            background-color: #143561;
            color: white;
        }
    </style>
</head>
<body>

    <div class="container d-flex justify-content-center">
        <div class="login-card">
            <div class="login-header">
                <h3>KantinKita</h3>
                <small>Sistem Manajemen Kantin</small>
            </div>
            
            <div class="p-4 px-md-5">
                <?php if($error_msg): ?>
                    <div class="alert alert-danger p-2 text-center mb-4" style="font-size: 0.9rem; font-weight: bold;">
                        <?= $error_msg; ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold mb-1">Username</label>
                        <input type="text" name="username" class="form-control py-2" placeholder="Masukkan username" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold mb-1">Password</label>
                        <input type="password" name="password" class="form-control py-2" placeholder="Masukkan password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-login w-100 fs-5 mt-2">Masuk</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>