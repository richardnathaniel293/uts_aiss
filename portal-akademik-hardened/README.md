# Portal Akademik — Versi AFTER HARDENING (Project UTS AISS)

Versi **ter-hardening** dari Portal Akademik. Pasangan dari versi *before* di
folder `../portal-akademik/`. Perbandingan before/after menjadi dasar **BAB III**
(implementasi & hardening) dan **BAB IV** (hasil VA before/after).

> ⚠️ Lingkungan lab/simulasi. Secret di `.env` & `authelia/` adalah contoh — ganti untuk produksi.

## Defense-in-Depth yang diterapkan

| Lapisan | Kontrol | Melawan |
|---|---|---|
| **Edge / Transport** | Nginx **TLS/HTTPS** + redirect 80→443, HSTS, security headers, `server_tokens off` | T7, T8 |
| **IAM** | **Authelia** SSO + **MFA (TOTP)** via forward-auth; step-up two_factor utk `/admin.php`; regulation anti brute force | T3, T6 |
| **WAF** | **ModSecurity + OWASP CRS** (paranoia 1) | T1, T5 (defense-in-depth) |
| **Aplikasi** | Prepared statement, **Argon2id**, **RBAC** default-deny, perbaikan **IDOR**, sesi aman, lockout, error generik | T1, T2, T4, T5, T6, T8, T10 |
| **Data** | **AES-256-GCM** untuk PII (at-rest), akun DB **least-privilege**, DB tidak diekspos | T2, T9 |
| **Host/Network** | Firewall default-deny (ufw/iptables), ganti port SSH, **Fail2ban**, segmentasi jaringan Docker | T3, T9 |

## Menjalankan

Prasyarat: Docker + Docker Compose. Tambahkan ke file hosts:
```
127.0.0.1 portal.local
```
Lalu:
```bash
docker compose -f docker-compose.hardened.yml --env-file .env up -d --build
```
Buka **https://portal.local** (sertifikat self-signed → terima peringatan browser).

Alur: Authelia meminta login (SSO); membuka **/admin.php** memicu **MFA (TOTP)**.
Link pendaftaran TOTP (lab) muncul di `authelia/notification.txt`.

Akun: `admin / Admin@123`, `budi / budi123` (lihat juga `authelia/users_database.yml`).

Stop / reset:
```bash
docker compose -f docker-compose.hardened.yml down        # stop
docker compose -f docker-compose.hardened.yml down -v      # + hapus data
```

## Bukti before/after (ringkas)

Diuji pada lapisan aplikasi (lihat `docs/BAB-III-Hardening.md` untuk detail & log):

| Serangan | Before | After |
|---|---|---|
| SQLi auth bypass `admin' --` | login sebagai admin | **ditolak** (prepared stmt) |
| SQLi UNION di pencarian | dump hash & PII | **"Tidak ada hasil"** |
| IDOR `nilai.php?nim=` | lihat KHS orang lain | **hanya KHS sendiri** |
| Buka `/admin.php` sbg mahasiswa | seluruh akun + hash bocor | **403 Forbidden** |
| Brute force login | tanpa batas | **terkunci setelah 5x** + Fail2ban/Authelia |

## Struktur
```
portal-akademik-hardened/
├── docker-compose.hardened.yml   # orkestrasi berlapis + segmentasi jaringan
├── .env                          # secret lab (APP_KEY, password DB)
├── app/                          # kode PHP hardened + Dockerfile
├── db/init.sql                   # seed: Argon2id + PII terenkripsi + user least-priv
├── nginx/                        # TLS, security headers, forward-auth; certs/ & snippets/
├── authelia/                     # IAM: configuration.yml + users_database.yml
├── fail2ban/                     # jail & filter brute force
├── host/                         # firewall ufw/iptables + catatan hardening host
└── docs/BAB-III-Hardening.md     # pemetaan ancaman→perbaikan + bukti
```
