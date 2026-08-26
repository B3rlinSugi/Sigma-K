# BUSINESS DOMAIN MAP: SIGMA-K

> **Status:** DISCOVERY BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  

Dokumen ini memetakan domain bisnis (bounded contexts) yang teridentifikasi dalam sistem SIGMA-K, mencakup perbandingan antara data/bukti legacy (*existing evidence*) dengan kebutuhan baru (*new requirements*).

---

## Ringkasan Matriks Domain Bisnis

```
+-----------------------------------------------------------------------------------+
|                               SIGMA-K CORE DOMAIN                                 |
+-----------------------------------------------------------------------------------+
|  [Cabinet & Period]  <--->  [Institution Membership]  <--->  [Institution Master]  |
|         |                                                           |             |
|         v                                                           v             |
|  [Historical Tracker]                                      [Tugas & Fungsi]       |
|                                                                     |             |
|                                                            [Org Structure]        |
|                                                                     |             |
|                                                            [Position / Eselon]    |
+-----------------------------------------------------------------------------------+
|                             GOVERNANCE & WORKFLOW                                 |
+-----------------------------------------------------------------------------------+
|  [Submission]  --->  [Verification]  --->  [Approval / Revision]  --->  [Audit]   |
|         ^                   ^                     ^                      |        |
|         +-------------------+---------------------+                      v        |
|                                     |                              [Notification] |
|                             [User & Auth / RBAC]                                  |
+-----------------------------------------------------------------------------------+
|                             INSIGHT & PRESENTATION                                |
+-----------------------------------------------------------------------------------+
|                     [Executive Dashboard]  <--->  [Data Analytics]                |
+-----------------------------------------------------------------------------------+
```

---

## Detail Domain Bisnis

### 1. Institution Management (Master Instansi)
- **Tujuan:** Mengelola data induk seluruh entitas instansi pemerintah pusat (Kementerian, LPNK, LNS) dan pemerintah daerah (Provinsi, Kab/Kota) secara terstandarisasi.
- **Aktor:** Admin, Verifikator, User.
- **Input:** Nama instansi, singkatan/akronim, jenis instansi, kode wilayah, kode referensi nasional, status aktif.
- **Output:** Master list instansi terpusat, filter pencarian instansi, katalog instansi aktif.
- **Relasi dengan Domain Lain:** Cabinet Membership, Institution Profile, Organization Structure, Dashboard.
- **Existing Evidence:** Tabel `tb_instansi`, `tb_jenis_instansi`, `tb_wilayah`, `data_pemda`.
- **Requirement Baru:** Standardisasi kodefikasi unik, pemisahan entitas K/L dan Pemda yang lebih bersih tanpa tabel mapping ad-hoc.
- **Unknown/TBD:** Format penomoran/kode unik instansi standar nasional (apakah menggunakan kode Kemendagri / BPS / KemenPANRB).

---

### 2. Cabinet Management (Manajemen Kabinet)
- **Tujuan:** Mengelola daftar kabinet/pemerintahan di Indonesia (misal: Kabinet Indonesia Maju, Kabinet Merah Putih).
- **Aktor:** Admin, User (Read-only).
- **Input:** Nama kabinet, nama Presiden/Wakil Presiden, deskripsi, status aktif/arsip.
- **Output:** Daftar kabinet, status kabinet aktif terpilih (*current active cabinet*).
- **Relasi dengan Domain Lain:** Cabinet Period, Institution Membership, Historical Institutional Changes, Dashboard.
- **Existing Evidence:** Tabel `data_kl` (field `tahun`, `is_active`, label kabinet).
- **Requirement Baru:** Entitas Kabinet berdiri sendiri (first-class entity) dengan metadata lengkap, bukan sekadar atribut tahun di tabel `data_kl`.
- **Unknown/TBD:** Apakah kabinet hanya berlaku untuk K/L tingkat pusat atau juga mempengaruhi nomenklatur perwakilan instansi di daerah.

---

### 3. Cabinet Period (Periode Kabinet)
- **Tujuan:** Menentukan rentang waktu berlakunya suatu kabinet (tahun mulai, tahun selesai, tanggal penetapan dasar hukum).
- **Aktor:** Admin.
- **Input:** Tanggal mulai, tanggal berakhir, dasar hukum (Keppres/Perpres pembentukan kabinet), status periode.
- **Output:** Rentang waktu aktif kabinet, validasi rentang waktu untuk histori kelembagaan.
- **Relasi dengan Domain Lain:** Cabinet Management, Institution Membership, Audit.
- **Existing Evidence:** Field `tahun` pada tabel `data_kl`.
- **Requirement Baru:** Pengaturan masa jabatan/periode formal dengan validasi temporal (start date - end date).
- **Unknown/TBD:** Kebijakan penanganan masa transisi antar-kabinet (periode overlap).

