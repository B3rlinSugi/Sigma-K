# PROJECT BASELINE: SIGMA-K

> **Status:** DISCOVERY BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  
> **Corpus / Repository:** SIGMA-K  

---

## 1. Identitas & Nama Project
- **Nama Resmi Project:** SIGMA-K (Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan)
- **Nama Inisiatif Sebelumnya (Legacy):** E-SKLD / SIGMA-K Legacy
- **Stakeholder Utama:** Pimpinan / Sekretaris Deputi (SESDEP) Kelembagaan dan Tata Laksana, Kementerian PANRB

---

## 2. Tujuan Project
Tujuan utama pengembangan SIGMA-K adalah melakukan modernisasi menyeluruh terhadap sistem pengelolaan data kelembagaan nasional, mencakup:
1. **Pusat Master Data Kelembagaan (Single Source of Truth):** Mengelola master data instansi pemerintah pusat (Kementerian, Lembaga Pemerintah Non-Kementerian, Lembaga Non-Struktural, dll.) dan instansi pemerintah daerah (Provinsi, Kabupaten, Kota) di seluruh Indonesia.
2. **Pengelolaan Periodesasi & Kabinet Pemerintahan:** Menyediakan mekanisme dinamis dan historis dalam memetakan nomenklatur, struktur, dan komposisi instansi pada setiap periode kabinet (misalnya Kabinet Merah Putih vs kabinet-kabinet sebelumnya).
3. **Pencatatan Perubahan Kelembagaan:** Melacak riwayat pemecahan, penggabungan, pembentukan baru, atau pembubaran instansi serta perubahan tugas dan fungsi antarperiode.
4. **Hierarki Struktur Organisasi & Tugas Fungsi:** Memetakan bagan struktur organisasi berjenjang (parent-child), jabatan/eselon, serta detail tugas dan fungsi unit kerja.
5. **Workflow Pengajuan, Verifikasi, & Notifikasi Realtime:** Menerapkan alur kerja tata kelola data yang akuntabel dengan peran User, Verifikator, dan Admin yang didukung sistem notifikasi realtime.
6. **Eksekutif Dashboard & Data Analytics:** Menyajikan visualisasi data kelembagaan, rekapitulasi postur, dan analitik untuk pimpinan dalam pengambilan keputusan strategis.

---

## 3. Latar Belakang
Pengelolaan kelembagaan instansi pemerintah di Indonesia terus berkembang seiring dinamika restrukturisasi kabinet dan reformasi birokrasi. Sistem terdahulu (E-SKLD / SIGMA-K Legacy) telah menginisiasi digitalisasi data kelembagaan. Namun, sistem legacy menghadapi berbagai tantangan struktural, antara lain:
- Penyimpanan relasi kabinet yang bersifat denormalisasi (misal kolom string/list ID instansi).
- Redundansi tabel pemetaan data (*data_map_*).
- Keterbatasan fitur pelacakan histori perubahan struktur dan tugas fungsi.
- Ketiadaan mekanisme verifikasi data berjenjang dan audit logging yang komprehensif.
- Belum tersedianya mekanisme realtime notification untuk aktivitas mutasi data.
- UI/UX yang perlu dimodernisasi agar intuitif dan representatif saat dipresentasikan kepada pimpinan (SESDEP).

---

## 4. Kondisi Sistem Existing (Legacy Reference)
Sistem legacy dijadikan sebagai **Legacy Reference** (bukan untuk di-copy mentah), dengan karakteristik:
- **Database:** Menggunakan database legacy `eskld`.
- **Tabel Utama Teridentifikasi:**
  - `tb_instansi`, `tb_jenis_instansi`, `tb_wilayah`
  - `ref_eselon`, `tbl_ref_instansi_org`
  - `data_kl`, `data_pemda`
  - Tabel ad-hoc mapping: `data_map`, `data_map_pemda`, `data_map_pemda_baru`, `data_map_yudhi_latest`
  - `users`
- **Views Teridentifikasi:** `v_postur_asn` dan beberapa database VIEW rekap/dashboard.
- **Mekanisme Kabinet Legacy:** Berbasis tabel `data_kl` dengan kolom `tahun`, `is_active`, dan `list_id_kl` (daftar ID berformat delimit).
- **Mekanisme Struktur Organisasi Legacy:** Hierarki organisasi berbasis kolom `parent_id` pada `tbl_ref_instansi_org`.

---

## 5. Masalah yang Ingin Diselesaikan
| No | Masalah Existing / Legacy | Solusi pada SIGMA-K Modern |
|---|---|---|
| 1 | Relasi kabinet dan instansi tidak normalized (`list_id_kl`). | Normalisasi relasi Cabinet, Cabinet Period, dan Cabinet Membership dengan audit sejarah. |
| 2 | Kesulitan menelusuri evolusi struktur kementerian antar kabinet. | Modul Historical Institutional Tracking (Pencatatan perubahan nomenklatur, pemisahan, & merger instansi). |
| 3 | Data Tugas dan Fungsi belum terkelola secara terstruktur. | Domain khusus Tugas & Fungsi terintegrasi dengan struktur organisasi dan unit kerja. |
| 4 | Alur pengubahan data tidak memiliki verifikasi dan notifikasi. | Workflow multi-role (User -> Verifikator -> Admin) dilengkapi Realtime Notification & Audit Trail. |
| 5 | Redundansi data dan tabel sementara (*scratch tables*). | Model data baru yang bersih, berelasi formal, dan scalable, dengan strategi migrasi data bersih dari legacy. |
| 6 | Visualisasi dashboard eksekutif terbatas. | Modern Dashboard & Analytics Workspace untuk kebutuhan pimpinan dan data analyst. |

---

