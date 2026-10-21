<?php
/**
 * Kebijakan Privasi
 * Portal Informasi Peserta
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

initSession();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="Kebijakan privasi Portal Informasi Peserta.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0f2140">
    <title>Kebijakan Privasi — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="style.css?v=<?= APP_VERSION ?>">
</head>
<body>
    <div class="page-wrapper">

        <!-- Header -->
        <header class="header">
            <div class="header-inner">
                <div class="header-brand">
                    <div class="header-logo">
                        <?= icon('compass', 22) ?>
                    </div>
                    <div>
                        <div class="header-title"><?= e(APP_NAME) ?></div>
                        <div class="header-subtitle">Kebijakan Privasi</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="privacy-page">
            <a href="javascript:history.back()" class="back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                Kembali
            </a>

            <h1 style="font-size: 1.3rem; color: var(--navy-800); margin-bottom: 8px;">Kebijakan Privasi</h1>
            <p style="color: var(--gray-400); font-size: 0.8rem; margin-bottom: 24px;">
                Terakhir diperbarui: <?= date('d F Y') ?>
            </p>

            <h2>1. Data yang Kami Kumpulkan</h2>
            <p>Saat Anda mendaftar melalui portal ini, kami mengumpulkan informasi berikut:</p>
            <ul>
                <li>Nama lengkap</li>
                <li>Departemen / unit kerja</li>
                <li>Nomor telepon seluler (HP)</li>
            </ul>

            <h2>2. Tujuan Pengumpulan Data</h2>
            <p>Data yang dikumpulkan digunakan semata-mata untuk:</p>
            <ul>
                <li>Mengidentifikasi peserta kegiatan perjalanan.</li>
                <li>Memberikan akses ke informasi perjalanan yang relevan.</li>
                <li>Keperluan koordinasi dan komunikasi terkait kegiatan.</li>
                <li>Pencatatan administratif kegiatan.</li>
            </ul>

            <h2>3. Penyimpanan Data</h2>
            <p>Data peserta disimpan secara aman di database server dengan akses yang dibatasi hanya kepada panitia penyelenggara. Data disimpan selama periode kegiatan berlangsung dan akan dihapus setelah kegiatan selesai atau sesuai kebutuhan administratif.</p>

            <h2>4. Perlindungan Data</h2>
            <p>Kami mengambil langkah-langkah teknis berikut untuk melindungi data Anda:</p>
            <ul>
                <li>Penggunaan prepared statements untuk mencegah SQL injection.</li>
                <li>Validasi input pada sisi klien dan server.</li>
                <li>Perlindungan terhadap serangan CSRF (Cross-Site Request Forgery).</li>
                <li>Sesi aman dengan konfigurasi httponly cookie.</li>
                <li>Pembatasan akses data antarpengguna.</li>
            </ul>

            <h2>5. Berbagi Data</h2>
            <p>Data peserta <strong>tidak akan</strong> dibagikan kepada pihak ketiga tanpa persetujuan Anda, kecuali diwajibkan oleh peraturan perundang-undangan yang berlaku.</p>

            <h2>6. Hak Peserta</h2>
            <p>Anda memiliki hak untuk:</p>
            <ul>
                <li>Mengetahui data apa saja yang disimpan tentang Anda.</li>
                <li>Meminta pembaruan atau koreksi data yang tidak akurat.</li>
                <li>Meminta penghapusan data pribadi Anda.</li>
            </ul>
            <p>Untuk menggunakan hak-hak tersebut, silakan hubungi panitia penyelenggara.</p>

            <h2>7. Kontak</h2>
            <p>Jika Anda memiliki pertanyaan tentang kebijakan privasi ini, silakan hubungi panitia penyelenggara kegiatan melalui kontak yang tersedia di halaman informasi.</p>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Seluruh hak cipta dilindungi.</p>
        </footer>
    </div>
</body>
</html>
