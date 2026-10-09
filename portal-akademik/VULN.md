# Daftar Kerentanan Bawaan (Before Hardening)

Dokumen ini memetakan celah yang **sengaja ditanam** pada Portal Akademik ke
metode **STRIDE**, kategori **OWASP Top 10 2021**, cara membuktikannya (BAB IV),
dan rencana perbaikan (BAB III). Memenuhi syarat "minimal 5 titik kerentanan".

| # | Kerentanan | Lokasi | STRIDE | OWASP | Bukti / Serangan | Rencana Hardening |
|---|------------|--------|--------|-------|------------------|-------------------|
| 1 | **SQL Injection (auth bypass)** | `login.php` | Spoofing, Tampering | A03 Injection | Login sebagai admin dengan username `admin' -- ` atau `' OR '1'='1' -- ` | Prepared statement (`bind_param`); WAF ModSecurity CRS |
| 2 | **Password hashing lemah (MD5, no salt)** | `login.php`, `db/init.sql` | Information Disclosure | A02 Cryptographic Failures | Hash MD5 dari `admin.php` di-crack via hashcat/john (rainbow table) | `password_hash()` Argon2id; migrasi hash |
| 3 | **Brute force / tanpa rate-limit & MFA** | `login.php` | Spoofing, DoS | A07 Auth Failures | Hydra ke form login menembus akun lemah | Fail2ban + lockout; IAM Authelia/Keycloak + **MFA (TOTP)** di reverse proxy |
| 4 | **IDOR / Broken Access Control (KHS)** | `nilai.php` | Elevation of Privilege | A01 Broken Access Control | Login `budi`, ubah `nilai.php?nim=160423002` → lihat KHS mahasiswa lain | Abaikan `?nim` untuk non-admin; pakai `$_SESSION['nim']`; cek kepemilikan |
| 5 | **SQL Injection (UNION, data exfiltration)** | `search.php` | Information Disclosure, Tampering | A03 Injection | `q = %' UNION SELECT username,password,role,1 FROM users -- ` → dump hash | Prepared statement; WAF |
| 6 | **Broken Access Control (panel admin)** | `admin.php`, `auth.php` | Elevation of Privilege | A01 Broken Access Control | Login mahasiswa biasa lalu buka `/admin.php` → lihat semua akun & data pribadi | RBAC: cek `role === 'admin'`, kalau tidak `403` |
| 7 | **Data in Transit tanpa enkripsi (HTTP)** | `nginx/default.conf`, `docker-compose.yml` | Information Disclosure, Tampering | A02 Cryptographic Failures | Sniffing kredensial via Wireshark saat login | TLS/HTTPS (redirect 80→443), HSTS |
| 8 | **Information Disclosure (error & banner)** | `db.php`, `login.php`, `search.php`, Dockerfile, nginx | Information Disclosure | A05 Security Misconfiguration | Pesan error SQL/PHP & versi server terlihat | Matikan `display_errors`; pesan generik; `server_tokens off` |
| 9 | **Attack surface berlebih / kredensial lemah** | `docker-compose.yml` | Tampering, EoP | A05 Security Misconfiguration | Port `3306` DB terekspos ke host; password `root123`/`portal123` | Tutup port DB; secrets kuat; akun DB privilege minimal |
| 10 | **Session lemah (no flags, no regen)** | `header.php` | Spoofing (session hijack) | A07 Auth Failures | Cookie `PHPSESSID` tanpa `Secure/HttpOnly/SameSite`, tidak di-regenerate saat login | Set cookie flags; `session_regenerate_id(true)` saat login |

## Payload cepat untuk demo (BAB IV)

```text
# 1. SQLi auth bypass (kolom username di /login.php)
admin' -- 
' OR '1'='1' -- 

# 5. SQLi UNION dump kredensial (kolom pencarian di /search.php)
%' UNION SELECT username, password, role, 1 FROM users -- 

# 4. IDOR (setelah login sebagai budi)
/nilai.php?nim=160423002
/nilai.php?nim=160423003

# 6. Broken access control (setelah login sebagai budi/mahasiswa)
/admin.php
```

> Semua payload di atas untuk **lingkungan lab milik sendiri** dalam rangka
> tugas UTS. Jangan diarahkan ke sistem pihak lain.
