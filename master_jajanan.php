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

// Tambah Menu Baru
if (isset($_POST['tambah'])) {
    $nama = $koneksi->real_escape_string($_POST['nama_jajanan']);
    $harga_modal = $_POST['harga_modal']; 
    $harga_jual = $_POST['harga'];
    $stok = $_POST['stok'];
    $koneksi->query("INSERT INTO jajanan (nama_jajanan, harga_modal, harga, stok) VALUES ('$nama', '$harga_modal', '$harga_jual', '$stok')");
    header("Location: master_jajanan.php");
    exit;
}

// Edit Menu & Stok
if (isset($_POST['edit_menu'])) {
    $id_edit = $_POST['id_edit'];
    $nama = $koneksi->real_escape_string($_POST['nama_jajanan']);
    $harga_modal = $_POST['harga_modal']; 
    $harga_jual = $_POST['harga'];
    $stok = $_POST['stok'];
    
    $koneksi->query("UPDATE jajanan SET nama_jajanan = '$nama', harga_modal = '$harga_modal', harga = '$harga_jual', stok = '$stok' WHERE id = '$id_edit'");
    header("Location: master_jajanan.php");
    exit;
}

// HAPUS MENU (FITUR BARU DENGAN KEAMANAN)
if (isset($_POST['hapus_menu'])) {
    $id_hapus = $_POST['id_edit'];
    
    // Cek dulu apakah jajanan ini sudah pernah dipesan (ada di tabel detail_pesanan)
    $cek_riwayat = $koneksi->query("SELECT * FROM detail_pesanan WHERE jajanan_id = '$id_hapus'");
    
    if ($cek_riwayat->num_rows > 0) {
        // Jika sudah pernah laku, jangan dihapus. Munculkan peringatan.
        echo "<script>
                alert('GAGAL MENGHAPUS: Kargo ini sudah pernah dibeli oleh kelasi dan tercatat di Logbook! Menghapusnya akan merusak riwayat transaksi.\\n\\nSolusi: Silakan edit saja nama dan harganya menjadi menu yang baru.'); 
                window.location='master_jajanan.php';
              </script>";
        exit;
    } else {
        // Jika belum pernah laku (menu baru / salah ketik), aman untuk dihapus
        $koneksi->query("DELETE FROM jajanan WHERE id = '$id_hapus'");
        header("Location: master_jajanan.php");
        exit;
    }
}

