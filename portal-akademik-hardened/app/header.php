<?php
/**
 * Header + navigasi bersama — versi hardened.
 * Sesi diinisialisasi oleh session_boot.php (cookie HttpOnly/SameSite/Secure).
 */
require_once __DIR__ . '/session_boot.php';
$loggedIn = isset($_SESSION['user_id']);
$role     = $_SESSION['role']     ?? null;
$username = $_SESSION['username'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Akademik - Universitas Nusantara</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">
        <span class="logo">🎓</span>
        <span class="brand-name">Portal Akademik <small>Universitas Nusantara</small></span>
    </div>
    <nav class="mainnav">
        <?php if ($loggedIn): ?>
            <a href="/dashboard.php">Dashboard</a>
            <a href="/nilai.php">Kartu Hasil Studi</a>
            <a href="/search.php">Direktori Mahasiswa</a>
            <?php if ($role === 'admin'): ?>
                <a href="/admin.php">Panel Admin</a>
            <?php endif; ?>
            <span class="nav-user">👤 <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($role) ?>)</span>
            <a class="btn-logout" href="/logout.php">Keluar</a>
        <?php else: ?>
            <a href="/login.php">Masuk</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
