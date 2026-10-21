<?php
/**
 * Index / Entry Point
 * Portal Informasi Peserta
 * 
 * Mengarahkan pengguna ke halaman yang sesuai
 * berdasarkan status sesi.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

initSession();

if (isAuthenticated()) {
    redirect('info.php');
} else {
    redirect('register.php');
}
