# 🌐 OmniVersion — Multi-Version Support for PMMP

**OmniVersion** adalah plugin PMMP (PocketMine-MP) yang dirancang untuk menghubungkan berbagai versi Minecraft Bedrock ke satu server.  
Dengan sistem *protocol translation*, plugin ini membuat pemain dari versi berbeda tetap bisa join tanpa perlu server proxy tambahan.

## ✨ Fitur Utama
- 🔄 **Multi-Version Support** — Pemain dari berbagai versi Bedrock bisa join ke server yang sama.
- 🔁 **Protocol Translation** — Menerjemahkan paket antar versi agar tetap kompatibel.
- ⚡ **Tanpa Proxy** — Murni plugin PHP tanpa perlu Nukkit/Bungee/Waterdog.
- 🛡 **Stabil & Aman** — Menghindari crash akibat perbedaan format paket.
- 🔌 **Integrasi Mudah** — Cukup letakkan di folder `plugins`.

## 🚀 Cara Instalasi
1. Download file `OmniVersion.phar` (akan tersedia saat rilis).
2. Masukkan ke folder:

/plugins

3. Restart server PocketMine-MP.
4. OmniVersion aktif otomatis.

## 📁 Struktur Direktori (Developer)

OmniVersion/ ├── src/ │   └── OmniVersion/ │       ├── Main.php │       ├── protocol/ │       │   ├── Translator.php │       │   ├── PacketMapper.php │       │   └── VersionTable.php │       └── utils/ │           └── Logger.php └── plugin.yml

## 🧠 Cara Kerja Singkat
- Plugin membaca **versi protokol** dari klien.
- Jika protokol tidak cocok dengan server, OmniVersion:
  - mencocokkan versi,
  - menterjemahkan paket masuk/keluar,
  - memastikan format sesuai versi server.
- Pemain bisa tetap bermain walau berbeda versi.

## 🛠 Status Proyek
🚧 **Sedang dalam tahap pengembangan awal**  
Fokus awal:
- Pemetaan versi protokol
- Translator paket dasar
- Kompatibilitas login dan join server

## 🤝 Kontribusi
Kontribusi sangat diterima!  
Silakan buat:
- Pull request
- Issue bug
- Request fitur baru

## 📜 Lisensi
MIT License — bebas digunakan untuk proyek pribadi maupun komersial.

---

### 💬 Dukungan
Jika ingin request fitur, silakan buka **Issues** di repo ini.  
Butuh bantuan? Tanyakan saja — OmniVersion akan terus berkembang!