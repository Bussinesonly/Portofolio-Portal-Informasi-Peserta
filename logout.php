<?php
/**
 * Logout / Keluar
 * Portal Informasi Peserta
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

initSession();

// Hapus seluruh data sesi
$_SESSION = [];

// Hapus cookie sesi
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

redirect('register.php');
