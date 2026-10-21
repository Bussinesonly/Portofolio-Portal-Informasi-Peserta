# Portal Informasi Peserta

Website portal informasi perjalanan tertutup khusus bagi peserta yang telah melakukan registrasi. Dibangun dengan **PHP Native**, **MySQL**, **Vanilla HTML5/CSS3/JS**, ringan, mobile-first, dan siap dideploy di shared hosting biasa (cPanel).

---

## 🚀 Fitur Utama

1. **Alur Akses Tertutup:**
   - Peserta baru otomatis diarahkan ke halaman registrasi (`register.php`).
   - Sesi terlindungi (`info.php` hanya dapat diakses setelah registrasi).
   - Tombol keluar (`logout.php`) dengan pembersihan sesi aman.

2. **Formulir Registrasi:**
   - Validasi ganda (sisi klien via JavaScript & sisi server via PHP).
   - Format nomor HP otomatis dinormalisasi (awalan `+62` / `62` / `0` diselaraskan ke `08xxxxxxxxxx`).
   - Pencegahan pendaftaran duplikat tidak sengaja (kombinasi nama + nomor HP).
   - Proteksi keamanan CSRF Token & Prepared Statements (bebas SQL Injection).
   - Checkbox persetujuan data pribadi yang terhubung ke Kebijakan Privasi (`privacy.php`).

3. **Portal Informasi Perjalanan:**
   - Banner sambutan personalisasi (nama peserta & badge departemen).
   - Tampilan Accordion Card modern & rapi (dikelompokkan berdasarkan kategori: Jadwal, Rute, Persiapan, Peraturan, Kontak).
   - Palet warna profesional Navy Blue (`#0a1628` – `#1b3a6b`) dan aksen biru muda.
   - Tabel jadwal acara terstruktur, checklist perlengkapan dengan ikon centang, dan kotak kontak darurat yang dapat langsung diklik (`tel:` link).

4. **Panel Admin (`admin.php`):**
   - Melihat ringkasan statistik (jumlah peserta terdaftar & item informasi).
   - Melihat tabel daftar seluruh peserta terdaftar beserta departemen, nomor HP, dan waktu daftar.
   - Mengelola item informasi (tambah informasi baru, aktifkan/nonaktifkan, dan hapus).
   - Kredensial default: `admin` / `admin123` *(dapat diubah di bagian atas `admin.php`)*.

---

## 📁 Struktur Direktori

```text
Portofolio-Portal-Informasi-Peserta/
├── config.php      # Konfigurasi database & setelan aplikasi
├── db.php          # Koneksi database PDO & auto-setup tabel + data awal
├── helpers.php     # Helper sesi, auth, normalisasi nomor HP, CSRF, & ikon SVG
├── index.php       # Entry point pengarah (redirector ke info.php atau register.php)
├── register.php    # Halaman pendaftaran peserta
├── info.php        # Halaman utama informasi perjalanan peserta
├── logout.php      # Proses keluar & penghancuran sesi aman
├── privacy.php     # Halaman kebijakan privasi
├── admin.php       # Panel administrasi peserta & konten informasi
├── style.css       # Desain mobile-first, palet navy blue, responsif
├── script.js       # Validasi real-time & interaksi accordion card
└── .htaccess       # Security headers, pencegahan direct indexing & proteksi file
```

---

## 🛠️ Cara Menjalankan Secara Lokal (Local Development)

### Menggunakan PHP Built-in Server & Laragon/XAMPP:
1. Pastikan layanan MySQL/MariaDB aktif (misalnya melalui Laragon atau XAMPP).
2. Sesuaikan kredensial di `config.php` jika diperlukan (default: `localhost`, user `root`, password kosong, nama db `portal_peserta`).
3. Jalankan server lokal dari folder proyek:
   ```bash
   php -S 127.0.0.1:8000
   ```
4. Buka browser smartphone atau desktop di:
   - Portal Peserta: [http://127.0.0.1:8000/](http://127.0.0.1:8000/)
   - Panel Admin: [http://127.0.0.1:8000/admin.php](http://127.0.0.1:8000/admin.php)

> **Catatan Otomatis:** Saat pertama kali halaman dibuka, sistem akan otomatis membuat database `portal_peserta`, tabel `peserta`, tabel `informasi`, serta mengisikan data awal informasi perjalanan kegiatan.

---

## 🌐 Cara Deploy ke Shared Hosting (cPanel)

1. Upload seluruh file ke folder `public_html` (atau subdomain Anda).
2. Buat database MySQL baru dan user database melalui cPanel -> **MySQL Databases**.
3. Buka file `config.php` di File Manager cPanel, sesuaikan:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'namauser_portal_peserta');
   define('DB_USER', 'namauser_dbuser');
   define('DB_PASS', 'password_database_anda');
   ```
4. Ubah kredensial admin di `admin.php`:
   ```php
   define('ADMIN_USER', 'admin_anda');
   define('ADMIN_PASS', 'password_rahasia_anda');
   ```
5. Akses domain Anda. Database dan tabel akan diinisialisasi otomatis pada kunjungan pertama.
