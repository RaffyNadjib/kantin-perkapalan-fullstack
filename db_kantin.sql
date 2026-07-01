-- Tabel users (Hanya untuk Admin karena pelanggan tidak perlu login)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel jajanan (Master stok barang)
CREATE TABLE jajanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jajanan VARCHAR(100) NOT NULL,
    harga DECIMAL(10, 2) NOT NULL,
    stok INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel pesanan (Transaksi utama, langsung menyimpan nama pelanggan)
CREATE TABLE pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pelanggan VARCHAR(100) NOT NULL,
    tanggal_pesanan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_harga DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status ENUM('pending', 'selesai', 'dibatalkan') DEFAULT 'pending'
);

-- Tabel detail_pesanan (Item dalam pesanan)
CREATE TABLE detail_pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pesanan_id INT NOT NULL,
    jajanan_id INT NOT NULL,
    jumlah INT NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    -- Menggunakan CASCADE agar saat pesanan dibatalkan/dihapus, detailnya ikut terhapus
    FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE ON UPDATE CASCADE,
    -- Menggunakan RESTRICT agar master jajanan tidak bisa dihapus jika sedang ada di pesanan aktif/riwayat
    FOREIGN KEY (jajanan_id) REFERENCES jajanan(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- ==========================================
-- DUMMY DATA (Simulasi INSERT)
-- ==========================================

-- Insert Users (Hanya data Admin)
INSERT INTO users (username, password) VALUES
('admin_kantin', 'hashed_password_admin123');

-- Insert Jajanan
INSERT INTO jajanan (nama_jajanan, harga, stok) VALUES
('Nasi Goreng Spesial', 15000.00, 20),
('Es Teh Manis', 4000.00, 50),
('Mie Ayam Bakso', 12000.00, 15),
('Gorengan Tempe', 2000.00, 100);

-- Insert Pesanan
-- Pesanan 1 (Selesai)
INSERT INTO pesanan (nama_pelanggan, total_harga, status) VALUES
('Andi', 19000.00, 'selesai'); 
SET @pesanan1_id = LAST_INSERT_ID();

-- Pesanan 2 (Pending)
INSERT INTO pesanan (nama_pelanggan, total_harga, status) VALUES
('Budi', 24000.00, 'pending');
SET @pesanan2_id = LAST_INSERT_ID();

-- Insert Detail Pesanan
-- Detail Pesanan 1 (Andi pesan 1 Nasi Goreng, 1 Es Teh Manis)
INSERT INTO detail_pesanan (pesanan_id, jajanan_id, jumlah, subtotal) VALUES
(@pesanan1_id, 1, 1, 15000.00), -- 1x Nasi Goreng Spesial
(@pesanan1_id, 2, 1, 4000.00);  -- 1x Es Teh Manis

-- Detail Pesanan 2 (Budi pesan 2 Mie Ayam Bakso)
INSERT INTO detail_pesanan (pesanan_id, jajanan_id, jumlah, subtotal) VALUES
(@pesanan2_id, 3, 2, 24000.00); -- 2x Mie Ayam Bakso