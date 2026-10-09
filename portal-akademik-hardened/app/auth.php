<?php
/**
 * Guard akses — versi hardened.
 *  - require login (seperti sebelumnya)
 *  - require_admin(): RBAC default-deny untuk area admin -> fix T6 (broken access control)
 */
require_once __DIR__ . '/session_boot.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

/**
 * Tolak akses bila bukan admin. Panggil SEBELUM header.php di halaman admin.
 */
function require_admin(): void {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">'
           . '<title>403 Forbidden</title><link rel="stylesheet" href="/style.css"></head><body>'
           . '<div class="container"><div class="card">'
           . '<h2>403 — Akses Ditolak</h2>'
           . '<div class="alert alert-error">Area ini khusus administrator. '
           . 'Percobaan akses dicatat.</div>'
           . '<p><a class="btn" href="/dashboard.php">Kembali ke Dashboard</a></p>'
           . '</div></div></body></html>';
        error_log('[portal] RBAC deny: user=' . ($_SESSION['username'] ?? '?')
                . ' role=' . ($_SESSION['role'] ?? '?') . ' path=' . ($_SERVER['REQUEST_URI'] ?? ''));
        exit;
    }
}
