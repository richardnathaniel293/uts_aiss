# Portal Akademik — Target Aplikasi Web Enterprise (Project UTS AISS)

Aplikasi web enterprise sederhana ("Portal Akademik") yang menjadi **objek**
Defense-in-Depth & Zero Trust pada Project UTS Advanced Information Security System.

Repo ini berisi versi **BEFORE HARDENING** — sengaja dibuat rentan agar dapat
dilakukan Threat Modeling (STRIDE), Vulnerability Assessment, simulasi serangan,
lalu dibandingkan dengan versi **AFTER HARDENING** (BAB IV: before/after).

> ⚠️ **Hanya untuk lab/simulasi milik sendiri.** Jangan hubungkan ke internet
> publik atau arahkan serangan ke sistem pihak lain.

## Topologi

```
[ Browser ]
     │  http://localhost:8080
     ▼
[ nginx ]  reverse proxy  (container: portal-proxy)
     │  http://app:80
     ▼
[ PHP / Apache ]  aplikasi  (container: portal-app)
     │  mysqli
     ▼
[ MariaDB ]  database       (container: portal-db)
```

## Menjalankan

Prasyarat: Docker + Docker Compose.

```bash
cd portal-akademik
docker compose up -d --build
```

Buka: **http://localhost:8080**

Menghentikan / reset:

```bash
docker compose down          # stop
docker compose down -v       # stop + hapus data DB (reset seed)
```

## Akun demo (lingkungan simulasi)

| Username | Password   | Role      | NIM        |
|----------|------------|-----------|------------|
| `admin`  | `Admin@123`| admin     | —          |
| `budi`   | `budi123`  | mahasiswa | 160423001  |
| `sari`   | `sari123`  | mahasiswa | 160423002  |
| `andi`   | `P@ssw0rd` | mahasiswa | 160423003  |

## Fitur (permukaan serangan)

- **/login.php** — form login (SSO simulasi)
- **/dashboard.php** — ringkasan akademik mahasiswa
- **/nilai.php** — Kartu Hasil Studi per NIM
- **/search.php** — direktori/pencarian mahasiswa
- **/admin.php** — panel admin (manajemen akun & data)

## Kerentanan bawaan

Lihat **[VULN.md](VULN.md)** — 10 celah dipetakan ke STRIDE, OWASP Top 10,
langkah pembuktian (BAB IV), dan rencana hardening (BAB III).

## Struktur

```
portal-akademik/
├── docker-compose.yml     # orkestrasi 3 container
├── VULN.md                # peta kerentanan (STRIDE/OWASP) + payload demo
├── README.md
├── db/
│   └── init.sql           # skema + seed data
├── app/                   # kode PHP + Dockerfile
│   ├── Dockerfile
│   ├── db.php  auth.php  header.php  footer.php  style.css
│   ├── index.php  login.php  logout.php  dashboard.php
│   ├── nilai.php  search.php  admin.php
└── nginx/
    └── default.conf       # konfigurasi reverse proxy (HTTP, before hardening)
```

## Roadmap hardening (tahap berikutnya)

1. **Data:** prepared statement, Argon2id, TLS/HTTPS, enkripsi at-rest.
2. **App/IAM:** Authelia/Keycloak + MFA sebagai forward-auth di nginx; RBAC.
3. **WAF:** ModSecurity + OWASP CRS di reverse proxy.
4. **Host/Network:** Fail2ban, ufw/iptables (atau pfSense), tutup port DB,
   ganti port default, matikan service tak terpakai, security headers.
