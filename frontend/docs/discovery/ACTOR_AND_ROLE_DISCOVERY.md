# ACTOR AND ROLE DISCOVERY: SIGMA-K

> **Status:** DISCOVERY BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  

Dokumen ini mendokumentasikan peran (roles) dan aktor yang berinteraksi dengan sistem SIGMA-K berdasarkan data discovery awal. Kewenangan yang belum dikonfirmasi secara formal dicatat sebagai **Unknown/TBD** untuk mencegah asumsi liar.

---

## 1. Matriks Ringkasan Aktor

| Aktor / Role | Tipe Aktor | Status Konfirmasi | Scope Akses |
|---|---|---|---|
| **USER** | Human (Operator Instansi) | **CONFIRMED** | Terbatas pada instansi yang ditugaskan (Scoped). |
| **VERIFIKATOR** | Human (Petugas Peninjau KemenPANRB) | **CONFIRMED** | Lintas instansi / bidang tugas verifikasi. |
| **ADMIN** | Human (Administrator Sistem / KemenPANRB) | **CONFIRMED** | Seluruh sistem (Global System Access). |
| **PIMPINAN / SESDEP** | Human (Eksekutif Viewer) | **PROPOSED CANDIDATE** | Read-only eksekutif & analytics tingkat tinggi. |
| **DATA ANALYST** | Human (Analyst - Ikhsan) | **CONFIRMED (Internal)** | Akses data model, query agregat, postur ASN. |
| **SYSTEM / ENGINE** | Automated Process / Background Worker | **CONFIRMED (Internal)** | Pemrosesan realtime notifikasi, audit logging. |

---

## 2. Detail Role Terkonfirmasi

### A. Role: `USER` (Operator Instansi)
- **Purpose:** Bertindak sebagai pengelola data operasional pada tingkat instansi pemerintah terkait (Kementerian, Lembaga, atau Pemerintah Daerah), bertanggung jawab memelihara profil instansi, memperbarui struktur organisasi, dan mendaftarkan butir tugas dan fungsi.
- **Known Responsibilities:**
  - Mengelola profil instansi yang menjadi tanggung jawabnya.
  - Memasukkan dan memperbarui bagan struktur organisasi unit kerja internal.
  - Memasukkan dan memperbarui butir Tugas dan Fungsi (Tupoksi) instansi/unit kerja.
  - Mengajukan draf perubahan data (*submission draft*) ke sistem untuk diverifikasi.
  - Melakukan revisi atas pengajuan yang dikembalikan oleh Verifikator.
- **Known Permissions:**
  - `READ_INSTITUTION_SELF` (Melihat profil instansi sendiri).
  - `EDIT_INSTITUTION_DRAFT` (Membuat/mengubah draf data instansi sendiri).
  - `SUBMIT_INSTITUTION_CHANGE` (Mengirim tiket pengajuan perubahan data).
  - `READ_OWN_SUBMISSION_HISTORY` (Melihat status tiket pengajuan sendiri).
  - `RECEIVE_NOTIFICATIONS` (Menerima notifikasi status pengajuan/revisi).
- **Unknown / TBD:**
  - `TBD-ROLE-001`: Apakah satu USER dapat memegang lebih dari satu instansi (misal konsultan/koordinator wilayah).
  - `TBD-ROLE-002`: Apakah ada pemisahan sub-role di dalam instansi (misal Operator Unit Eselon I vs Operator Pusat Instansi).
  - `TBD-ROLE-003`: Batas kewenangan apakah USER boleh melihat draft instansi lain atau hanya instansinya sendiri.

---

### B. Role: `VERIFIKATOR` (Petugas Verifikasi)
- **Purpose:** Bertindak sebagai penilai/pemeriksa keabsahan dan kepatuhan administratif atas setiap usulan perubahan data kelembagaan yang diajukan oleh USER sebelum disahkan ke master data aktif.
- **Known Responsibilities:**
  - Menerima dan meninjau daftar tiket pengajuan perubahan data kelembagaan.
  - Memeriksa kelengkapan dasar hukum (regulasi Perpres/Permen/Perda) pendukung.
  - Memeriksa kesesuaian struktur organisasi dan tupoksi yang diajukan.
  - Memberikan catatan koreksi/revisi (*revision notes*) jika terdapat ketidaksesuaian data.
  - Memberikan rekomendasi persetujuan (*verification pass*) untuk diteruskan ke Admin / Approval final.
- **Known Permissions:**
  - `READ_ALL_SUBMISSIONS` (Melihat seluruh antrean tiket pengajuan yang ditugaskan).
  - `REVIEW_SUBMISSION_DIFF` (Melihat komparasi data lama vs usulan data baru).
  - `REQUEST_REVISION` (Mengembalikan pengajuan ke User dengan catatan perbaikan).
  - `VERIFY_SUBMISSION` (Menandai pengajuan sebagai terverifikasi / siap approval).
  - `RECEIVE_NOTIFICATIONS` (Menerima notifikasi saat ada submission baru atau revisi masuk).
