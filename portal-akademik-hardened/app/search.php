<?php
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/header.php';

/*
 * fix T5 (SQL Injection UNION): PREPARED STATEMENT dengan parameter terikat.
 *   Pola LIKE memakai placeholder sehingga input tidak pernah menjadi bagian
 *   struktur SQL. UNION/komentar hanya diperlakukan sebagai teks pencarian.
 */
$q = trim($_GET['q'] ?? '');
$results = null;

if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = mysqli_prepare($conn, 'SELECT nim, nama, prodi, angkatan FROM mahasiswa WHERE nama LIKE ? ORDER BY nama');
    mysqli_stmt_bind_param($stmt, 's', $like);
    mysqli_stmt_execute($stmt);
    $results = mysqli_stmt_get_result($stmt);
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
  <?php elseif ($q !== ''): ?>
    <div class="alert alert-info">Tidak ada hasil untuk "<?= htmlspecialchars($q) ?>".</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
