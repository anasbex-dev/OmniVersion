# 🌐 OmniVersion — Multi-Version Support for PMMP

![Banner](banner.jpg )

![Logo](logo.png)

**OmniVersion** adalah plugin PMMP (PocketMine-MP) yang memungkinkan berbagai versi Minecraft Bedrock terhubung ke satu server.  
Dengan sistem *protocol translation*, pemain dari versi berbeda tetap dapat join tanpa server proxy tambahan.

---

## ✨ Fitur Utama
- 🔄 **Multi-Version Support** — Pemain dari berbagai versi Bedrock bisa join server yang sama.
- 🔁 **Protocol Translation** — Menerjemahkan paket lintas versi agar tetap kompatibel.
- ⚡ **Tanpa Proxy** — Plugin PHP murni, tidak membutuhkan Waterdog/Nukkit/Bungee.
- 🛡 **Stabil & Aman** — Menghindari crash dari perbedaan format paket.
- 🔌 **Integrasi Mudah** — Cukup drop ke folder `plugins`.

---

## 🚀 Cara Instalasi

1. Download **OmniVersion.phar** (tersedia saat GitHub Release).
2. Letakkan ke folder:

/plugins

3. Restart server PocketMine-MP.
4. Plugin aktif otomatis.

---

## 📁 Struktur Direktori (Untuk Developer)

OmniVersion/ ├── src/ │   └── OmniVersion/ │       ├── Main.php │       ├── protocol/ │       │   ├── Translator.php │       │   ├── PacketMapper.php │       │   └── VersionTable.php │       └── utils/ │           └── Logger.php └── plugin.yml

---

## 🧠 Cara Kerja Singkat

1. Plugin membaca **versi protokol** dari klien.
2. Jika tidak cocok dengan server, OmniVersion akan:
   - mencocokkan versi,
   - menerjemahkan paket masuk/keluar,
   - memastikan format sesuai protokol server.
3. Pemain tetap bisa join walau beda versi.

---

## 🛠 Status Proyek

🚧 **Dalam tahap pengembangan awal**

Fokus saat ini:
- Pemetaan tabel versi protokol  
- Translator paket dasar  
- Kompatibilitas login & join server  

---

## 🤝 Kontribusi

Kontribusi sangat diterima!

Silakan:
- Membuat **Pull Request**  
- Membuka **Issues**  
- Request fitur baru  

---

## 📜 Lisensi

Proyek ini dirilis di bawah **Apache License 2.0**.

Copyright 2025-present anasbex-dev

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at:

http://www.apache.org/licenses/LICENSE-2.0

---

## 💬 Dukungan

Punya ide fitur atau menemukan bug?  
Silakan buka **Issues**.

Terima kasih sudah mendukung OmniVersion! 🚀