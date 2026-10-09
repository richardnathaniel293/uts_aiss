<?php
/**
 * Guard sederhana: pastikan user sudah login.
 * Dipakai oleh halaman yang seharusnya terproteksi.
 *
 * [VULN - Broken Access Control] guard ini HANYA cek "sudah login",
 * tidak pernah cek ROLE. Sehingga admin.php (yang meng-include ini)
 * bisa diakses mahasiswa biasa. Diperbaiki saat hardening (RBAC).
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