$jajanan = $koneksi->query("SELECT * FROM jajanan ORDER BY nama_jajanan ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok - Kantin Perkapalan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --navy-dark: #0A1D37; --ocean-blue: #2980B9; --wave-light: #F0F4F8; --warning-yellow: #F1C40F; }
        body { background-color: var(--wave-light); font-family: 'Segoe UI', sans-serif; }
        .navbar-custom { background-color: var(--navy-dark); border-bottom: 4px solid var(--ocean-blue); }
        .navbar-custom .navbar-brand { font-weight: 800; color: #ffffff; }
        .navbar-custom .nav-link { color: var(--wave-light); font-weight: 500; transition: 0.3s; }
        .navbar-custom .nav-link:hover { color: var(--warning-yellow); }
        .nav-active { color: var(--warning-yellow) !important; border-bottom: 2px solid var(--warning-yellow); }

        .cargo-card { border: none; border-top: 4px solid var(--ocean-blue); border-radius: 8px; box-shadow: 0 4px 10px rgba(10, 29, 55, 0.05); transition: 0.2s; background: #fff; }
        .cargo-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(10, 29, 55, 0.1); border-top-color: var(--warning-yellow); }
        
        .btn-ocean { background-color: var(--ocean-blue); color: white; transition: 0.2s;}
        .btn-ocean:hover { background-color: #1c6696; color: white; }
        .btn-warning-custom { background-color: var(--warning-yellow); color: var(--navy-dark); font-weight: bold; transition: 0.2s;}
        .btn-warning-custom:hover { background-color: #d4ac0d; transform: scale(1.02); }
        
        .btn-edit-kargo {
            background-color: var(--wave-light);
            color: var(--ocean-blue);
            border: 1px solid var(--ocean-blue);
            font-weight: bold;
            transition: 0.2s;
        }
        .btn-edit-kargo:hover {
            background-color: var(--ocean-blue);
            color: white;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom shadow-sm mb-5">
        <div class="container">
            <a class="navbar-brand text-white" href="index.php"><i class="bi bi-water me-2"></i>KANTIN PERKAPALAN</a>
            <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Anjungan</a></li>
                    <li class="nav-item"><a class="nav-link" href="kasir.php"><i class="bi bi-cart-plus"></i> Kasir</a></li>
                    
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="master_jajanan.php"><i class="bi bi-box-seam"></i> Palka Gudang</a></li>
                        <li class="nav-item"><a class="nav-link" href="data_transaksi.php"><i class="bi bi-journal-text"></i> Logbook Transaksi</a></li>
                    <?php endif; ?>
                </ul>

                <div class="d-flex align-items-center">
                    <span class="text-light me-3 fw-bold">
                        <i class="bi bi-person-badge me-1"></i> 
                        <?= ($_SESSION['role'] == 'admin') ? 'Kapten' : 'User'; ?> 
                        <span class="text-warning"><?= htmlspecialchars($_SESSION['username']); ?></span>
                    </span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm fw-bold">Berlabuh (Logout)</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 bg-white p-3 py-4 rounded shadow-sm" style="border-left: 5px solid var(--navy-dark);">
            <h3 class="fw-bold mb-0" style="color: var(--navy-dark);"><i class="bi bi-boxes"></i> Manajemen Stok</h3>
            
            <div class="d-flex gap-2 flex-wrap justify-content-md-end">
                <a href="reset_stok.php" class="btn btn-danger fw-bold shadow-sm" onclick="return confirm('PERINGATAN KAPAL! Yakin ingin mengosongkan (menjadi 0) SELURUH sisa stok jajanan? Tindakan ini tidak dapat dikembalikan.')">
                    <i class="bi bi-trash3-fill me-1"></i> Kosongkan Stok
                </a>
                <button class="btn btn-warning-custom px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-circle-fill me-1"></i> Tambah Menu Baru
                </button>
            </div>
        </div>

        <div class="row g-4">
            <?php while($j = $jajanan->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4">
                <div class="cargo-card h-100 p-4 d-flex flex-column">
                    
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold mb-0" style="color: var(--navy-dark);">
                            <?= htmlspecialchars($j['nama_jajanan']); ?>
                        </h5>
                        <span class="badge <?= $j['stok'] < 10 ? 'bg-danger' : 'bg-success'; ?> fs-6 shadow-sm" title="Sisa Stok"><i class="bi bi-box-seam"></i> <?= $j['stok']; ?></span>
                    </div>
                    
                    <div class="border-bottom pb-3 mb-4 mt-2">
                        <small class="text-muted d-block mb-1"><i class="bi bi-tag opacity-75"></i> Modal: Rp <?= number_format($j['harga_modal'], 0, ',', '.'); ?></small>
                        <span class="text-success fw-bold fs-6"><i class="bi bi-tag-fill me-1"></i> Jual: Rp <?= number_format($j['harga'], 0, ',', '.'); ?></span>
                    </div>
                    
                    <div class="mt-auto">
                        <button class="btn btn-edit-kargo w-100" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $j['id']; ?>">
                            <i class="bi bi-pencil-square me-2"></i>Edit Menu & Stok
                        </button>
                    </div>

                </div>
            </div>

            <div class="modal fade" id="modalEdit<?= $j['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="" method="POST" class="modal-content border-0 shadow">
                        <div class="modal-header text-white" style="background-color: var(--ocean-blue);">
                            <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Data Stok</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <input type="hidden" name="id_edit" value="<?= $j['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">Nama Menu</label>
                                <input type="text" name="nama_jajanan" class="form-control" required value="<?= htmlspecialchars($j['nama_jajanan']); ?>">
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label fw-bold text-secondary">Harga Modal (Rp)</label>
                                    <input type="number" name="harga_modal" class="form-control" required value="<?= $j['harga_modal']; ?>">
                                </div>
                                <div class="col">
                                    <label class="form-label fw-bold text-secondary">Harga Jual (Rp)</label>
                                    <input type="number" name="harga" class="form-control" required value="<?= $j['harga']; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label fw-bold text-secondary">Sisa Stok</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-box-seam"></i></span>
                                    <input type="number" name="stok" class="form-control fw-bold" required value="<?= $j['stok']; ?>">
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer bg-light d-flex justify-content-between">
                            <button type="submit" name="hapus_menu" class="btn btn-outline-danger fw-bold" onclick="return confirm('Yakin ingin menghapus kargo ini secara permanen?')">
                                <i class="bi bi-trash3 me-1"></i> Hapus
                            </button>
                            <button type="submit" name="edit_menu" class="btn btn-ocean py-2 fw-bold px-4">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="" method="POST" class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: var(--navy-dark);">
                    <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2"></i> Registrasi Menu Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">Nama Menu</label>
                        <input type="text" name="nama_jajanan" class="form-control" required placeholder="Contoh: Roti Bakar">
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label fw-bold text-secondary">Harga Modal (Rp)</label>
                            <input type="number" name="harga_modal" class="form-control" required placeholder="Contoh: 3000">
                        </div>
                        <div class="col">
                            <label class="form-label fw-bold text-secondary">Harga Jual (Rp)</label>
                            <input type="number" name="harga" class="form-control" required placeholder="Contoh: 5000">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">Stok Awal</label>
                        <input type="number" name="stok" class="form-control" required placeholder="Contoh: 20">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" name="tambah" class="btn btn-warning-custom w-100 py-2 fs-5">Simpan Menu</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>