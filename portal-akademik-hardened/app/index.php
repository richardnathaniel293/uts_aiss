<?php
require_once __DIR__ . '/session_boot.php';
header('Location: ' . (isset($_SESSION['user_id']) ? '/dashboard.php' : '/login.php'));
exit;
