const express = require('express');
const cors = require('cors');
const mysql = require('mysql2/promise');

const app = express();
const port = 3000;

app.use(cors());
app.use(express.json());

// Konfigurasi Database
const pool = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'db_kantin'
});

console.log('Menghubungkan ke database db_kantin...');

// 1. LOGIN
app.post('/api/login', async (req, res) => {
    console.log(`\n[RADAR LOGIN] HP sedang mencoba masuk dengan username: "${req.body.username}"`);
    try {
        const { username, password } = req.body;
        
        // CATATAN: Pastikan nama tabel Anda benar-benar 'users'. Jika beda, ubah kata 'users' di bawah ini!
        const [rows] = await pool.query("SELECT * FROM users WHERE username=? AND password=?", [username, password]);
        
        if (rows.length > 0) {
            console.log(`[RADAR LOGIN] SUKSES! Ditemukan di database. Peran: ${rows[0].role}`);
            res.json({ success: true, role: rows[0].role });
        } else {
            console.log(`[RADAR LOGIN] GAGAL! Username/Password tidak cocok dengan isi database.`);
            res.json({ success: false, message: "Username atau password salah!" });
        }
    } catch (error) {
        console.log(`[ERROR DATABASE] ${error.message}`);
        res.json({ success: false, message: "Error Database: " + error.message });
    }
});

// 2. DASHBOARD
app.get('/api/dashboard', async (req, res) => {
    try {
        const [stokRows] = await pool.query("SELECT SUM(stok) as total_stok FROM jajanan");
        const totalStok = stokRows[0].total_stok || 0;

        const [menuRows] = await pool.query("SELECT nama_jajanan, harga, stok FROM jajanan ORDER BY id ASC");
        
        const [pendingRows] = await pool.query("SELECT COUNT(*) as jml FROM pesanan WHERE status = 'pending'");
        const totalPending = pendingRows[0].jml || 0;

        const [antreanRows] = await pool.query("SELECT id, nama_pelanggan, status, tanggal_pesanan FROM pesanan ORDER BY id DESC LIMIT 5");
        
        for (let i = 0; i < antreanRows.length; i++) {
            const pesanan_id = antreanRows[i].id;
            const [detailRows] = await pool.query("SELECT d.id, j.nama_jajanan, d.jumlah FROM detail_pesanan d JOIN jajanan j ON d.jajanan_id = j.id WHERE d.pesanan_id = ?", [pesanan_id]);
            antreanRows[i].details = detailRows;
        }

        res.json({ success: true, total_stok: Number(totalStok), menu: menuRows, antrean: antreanRows, pending: Number(totalPending) });
    } catch (error) { res.status(500).json({ error: error.message }); }
});

// 3. UPDATE STATUS (Dashboard)
app.post('/api/update_status', async (req, res) => {
    try {
        const { id, status } = req.body;
        await pool.query("UPDATE pesanan SET status = ? WHERE id = ?", [status, id]);
        res.json({ success: true });
    } catch (error) { res.status(500).json({ error: error.message }); }
});

// 4. GET MENU (Kasir & Gudang)
app.get('/api/menu', async (req, res) => {
    try {
        const [rows] = await pool.query("SELECT * FROM jajanan ORDER BY id DESC");
        res.json({ success: true, data: rows });
    } catch (error) { res.status(500).json({ error: error.message }); }
});

// 5. CHECKOUT (Kasir)
app.post('/api/checkout', async (req, res) => {
    const { nama_pembeli, total_harga, uang_pembeli, kembalian, metode_pembayaran, keranjang } = req.body;
    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();
        const [pesananResult] = await connection.query(
            "INSERT INTO pesanan (nama_pelanggan, tanggal_pesanan, total_harga, uang_pembeli, kembalian, metode_pembayaran, status) VALUES (?, NOW(), ?, ?, ?, ?, 'pending')",
            [nama_pembeli, total_harga, uang_pembeli, kembalian, metode_pembayaran]
        );
        const pesanan_id = pesananResult.insertId;

        for (let item of keranjang) {
            await connection.query("INSERT INTO detail_pesanan (pesanan_id, jajanan_id, jumlah, subtotal) VALUES (?, ?, ?, ?)", [pesanan_id, item.id, item.qty, item.subtotal]);
            await connection.query("UPDATE jajanan SET stok = stok - ? WHERE id = ?", [item.qty, item.id]);
        }
        await connection.commit();
        res.json({ success: true });
    } catch (error) {
        await connection.rollback();
        res.json({ success: false, message: "Gagal memproses pesanan" });
    } finally { connection.release(); }
});

