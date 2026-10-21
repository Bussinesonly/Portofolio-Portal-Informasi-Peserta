<?php
/**
 * Koneksi Database & Auto-Setup Tabel
 * Portal Informasi Peserta
 */

require_once __DIR__ . '/config.php';

/**
 * Membuat koneksi PDO ke MySQL.
 * 
 * @return PDO
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // Koneksi awal tanpa nama database (untuk membuat DB jika belum ada)
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Buat database jika belum ada
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` 
                     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");

        // Buat tabel peserta
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `peserta` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `nama_lengkap` VARCHAR(150)  NOT NULL,
                `departemen`   VARCHAR(100)  NOT NULL,
                `no_hp`        VARCHAR(20)   NOT NULL,
                `token`        VARCHAR(64)   NOT NULL UNIQUE,
                `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_access`  DATETIME      NULL,
                INDEX `idx_token`  (`token`),
                INDEX `idx_no_hp` (`no_hp`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Buat tabel informasi perjalanan
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `informasi` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `urutan`       INT UNSIGNED  NOT NULL DEFAULT 0,
                `kategori`     VARCHAR(50)   NOT NULL DEFAULT 'umum',
                `judul`        VARCHAR(200)  NOT NULL,
                `ikon`         VARCHAR(50)   NULL,
                `konten`       TEXT          NOT NULL,
                `aktif`        TINYINT(1)    NOT NULL DEFAULT 1,
                `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`   DATETIME      NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_aktif_urutan` (`aktif`, `urutan`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Seed data informasi jika tabel masih kosong
        $count = $pdo->query("SELECT COUNT(*) FROM `informasi`")->fetchColumn();
        if ((int)$count === 0) {
            seedInformasi($pdo);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo '<div style="font-family:system-ui;padding:2rem;text-align:center;">';
        echo '<h2>Koneksi Database Gagal</h2>';
        echo '<p>Pastikan konfigurasi database di <code>config.php</code> sudah benar.</p>';
        echo '<p style="color:#666;font-size:0.85rem;">' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
        exit;
    }

    return $pdo;
}

/**
 * Mengisi data awal informasi perjalanan.
 */
function seedInformasi(PDO $pdo): void
{
    $data = [
        [
            'urutan'   => 1,
            'kategori' => 'jadwal',
            'judul'    => 'Jadwal Keberangkatan',
            'ikon'     => 'clock',
            'konten'   => '<p><strong>Hari/Tanggal:</strong> Sabtu, 15 November 2026</p>
<p><strong>Waktu Berkumpul:</strong> Pukul 05.30 WIB</p>
<p><strong>Estimasi Berangkat:</strong> Pukul 06.00 WIB</p>
<p><strong>Titik Kumpul:</strong> Lobby Utama Gedung A</p>
<div class="info-note">
    <strong>⏰ Penting:</strong> Harap hadir tepat waktu. Bus akan berangkat sesuai jadwal tanpa menunggu peserta yang terlambat.
</div>'
        ],
        [
            'urutan'   => 2,
            'kategori' => 'rute',
            'judul'    => 'Rute Perjalanan',
            'ikon'     => 'map',
            'konten'   => '<p>Perjalanan menuju <strong>Taman Wisata Alam Gunung Pancar, Bogor</strong>.</p>
<p><strong>Estimasi Waktu Tempuh:</strong> ± 2 jam 30 menit</p>
<p><strong>Rute:</strong> Jakarta → Tol Jagorawi → Exit Sentul → Gunung Pancar</p>
<p>Bus akan melakukan <strong>1 kali pemberhentian</strong> di rest area untuk istirahat.</p>'
        ],
        [
            'urutan'   => 3,
            'kategori' => 'jadwal',
            'judul'    => 'Susunan Acara',
            'ikon'     => 'calendar',
            'konten'   => '<table class="schedule-table">
    <thead><tr><th>Waktu</th><th>Kegiatan</th></tr></thead>
    <tbody>
        <tr><td>05.30 – 06.00</td><td>Registrasi & Persiapan</td></tr>
        <tr><td>06.00 – 08.30</td><td>Perjalanan Menuju Lokasi</td></tr>
        <tr><td>08.30 – 09.00</td><td>Pembukaan & Briefing</td></tr>
        <tr><td>09.00 – 12.00</td><td>Kegiatan Outbound & Team Building</td></tr>
        <tr><td>12.00 – 13.30</td><td>ISHOMA (Istirahat, Sholat, Makan)</td></tr>
        <tr><td>13.30 – 15.30</td><td>Fun Games & Lomba</td></tr>
        <tr><td>15.30 – 16.00</td><td>Penutupan & Pembagian Hadiah</td></tr>
        <tr><td>16.00 – 18.30</td><td>Perjalanan Kembali</td></tr>
    </tbody>
</table>'
        ],
        [
            'urutan'   => 4,
            'kategori' => 'persiapan',
            'judul'    => 'Perlengkapan yang Dibawa',
            'ikon'     => 'bag',
            'konten'   => '<ul class="checklist">
    <li>Pakaian olahraga / kaos yang nyaman</li>
    <li>Sepatu olahraga / sneakers</li>
    <li>Jaket tipis atau jas hujan</li>
    <li>Topi / kacamata hitam</li>
    <li>Obat-obatan pribadi</li>
    <li>Botol minum tumbler</li>
    <li>Sunscreen / tabir surya</li>
    <li>Power bank & kabel charger</li>
</ul>
<div class="info-note">
    <strong>📌 Catatan:</strong> Gunakan pakaian yang nyaman untuk bergerak. Hindari menggunakan sandal jepit.
</div>'
        ],
        [
            'urutan'   => 5,
            'kategori' => 'peraturan',
            'judul'    => 'Peraturan Peserta',
            'ikon'     => 'shield',
            'konten'   => '<ol>
    <li>Wajib mematuhi instruksi panitia selama kegiatan berlangsung.</li>
    <li>Dilarang meninggalkan area kegiatan tanpa izin panitia.</li>
    <li>Menjaga kebersihan dan ketertiban di lokasi.</li>
    <li>Tidak membawa atau mengonsumsi minuman beralkohol.</li>
    <li>Melaporkan kondisi kesehatan khusus kepada panitia.</li>
    <li>Menjaga barang bawaan pribadi masing-masing.</li>
    <li>Berpartisipasi aktif dalam seluruh rangkaian acara.</li>
</ol>'
        ],
        [
            'urutan'   => 6,
            'kategori' => 'kontak',
            'judul'    => 'Kontak Darurat & Panitia',
            'ikon'     => 'phone',
            'konten'   => '<div class="contact-card">
    <p><strong>Koordinator Lapangan</strong></p>
    <p>Budi Santoso — <a href="tel:+6281234567890">0812-3456-7890</a></p>
</div>
<div class="contact-card">
    <p><strong>Sekretariat Panitia</strong></p>
    <p>Siti Rahayu — <a href="tel:+6285678901234">0856-7890-1234</a></p>
</div>
<div class="contact-card">
    <p><strong>Tim Medis</strong></p>
    <p>dr. Ahmad — <a href="tel:+6287890123456">0878-9012-3456</a></p>
</div>
<div class="info-note">
    <strong>🚨 Darurat:</strong> Hubungi koordinator lapangan atau tim medis segera jika terjadi keadaan darurat.
</div>'
        ],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO `informasi` (`urutan`, `kategori`, `judul`, `ikon`, `konten`)
        VALUES (:urutan, :kategori, :judul, :ikon, :konten)
    ");

    foreach ($data as $item) {
        $stmt->execute($item);
    }
}