---

### 4. Institution Membership (Keanggotaan Instansi dalam Kabinet)
- **Tujuan:** Memetakan instansi-instansi mana saja yang eksis dan aktif pada suatu periode kabinet tertentu.
- **Aktor:** Admin, Verifikator.
- **Input:** ID Kabinet, ID Instansi, status keanggotaan, tanggal mulai bergabung dalam kabinet, tanggal berakhir.
- **Output:** Komposisi resmi K/L pada kabinet terpilih (misal: daftar 48 kementerian/lembaga pada Kabinet Merah Putih).
- **Relasi dengan Domain Lain:** Cabinet Management, Institution Management, Historical Tracker, Dashboard.
- **Existing Evidence:** Field `list_id_kl` (string berisi list ID berformat koma/delimit pada `data_kl`).
- **Requirement Baru:** Relasi relasional many-to-many ternormalisasi (*CabinetMembership entity*) menggantikan string delimit `list_id_kl`.
- **Unknown/TBD:** Status instansi non-kementerian (apakah seluruh LPNK/LNS otomatis masuk keanggotaan kabinet atau hanya kementerian koordinator dan teknis).

---

### 5. Institution Profile (Profil Instansi)
- **Tujuan:** Mengelola informasi komprehensif mengenai profil instansi (alamat, kontak, website, dasar hukum pendirian, logo, visi/misi).
- **Aktor:** User (Operator Instansi), Admin, Verifikator.
- **Input:** Detail profil instansi, kontak resmi, alamat kantor, regulasi pendirian (Perpres/Permen).
- **Output:** Halaman detail profil instansi yang lengkap dan terverifikasi.
- **Relasi dengan Domain Lain:** Institution Management, Tugas & Fungsi, Organization Structure, Submission.
- **Existing Evidence:** Parsial di `tb_instansi`, namun metadata profil belum terstruktur penuh.
- **Requirement Baru:** Manajemen profil kaya data dengan riwayat regulasi dan lampiran dokumen dasar hukum.
- **Unknown/TBD:** Batas ukuran file lampiran dasar hukum (PDF Perpres/Permen) dan lokasi penyimpanan aset.

---

### 6. Tugas & Fungsi (Tupoksi Kelembagaan)
- **Tujuan:** Mengelola butir-butir Tugas dan Fungsi instansi serta unit kerja di bawahnya sesuai regulasi yang berlaku.
- **Aktor:** User (Operator Instansi), Verifikator, Admin.
- **Input:** Teks Tugas, butir-butir Fungsi, dasar hukum pasal/ayat, relasi ke unit organisasi terkait.
- **Output:** Matriks tugas dan fungsi per instansi dan per unit kerja, laporan keselarasan tupoksi.
- **Relasi dengan Domain Lain:** Institution Profile, Organization Structure, Verification, Data Analytics.
- **Existing Evidence:** Belum terstruktur secara formal di database legacy (hanya ada referensi parsial).
- **Requirement Baru:** Modul dedicated untuk pencatatan, pembaruan, dan analisis Tugas & Fungsi per instansi/unit.
- **Unknown/TBD:** Apakah diperlukan fitur text analysis/semantic matching untuk mendeteksi tumpang tindih fungsi antar instansi.

---

### 7. Organization Structure (Struktur Organisasi)
- **Tujuan:** Memetakan hierarki bagan organisasi instansi secara berjenjang dari pimpinan tertinggi hingga unit eselon di bawahnya.
- **Aktor:** User, Verifikator, Admin.
- **Input:** Nama unit kerja, level hirarki, `parent_id` (unit atasan), kode unit, tingkat eselon.
- **Output:** Pohon struktur organisasi visual (interactive org chart), daftar hierarki unit kerja.
- **Relasi dengan Domain Lain:** Institution Management, Position/Eselon, Tugas & Fungsi, Dashboard.
- **Existing Evidence:** Tabel `tbl_ref_instansi_org` dengan kolom `parent_id`.
- **Requirement Baru:** Visualisasi org-chart interaktif, drag-and-drop / node management, validasi circular dependency hierarki.
- **Unknown/TBD:** Maksimum kedalaman hierarki unit kerja (misal: Sekretariat Jenderal -> Biro -> Bagian -> Subbagian / Kelompok Jabatan Fungsional pasca penyederhanaan birokrasi).

---

### 8. Position / Eselon (Jabatan & Tingkat Eselon)
- **Tujuan:** Mengelola master data tingkat jabatan struktural/eselon (Eselon I.a, I.b, II.a, II.b, III, IV, Non-Eselon, Fungsional).
- **Aktor:** Admin.
- **Input:** Kode eselon, nama tingkatan eselon, bobot/urutan jabatan.
- **Output:** Referensi standar eselon untuk penugasan pada unit struktur organisasi.
- **Relasi dengan Domain Lain:** Organization Structure, Dashboard, Data Analytics.
- **Existing Evidence:** Tabel `ref_eselon`.
- **Requirement Baru:** Penyesuaian klasifikasi pasca-delayering (penyetaraan jabatan fungsional) dan integrasi dengan hierarki unit.
- **Unknown/TBD:** Standar kode jabatan fungsional yang menggantikan eselon III & IV.

