<?php
require __DIR__ . '/db.php';
require __DIR__ . '/crypto.php';
require __DIR__ . '/auth.php';

require_admin();                 // fix T6: RBAC default-deny SEBELUM output apa pun
require __DIR__ . '/header.php';

/*
 * Hardening tambahan:
 *  - Hash password TIDAK ditampilkan (sebelumnya bocor ke UI).
 *  - PII (no_telp, alamat) didekripsi dari ciphertext AES hanya untuk admin.
 */
$users = mysqli_query($conn, 'SELECT id, username, role, nim FROM users ORDER BY id');
$mhs   = mysqli_query($conn, 'SELECT nim, nama, prodi, email, no_telp, alamat FROM mahasiswa ORDER BY nim');
?>
<div class="card">
  <h2>Panel Admin</h2>
  <p class="sub">Manajemen akun &amp; data mahasiswa (area terbatas — RBAC).</p>

  <h3 style="color:#0f2b46">Daftar Akun</h3>
  <table>
    <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>NIM</th></tr></thead>
    <tbody>
    <?php while ($u = mysqli_fetch_assoc($users)): ?>
      <tr>
        <td><?= htmlspecialchars($u['id']) ?></td>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= htmlspecialchars($u['role']) ?></td>
        <td><?= htmlspecialchars($u['nim'] ?? '-') ?></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  <p class="muted">Hash password tidak ditampilkan (prinsip minimisasi paparan data).</p>
</div>

<div class="card">
  <h3 style="color:#0f2b46">Data Pribadi Mahasiswa</h3>
  <p class="muted">Kolom No. Telp &amp; Alamat disimpan terenkripsi (AES-256-GCM) dan hanya didekripsi untuk admin.</p>
  <table>
    <thead><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Email</th><th>No. Telp</th><th>Alamat</th></tr></thead>
    <tbody>
    <?php while ($m = mysqli_fetch_assoc($mhs)): ?>
      <tr>
        <td><?= htmlspecialchars($m['nim']) ?></td>
        <td><?= htmlspecialchars($m['nama']) ?></td>
        <td><?= htmlspecialchars($m['prodi']) ?></td>
        <td><?= htmlspecialchars($m['email']) ?></td>
        <td><?= htmlspecialchars(pa_decrypt($m['no_telp'])) ?></td>
        <td><?= htmlspecialchars(pa_decrypt($m['alamat'])) ?></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>
