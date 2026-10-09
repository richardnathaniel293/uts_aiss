<?php
/**
 * Enkripsi Data at Rest untuk kolom PII (no_telp, alamat) — AES-256-GCM.
 * Kunci diambil dari env APP_KEY (64 hex = 32 byte). Format tersimpan:
 *   base64( iv[12] || tag[16] || ciphertext )
 */
function pa_key(): string {
    $hex = getenv('APP_KEY') ?: '';
    if (strlen($hex) !== 64 || !ctype_xdigit($hex)) {
        error_log('APP_KEY tidak valid (butuh 64 hex).');
        http_response_code(500);
        exit('Server error');
    }
    return hex2bin($hex);
}

function pa_encrypt(string $plain): string {
    $key = pa_key();
    $iv  = random_bytes(12);
    $tag = '';
    $ct  = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $ct);
}

function pa_decrypt(?string $b64): string {
    if ($b64 === null || $b64 === '') return '';
    $raw = base64_decode($b64, true);
    if ($raw === false || strlen($raw) < 29) return '';
    $key = pa_key();
    $iv  = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $ct  = substr($raw, 28);
    $pt  = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $pt === false ? '' : $pt;
}
