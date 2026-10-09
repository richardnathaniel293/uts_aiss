<?php
/**
 * Koneksi database (mysqli) — versi hardened.
 *  - Kredensial WAJIB dari environment (tanpa fallback hardcoded)  -> fix misconfig
 *  - Akun DB least-privilege (portal_app)                          -> fix T9
 *  - Error tidak dibocorkan ke user (pesan generik + error_log)    -> fix T8 (info disclosure)
 */
$DB_HOST = getenv('DB_HOST');
$DB_USER = getenv('DB_USER');
$DB_PASS = getenv('DB_PASS');
$DB_NAME = getenv('DB_NAME');

if (!$DB_HOST || !$DB_USER || !$DB_NAME) {
    error_log('[portal] Konfigurasi DB tidak lengkap (env).');
    http_response_code(500);
    exit('Server error.');
}

// Jangan lempar detail error mysqli ke output.
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$conn) {
    error_log('[portal] Koneksi DB gagal: ' . mysqli_connect_error());
    http_response_code(503);
    exit('Layanan sedang tidak tersedia. Silakan coba beberapa saat lagi.');
}
mysqli_set_charset($conn, 'utf8mb4');
