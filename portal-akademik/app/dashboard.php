<?php
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';   // wajib login
require __DIR__ . '/header.php';

$nim  = $_SESSION['nim'] ?? null;
$mhs  = null;
$jml_mk = 0;

if ($nim) {
    // NIM diambil dari session (tepercaya), jadi query ini relatif aman.
    $res = mysqli_query($conn, "SELECT * FROM mahasiswa WHERE nim = '" . mysqli_real_escape_string($conn, $nim) . "'");
    $mhs = $res ? mysqli_fetch_assoc($res) : null;

    $r2 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM nilai WHERE nim = '" . mysqli_real_escape_string($conn, $nim) . "'");
    $jml_mk = $r2 ? (int)mysqli_fetch_assoc($r2)['c'] : 0;
}
?>
<div class="card">
  <h2>Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h2>
  <p class="sub">
    Peran:
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
      <span class="badge badge-admin">ADMIN</span>
    <?php else: ?>
      <span class="badge badge-mhs">MAHASISWA</span>
    <?php endif; ?>
  </p>

  <?php if ($mhs): ?>
    <div class="stat-grid">
      <div class="stat"><div class="num"><?= htmlspecialchars($mhs['ipk']) ?></div><div class="lbl">IPK</div></div>
      <div class="stat"><div class="num"><?= $jml_mk ?></div><div class="lbl">Mata Kuliah</div></div>
      <div class="stat"><div class="num"><?= htmlspecialchars($mhs['angkatan']) ?></div><div class="lbl">Angkatan</div></div>
    </div>
    <p class="muted" style="margin-top:16px">
      <strong><?= htmlspecialchars($mhs['nama']) ?></strong> · <?= htmlspecialchars($mhs['prodi']) ?> ·
      NIM <?= htmlspecialchars($mhs['nim']) ?>
    </p>
  <?php else: ?>
    <div class="alert alert-info">Akun administrator. Gunakan <a href="/admin.php">Panel Admin</a> untuk mengelola data.</div>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Pintasan</h2>
  <p class="sub">Akses cepat layanan akademik.</p>
  <p>
    <a class="btn" href="/nilai.php">Lihat Kartu Hasil Studi</a>
    &nbsp;
    <a class="btn" href="/search.php" style="background:#173d63">Cari Mahasiswa</a>
  </p>
</div>
<?php require __DIR__ . '/footer.php'; ?>
