<?php
require_once __DIR__ . '/config.php';

// Hapus semua data sesi
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Mulai sesi baru hanya untuk flash message
session_start();
set_flash('info', 'Anda telah berhasil keluar dari akun member.');

header('Location: login.php');
exit;
