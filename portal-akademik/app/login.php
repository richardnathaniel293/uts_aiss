<?php
require __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    /*
     * =========================================================================
     * [VULN 1 - SQL Injection / Tampering + Spoofing (STRIDE)]
     *   Input user dirangkai langsung ke query -> auth bypass.
     *   Contoh payload username:  admin' --
     *   Contoh payload universal: ' OR '1'='1' --
     *
     * [VULN 2 - Weak Cryptography]
     *   Password di-hash MD5 tanpa salt -> mudah di-crack (rainbow table).
     *
     * [VULN 3 - Broken Authentication / DoS]
     *   Tidak ada rate limiting / lockout -> rentan brute force (Hydra).
     *   Tidak ada MFA.
     *
     * Saat HARDENING: gunakan prepared statement, password_hash() Argon2id,
     * pindahkan autentikasi ke IAM (Authelia/Keycloak) + MFA di reverse proxy,
     * dan pasang Fail2ban.
     * =========================================================================
     */
    // Query sengaja satu baris: komentar SQL (-- / #) hanya berlaku sampai
    // akhir baris, sehingga payload klasik auth-bypass bekerja sebagaimana mestinya.
    $sql = "SELECT id, username, role, nim FROM users WHERE username = '$username' AND password = '" . md5($password) . "'";

    $res = mysqli_query($conn, $sql);

    if ($res === false) {
        // [VULN - Information Disclosure] error SQL bocor ke user (membantu attacker menyusun payload).
        $error = 'Query error: ' . mysqli_error($conn);
    } elseif (mysqli_num_rows($res) > 0) {
        $u = mysqli_fetch_assoc($res);
        $_SESSION['user_id']  = $u['id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['role']     = $u['role'];
        $_SESSION['nim']      = $u['nim'];
        header('Location: /dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}

require __DIR__ . '/header.php';
?>
<div class="login-wrap">
  <div class="card">
    <h2>Masuk ke Portal</h2>
    <p class="sub">Gunakan akun SSO akademik Anda.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/login.php">
      <div class="form-group">
        <label for="username">Username / NIM</label>
        <input type="text" id="username" name="username" autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password">
      </div>
      <button class="btn btn-block" type="submit">Masuk</button>
    </form>

    <div class="hint">
      Akun demo (lingkungan simulasi): <code>budi / budi123</code> · <code>admin / Admin@123</code>
    </div>
  </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
