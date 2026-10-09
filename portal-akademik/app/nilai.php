<?php
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';   // cukup login (tidak cek kepemilikan!)
require __DIR__ . '/header.php';

/*
 * =============================================================================
 * [VULN 4 - Broken Access Control / IDOR - Elevation of Privilege (STRIDE)]
 *   Parameter ?nim= diambil dari URL tanpa memverifikasi bahwa nim tersebut
 *   milik user yang sedang login. Mahasiswa "budi" bisa melihat KHS mahasiswa
 *   lain hanya dengan mengganti:  /nilai.php?nim=160423002
 *
 * Saat HARDENING: abaikan ?nim dari user biasa; pakai $_SESSION['nim'].
 *   Hanya role admin yang boleh memilih nim lain.
 * =============================================================================
 */
$nim = $_GET['nim'] ?? ($_SESSION['nim'] ?? '');

// data mahasiswa (escaped) — fokus vuln di sini adalah IDOR, bukan SQLi
$safe = mysqli_real_escape_string($conn, $nim);
$mhs  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM mahasiswa WHERE nim='$safe'"));
$rows = mysqli_query($conn, "SELECT * FROM nilai WHERE nim='$safe' ORDER BY semester");
?>
<div class="card">
  <h2>Kartu Hasil Studi</h2>
  <p class="sub">Rekap nilai per mata kuliah.</p>

  <form method="get" action="/nilai.php" style="margin-bottom:18px">
    <div class="form-group" style="max-width:260px">
      <label>NIM</label>
      <input type="text" name="nim" value="<?= htmlspecialchars($nim) ?>">
    </div>
    <button class="btn" type="submit">Tampilkan</button>
  </form>

  <?php if ($mhs): ?>
    <p class="muted"><strong><?= htmlspecialchars($mhs['nama']) ?></strong>
       · <?= htmlspecialchars($mhs['prodi']) ?> · IPK <?= htmlspecialchars($mhs['ipk']) ?></p>
    <table>
      <thead><tr><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Semester</th><th>Nilai</th></tr></thead>
      <tbody>
      <?php while ($r = mysqli_fetch_assoc($rows)): ?>
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
    <div class="alert alert-info">Data untuk NIM tersebut tidak ditemukan.</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
