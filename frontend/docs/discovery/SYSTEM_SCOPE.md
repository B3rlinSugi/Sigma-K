# SYSTEM SCOPE: SIGMA-K

> **Status:** DISCOVERY BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  

Dokumen ini mendefinisikan batasan ruang lingkup (*system boundaries*) untuk pengembangan SIGMA-K, memisahkan secara tegas apa yang masuk dalam lingkup aktif, apa yang secara eksplisit dikeluarkan, apa yang dicadangkan untuk masa depan, dan aspek yang masih membutuhkan konfirmasi.

---

## 1. IN SCOPE (Ruang Lingkup Aktif / Prototype & Magang)

Ruang lingkup yang menjadi komitmen pengembangan dalam fase saat ini mencakup:

### A. Manajemen Master Instansi & Profil
- Pengelolaan data instansi Kementerian, Lembaga Pemerintah Non-Kementerian (LPNK), Lembaga Non-Struktural (LNS), dan Pemerintah Daerah (Provinsi, Kab/Kota).
- Pengelolaan profil detail instansi (identitas, dasar hukum pembentukan, kontak, alamat, logo).
- Filter, pencarian, dan kategorisasi instansi berdasarkan tipe dan wilayah.

### B. Manajemen Kabinet & Periodesasi (Historical Cabinet)
- Pengelolaan master kabinet pemerintahan Indonesia (nama kabinet, periode tahun, pimpinan/presiden).
- Pemetaan keanggotaan instansi dalam kabinet tertentu (misalnya daftar kementerian resmi pada Kabinet Merah Putih vs kabinet sebelumnya).
- Pelacakan dan visualisasi riwayat perubahan status kelembagaan antar-kabinet (institusi baru, pemisahan kementerian, penggabungan, atau pembubaran).

### C. Struktur Organisasi & Eselon
- Pemodelan hierarki bagan struktur organisasi unit kerja (Eselon I, Eselon II, dsb.) berbasis relasi *parent-child*.
- Visualisasi interaktif pohon struktur organisasi (*Interactive Org Chart*).
- Pencegahan kesalahan relasi berulang (*circular dependency check*).

### D. Pengelolaan Tugas & Fungsi (Tupoksi)
- Pencatatan dan pengelolaan butir-butir Tugas dan Fungsi pada level instansi dan unit kerja.
- Pengaitan tugas dan fungsi dengan dasar hukum/pasal regulasi terkait.

### E. Tata Kelola Pengguna & Hak Akses (RBAC)
- Otentikasi pengguna berbasis peran (*Role-Based Access Control*).
- Implementasi 3 peran utama terkonfirmasi: `USER` (Operator Instansi), `VERIFIKATOR` (Peninjau KemenPANRB), dan `ADMIN` (Administrator Sistem).
- Pembatasan akses berbasis instansi (User hanya dapat mengelola data instansinya sendiri).

### F. Workflow Pengajuan & Verifikasi (Draft-to-Publish)
- Siklus pengajuan perubahan data: `DRAFT` $\rightarrow$ `SUBMITTED` $\rightarrow$ `IN_REVIEW` $\rightarrow$ `REVISION_REQUIRED` $\rightarrow$ `APPROVED` / `REJECTED`.
- Antarmuka komparasi data usulan vs data aktif (*diff preview*) untuk verifikator.
- Riwayat catatan revisi antara verifikator dan operator instansi.

### G. Notifikasi Realtime & Audit Trail
- Sistem notifikasi realtime saat terjadi mutasi data (Create, Update, Delete, Submit, Verify, Approve, Reject).
- Pencatatan log audit immutable (*who, what, when, old values, new values*) untuk setiap perubahan data master.

### H. Dashboard Eksekutif & Baseline Data Analytics
- Dashboard eksekutif interaktif untuk pimpinan/SESDEP (statistik kabinet, rekapitulasi instansi, progres verifikasi).
- Integrasi model data analitik untuk evaluasi postur kelembagaan (bekerja sama dengan Data Analyst Ikhsan).

