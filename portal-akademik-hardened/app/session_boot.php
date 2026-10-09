<?php
/**
 * Bootstrap sesi aman — fix T10 (session hijack).
 * Cookie: HttpOnly + SameSite=Lax + Secure (otomatis saat HTTPS / di belakang TLS proxy).
 * ID sesi diregenerasi saat login (lihat login.php).
 */
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);
    session_name('PORTALSESID');
    session_start();

    // Idle timeout 30 menit
    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > 1800)) {
        $_SESSION = [];
        session_destroy();
        header('Location: /login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}
