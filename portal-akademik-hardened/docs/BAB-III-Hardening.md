# BAB III — Langkah Implementasi & Hardening
### Sistem: Portal Akademik — Universitas Nusantara

Bab ini menjabarkan penerapan **Defense-in-Depth** dan prinsip **Zero Trust** pada
Portal Akademik, memperbaiki 10 ancaman (T1–T10) dari [Threat Model STRIDE](../../portal-akademik/docs/threat-model-stride.md).
Referensi kode: folder `portal-akademik-hardened/` (versi *after*), dibandingkan dengan
`portal-akademik/` (versi *before*).

---

## 3.1 Arsitektur Pertahanan Berlapis

```
Internet (Untrusted)
   │  HTTPS (TLS)
   ▼
[ proxy ]  Nginx  — TLS, HSTS, security headers, forward-auth               (DMZ)
   │  auth_request ─────────────► [ authelia ]  SSO + MFA (TOTP), regulation
   ▼
[ waf ]  ModSecurity + OWASP CRS — inspeksi & blokir payload                (edge)
   ▼
[ app ]  PHP — prepared stmt, Argon2id, RBAC, IDOR-fix, sesi aman          (App Tier)
   ▼
[ db ]  MariaDB — least-privilege, tidak diekspos, PII terenkripsi          (Data Tier, internal)

[ fail2ban ] mengawasi log → ban IP brute force di lapisan jaringan
[ firewall host ] ufw/iptables default-deny (VM / pfSense)
```

Setiap request melewati minimal 4 lapisan kontrol sebelum menyentuh data — sehingga
kegagalan satu kontrol tidak langsung berujung kompromi (asumsi *breach*).

---

## 3.2 Pemetaan Ancaman → Perbaikan

| Ancaman | Perbaikan | Lokasi (file/konfigurasi) |
|---|---|---|
| **T1** SQLi auth bypass | Prepared statement (`mysqli_prepare`+`bind_param`); WAF CRS | `app/login.php`; `waf` (CRS) |
| **T2** Hash lemah (MD5) | `password_hash()` **Argon2id** + rehash otomatis; enkripsi at-rest | `app/login.php`, `db/init.sql` |
| **T3** Brute force | Lockout per akun (5× → 15 mnt) + **Authelia regulation** + **Fail2ban** | `app/login.php`, `authelia/configuration.yml`, `fail2ban/` |
| **T4** IDOR KHS | Abaikan `?nim` utk non-admin; pakai `$_SESSION['nim']` | `app/nilai.php` |
| **T5** SQLi UNION | Prepared statement pada pencarian; WAF CRS | `app/search.php`; `waf` |
| **T6** Broken access admin | **RBAC** `require_admin()` default-deny (403) + MFA via Authelia | `app/auth.php`, `app/admin.php`, `authelia/` |
| **T7** HTTP tanpa TLS | TLS/HTTPS + redirect 80→443 + HSTS | `nginx/default.conf`, `nginx/certs/` |
| **T8** Kebocoran error/banner | `display_errors=Off`, pesan generik, `server_tokens off`, `ServerTokens Prod` | `app/db.php`, `app/Dockerfile`, `nginx/default.conf` |
| **T9** Attack surface & kredensial DB | DB tidak diekspos + **akun least-privilege** + segmentasi jaringan + firewall | `docker-compose.hardened.yml`, `db/init.sql`, `host/` |
| **T10** Sesi lemah | Cookie `HttpOnly/SameSite/Secure`, `session_regenerate_id`, idle timeout | `app/session_boot.php`, `app/login.php` |

---

## 3.3 Contoh Perubahan Kunci (Before → After)

### T1/T5 — SQL Injection → Prepared Statement
**Before** (`portal-akademik/app/login.php`):
```php
$sql = "SELECT ... FROM users WHERE username = '$username' AND password = '".md5($password)."'";
$res = mysqli_query($conn, $sql);
```
**After** (`portal-akademik-hardened/app/login.php`):
```php
$stmt = mysqli_prepare($conn, 'SELECT id,username,role,nim,password,... FROM users WHERE username = ?');
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$u = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if ($u && password_verify($password, $u['password'])) { /* ... */ }
```

### T2 — MD5 → Argon2id
```php
// seed (db/init.sql): password_hash($p, PASSWORD_ARGON2ID, [...])
// verifikasi: password_verify($password, $hash)  // + password_needs_rehash → rehash
```

### T4 — IDOR → kontrol kepemilikan
**Before:** `$nim = $_GET['nim'] ?? $_SESSION['nim'];`  (siapa pun bisa ganti NIM)
**After:** `$nim = $isAdmin ? ($_GET['nim'] ?? $sessionNim) : $sessionNim;`  (mahasiswa dikunci ke NIM sendiri)

### T6 — Broken Access Control → RBAC
```php
function require_admin(): void {
    if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); /* tolak */ exit; }
}
// admin.php: require_admin();  // dipanggil sebelum output apa pun
```

### T2/PII — Enkripsi Data at Rest (AES-256-GCM)
```php
// crypto.php: pa_encrypt()/pa_decrypt() dengan kunci APP_KEY (32 byte)
// kolom no_telp & alamat disimpan sebagai base64(iv|tag|ciphertext)
```

---

## 3.4 Bukti Verifikasi (diuji pada lapisan aplikasi)

Pengujian dilakukan terhadap app *before* (port 8090) dan *after* (port 8091) dengan
payload identik. Ringkasan (bukti lengkap/log disiapkan untuk BAB IV):

| # | Uji | Before | After |
|---|-----|--------|-------|
| 1 | Login sah `admin/Admin@123` | 302 sukses | 302 sukses (tetap berfungsi) |
| 2 | SQLi `admin' --` | **302 (bypass)** | **200 (ditolak)** |
| 3 | SQLi `admin'#` | **302 (bypass)** | **200 (ditolak)** |
| 4 | SQLi `' OR '1'='1' --` | **302 (bypass)** | **200 (ditolak)** |
| 5 | IDOR `nilai.php?nim=160423002` (sbg budi) | data **Sari** tampil | hanya **Budi** (param diabaikan) |
| 6 | `/admin.php` sbg budi | panel admin + **hash bocor** | **403 Forbidden** |
| 7 | SQLi UNION di pencarian | dump username+hash | **"Tidak ada hasil"** |
| 8 | Brute force (6× salah) | tak terbatas | **akun terkunci** + `AUTH_FAIL` ter-log |
| 9 | PII di DB | plaintext | **ciphertext** (AES-256-GCM) |

> Lapisan WAF, TLS, Authelia+MFA, Fail2ban, dan firewall dijalankan via
> `docker compose -f docker-compose.hardened.yml up -d --build` dan diverifikasi
> saat demo (scan Nmap before/after + percobaan serangan diblokir WAF/IAM).

---

## 3.5 Keselarasan Zero Trust (NIST SP 800-207)

- **Policy Enforcement Point (PEP):** Nginx + Authelia forward-auth — tiap request diverifikasi.
- **Verifikasi eksplisit:** SSO + MFA (two_factor untuk area admin).
- **Least privilege:** akun DB `portal_app` hanya SELECT/INSERT/UPDATE seperlunya; RBAC di aplikasi.
- **Asumsi breach & segmentasi:** jaringan Docker `internal` tanpa rute keluar; DB tak terekspos.
- **Enkripsi menyeluruh:** TLS in-transit; Argon2id + AES-256-GCM at-rest.
- **Monitoring:** log aplikasi/Nginx → Fail2ban; regulation Authelia.
