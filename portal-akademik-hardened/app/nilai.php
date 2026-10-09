<?php
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';   // wajib login
require __DIR__ . '/header.php';

/*
 * fix T4 (IDOR / Broken Access Control):
 *   Mahasiswa HANYA boleh melihat KHS miliknya sendiri (dari session).
 *   Parameter ?nim dari URL diabaikan untuk mahasiswa; hanya admin yang
 *   boleh memilih NIM lain. + query pakai prepared statement.
 */
$isAdmin    = ($_SESSION['role'] ?? '') === 'admin';
$sessionNim = $_SESSION['nim'] ?? '';
$nim        = $isAdmin ? ($_GET['nim'] ?? $sessionNim) : $sessionNim;

$mhs = null; $rows = null;
if ($nim !== '') {
    $s1 = mysqli_prepare($conn, 'SELECT nim, nama, prodi, ipk FROM mahasiswa WHERE nim = ?');
    mysqli_stmt_bind_param($s1, 's', $nim);
    mysqli_stmt_execute($s1);
    $mhs = mysqli_fetch_assoc(mysqli_stmt_get_result($s1));

    $s2 = mysqli_prepare($conn, 'SELECT kode_mk, nama_mk, sks, semester, nilai_huruf FROM nilai WHERE nim = ? ORDER BY semester');
    mysqli_stmt_bind_param($s2, 's', $nim);
    mysqli_stmt_execute($s2);
    $rows = mysqli_stmt_get_result($s2);
}
?>
<div class="card">
  <h2>Kartu Hasil Studi</h2>
  <p class="sub">Rekap nilai per mata kuliah.</p>

  <?php if ($isAdmin): ?>
  <form method="get" action="/nilai.php" style="margin-bottom:18px">
    <div class="form-group" style="max-width:260px">
      <label>NIM (admin dapat memilih)</label>
      <input type="text" name="nim" value="<?= htmlspecialchars($nim) ?>">
    </div>
    <button class="btn" type="submit">Tampilkan</button>
  </form>
  <?php endif; ?>

  <?php if ($mhs): ?>
    <p class="muted"><strong><?= htmlspecialchars($mhs['nama']) ?></strong>
       · <?= htmlspecialchars($mhs['prodi']) ?> · IPK <?= htmlspecialchars($mhs['ipk']) ?></p>
    <table>
      <thead><tr><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Semester</th><th>Nilai</th></tr></thead>
      <tbody>
      <?php while ($rows && $r = mysqli_fetch_assoc($rows)): ?>
        <tr>
          <td><?= htmlspecialchars($r['kode_mk']) ?></td>
          <td><?= htmlspecialchars($r['nama_mk']) ?></td>
          <td><?= htmlspecialchars($r['sks']) ?></td>
          <td><?= htmlspecialchars($r['semester']) ?></td>
          <td><strong><?= htmlspecialchars($r['nilai_huruf']) ?></strong></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-info">Data KHS tidak ditemukan.</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