### I. Deliverable Khusus
- Pembuatan **Interactive Prototype** yang siap dipresentasikan di hadapan pimpinan / SESDEP.
- Preservasi dan migrasi data bersih dari database legacy `eskld`.
- Standar rekayasa perangkat lunak profesional (Frontend, Backend, Database, API, Unit Testing, Documentation) terpusat pada repository GitHub.

---

## 2. OUT OF SCOPE (Secara Eksplisit Dikeluarkan dari Tahap Ini)

Item berikut secara tegas **TIDAK** termasuk dalam lingkup pengembangan tahap ini:
1. **Pembuatan Source Code Aplikasi pada Fase Discovery:** Tidak ada penulisan source code Laravel, Next.js, React, atau pembuatan database baru sebelum fase Discovery Baseline disetujui.
2. **Penggantian / Modifikasi Folder `KemenPANRB_LEGACY`:** Folder legacy lama tidak boleh diakses atau diubah.
3. **Penyalinan Mentah (*Blind Copy-Paste*) Kode Legacy:** Seluruh kode legacy dianggap referensi logika, bukan komponen yang disalin langsung.
4. **Sistem Penggajian & Kepegawaian Detail (HRIS/Payroll):** SIGMA-K berfokus pada **struktur kelembagaan** dan **postur makro**, bukan mutasi pegawai personal, absensi, atau payroll ASN.
5. **Integrasi Payment Gateway / Keuangan Negara:** Tidak ada transaksi finansial dalam sistem ini.
6. **Modul Pengadaan Barang & Jasa (PBJ):** Bukan domain tata kelola kelembagaan KemenPANRB.

---

## 3. FUTURE SCOPE (Ruang Lingkup Jangka Panjang / Pasca-Magang)

Item berikut direncanakan untuk pengembangan sistem matang skala nasional di masa depan:
1. **Integrasi Skala Penuh Seluruh Pemda (548 Pemda se-Indonesia):** Onboarding dan pelatihan mandiri bagi seluruh admin Pemda se-Indonesia.
2. **Single Sign-On (SSO) Terpusat Nasional:** Integrasi dengan portal ASN Digital / Identitas Digital Nasional (INAPAS / Satu Data Indonesia).
3. **AI-Driven Semantic Policy Checker:** Pemanfaatan Large Language Model (LLM) untuk mendeteksi tumpang tindih (*overlap*) Tugas dan Fungsi antar 48+ kementerian secara otomatis.
4. **Interoperabilitas SPBE (Sistem Pemerintahan Berbasis Elektronik):** Penyediaan API gateway publik terstandarisasi untuk konsumsi Satu Data Indonesia dan Bappenas.
5. **Aplikasi Mobile Native (iOS / Android):** Aplikasi mobile khusus eksekutif pimpinan untuk monitoring real-time.

---

## 4. UNKNOWN / TBD (Memerlukan Klarifikasi Tambahan)

| Area Scope | Poin Ketidakpastian | Tindakan yang Dibutuhkan |
|---|---|---|
| **Alur Verifikasi** | Apakah verifikasi dilakukan oleh 1 verifikator atau membutuhkan persetujuan berjenjang hingga Direktur/Deputi? | Wawancara alur SOP verifikasi dengan stakeholder KemenPANRB. |
| **Kanal Notifikasi** | Apakah cukup *in-app notification* (Web) atau harus ada integrasi WhatsApp Gateway / Email? | Konfirmasi ketersediaan infrastruktur mail server / WA API resmi kementerian. |
| **Integrasi SIASN/BKN** | Apakah view postur ASN (`v_postur_asn`) akan mendapatkan API feed otomatis dari BKN atau di-upload manual per periode? | Konfirmasi dengan Data Analyst Ikhsan dan pemilik data di KemenPANRB. |
| **Infrastruktur Target** | Apakah deployment target menggunakan Server PDN (Pusat Data Nasional), Cloud AWS/GCP, atau Server internal On-Premise? | Konfirmasi dengan tim IT / Pusdatin KemenPANRB. |
