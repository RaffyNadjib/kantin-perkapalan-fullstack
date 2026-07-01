<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Ambil data jajanan yang stoknya masih ada
$query_jajanan = $koneksi->query("SELECT * FROM jajanan WHERE stok > 0 ORDER BY nama_jajanan ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Kantin Perkapalan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        /* TEMA PERKAPALAN / MARITIM */
        :root { 
            --navy-dark: #0A1D37;      
            --ocean-blue: #2980B9;     
            --wave-light: #F0F4F8;     
            --warning-yellow: #F1C40F; 
        }
        
        body { background-color: var(--wave-light); font-family: 'Segoe UI', sans-serif; }

        .navbar-custom { background-color: var(--navy-dark); border-bottom: 4px solid var(--ocean-blue); }
        .navbar-custom .navbar-brand { font-weight: 800; letter-spacing: 1px; color: #ffffff; }
        .navbar-custom .nav-link { color: var(--wave-light); font-weight: 500; transition: 0.3s; }
        .navbar-custom .nav-link:hover { color: var(--warning-yellow); }
        .nav-active { color: var(--warning-yellow) !important; border-bottom: 2px solid var(--warning-yellow); }

        .form-section {
            background-color: #ffffff; border-radius: 12px;
            box-shadow: 0 5px 15px rgba(10, 29, 55, 0.05); border-top: 5px solid var(--ocean-blue);
        }
        
        /* --- DESAIN KARTU MENU --- */
        .menu-card {
            border: 2px solid #dee2e6;
            border-radius: 12px;
            transition: all 0.2s ease-in-out;
            background-color: #ffffff;
            position: relative;
        }
        
        .menu-card:hover {
            border-color: #8bbdeb;
            transform: translateY(-2px);
        }

        /* Checkbox dan Label */
        .form-check-input { width: 1.8em; height: 1.8em; cursor: pointer; flex-shrink: 0; }
        .form-check-input:checked { background-color: var(--ocean-blue); border-color: var(--ocean-blue); }
        .form-check-label { cursor: pointer; width: 100%; user-select: none; }
        .clickable-area { cursor: pointer; }

        .btn-navy { background-color: var(--navy-dark); color: white; transition: 0.3s; }
        .btn-navy:hover { background-color: #143561; color: white; transform: scale(1.02); }
        
        .badge-price { background-color: var(--ocean-blue); font-size: 1rem; }
        
        /* Tombol Plus Minus & Shortcut Menu */
        .btn-qty { background-color: white; border: 1px solid #ced4da; color: var(--navy-dark); font-weight: bold;}
        .btn-qty:hover { background-color: #e9ecef; }
        
        .btn-shortcut {
            font-size: 0.85rem;
            font-weight: bold;
            color: var(--ocean-blue);
            border: 1px solid var(--ocean-blue);
            background-color: transparent;
            transition: 0.2s;
        }
        .btn-shortcut:hover, .btn-shortcut:active {
            background-color: var(--ocean-blue);
            color: white;
        }

        /* Tombol Shortcut Uang */
        .btn-uang-quick {
            font-weight: bold;
            color: var(--ocean-blue);
            border: 1px solid #dee2e6;
            background-color: white;
            transition: 0.2s;
            padding-left: 5px;
            padding-right: 5px;
        }
        .btn-uang-quick:hover, .btn-uang-quick:active {
            background-color: var(--ocean-blue);
            color: white;
            border-color: var(--ocean-blue);
        }
        
        /* Tombol +/- untuk Nominal Uang */
        .btn-uang-adjust { background-color: #f8f9fa; border-color: #dee2e6; color: var(--navy-dark); transition: 0.2s; }
        .btn-uang-adjust:hover { background-color: #e2e6ea; }

        /* Hilangkan panah spinner bawaan input number */
        input[type="number"]::-webkit-outer-spin-button, input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        input[type="number"] { -moz-appearance: textfield; }

        /* Panel Pembayaran */
        .payment-panel {
            background-color: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 12px;
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
        <form action="proses_kasir.php" method="POST" id="formKasir" class="form-section p-4 p-md-5">
            
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <h3 class="fw-bold mb-0" style="color: var(--navy-dark);"><i class="bi bi-cart-check"></i> Form Pesanan Baru</h3>
            </div>

            <div class="mb-5">
                <label class="form-label fw-bold text-secondary fs-5">Nama Pembeli</label>
                <div class="input-group input-group-lg shadow-sm">
                    <span class="input-group-text bg-white" style="color: var(--ocean-blue);"><i class="bi bi-person-fill"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="nama_pelanggan" placeholder="Contoh: Faisal TKRO 2..." required autofocus>
                </div>
            </div>

            <h5 class="fw-bold mb-4" style="color: var(--navy-dark);"><i class="bi bi-ui-checks-grid"></i> Pilih Menu</h5>
            
            <div class="row g-3 mb-5">
                <?php while($row = $query_jajanan->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="menu-card h-100 p-3" id="card-<?= $row['id']; ?>">
                        
                        <label class="d-flex align-items-center mb-3 clickable-area w-100" for="menu<?= $row['id']; ?>">
                            <input class="form-check-input me-3 shadow-sm menu-checkbox" type="checkbox" name="jajanan_id[]" value="<?= $row['id']; ?>" id="menu<?= $row['id']; ?>" data-target="card-<?= $row['id']; ?>" data-harga="<?= $row['harga']; ?>">
                            <span class="fw-bold fs-5 form-check-label" style="color: var(--navy-dark);">
                                <?= htmlspecialchars($row['nama_jajanan']); ?>
                            </span>
                        </label>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge badge-price px-3 py-2">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></span>
                            <small class="text-muted fw-bold"><i class="bi bi-box2"></i> Stok: <span id="stok-<?= $row['id']; ?>"><?= $row['stok']; ?></span></small>
                        </div>
                        
                        <div class="input-group mt-auto shadow-sm mb-2">
                            <button class="btn btn-qty btn-minus" type="button" data-id="<?= $row['id']; ?>"><i class="bi bi-dash-lg"></i></button>
                            <input type="number" class="form-control text-center fw-bold fs-5 qty-input" name="jumlah[<?= $row['id']; ?>]" id="qty-<?= $row['id']; ?>" value="1" min="1" max="<?= $row['stok']; ?>">
                            <button class="btn btn-qty btn-plus" type="button" data-id="<?= $row['id']; ?>"><i class="bi bi-plus-lg"></i></button>
                        </div>

                        <div class="d-flex justify-content-center gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-shortcut flex-fill btn-quick" data-id="<?= $row['id']; ?>" data-val="2">+2</button>
                            <button type="button" class="btn btn-sm btn-shortcut flex-fill btn-quick" data-id="<?= $row['id']; ?>" data-val="5">+5</button>
                            <button type="button" class="btn btn-sm btn-shortcut flex-fill btn-quick" data-id="<?= $row['id']; ?>" data-val="10">+10</button>
                        </div>

                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="payment-panel p-4 mb-4">
                <h5 class="fw-bold mb-4" style="color: var(--navy-dark);"><i class="bi bi-receipt"></i> Rincian Pembayaran</h5>
                
                <div class="row">
                    
                    <div class="col-md-3 mb-4 mb-md-0 d-flex flex-column justify-content-center text-center text-md-start border-end-md">
                        <label class="form-label text-secondary fw-bold mb-1">Total Tagihan</label>
                        <h2 class="fw-bold mb-0 text-danger" id="display-total">Rp 0</h2>
                        <input type="hidden" id="input-total" value="0">
                    </div>
                    
                    <div class="col-md-6 px-md-4 mb-4 mb-md-0">
                        <div class="row g-2 mb-3">
                            <div class="col-sm-5">
                                <label class="form-label text-secondary fw-bold mb-1">Metode</label>
                                <select class="form-select form-select-lg fw-bold border-ocean shadow-sm" name="metode_pembayaran" id="metode-pembayaran">
                                    <option value="Cash">💵 Cash</option>
                                    <option value="Transfer">📱 Transfer / QRIS</option>
                                </select>
                            </div>
                            
                            <div class="col-sm-7">
                                <label class="form-label text-secondary fw-bold mb-1">Nominal Uang</label>
                                <div class="input-group input-group-lg shadow-sm">
                                    <span class="input-group-text bg-white fw-bold px-2 text-muted">Rp</span>
                                    <button class="btn btn-uang-adjust border px-3" type="button" id="btn-min-uang"><i class="bi bi-dash-lg"></i></button>
                                    
                                    <input type="number" class="form-control fw-bold fs-4 text-center px-1" name="uang_pembeli" id="uang-pembeli" placeholder="0" min="0">
                                    
                                    <button class="btn btn-uang-adjust border px-3" type="button" id="btn-plus-uang"><i class="bi bi-plus-lg"></i></button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex flex-wrap gap-2 justify-content-between w-100">
                            <button type="button" class="btn btn-uang-quick flex-fill rounded shadow-sm py-2 fs-6" data-val="500">500</button>
                            <button type="button" class="btn btn-uang-quick flex-fill rounded shadow-sm py-2 fs-6" data-val="5000">5k</button>
                            <button type="button" class="btn btn-uang-quick flex-fill rounded shadow-sm py-2 fs-6" data-val="10000">10k</button>
                            <button type="button" class="btn btn-uang-quick flex-fill rounded shadow-sm py-2 fs-6" data-val="20000">20k</button>
                            <button type="button" class="btn btn-uang-quick flex-fill rounded shadow-sm py-2 fs-6" data-val="50000">50k</button>
                            <button type="button" class="btn btn-uang-quick flex-fill rounded shadow-sm py-2 fs-6" data-val="100000">100k</button>
                            <button type="button" class="btn btn-outline-danger flex-fill rounded fw-bold shadow-sm py-2 fs-6" id="btn-clear-uang" title="Hapus Uang">C</button>
                        </div>
                    </div>
                    
                    <div class="col-md-3 d-flex flex-column justify-content-center text-center text-md-end border-start-md">
                        <label class="form-label text-secondary fw-bold mb-1">Kembalian</label>
                        <h2 class="fw-bold mb-0 text-success" id="display-kembalian">Rp 0</h2>
                    </div>

                </div>
            </div>

            <div class="text-end border-top pt-4">
                <button type="submit" class="btn btn-navy btn-lg px-5 shadow">
                    <i class="bi bi-anchor me-2"></i> Pesan & Bayar
                </button>
            </div>
            
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function formatRupiah(angka) { return new Intl.NumberFormat('id-ID').format(angka); }

            function kalkulasiTotal() {
                let totalTagihan = 0;
                const checkedBoxes = document.querySelectorAll('.menu-checkbox:checked');
                checkedBoxes.forEach(function(checkbox) {
                    const idMenu = checkbox.value;
                    const harga = parseInt(checkbox.getAttribute('data-harga')) || 0;
                    const qty = parseInt(document.getElementById('qty-' + idMenu).value) || 0;
                    totalTagihan += (harga * qty);
                });

                document.getElementById('display-total').innerText = 'Rp ' + formatRupiah(totalTagihan);
                document.getElementById('input-total').value = totalTagihan;
                document.getElementById('uang-pembeli').min = totalTagihan;
                
                // Jika metode transfer, otomatis set uang = tagihan
                if(document.getElementById('metode-pembayaran').value === 'Transfer'){
                    document.getElementById('uang-pembeli').value = totalTagihan;
                }
                kalkulasiKembalian();
            }

            function kalkulasiKembalian() {
                const totalTagihan = parseInt(document.getElementById('input-total').value) || 0;
                const uangMasuk = parseInt(document.getElementById('uang-pembeli').value) || 0;
                const kembalian = uangMasuk - totalTagihan;
                const displayKembalian = document.getElementById('display-kembalian');

                if (uangMasuk === 0) {
                    displayKembalian.innerText = 'Rp 0';
                    displayKembalian.className = 'fw-bold mb-0 text-success';
                } else if (kembalian < 0) {
                    displayKembalian.innerText = 'Uang Kurang!';
                    displayKembalian.className = 'fw-bold mb-0 text-danger';
                } else {
                    displayKembalian.innerText = 'Rp ' + formatRupiah(kembalian);
                    displayKembalian.className = 'fw-bold mb-0 text-success';
                }
            }

            // Trigger saat input uang diketik
            document.getElementById('uang-pembeli').addEventListener('input', kalkulasiKembalian);
            
            // TOMBOL ADJUST (+/- 1000) UNTUK UANG TUNAI
            document.getElementById('btn-plus-uang').addEventListener('click', function() {
                if(document.getElementById('metode-pembayaran').value === 'Cash') {
                    const inputUang = document.getElementById('uang-pembeli');
                    let currentVal = parseInt(inputUang.value) || 0;
                    inputUang.value = currentVal + 1000;
                    kalkulasiKembalian();
                }
            });

            document.getElementById('btn-min-uang').addEventListener('click', function() {
                if(document.getElementById('metode-pembayaran').value === 'Cash') {
                    const inputUang = document.getElementById('uang-pembeli');
                    let currentVal = parseInt(inputUang.value) || 0;
                    if (currentVal >= 1000) {
                        inputUang.value = currentVal - 1000;
                        kalkulasiKembalian();
                    }
                }
            });

            // TOMBOL SHORTCUT UANG TUNAI BESAR
            document.querySelectorAll('.btn-uang-quick').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Hanya bisa diklik jika metode Cash
                    if(document.getElementById('metode-pembayaran').value === 'Cash') {
                        const inputUang = document.getElementById('uang-pembeli');
                        const valToAdd = parseInt(this.getAttribute('data-val'));
                        let currentVal = parseInt(inputUang.value) || 0;
                        
                        inputUang.value = currentVal + valToAdd;
                        kalkulasiKembalian(); 
                    }
                });
            });

            // TOMBOL CLEAR UANG (C)
            document.getElementById('btn-clear-uang').addEventListener('click', function() {
                if(document.getElementById('metode-pembayaran').value === 'Cash') {
                    document.getElementById('uang-pembeli').value = '';
                    kalkulasiKembalian();
                }
            });
            
            // Trigger saat ubah metode pembayaran
            document.getElementById('metode-pembayaran').addEventListener('change', function() {
                if(this.value === 'Transfer') {
                    const total = document.getElementById('input-total').value;
                    document.getElementById('uang-pembeli').value = total;
                    document.getElementById('uang-pembeli').readOnly = true; 
                } else {
                    document.getElementById('uang-pembeli').value = '';
                    document.getElementById('uang-pembeli').readOnly = false;
                }
                kalkulasiKembalian();
            });

            // Logika Aktivasi Kartu Menu
            function activateCard(id) {
                const checkbox = document.getElementById('menu' + id);
                const cardElement = document.getElementById('card-' + id);
                if (!checkbox.checked) checkbox.checked = true;
                cardElement.style.backgroundColor = '#d6eaf8'; 
                cardElement.style.borderColor = '#2980B9';
                cardElement.style.boxShadow = '0 0 0 2px #2980B9';
                kalkulasiTotal(); 
            }

            const checkboxes = document.querySelectorAll('.menu-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const cardElement = document.getElementById(this.getAttribute('data-target'));
                    if (this.checked) {
                        cardElement.style.backgroundColor = '#d6eaf8'; 
                        cardElement.style.borderColor = '#2980B9';
                        cardElement.style.boxShadow = '0 0 0 2px #2980B9';
                    } else {
                        cardElement.style.backgroundColor = '#ffffff'; 
                        cardElement.style.borderColor = '#dee2e6';
                        cardElement.style.boxShadow = 'none';
                    }
                    kalkulasiTotal(); 
                });
            });

            // Tombol +/- dan Kelipatan Menu
            document.querySelectorAll('.btn-minus').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const input = document.getElementById('qty-' + id);
                    if ((parseInt(input.value) || 1) > 1) input.value = parseInt(input.value) - 1;
                    activateCard(id); 
                });
            });

            document.querySelectorAll('.btn-plus').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const input = document.getElementById('qty-' + id);
                    if ((parseInt(input.value) || 0) < parseInt(input.getAttribute('max'))) input.value = parseInt(input.value) + 1;
                    activateCard(id); 
                });
            });

            document.querySelectorAll('.btn-quick').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const input = document.getElementById('qty-' + id);
                    let currentVal = parseInt(input.value) || 0;
                    if (currentVal === 1) currentVal = 0; 
                    let newVal = currentVal + parseInt(this.getAttribute('data-val'));
                    input.value = newVal <= parseInt(input.getAttribute('max')) ? newVal : parseInt(input.getAttribute('max'));
                    activateCard(id); 
                });
            });
            
            document.querySelectorAll('.qty-input').forEach(input => {
                input.addEventListener('input', function() {
                    if (parseInt(this.value) > parseInt(this.getAttribute('max'))) this.value = this.getAttribute('max');
                    kalkulasiTotal(); 
                });
                input.addEventListener('blur', function() {
                    if (this.value === '' || parseInt(this.value) < 1) this.value = 1;
                    kalkulasiTotal(); 
                });
            });

            // Validasi Form
            document.getElementById('formKasir').addEventListener('submit', function(e) {
                const totalTagihan = parseInt(document.getElementById('input-total').value) || 0;
                const uangMasuk = parseInt(document.getElementById('uang-pembeli').value) || 0;
                if (totalTagihan === 0) { e.preventDefault(); alert('Pilih minimal 1 menu!'); return false; }
                if (uangMasuk < totalTagihan) { e.preventDefault(); alert('GAGAL: Nominal uang kurang!'); return false; }
            });
        });
    </script>
</body>
</html>