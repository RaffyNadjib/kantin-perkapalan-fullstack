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

$hari_ini = date('Y-m-d');
$q_kotor = $koneksi->query("SELECT SUM(total_harga) as kotor FROM pesanan WHERE status = 'selesai' AND DATE(tanggal_pesanan) = '$hari_ini'");
$pendapatan_kotor = $q_kotor->fetch_assoc()['kotor'] ?? 0;

$q_bersih = $koneksi->query("
    SELECT SUM(dp.subtotal - (dp.jumlah * j.harga_modal)) as bersih 
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id = dp.pesanan_id
    JOIN jajanan j ON dp.jajanan_id = j.id
    WHERE p.status = 'selesai' AND DATE(p.tanggal_pesanan) = '$hari_ini'
");
$pendapatan_bersih = $q_bersih->fetch_assoc()['bersih'] ?? 0;

$riwayat = $koneksi->query("SELECT * FROM pesanan ORDER BY tanggal_pesanan DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Transaksi - Kantin Perkapalan</title>
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
        
        .logbook-card { border: none; border-radius: 10px; box-shadow: 0 5px 15px rgba(10, 29, 55, 0.08); overflow: hidden; }
        .logbook-header { background-color: var(--navy-dark); color: white; border-bottom: 4px solid var(--ocean-blue); }
        .table-custom thead { background-color: var(--ocean-blue); color: white; }
        .card-kotor { background: linear-gradient(135deg, var(--ocean-blue), #5dade2); color: white; border-radius: 10px; border: none;}
        .card-bersih { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border-radius: 10px; border: none;}

        /* CSS KHUSUS DESAIN NOTA THERMAL KASIR */
        .nota-print {
            font-family: 'Courier New', Courier, monospace;
            background-color: #fff;
            color: #000;
            padding: 20px;
            text-transform: uppercase;
        }
        .nota-header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
        .nota-body { border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
        .nota-footer { text-align: right; }
        .nota-item { display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 5px; }
        .nota-total { display: flex; justify-content: space-between; font-weight: bold; font-size: 1rem; }
        .btn-lihat-nota:hover { color: var(--warning-yellow) !important; text-decoration: underline !important; }
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
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card card-kotor p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 fw-bold opacity-75"><i class="bi bi-wallet2 me-2"></i>Pendapatan Kotor Hari Ini</h6>
                            <h2 class="mb-0 fw-bold">Rp <?= number_format($pendapatan_kotor, 0, ',', '.'); ?></h2>
                        </div>
                        <i class="bi bi-cash-stack" style="font-size: 3.5rem; opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-bersih p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 fw-bold opacity-75"><i class="bi bi-graph-up-arrow me-2"></i>Laba Bersih Hari Ini</h6>
                            <h2 class="mb-0 fw-bold">Rp <?= number_format($pendapatan_bersih, 0, ',', '.'); ?></h2>
                        </div>
                        <i class="bi bi-piggy-bank" style="font-size: 3.5rem; opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="logbook-card bg-white">
            <div class="logbook-header p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0 fw-bold"><i class="bi bi-journal-check text-warning me-2"></i>History Transaksi</h3>
                    <small class="text-light opacity-75">Klik nama pembeli untuk melihat Struk Nota Pembelian</small>
                </div>
                <a href="reset_hari_ini.php" class="btn btn-danger fw-bold shadow-sm" onclick="return confirm('Yakin reset data hari ini?')"><i class="bi bi-trash3-fill"></i> Reset</a>
            </div>
            
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead class="table-custom">
                        <tr>
                            <th class="py-3">ID Resi</th>
                            <th class="py-3">Waktu Pembelian</th>
                            <th class="py-3 text-start">Nama Pembeli (Klik untuk Nota)</th>
                            <th class="py-3">Total Tagihan</th>
                            <th class="py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <?php if ($riwayat->num_rows > 0): ?>
                            <?php while($r = $riwayat->fetch_assoc()): ?>
                            <tr>
                                <td class="text-secondary fw-bold">#<?= $r['id']; ?></td>
                                <td><?= date('d M Y, H:i', strtotime($r['tanggal_pesanan'])); ?></td>
                                
                                <td class="text-start fw-bold">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#notaModal<?= $r['id']; ?>" class="text-decoration-none btn-lihat-nota" style="color: var(--navy-dark);">
                                        <?= htmlspecialchars($r['nama_pelanggan']); ?> <i class="bi bi-receipt ms-1 text-primary"></i>
                                    </a>
                                </td>

                                <td class="fw-bold">Rp <?= number_format($r['total_harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge <?= $r['status'] == 'selesai' ? 'bg-success' : 'bg-warning text-dark'; ?> px-3 py-2">
                                        <?= strtoupper($r['status']); ?>
                                    </span>
                                </td>
                            </tr>

                            <div class="modal fade" id="notaModal<?= $r['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm modal-dialog-centered">
                                    <div class="modal-content border-0 bg-transparent">
                                        
                                        <div class="nota-print rounded shadow-lg">
                                            
                                            <div class="nota-header">
                                                <h4 class="fw-bold mb-0">KANTIN PERKAPALAN</h4>
                                                <small style="text-transform: none;">Resi: #<?= $r['id']; ?> | Kasir: <?= $_SESSION['username']; ?></small><br>
                                                <small style="text-transform: none;"><?= date('d/m/Y H:i:s', strtotime($r['tanggal_pesanan'])); ?></small><br>
                                                <small style="text-transform: none;">Pembeli: <b><?= htmlspecialchars($r['nama_pelanggan']); ?></b></small>
                                            </div>
                                            
                                            <div class="nota-body">
                                                <?php 
                                                $id_pesanan = $r['id'];
                                                $rincian = $koneksi->query("SELECT dp.jumlah, dp.subtotal, j.nama_jajanan, j.harga FROM detail_pesanan dp JOIN jajanan j ON dp.jajanan_id = j.id WHERE dp.pesanan_id = '$id_pesanan'");
                                                while($item = $rincian->fetch_assoc()): 
                                                ?>
                                                    <div class="nota-item mb-1">
                                                        <span style="text-align: left; width: 65%;"><?= htmlspecialchars($item['nama_jajanan']); ?> <br><small><?= $item['jumlah']; ?> x <?= number_format($item['harga'], 0, ',', '.'); ?></small></span>
                                                        <span style="width: 35%; text-align: right;"><?= number_format($item['subtotal'], 0, ',', '.'); ?></span>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                            
                                            <div class="nota-footer">
                                                <div class="nota-total mb-2">
                                                    <span>TOTAL:</span>
                                                    <span>Rp <?= number_format($r['total_harga'], 0, ',', '.'); ?></span>
                                                </div>
                                                
                                                <div class="nota-item">
                                                    <span>BAYAR (<?= strtoupper($r['metode_pembayaran']); ?>):</span>
                                                    <span><?= number_format($r['uang_pembeli'], 0, ',', '.'); ?></span>
                                                </div>
                                                <div class="nota-item mb-3">
                                                    <span>KEMBALI:</span>
                                                    <span><?= number_format($r['kembalian'], 0, ',', '.'); ?></span>
                                                </div>

                                                <div style="text-align: center; border-top: 1px dashed #000; padding-top: 10px;">
                                                    <small style="text-transform: none;">Terima Kasih Telah Membeli<br>Di Kantin Perkapalan</small>
                                                </div>
                                            </div>
                                            
                                        </div>
                                        
                                        <div class="text-center mt-3">
                                            <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm fw-bold" data-bs-dismiss="modal">Tutup Nota</button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="py-5 text-muted">Belum ada catatan di buku history.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>