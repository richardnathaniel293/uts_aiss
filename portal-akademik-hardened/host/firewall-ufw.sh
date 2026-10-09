#!/usr/bin/env bash
# =====================================================================
#  Host Firewall (ufw) - VM aplikasi Portal Akademik
#  Tujuan: default-deny, hanya buka yang perlu  -> fix T9 (attack surface)
#  Jalankan sebagai root di VM (Ubuntu/Debian).
# =====================================================================
set -euo pipefail

SSH_PORT="${SSH_PORT:-2222}"   # ganti port SSH default (22) -> kurangi attack surface

ufw --force reset
ufw default deny incoming
ufw default allow outgoing

# Akses publik aplikasi (via reverse proxy)
ufw allow 80/tcp   comment 'HTTP (redirect ke HTTPS)'
ufw allow 443/tcp  comment 'HTTPS'

# SSH di port non-default + rate limit (anti brute force)
ufw limit ${SSH_PORT}/tcp comment 'SSH (rate-limited)'

# Port internal TIDAK boleh diakses dari luar (DB, WAF, IAM hanya via jaringan docker internal)
ufw deny 3306/tcp  comment 'MariaDB - internal only'
ufw deny 8080/tcp  comment 'WAF - internal only'
ufw deny 9091/tcp  comment 'Authelia - internal only'

ufw --force enable
ufw status verbose
