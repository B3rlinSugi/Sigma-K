# SIGMA-K — PHASE 4: PROTOTYPE OBJECTIVES & SCOPE

## 1. Executive Summary
Dokumen ini menetapkan tujuan strategis, batasan ruang lingkup, audiens pengguna, dan kriteria keberhasilan dari implementasi Prototipe Antarmuka Interaktif (*High-Fidelity Enterprise Interactive Prototype*) sistem **SIGMA-K** (Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan) untuk Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (KemenPANRB).

> [!IMPORTANT]
> **PEMBERITAHUAN DATA DEMO (PROTOTYPE NOTICE):**  
> Seluruh angka agregat (seperti ilustrasi 48 K/L Kabinet Merah Putih, 548 Pemda), bagan struktur unit kerja, serta metrik KPI di dalam prototipe ini adalah **DATA SIMULASI / DATA PROTOTIPE** untuk keperluan pengujian antarmuka dan alur bisnis. Data ini **BUKAN** data resmi final sebelum disahkan melalui proses migrasi dan verifikasi data master resmi.

---

## 2. Tujuan Strategis Prototipe
Prototipe interaktif SIGMA-K dibangun untuk memenuhi 4 objektif kunci:
1. **Demonstrasi Eksekutif kepada SESDEP:** Memberikan simulasi visual dan fungsional yang hidup (*live and tangible*) kepada Sekretaris Deputi (SESDEP) Kelembagaan dan Tata Laksana mengenai cara kerja modernisasi pengelolaan data kelembagaan nasional.
2. **Validasi Kebutuhan & Alur Kerja (Phase 1 & Phase 2 Baseline):** Memvalidasi secara interaktif seluruh alur bisnis yang telah didesain pada fase sebelumnya, meliputi pendaftaran era kabinet, pemecahan/penggabungan kementerian, kanvas graf pohon hierarki unit kerja (React Flow), draf pengajuan usulan berjenjang, dan telaah verifikasi berdampingan (*side-by-side diff review*).
3. **Penyelarasan Kolaborasi Tim Teknis (Berlin & Ikhsan):** Mengintegrasikan hasil perancangan arsitektur software/database (Berlin) dengan hasil perumusan indikator performa kelembagaan (*Proposed KPIs*) dari Data Analyst (Ikhsan & Pak Sigit).
4. **Fondasi Kode Frontend Siap Integrasi (API-Ready Architecture):** Seluruh kode antarmuka dibangun menggunakan Next.js 14 App Router, TypeScript murni, Tailwind CSS, dan Service Abstraction Layer yang siap dihubungkan 1:1 ke endpoint REST API NestJS pada tahap implementasi backend.

---

## 3. Matriks Persona Pengguna & Batasan Otorisasi Prototipe
Prototipe ini dilengkapi dengan fitur **Persona Switcher** pada bilah navigasi atas (*TopBar*) yang memungkinkan demonstrasi berganti sudut pandang secara instan:

| Persona / Role | Profil Pengguna Demo | Lingkup Akses & Hak Aksi | Skenario Demo Utama |
| :--- | :--- | :--- | :--- |
| **`USER`** (Operator K/L) | Budi Santoso, S.AP.<br>*(Operator Kemenko Pangan)* | Mengelola draf usulan perubahan instansi sendiri, melihat bagan struktur kementeriannya, menanggapi catatan revisi. | Membuat pengajuan struktur baru Biro Perencanaan, memperbaiki rujukan pasal hukum usulan tupoksi. |
| **`VERIFIKATOR`** (Analis PANRB) | Siti Rahmawati, S.Sos.<br>*(Analis Kelembagaan PANRB)* | Mengakses antrean verifikasi nasional, membuka ruang telaah berdampingan (*side-by-side diff*), menyetujui (*Pass*), meminta revisi, atau menolak usulan. | Meneliti keabsahan dokumen Perpres, membandingkan data eksisting vs draf, memberikan catatan perbaikan. |
| **`ADMIN`** (Admin Pusat) | Ahmad Fauzi, S.Kom.<br>*(Administrator Sistem PANRB)* | Akses seluruh modul master data, menambah kabinet baru, mengesahkan usulan lolos verifikasi langsung ke Master Data secara atomik. | Mendaftarkan era kabinet baru, mengesahkan pengajuan Kemendikdasmen ke basis data master. |
| **`SESDEP`** (Pimpinan) | Nanang Khoiruddin, M.Si.<br>*(Sekretaris Deputi Kelembagaan)* | *Executive Perspective (Prototype Persona)*: Memantau Dashboard Eksekutif, membaca metrik analitik kelembagaan, memeriksa log audit forensik. | Meninjau ringkasan komparasi 48 K/L Kabinet Merah Putih, rasio delayering jabatan fungsional. |

> [!NOTE]
> **Status Persona SESDEP:** Persona `SESDEP / PIMPINAN` diimplementasikan secara khusus untuk sudut pandang eksekutif (*Executive Perspective / Prototype Persona*) dan **BUKAN** role RBAC produksi permanen. Model otorisasi backend definitif akan difinalisasi setelah konfirmasi pimpinan pada Phase 5.

---

## 4. Batasan Ruang Lingkup (Strict Phase 4 Boundaries)
Sesuai ketetapan arsitektur Phase 4:
- **TIDAK ADA Implementasi Backend Produksi:** Tidak ada NestJS runtime, PostgreSQL migrations, Prisma migrations, ataupun Redis/WebSocket produksi pada fase ini.
- **TIDAK ADA Modifikasi Database Legacy:** Sistem legacy E-SKLD hanya diperlakukan sebagai referensi data historis.
- **Data Binding:** Sepenuhnya menggunakan **Typed Mock Data Store** + **API-Ready Service Layer** yang siap dialihkan ke NestJS REST API.
- **Desain Institusional Pemerintahan:** Mengutamakan palet resmi KemenPANRB Navy (`#0B2A4A`), Garuda Gold (`#D4AF37`), serta kontras tinggi berstandar WCAG 2.1 AA.
