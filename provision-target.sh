#!/usr/bin/env bash
# =====================================================================
#  provision-target.sh — siapkan VM target (Ubuntu Server) untuk BAB IV
#  Memasang Docker Engine + Compose, lalu menyalakan stack BEFORE & AFTER.
#  Jalankan di dalam VM UbuntuServer24:   sudo bash provision-target.sh
#  Letakkan skrip ini sejajar dengan folder portal-akademik/ dan
#  portal-akademik-hardened/ (hasil ekstrak bundle).
# =====================================================================
set -euo pipefail

say(){ printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }
err(){ printf '\n\033[1;31m[x] %s\033[0m\n' "$*" >&2; }

# --- Harus root ---
if [ "$(id -u)" -ne 0 ]; then err "Jalankan dengan sudo: sudo bash provision-target.sh"; exit 1; fi

# Direktori tempat skrip berada
DIR="$(cd "$(dirname "$0")" && pwd)"
BEFORE="$DIR/portal-akademik"
AFTER="$DIR/portal-akademik-hardened"

[ -d "$BEFORE" ] || { err "Folder portal-akademik/ tidak ada di $DIR"; exit 1; }
[ -d "$AFTER" ]  || { err "Folder portal-akademik-hardened/ tidak ada di $DIR"; exit 1; }

# --- 1. Install Docker Engine + Compose plugin (butuh internet via NAT) ---
if ! command -v docker >/dev/null 2>&1; then
  say "Memasang Docker Engine (get.docker.com)..."
  curl -fsSL https://get.docker.com | sh
else
  say "Docker sudah terpasang: $(docker --version)"
fi
if ! docker compose version >/dev/null 2>&1; then
  say "Memasang plugin docker compose..."
  apt-get update -y && apt-get install -y docker-compose-plugin
fi
systemctl enable --now docker >/dev/null 2>&1 || true
# izinkan user non-root menjalankan docker (opsional)
if [ -n "${SUDO_USER:-}" ]; then usermod -aG docker "$SUDO_USER" 2>/dev/null || true; fi

# --- 2. Normalkan akhir baris skrip/konfig (jika dibawa dari Windows) ---
command -v dos2unix >/dev/null 2>&1 || apt-get install -y dos2unix >/dev/null 2>&1 || true
if command -v dos2unix >/dev/null 2>&1; then
  find "$AFTER" "$BEFORE" -type f \( -name "*.sh" -o -name "*.conf" -o -name "*.yml" -o -name "Dockerfile" -o -name ".env" \) -print0 \
    | xargs -0 dos2unix >/dev/null 2>&1 || true
fi

# --- 3. Jalankan stack BEFORE ---
say "Membangun & menyalakan stack BEFORE (port 8080, 3306)..."
( cd "$BEFORE" && docker compose up -d --build )

# --- 4. Jalankan stack AFTER ---
say "Membangun & menyalakan stack AFTER (port 80, 443)..."
( cd "$AFTER" && docker compose -f docker-compose.hardened.yml --env-file .env up -d --build )

# --- 5. Deteksi IP host-only (192.168.56.x) ---
HOIP="$(ip -4 -o addr show 2>/dev/null | grep -oE '192\.168\.56\.[0-9]+' | head -1 || true)"

say "Status container:"
docker ps --format 'table {{.Names}}\t{{.Ports}}\t{{.Status}}'

echo
printf '\033[1;32m================= TARGET SIAP DISERANG =================\033[0m\n'
if [ -n "$HOIP" ]; then
  echo "IP host-only VM target : $HOIP"
  echo
  echo "  BEFORE : http://$HOIP:8080        (port 8080/tcp, 3306/tcp)"
  echo "  AFTER  : https://$HOIP   (443)    (port 80/tcp, 443/tcp)"
  echo
  echo "Di Kali, tambahkan ke /etc/hosts:"
  echo "    $HOIP  portal.local"
  echo "lalu akses AFTER via https://portal.local (agar cocok sertifikat & MFA Authelia)."
else
  err "IP 192.168.56.x belum ada di interface host-only."
  echo "Set IP statis (contoh enp0s8) via netplan, lalu 'sudo netplan apply':"
  echo "  network: {version: 2, ethernets: {enp0s8: {addresses: [192.168.56.10/24]}}}"
fi
printf '\033[1;32m=======================================================\033[0m\n'
echo "Cek ulang nanti: docker ps   |   matikan: docker compose down (di tiap folder)"