- **Unknown / TBD:**
  - `TBD-ROLE-004`: Apakah Verifikator memiliki wewenang untuk menolak mutlak (*direct reject*) suatu pengajuan tanpa melalui Admin.
  - `TBD-ROLE-005`: Apakah pembagian tugas Verifikator dipartisi berdasarkan jenis instansi (misal: Tim Verifikator K/L vs Tim Verifikator Pemda) atau wilayah geografis.
  - `TBD-ROLE-006`: Apakah Verifikator berhak mengedit langsung teks salah ketik (*typo*) pada draft pengajuan atau wajib dikembalikan ke User.

---

### C. Role: `ADMIN` (Administrator Sistem)
- **Purpose:** Bertindak sebagai pemegang kendali tertinggi tata kelola sistem SIGMA-K, bertanggung jawab atas konfigurasi sistem, manajemen master kabinet, manajemen referensi nasional, tata kelola akun pengguna, dan persetujuan akhir perubahan data.
- **Known Responsibilities:**
  - Mengelola Master Kabinet, Periode Kabinet, dan Keanggotaan K/L pada Kabinet aktif.
  - Mengelola referensi nasional (Jenis Instansi, Wilayah, Eselon).
  - Mengelola akun pengguna (Create, Update, Deactivate, Role Assignment).
  - Memberikan persetujuan akhir (*final approval*) atas pengajuan yang telah lolos verifikasi sehingga resmi masuk ke Master Data.
  - Memantau log audit (*audit trail*), kesehatan sistem, dan statistik operasional.
- **Known Permissions:**
  - `MANAGE_SYSTEM_SETTINGS` (Konfigurasi global sistem).
  - `MANAGE_CABINETS` (CRUD data kabinet, periode, dan keanggotaan kabinet).
  - `MANAGE_MASTER_REFERENCES` (CRUD jenis instansi, eselon, wilayah).
  - `MANAGE_USERS` (CRUD user, reset password, assign role & instansi).
  - `APPROVE_SUBMISSION` (Persetujuan final data ke Master Data aktif).
  - `OVERRIDE_DATA` (Kewenangan perbaikan data darurat langsung ke master data).
  - `VIEW_AUDIT_LOGS` (Melihat seluruh jejak aktivitas pengguna).
  - `RECEIVE_NOTIFICATIONS` (Menerima notifikasi eskalasi dan event sistem kritis).
- **Unknown / TBD:**
  - `TBD-ROLE-007`: Apakah ada pembagian peran Super Admin vs Admin Teknis vs Admin Fungsional Kelembagaan.
  - `TBD-ROLE-008`: Apakah Admin dapat mendelegasikan hak final approval kepada Verifikator untuk kategori perubahan data minor (misal nomor telepon / website).

---

## 3. Candidate / Auxiliary Roles (Untuk Evaluasi Lanjutan)

### D. Role: `PIMPINAN / SESDEP` (Eksekutif Viewer - PROPOSED)
- **Purpose:** Menyediakan akses antarmuka dashboard eksekutif berorientasi pengawasan dan pengambilan keputusan strategis tanpa hak mutasi data operasional.
- **Known Potential Responsibilities:**
  - Memantau postur kelembagaan nasional, rekapitulasi kabinet, dan progres verifikasi data kelembagaan.
  - Mengakses visualisasi analitik data dan tren perampingan/penataan birokrasi.
- **Known Permissions (Proposed):**
  - `VIEW_EXECUTIVE_DASHBOARD`
  - `VIEW_ANALYTICS_REPORTS`
  - `EXPORT_EXECUTIVE_SUMMARY`
- **Unknown / TBD:**
  - `TBD-ROLE-009`: Apakah akun pimpinan/SESDEP memerlukan otorisasi khusus atau digabungkan dalam view role tersendiri.

---

## 4. Matriks Akses Fitur Awal (Preliminary Feature Matrix)

| Modul / Fitur | USER | VERIFIKATOR | ADMIN | PIMPINAN (TBD) |
|---|:---:|:---:|:---:|:---:|
| Executive Dashboard | Ringkasan Terbatas | Ringkasan Verifikasi | Full Dashboard | Full Executive |
| Master Data Kabinet | View Only | View Only | Full CRUD | View Only |
| Komposisi Anggota Kabinet | View Only | View Only | Full CRUD | View Only |
| Detail Instansi & Tupoksi (Own Instansi) | Create/Edit Draft | View All | Full CRUD | View All |
| Detail Instansi & Tupoksi (Other Instansi) | View Only | View All | Full CRUD | View All |
| Bagan Struktur Organisasi | Create/Edit Draft | View All | Full CRUD | View All |
| Submission Tiket Perubahan | Submit Draft | Review Only | Review & Approve | View Status |
| Layar Verifikasi & Catatan Revisi | Read Feedback | Full Verifikasi | Full Verifikasi | Read Only |
| Final Approval & Publishing | No Access | No Access | Approve / Publish | No Access |
| Manajemen Akun Pengguna | No Access | No Access | Full CRUD | No Access |
| Audit Trail Viewer | No Access | No Access | Full Access | View Summary |
| Notifikasi Realtime | Yes (Personal) | Yes (Workflow) | Yes (All System) | Yes (Summary) |
