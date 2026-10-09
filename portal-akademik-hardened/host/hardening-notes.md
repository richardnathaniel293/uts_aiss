# Host & Network Hardening — Catatan (Reduce Attack Surface)

Lapisan host/jaringan untuk VM aplikasi (cocok dengan lab VirtualBox + pfSense).

## 1. Firewall (default-deny)
- Jalankan `firewall-ufw.sh` **atau** `firewall-iptables.sh` (pilih salah satu).
- Hanya 80/443 (publik) + SSH (port non-default, rate-limited) yang terbuka.
- Port DB/WAF/IAM hanya via jaringan internal Docker (tidak di-publish ke host).

## 2. Kurangi attack surface
- **Ganti port default SSH** 22 → 2222 (`/etc/ssh/sshd_config`: `Port 2222`).
- **Nonaktifkan login root & password SSH**: `PermitRootLogin no`, `PasswordAuthentication no` (pakai kunci).
- **Matikan service tidak terpakai**:
  ```bash
  systemctl disable --now avahi-daemon cups bluetooth rpcbind 2>/dev/null || true
  systemctl list-units --type=service --state=running   # audit sisa service
  ```
- **Update & auto-patch**: `unattended-upgrades` aktif.
- **Hapus port DB yang terekspos** (dibanding versi before yang membuka 3306): sudah dihilangkan di `docker-compose.hardened.yml`.

## 3. pfSense (lab)
- WAN → hanya forward 443/80 ke VM proxy; blok sisanya.
- Aktifkan rule anti-scan / limit koneksi; log ke syslog.
- Segmentasikan VLAN: DMZ (proxy) terpisah dari segmen App/DB.

## 4. Verifikasi (untuk BAB IV — before/after)
```bash
# Dari luar VM, bandingkan hasil scan sebelum vs sesudah hardening:
nmap -sS -sV -p- <IP_VM>
# Before: 22, 3306, 8080 terbuka.  After: hanya 80/443 (+SSH custom).
```
