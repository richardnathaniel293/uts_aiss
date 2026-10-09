<?php
require __DIR__ . '/db.php';
require_once __DIR__ . '/session_boot.php';

$error  = null;
$info   = isset($_GET['timeout']) ? 'Sesi berakhir karena tidak aktif. Silakan masuk kembali.' : null;
$LOCK_THRESHOLD = 5;
$LOCK_MINUTES   = 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip       = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '-';

    /*
     * fix T1 (SQLi auth bypass): PREPARED STATEMENT — input tidak pernah dirangkai ke SQL.
     * fix T2 (weak hashing)    : password_verify() terhadap hash Argon2id.
     * fix T3 (brute force)     : lockout per akun + log untuk Fail2ban/IAM.
     * fix T8 (info disclosure) : pesan error generik & seragam (anti user-enumeration).
     * fix T10 (session)        : session_regenerate_id(true) saat berhasil.
     */
    $stmt = mysqli_prepare($conn,
        'SELECT id, username, role, nim, password, failed_attempts, locked_until
         FROM users WHERE username = ?');
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $u = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    $locked = $u && $u['locked_until'] !== null && strtotime($u['locked_until']) > time();

    if ($locked) {
        $error = 'Akun terkunci sementara karena terlalu banyak percobaan. Coba lagi nanti.';
    } elseif ($u && password_verify($password, $u['password'])) {
        // Rehash bila parameter berubah
        if (password_needs_rehash($u['password'], PASSWORD_ARGON2ID)) {
            $new = password_hash($password, PASSWORD_ARGON2ID);
            $up = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
            mysqli_stmt_bind_param($up, 'si', $new, $u['id']);
            mysqli_stmt_execute($up);
        }
        // Reset counter
        $rs = mysqli_prepare($conn, 'UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?');
        mysqli_stmt_bind_param($rs, 'i', $u['id']);
        mysqli_stmt_execute($rs);

        session_regenerate_id(true);
        $_SESSION['user_id']  = $u['id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['role']     = $u['role'];
        $_SESSION['nim']      = $u['nim'];
        header('Location: /dashboard.php');
        exit;
    } else {
        // Naikkan counter bila akun ada; pesan tetap generik.
        if ($u) {
            $fa   = (int)$u['failed_attempts'] + 1;
            $lock = $fa >= $LOCK_THRESHOLD ? date('Y-m-d H:i:s', time() + $LOCK_MINUTES * 60) : null;
            $uf = mysqli_prepare($conn, 'UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?');
            mysqli_stmt_bind_param($uf, 'isi', $fa, $lock, $u['id']);
            mysqli_stmt_execute($uf);
        }
        // Baris log terstruktur untuk Fail2ban (filter portal-auth).
        error_log('[portal] AUTH_FAIL user=' . $username . ' ip=' . $ip);
        $error = 'Username atau password salah.';
    }
}

require __DIR__ . '/header.php';
?>
<div class="login-wrap">
  <div class="card">
    <h2>Masuk ke Portal</h2>
    <p class="sub">Gunakan akun SSO akademik Anda.</p>

    <?php if ($info): ?><div class="alert alert-info"><?= htmlspecialchars($info) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post" action="/login.php">
      <div class="form-group">
        <label for="username">Username / NIM</label>
        <input type="text" id="username" name="username" autofocus autocomplete="username">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password">
      </div>
      <button class="btn btn-block" type="submit">Masuk</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