---

### 9. User Management (Manajemen Pengguna)
- **Tujuan:** Mengelola akun pengguna sistem, profil pengguna, status aktivasi, dan assignment instansi asal.
- **Aktor:** Admin.
- **Input:** Nama, email/NIP, password/kredensial, instansi terkait, role pengguna, status aktif.
- **Output:** Daftar pengguna terdaftar, status otentikasi sesi.
- **Relasi dengan Domain Lain:** Role & Authorization, Audit, Notification.
- **Existing Evidence:** Tabel `users`.
- **Requirement Baru:** Manajemen pengguna terstruktur dengan pengikatan instansi kerja (user hanya bisa mengelola instansinya sendiri).
- **Unknown/TBD:** Mekanisme integrasi SSO ASN Digital / KemenPANRB Auth.

---

### 10. Role & Authorization (Peran & Hak Akses / RBAC)
- **Tujuan:** Mengatur pembatasan akses fitur dan data berdasarkan peran (USER, ADMIN, VERIFIKATOR).
- **Aktor:** Admin, Sistem (Enforcer).
- **Input:** Role definition, permissions matrix, resource scoping.
- **Output:** Keputusan otorisasi (Allow / Deny) pada setiap aksi API dan navigasi UI.
- **Relasi dengan Domain Lain:** Seluruh Domain Bisnis.
- **Existing Evidence:** Field role dasar pada tabel `users`.
- **Requirement Baru:** Fine-grained Role-Based Access Control (RBAC) dengan pemisahan tegas kewenangan operasional.
- **Unknown/TBD:** Apakah verifikator dibagi per wilayah/bidang (misal Verifikator Wilayah Barat vs Timur, Verifikator K/L vs Pemda).

---

### 11. Submission (Pengajuan Perubahan Data)
- **Tujuan:** Memfasilitasi operator instansi (User) untuk mengajukan usulan penambahan/perubahan data profil, struktur, atau tupoksi kelembagaan.
- **Aktor:** User (Operator Instansi).
- **Input:** Draft data perubahan, dokumen dasar hukum pendukung, catatan pengajuan.
- **Output:** Nomor tiket pengajuan, status submission (`DRAFT`, `SUBMITTED`, `IN_REVIEW`, `REVISION_REQUIRED`, `APPROVED`, `REJECTED`).
- **Relasi dengan Domain Lain:** Verification, Revision, Approval, Notification, Audit.
- **Existing Evidence:** Belum ada di legacy (perubahan data langsung diedit di tabel tanpa siklus draft/submission).
- **Requirement Baru:** Mekanisme draft-to-submission untuk menjaga integritas data master.
- **Unknown/TBD:** Batas waktu (timeout/expiration) dari tiket pengajuan yang tidak diproses.

---

### 12. Verification (Verifikasi Data)
- **Tujuan:** Menyediakan antarmuka bagi Verifikator untuk memeriksa, membandingkan data usulan (diff viewer), dan memvalidasi keabsahan dasar hukum.
- **Aktor:** Verifikator.
- **Input:** ID Tiket Submission, hasil pemeriksaan dokumen, status verifikasi (Pass / Need Revision / Reject).
- **Output:** Rekomendasi verifikasi, catatan/feedback verifikator.
- **Relasi dengan Domain Lain:** Submission, Revision, Approval, Notification.
- **Existing Evidence:** Tidak ada di legacy.
- **Requirement Baru:** Layar verifikasi terdedikasi dengan visualisasi perbandingan data lama vs data usulan (*diff view*).
- **Unknown/TBD:** Apakah verifikator dapat langsung mengedit data usulan atau hanya memberi catatan revisi.

---

### 13. Approval (Persetujuan Akhir)
- **Tujuan:** Eksekusi persetujuan akhir oleh Admin/Pimpinan yang menyebabkan data usulan resmi diterapkan ke Master Data aktif.
- **Aktor:** Admin.
- **Input:** Rekomendasi verifikasi, konfirmasi approval.
- **Output:** Publikasi data usulan ke Master Data aktif, status tiket `APPROVED`.
- **Relasi dengan Domain Lain:** Submission, Verification, Master Data, Notification, Audit.
- **Existing Evidence:** Tidak ada di legacy.
- **Requirement Baru:** Atomic transaction untuk memindahkan data draft yang disetujui menjadi live data.
- **Unknown/TBD:** Apakah Admin dapat mendelegasikan hak approval kepada Lead Verifikator.

