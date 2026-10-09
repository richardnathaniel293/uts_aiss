<?php
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';   // [VULN] hanya cek login, TIDAK cek role admin!
require __DIR__ . '/header.php';

/*
 * =============================================================================
 * [VULN 6 - Broken Access Control - Elevation of Privilege (STRIDE)]
 *   Halaman admin ini seharusnya khusus role 'admin', tetapi hanya memakai
 *   auth.php (cek login saja). Akibatnya mahasiswa biasa yang tahu/menebak URL
 *   /admin.php dapat melihat SELURUH akun (termasuk hash password) & data
 *   pribadi seluruh mahasiswa (no_telp, alamat).
 *
 *   Cek yang BENAR (dinonaktifkan, diaktifkan saat hardening):
 *       if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit('Forbidden'); }
 * =============================================================================
 */

$users = mysqli_query($conn, "SELECT id, username, role, nim, password FROM users ORDER BY id");
$mhs   = mysqli_query($conn, "SELECT nim, nama, prodi, email, no_telp, alamat FROM mahasiswa ORDER BY nim");
?>
<div class="card">
  <h2>Panel Admin</h2>
  <p class="sub">Manajemen akun & data mahasiswa (area terbatas).</p>

  <h3 style="color:#0f2b46">Daftar Akun</h3>
  <table>
    <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>NIM</th><th>Password (hash)</th></tr></thead>
    <tbody>
    <?php while ($u = mysqli_fetch_assoc($users)): ?>
      <tr>
        <td><?= htmlspecialchars($u['id']) ?></td>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= htmlspecialchars($u['role']) ?></td>
        <td><?= htmlspecialchars($u['nim'] ?? '-') ?></td>
        <td><code><?= htmlspecialchars($u['password']) ?></code></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h3 style="color:#0f2b46">Data Pribadi Mahasiswa</h3>
  <table>
    <thead><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Email</th><th>No. Telp</th><th>Alamat</th></tr></thead>
    <tbody>
    <?php while ($m = mysqli_fetch_assoc($mhs)): ?>
      <tr>
        <td><?= htmlspecialchars($m['nim']) ?></td>
        <td><?= htmlspecialchars($m['nama']) ?></td>
        <td><?= htmlspecialchars($m['prodi']) ?></td>
        <td><?= htmlspecialchars($m['email']) ?></td>
        <td><?= htmlspecialchars($m['no_telp']) ?></td>
        <td><?= htmlspecialchars($m['alamat']) ?></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>
