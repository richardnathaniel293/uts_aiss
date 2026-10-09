# Setup VM Target (UbuntuServer24 + Docker) — untuk BAB IV

Tujuan: menjadikan **UbuntuServer24** sebagai target yang menjalankan stack
**before** dan **after**, lalu diserang dari **KaliLinux** — keduanya VM VirtualBox
pada jaringan host-only `192.168.56.0/24`.

> ✅ Docker dijalankan **di dalam VM Ubuntu** (Docker Engine, bukan Docker Desktop),
> sehingga **tidak mengaktifkan Hyper-V di host** → VirtualBox & lab tetap aman.

## Topologi
```
Laptop (host)
└── VirtualBox  — Host-Only Ethernet Adapter (192.168.56.1)
    ├── UbuntuServer24 (TARGET)  nic1=NAT(internet)  nic2=host-only 192.168.56.x
    │     └── Docker: before(:8080,:3306)  +  after(:80,:443)
    └── KaliLinux (ATTACKER)     nic1=NAT            nic2=host-only 192.168.56.y
```
Jaringan kedua VM **sudah benar** (NAT + host-only). Tidak ada yang perlu diubah di VirtualBox.

---

## Langkah A — Nyalakan VM target & catat IP-nya
```bash
# dari host (PowerShell / Git Bash):
"/c/Program Files/Oracle/VirtualBox/VBoxManage.exe" startvm UbuntuServer24 --type headless
```
Login ke VM, lalu lihat IP host-only:
```bash
ip -4 addr show | grep 192.168.56     # mis. 192.168.56.10 pada enp0s8
```
> Jika enp0s8 **tidak** punya IP 192.168.56.x: set statis via netplan
> (`/etc/netplan/50-cloud-init.yaml`):
> ```yaml
> network:
>   version: 2
>   ethernets:
>     enp0s8:
>       addresses: [192.168.56.10/24]
> ```
> lalu `sudo netplan apply`.

## Langkah B — Pindahkan bundle ke VM target
Bundle = `portal-akademik-vmtarget.zip` (berisi kedua folder project + skrip + panduan).
Pilih salah satu cara:

**B1. scp (paling mudah bila SSH aktif):**
```bash
# dari host, ganti <ip> & <user>:
scp portal-akademik-vmtarget.zip <user>@192.168.56.10:~/
```
**B2. Shared folder VirtualBox:** Settings VM → Shared Folders → arahkan ke folder
berisi zip → di VM: `sudo mount -t vboxsf <nama_share> /mnt && cp /mnt/*.zip ~`.
**B3. HTTP (fallback):** di host `python -m http.server 8000`, di VM
`wget http://192.168.56.1:8000/portal-akademik-vmtarget.zip` (izinkan port 8000 di firewall host).

## Langkah C — Ekstrak & jalankan provisioning
Di dalam VM target:
```bash
sudo apt-get update && sudo apt-get install -y unzip
unzip portal-akademik-vmtarget.zip -d portal-target
cd portal-target
sudo bash provision-target.sh
```
Skrip akan: pasang Docker → build & jalankan kedua stack → cetak IP target + URL +
nilai Tabel 4.1. Build pertama butuh beberapa menit (tarik image via NAT).

Verifikasi di VM:
```bash
docker ps        # harus ada portal-proxy/app/db (before) & portal-h-* (after)
curl -s  http://localhost:8080/login.php -o /dev/null -w "before: %{http_code}\n"
curl -sk https://localhost/login.php      -o /dev/null -w "after : %{http_code}\n"
```

## Langkah D — Uji akses dari Kali
Nyalakan KaliLinux, lalu:
```bash
ping -c2 192.168.56.10
curl -s  http://192.168.56.10:8080/login.php -o /dev/null -w "%{http_code}\n"   # before
echo "192.168.56.10 portal.local" | sudo tee -a /etc/hosts                       # untuk after
curl -sk https://portal.local/login.php -o /dev/null -w "%{http_code}\n"         # after
nmap -sS -sV -p- 192.168.56.10                                                    # bukti 4.2.2
```
Jika semua merespons → **target siap diserang**, lanjut ikuti `BAB-IV-Runbook.md`.

---

## Tabel 4.1 — Lingkungan Pengujian (isian final)
| Item | Isi |
|---|---|
| Mesin penguji | KaliLinux (VM VirtualBox), host-only `192.168.56.y` |
| Mesin target | UbuntuServer24 + Docker Engine (VM VirtualBox), host-only `192.168.56.10` |
| Jaringan | VirtualBox Host-Only `192.168.56.0/24` |
| Target BEFORE | `http://192.168.56.10:8080` — port `8080/tcp`, `3306/tcp` |
| Target AFTER | `https://portal.local` (→192.168.56.10) — port `80/tcp`, `443/tcp` |
| Alat uji | nmap, curl, nikto/ZAP, sqlmap, hydra, testssl.sh (isi versinya) |
| Tanggal | *(tanggal praktikum)* |

> Ganti `192.168.56.10/.y` dengan IP asli dari `ip a` di tiap VM.

## Troubleshooting
- **Kali tak bisa ping target:** pastikan nic2 kedua VM di host-only adapter yang sama & aktif (`ip a`). Di Ubuntu cek firewall: `sudo ufw status` (saat uji before, jangan blok 8080/3306).
- **Build Docker gagal (no internet):** nic1 NAT harus aktif; cek `ping -c2 8.8.8.8` di VM target.
- **`portal.local` tak dikenal di Kali:** pastikan baris di `/etc/hosts` menunjuk IP target.
- **Skrip error `\r`:** file terbawa format Windows → skrip sudah auto-`dos2unix`; bila tetap, jalankan `sudo apt install dos2unix && dos2unix provision-target.sh`.
- **Port 80/443 bentrok di VM:** pastikan tidak ada web server lain aktif di Ubuntu (`sudo ss -tlnp | grep -E ':80|:443'`).