---

### 14. Revision (Revisi Pengajuan)
- **Tujuan:** Memungkinkan User untuk memperbaiki butir-butir data yang dikembalikan oleh Verifikator dengan catatan koreksi.
- **Aktor:** User, Verifikator.
- **Input:** Catatan perbaikan, data yang telah dikoreksi.
- **Output:** Status pengajuan kembali (`RESUBMITTED`).
- **Relasi dengan Domain Lain:** Submission, Verification, Notification.
- **Existing Evidence:** Tidak ada di legacy.
- **Requirement Baru:** Pelacakan riwayat iterasi revisi (versioning catatan perbaikan).
- **Unknown/TBD:** Maksimum batas siklus revisi yang diperbolehkan sebelum tiket dinyatakan rejected.

---

### 15. Notification (Notifikasi Realtime)
- **Tujuan:** Memberikan pemberitahuan seketika (*realtime*) kepada aktor terkait ketika terjadi peristiwa penting (Create, Edit, Delete, Submit, Verify, Approve, Reject).
- **Aktor:** Sistem (Penerbit), User/Verifikator/Admin (Penerima).
- **Input:** Event trigger, metadata payload, recipient target.
- **Output:** In-app notification toast/bell badge, realtime alert event.
- **Relasi dengan Domain Lain:** Seluruh Domain Bisnis (khususnya Submission, Verification, Audit).
- **Existing Evidence:** Tidak ada di legacy.
- **Requirement Baru:** Engine realtime notification (kandidat WebSocket / SSE) dengan in-app notification center.
- **Unknown/TBD:** Apakah memerlukan kanal eksternal (Email / WhatsApp notification).

---

### 16. Audit (Audit Trail & Logging)
- **Tujuan:** Merekam seluruh jejak aktivitas pengguna dan mutasi data untuk akuntabilitas, transparansi, dan investigasi forensik data.
- **Aktor:** Sistem (Otomatis), Admin (Viewer).
- **Input:** User ID, IP Address, User Agent, Action Type, Entity Name, Entity ID, Old Values, New Values, Timestamp.
- **Output:** Log riwayat perubahan (*Audit Log Viewer*), laporan kepatuhan data.
- **Relasi dengan Domain Lain:** Seluruh Domain Bisnis.
- **Existing Evidence:** Tidak ada audit trail terstruktur di legacy.
- **Requirement Baru:** Immutable audit log table dengan payload snapshot JSON per transaksi.
- **Unknown/TBD:** Kebijakan retensi data audit log (misal disimpan selama 5 tahun atau selamanya).

---

### 17. Dashboard (Dashboard Eksekutif & Operasional)
- **Tujuan:** Menyajikan ringkasan visual metrik kelembagaan, komposisi kabinet aktif, status verifikasi, dan postur instansi secara komprehensif untuk Pimpinan/SESDEP dan Admin.
- **Aktor:** Pimpinan / SESDEP, Admin, Verifikator, User.
- **Input:** Agregasi data master, kabinet, tiket submission, dan postur kelembagaan.
- **Output:** Widget statistik, grafik distribusi instansi per jenis/wilayah, kartu ringkasan kabinet, feed aktivitas terbaru.
- **Relasi dengan Domain Lain:** Master Data, Cabinet, Organization Structure, Data Analytics.
- **Existing Evidence:** VIEW rekap/dashboard pada database `eskld`.
- **Requirement Baru:** UI dashboard modern berstandar enterprise, interaktif, responsif, dan siap dipresentasikan ke SESDEP.
- **Unknown/TBD:** Format widget khusus yang diinginkan pimpinan (misal: peta sebaran geografis instansi kementerian vs daerah).

---

### 18. Data Analytics (Analitik Kelembagaan & Postur ASN)
- **Tujuan:** Menganalisis pola kelembagaan, perbandingan struktur antar-kabinet, dan postur ASN/kelembagaan untuk rekomendasi kebijakan.
- **Aktor:** Data Analyst (Ikhsan), Pimpinan/SESDEP, Admin.
- **Input:** Data historis kabinet, data struktur organisasi, data `v_postur_asn`, data tupoksi.
- **Output:** Laporan analitik komparatif, visualisasi tren restrukturisasi birokrasi, postur ASN per instansi.
- **Relasi dengan Domain Lain:** Master Data, Cabinet, Position/Eselon, Dashboard.
- **Existing Evidence:** View `v_postur_asn` dan view-view agregat di legacy.
- **Requirement Baru:** Modul analitik data terstruktur yang dikembangkan bersama Data Analyst untuk kebutuhan perumusan kebijakan kelembagaan.
- **Unknown/TBD:** Sumber data update rutin untuk metrik postur ASN (apakah sinkronisasi dari SIASN / BKN).