## 6. Target Pengembangan
1. **Target Jangka Pendek (Internship Milestone & Prototype):**
   - Dokumen Discovery Baseline lengkap dan tervalidasi.
   - Perancangan arsitektur data dan sistem yang solid.
   - Pembuatan interactive Prototype (Figma / Functional Prototype) mencakup Dashboard, Cabinet Management, Daftar K/L & Pemda, Detail Instansi, Struktur Organisasi, dan Workflow Verifikasi untuk dipresentasikan ke SESDEP.
   - Pembangunan Core API, Frontend modern, Realtime Notification, dan Data Analytics baseline.
2. **Target Jangka Panjang (Production Scalability):**
   - Sistem scalable melayani seluruh K/L dan Pemda di Indonesia (38 Provinsi, 514 Kab/Kota).
   - Integrasi single sign-on (SSO) aparatur/KemenPANRB.
   - Integrasi mendalam dengan sistem ASN digital nasional.

---

## 7. Scope Awal vs Scope Jangka Panjang
- **Scope Awal:**
  - Modul Master Instansi (Kementerian, Lembaga, Pemda).
  - Modul Kabinet & Periode (termasuk implementasi spesifik Kabinet Merah Putih & kabinet pembanding).
  - Modul Detail Profil Instansi, Tugas & Fungsi.
  - Modul Struktur Organisasi (Hierarki parent-child visual).
  - Modul Role & User Management (USER, ADMIN, VERIFIKATOR).
  - Workflow Pengajuan Data & Verifikasi.
  - Notifikasi Realtime (Create, Update, Delete, Submit, Verify).
  - Dashboard Eksekutif & Analytics Rekapitulasi.
  - Preservasi & cleansing data dari database `eskld`.
- **Scope Jangka Panjang:**
  - Integrasi API publik & interoperabilitas Satu Data Indonesia / SPBE.
  - Advanced AI-driven institutional analytics (analisis tumpang tindih tupoksi).
  - Automated report generation format resmi kementerian (PDF/Word/Excel custom engine).

---

## 8. Aktor & Tim Project
### A. Aktor Sistem
1. **USER (Operator / Pengelola Data Instansi):** Mengisi, mengedit profil instansi, tupoksi, struktur, dan mengajukan draft perubahan.
2. **VERIFIKATOR:** Memeriksa, meninjau kesesuaian data usulan, memberikan catatan revisi, atau menyetujui usulan.
3. **ADMIN (Administrator Sistem):** Mengelola konfigurasi sistem, kabinet, referensi master, manajemen user, dan final approval.

### B. Tim Pengembang
- **Ikhsan:** Data Analyst (Analisis struktur data legacy, postur data kelembagaan, formulasi view/matriks analitik, data quality).
- **Lead / User:** Senior Software Architect & Lead Full-Stack Engineer (Arsitektur sistem, backend API, frontend application, realtime engine, database architecture, testing, dokumentasi).

---

## 9. Prinsip Pengembangan
1. **Legacy as Reference, Not Copy-Paste:** Sistem legacy dianalisis untuk memahami aturan bisnis dan data existing, bukan di-clone kodenya.
2. **Data Preservation:** Data valid dari sistem legacy harus tetap aman dan dapat dimigrasikan ke skema baru tanpa kehilangan nilai historis.
3. **Decoupled Technology Choice:** Pemilihan framework frontend, backend, dan database dilakukan secara objektif setelah fase discovery disetujui.
4. **Clean Architecture & Scalability:** Arsitektur modular, terisolasi per layer (API, Business Logic, Persistence, Presentation), dan siap menangani ekspansi data nasional.
5. **Security & Auditability:** Setiap mutasi data wajib memiliki jejak audit (*who, when, what changed*) dan permission check yang ketat.
6. **Executive Readiness:** Antarmuka dirancang modern, responsif, dan mudah dipahami pimpinan kementerian.

---

## 10. Known Facts
1. Database legacy bernama `eskld` berisi data `tb_instansi`, `data_kl`, `data_pemda`, `ref_eselon`, `tbl_ref_instansi_org`, dan view `v_postur_asn`.
2. Struktur kabinet di legacy menggunakan tabel `data_kl` dengan kolom `tahun`, `is_active`, dan `list_id_kl`.
3. Struktur organisasi menggunakan konsep `parent_id` berjenjang.
4. Tiga role awal yang terkonfirmasi adalah USER, ADMIN, dan VERIFIKATOR.
5. Diperlukan Prototype untuk presentasi ke pimpinan/SESDEP.
6. Sistem baru harus mendukung pencatatan perubahan antar-kabinet (misal Kabinet Merah Putih).
7. Diperlukan notifikasi realtime untuk aksi manipulasi data penting.

---

## 11. Assumptions
1. Seluruh data legacy pada `eskld` dapat diekstraksi untuk proses analisis dan ETL (Extract, Transform, Load) ke struktur database baru.
2. Lingkungan deployment di masa mendatang mendukung penggunaan protokol modern (HTTP REST/JSON, WebSocket / SSE, Database Relasional ACID).
3. Pihak SESDEP menginginkan tampilan interaktif yang dapat mendemonstrasikan perbandingan kabinet dan visualisasi pohon struktur organisasi secara dinamis.

---

## 12. Unknown / TBD
1. `TBD-001`: Kebijakan integrasi Single Sign-On (SSO) internal KemenPANRB / ASN Digital vs Database Authentication mandiri.
2. `TBD-002`: Detail Service Level Agreement (SLA) dan tahapan verifikasi (apakah verifikasi 1 tingkat atau berjenjang per unit kerja).
3. `TBD-003`: Aturan validasi formal perubahan nomenklatur instansi (misal dasar hukum Perpres/Permen).
4. `TBD-004`: Infrastruktur server target deployment (On-Premise PDN / Cloud / VM lokal KemenPANRB).
