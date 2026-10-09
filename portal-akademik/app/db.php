<?php
/**
 * Koneksi database (mysqli).
 * Kredensial diambil dari environment (docker-compose), dengan fallback.
 *
 * [VULN - minor] fallback kredensial di-hardcode. Saat hardening: hapus fallback,
 * wajibkan env / secrets, dan gunakan akun DB ber-privilege minimal (bukan root).
 */

$DB_HOST = getenv('DB_HOST') ?: 'db';
$DB_USER = getenv('DB_USER') ?: 'portal';
$DB_PASS = getenv('DB_PASS') ?: 'portal123';
$DB_NAME = getenv('DB_NAME') ?: 'portal_akademik';

$conn = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    // [VULN - Information Disclosure] menampilkan detail error DB ke user.
    // Saat hardening: log ke file, tampilkan pesan generik.
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
