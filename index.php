<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// 1. Pesanan Berjalan (Status Pending)
$q_pending = $koneksi->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pesanan_berjalan = $q_pending->fetch_assoc()['total'];

// 2. Total Stok Jajanan
$q_stok = $koneksi->query("SELECT SUM(stok) as total FROM jajanan");
$total_stok = $q_stok->fetch_assoc()['total'] ?? 0;

// 3. Data Stok Jajanan Lengkap
$daftar_jajanan = $koneksi->query("SELECT * FROM jajanan ORDER BY nama_jajanan ASC");

// 4. 5 Transaksi Terbaru
$pesanan_terbaru = $koneksi->query("SELECT * FROM pesanan ORDER BY tanggal_pesanan DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kantin Perkapalan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { 
            --navy-dark: #0A1D37;      
            --ocean-blue: #2980B9;     
            --wave-light: #F0F4F8;     
            --warning-yellow: #F1C40F; 
        }
        body { background-color: var(--wave-light); font-family: 'Segoe UI', sans-serif; }
        .navbar-custom { background-color: var(--navy-dark); border-bottom: 4px solid var(--ocean-blue); }
        .navbar-custom .navbar-brand { font-weight: 800; letter-spacing: 1px; color: #ffffff; }
        .navbar-custom .nav-link { color: var(--wave-light); font-weight: 500; transition: 0.3s;}
        .navbar-custom .nav-link:hover { color: var(--warning-yellow); }
        .nav-active { color: var(--warning-yellow) !important; border-bottom: 2px solid var(--warning-yellow); }
        
        .card-summary { 
            background-color: #ffffff; border: none; border-top: 5px solid var(--ocean-blue); 
            border-radius: 8px; box-shadow: 0 4px 10px rgba(10, 29, 55, 0.08); transition: 0.2s;
        }
        .card-summary:hover { transform: translateY(-3px); }
        .card-icon { font-size: 3rem; color: var(--ocean-blue); opacity: 0.9; }
        
        .table-custom thead { background-color: var(--ocean-blue); color: white; }
        .table-stok thead { background-color: var(--navy-dark); color: white; }
        .badge-selesai { background-color: var(--ocean-blue); }
        .badge-pending { background-color: var(--warning-yellow); color: #000; }

        /* Kustomisasi Dropdown Rincian */
        .detail-item { transition: 0.3s; }
        .detail-done { text-decoration: line-through; color: #6c757d; background-color: #f8f9fa; }
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
                        <?= (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') ? 'Kapten' : 'User'; ?> 
                        <span class="text-warning"><?= htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                    </span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm fw-bold">Berlabuh (Logout)</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <h3 class="mb-4 fw-bold" style="color: var(--navy-dark);"><i class="bi bi-compass"></i> Status Terkini</h3>
        
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card card-summary p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted fw-bold mb-1">Pesanan menunggu (Pending)</h6>
                            <h2 class="fw-bold mb-0" style="color: var(--navy-dark);"><?= $pesanan_berjalan; ?></h2>
                        </div>
                        <i class="bi bi-life-preserver card-icon text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-summary p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted fw-bold mb-1">Total Semua Stok</h6>
                            <h2 class="fw-bold mb-0" style="color: var(--navy-dark);"><?= $total_stok; ?></h2>
                        </div>
                        <i class="bi bi-boxes card-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold" style="color: var(--navy-dark);"><i class="bi bi-anchor"></i> Antrean Transaksi (Terbaru)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover text-center mb-0 align-middle">
                            <thead class="table-custom">
                                <tr>
                                    <th>No. Resi</th>
                                    <th>Rincian Pesanan</th>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <?php if ($pesanan_terbaru->num_rows > 0): ?>
                                    <?php while($row = $pesanan_terbaru->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">#<?= $row['id']; ?></td>
                                        
                                        <td class="text-start">
                                            <a data-bs-toggle="collapse" href="#detail-<?= $row['id']; ?>" class="text-decoration-none fw-bold d-block" style="color: var(--navy-dark);">
                                                <?= htmlspecialchars($row['nama_pelanggan']); ?> 
                                                <i class="bi bi-caret-down-fill text-warning ms-1" style="font-size: 0.8rem;"></i>
                                            </a>
                                            
                                            <div class="collapse mt-2" id="detail-<?= $row['id']; ?>">
                                                <ul class="list-group list-group-flush border rounded shadow-sm">
                                                    <?php 
                                                        // Query untuk mengambil item apa saja yang dibeli di pesanan ini
                                                        $id_pesanan = $row['id'];
                                                        $rincian = $koneksi->query("SELECT dp.jumlah, j.nama_jajanan FROM detail_pesanan dp JOIN jajanan j ON dp.jajanan_id = j.id WHERE dp.pesanan_id = '$id_pesanan'");
                                                        while($item = $rincian->fetch_assoc()): 
                                                    ?>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center detail-item p-2">
                                                            <span class="small fw-semibold">
                                                                <span class="badge bg-secondary me-1"><?= $item['jumlah']; ?>x</span> 
                                                                <?= htmlspecialchars($item['nama_jajanan']); ?>
                                                            </span>
                                                            
                                                            <?php if($row['status'] == 'pending'): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-success py-0 px-1 btn-check-item" title="Tandai Siap">
                                                                    <i class="bi bi-check2"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endwhile; ?>
                                                </ul>
                                            </div>
                                        </td>
                                        <td><?= date('H:i', strtotime($row['tanggal_pesanan'])); ?></td>
                                        <td>
                                            <span class="badge <?= $row['status'] == 'selesai' ? 'badge-selesai' : 'badge-pending'; ?> px-2 py-1">
                                                <?= strtoupper($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($row['status'] == 'pending'): ?>
                                                <a href="update_status.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-success fw-bold" title="Selesaikan Seluruh Pesanan"><i class="bi bi-check2-all"></i></a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-light text-muted border" disabled><i class="bi bi-check2-all"></i></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-muted py-4">Laut masih tenang, belum ada transaksi.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold" style="color: var(--navy-dark);"><i class="bi bi-tags"></i> Ketersediaan Menu</h5>
                        <small class="text-muted">Daftar menu dan sisa stok untuk pembeli</small>
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-stok sticky-top">
                                <tr>
                                    <th class="ps-3">Nama Menu</th>
                                    <th>Harga</th>
                                    <th class="text-center">Sisa Stok</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <?php while($j = $daftar_jajanan->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold text-dark"><?= htmlspecialchars($j['nama_jajanan']); ?></td>
                                    <td class="text-muted">Rp <?= number_format($j['harga'], 0, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <?php if($j['stok'] > 0): ?>
                                            <span class="badge bg-success rounded-pill px-3"><?= $j['stok']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger rounded-pill px-3">Habis</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkButtons = document.querySelectorAll('.btn-check-item');
            
            checkButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    // Mencegah dropdown tertutup saat tombol diklik
                    e.stopPropagation(); 
                    e.preventDefault();

                    const listItem = this.closest('.detail-item');
                    
                    // Efek visual coret teks dan ubah warna tombol
                    listItem.classList.toggle('detail-done');
                    this.classList.toggle('btn-outline-success');
                    this.classList.toggle('btn-success');
                    
                    // Ubah ikon dari check biasa ke check tebal (bi-check2-circle)
                    const icon = this.querySelector('i');
                    if(icon.classList.contains('bi-check2')) {
                        icon.classList.remove('bi-check2');
                        icon.classList.add('bi-check-circle-fill', 'text-white');
                    } else {
                        icon.classList.remove('bi-check-circle-fill', 'text-white');
                        icon.classList.add('bi-check2');
                    }
                });
            });
        });
    </script>
</body>
</html>