// 6. TAMBAH MENU (Gudang)
app.post('/api/tambah_menu', async (req, res) => {
    try {
        const { nama_jajanan, harga_modal, harga, stok } = req.body;
        await pool.query("INSERT INTO jajanan (nama_jajanan, harga_modal, harga, stok) VALUES (?, ?, ?, ?)", [nama_jajanan, harga_modal || 0, harga, stok]);
        res.json({ success: true });
    } catch (error) { res.status(500).json({ error: error.message }); }
});

// 7. EDIT MENU (Gudang)
app.post('/api/edit_menu', async (req, res) => {
    try {
        const { id, nama_jajanan, harga_modal, harga, stok } = req.body;
        await pool.query("UPDATE jajanan SET nama_jajanan=?, harga_modal=?, harga=?, stok=? WHERE id=?", [nama_jajanan, harga_modal || 0, harga, stok, id]);
        res.json({ success: true });
    } catch (error) { res.status(500).json({ error: error.message }); }
});

// 8. HAPUS MENU (Gudang)
app.post('/api/hapus_menu', async (req, res) => {
    try {
        const { id } = req.body;
        await pool.query("DELETE FROM jajanan WHERE id=?", [id]);
        res.json({ success: true });
    } catch (error) { res.status(500).json({ error: error.message }); }
});

// 9. KOSONGKAN STOK (Gudang)
app.get('/api/kosongkan_stok', async (req, res) => {
    try {
        await pool.query("UPDATE jajanan SET stok = 0");
        res.json({ success: true });
    } catch (error) { res.status(500).json({ error: error.message }); }
});

// 10. GET LOGBOOK
app.get('/api/logbook', async (req, res) => {
    try {
        const [kotorRows] = await pool.query("SELECT SUM(total_harga) as kotor FROM pesanan WHERE DATE(tanggal_pesanan) = CURDATE() AND status = 'selesai'");
        const kotor = kotorRows[0].kotor || 0;

        const [bersihRows] = await pool.query("SELECT SUM(d.subtotal - (j.harga_modal * d.jumlah)) as bersih FROM detail_pesanan d JOIN pesanan p ON d.pesanan_id = p.id JOIN jajanan j ON d.jajanan_id = j.id WHERE DATE(p.tanggal_pesanan) = CURDATE() AND p.status = 'selesai'");
        const bersih = bersihRows[0].bersih || 0;

        const [historyRows] = await pool.query("SELECT * FROM pesanan ORDER BY id DESC");
        for (let i = 0; i < historyRows.length; i++) {
            const pid = historyRows[i].id;
            const [detRows] = await pool.query("SELECT d.jumlah, j.nama_jajanan, j.harga, d.subtotal FROM detail_pesanan d JOIN jajanan j ON d.jajanan_id = j.id WHERE d.pesanan_id = ?", [pid]);
            historyRows[i].details = detRows;
        }

        res.json({ success: true, pendapatan_kotor: Number(kotor), laba_bersih: Number(bersih), history: historyRows });
    } catch (error) { res.status(500).json({ error: error.message }); }
});

// 11. RESET LOGBOOK
app.get('/api/reset_logbook', async (req, res) => {
    try {
        await pool.query("DELETE FROM detail_pesanan");
        await pool.query("DELETE FROM pesanan");
        res.json({ success: true });
    } catch (error) { res.status(500).json({ error: error.message }); }
});

app.listen(port, () => { console.log(`Server Node.js Fullstack berjalan di http://localhost:${port}`); });