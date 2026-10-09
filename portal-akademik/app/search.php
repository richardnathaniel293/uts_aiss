<?php
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/header.php';

/*
 * =============================================================================
 * [VULN 5 - SQL Injection (UNION-based) - Information Disclosure (STRIDE)]
 *   Parameter pencarian 'q' dirangkai langsung ke query LIKE.
 *   Query dasar mengembalikan 4 kolom (nim, nama, prodi, angkatan), sehingga
 *   attacker bisa membocorkan tabel users lewat UNION, contoh q:
 *       %' UNION SELECT username, password, role, 1 FROM users --
 *   -> menampilkan hash password seluruh akun.
 *
 * Saat HARDENING: gunakan prepared statement (bind_param), dan/atau WAF
 * (ModSecurity CRS) untuk memblokir pola SQLi.
 * =============================================================================
 */
$q = $_GET['q'] ?? '';
$results = null;
$err = null;

if ($q !== '') {
    $sql = "SELECT nim, nama, prodi, angkatan
            FROM mahasiswa
            WHERE nama LIKE '%$q%'";
    $results = mysqli_query($conn, $sql);
    if ($results === false) {
        $err = 'Query error: ' . mysqli_error($conn);   // [VULN] error bocor
    }
}
?>
<div class="card">
  <h2>Direktori Mahasiswa</h2>
  <p class="sub">Cari mahasiswa berdasarkan nama.</p>

  <form method="get" action="/search.php" style="margin-bottom:18px">
    <div class="form-group" style="max-width:360px">
      <label>Nama mahasiswa</label>
      <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="mis. Budi">
    </div>
    <button class="btn" type="submit">Cari</button>
  </form>

  <?php if ($err): ?>
    <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <?php if ($results && mysqli_num_rows($results) > 0): ?>
    <table>
      <thead><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Angkatan</th></tr></thead>
      <tbody>
      <?php while ($r = mysqli_fetch_assoc($results)): ?>
        <tr>
          <td><?= htmlspecialchars($r['nim']) ?></td>
          <td><?= htmlspecialchars($r['nama']) ?></td>
          <td><?= htmlspecialchars($r['prodi']) ?></td>
          <td><?= htmlspecialchars($r['angkatan']) ?></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  <?php elseif ($q !== '' && !$err): ?>
    <div class="alert alert-info">Tidak ada hasil untuk "<?= htmlspecialchars($q) ?>".</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
