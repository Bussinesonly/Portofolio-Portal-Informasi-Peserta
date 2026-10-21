<?php
/**
 * Konfigurasi Database dan Aplikasi
 * Portal Informasi Peserta
 * 
 * Ubah nilai konstanta di bawah sesuai dengan
 * kredensial hosting Anda.
 */

// ─── Database ────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'portal_peserta');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ─── Aplikasi ────────────────────────────────────────
define('APP_NAME', 'Portal Informasi Peserta');
define('APP_VERSION', '1.0.0');

// Durasi sesi akses dalam detik (default: 7 hari)
define('SESSION_LIFETIME', 60 * 60 * 24 * 7);

// Timezone
date_default_timezone_set('Asia/Jakarta');
