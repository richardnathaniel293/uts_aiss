#!/usr/bin/env bash
# =====================================================================
#  Host Firewall (iptables) - alternatif ufw  -> fix T9
#  Default-deny inbound; izinkan loopback, established, 80/443, SSH (custom).
# =====================================================================
set -euo pipefail
SSH_PORT="${SSH_PORT:-2222}"

iptables -F
iptables -X
iptables -P INPUT   DROP
iptables -P FORWARD DROP
iptables -P OUTPUT  ACCEPT

# loopback & koneksi yang sudah terjalin
iptables -A INPUT -i lo -j ACCEPT
iptables -A INPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT

# layanan publik
iptables -A INPUT -p tcp --dport 80  -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# SSH custom + rate limit sederhana
iptables -A INPUT -p tcp --dport "${SSH_PORT}" -m conntrack --ctstate NEW \
         -m recent --set --name SSH
iptables -A INPUT -p tcp --dport "${SSH_PORT}" -m conntrack --ctstate NEW \
         -m recent --update --seconds 60 --hitcount 5 --name SSH -j DROP
iptables -A INPUT -p tcp --dport "${SSH_PORT}" -j ACCEPT

# tolak eksplisit port internal (jika ter-bind ke host)
for p in 3306 8080 9091; do iptables -A INPUT -p tcp --dport $p -j DROP; done

iptables -L -n -v
echo "Simpan dengan: iptables-save > /etc/iptables/rules.v4"
