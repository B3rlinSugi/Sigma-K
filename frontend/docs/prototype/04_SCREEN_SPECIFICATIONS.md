# SIGMA-K — SCREEN SPECIFICATIONS (16 SCREENS)

## 1. Spesifikasi Layar Fungsional

### SCREEN 01: Executive Overview Dashboard (`/`)
- **Tujuan:** Memberikan ringkasan eksekutif bagi SESDEP dan Pimpinan Kementerian PANRB.
- **Komponen Utama:**
  - Kartu Metrik Utama: Total 48 K/L Kabinet Merah Putih (+14 Baru), 548 Pemda, Antrean Verifikasi Aktif, Berkas Lolos Siap Pengesahan.
  - Kartu Sorotan Kabinet Aktif: Presiden, Wakil Presiden, Dasar Hukum Keppres 133/P/2024.
  - Tabel Pengajuan Terkini dengan tombol detail tiket langsung.
  - Bagan Mini Komposisi K/L (Kemenko, Kementerian Teknis, LPNK, LNS).
  - Kartu Intelijensi Indikator Performa (*Proposed KPIs*).

### SCREEN 02: Cabinet List (`/cabinets`)
- **Tujuan:** Menampilkan riwayat seluruh kabinet pemerintahan Republik Indonesia.
- **Interaksi:** Pencarian teks bebas nama kabinet/presiden, indikator badge kabinet aktif, tombol aksi komparasi kabinet, dan tombol tambah kabinet bagi Admin.

### SCREEN 03: Create / Edit Cabinet (`/cabinets/new`)
- **Tujuan:** Pendaftaran era kabinet baru berbasis Keputusan Presiden.
- **Validasi:** Validasi kelengkapan nama, presiden, tanggal pelantikan, validasi tanggal berakhir harus $\ge$ tanggal mulai, serta sakelar penanda Kabinet Aktif Default.

### SCREEN 04: Cabinet Detail & Memberships (`/cabinets/[id]`)
- **Tujuan:** Rincian 48 kementerian dan lembaga yang bernaung di bawah suatu kabinet.
- **Interaksi:** Tab filter kategori (Semua, Kemenko, Kementerian Teknis), tabel daftar anggota berurut nomor urut kabinet, dan tautan langsung ke profil instansi.

### SCREEN 05: Cabinet Comparison (Diff Showcase) (`/cabinets/compare`)
- **Tujuan:** Membandingkan transformasi struktural antar-kabinet (Kabinet Indonesia Maju 2019 vs Kabinet Merah Putih 2024).
- **Fitur Khusus:** Kartu ringkasan delta (+7 Baru, 3 Split, 1 Merge, 5 Rename, 22 Tetap), Tab filter perubahan, dan komponen `DiffViewer` untuk menganalisis pemecahan kementerian (contoh: Kemendikbudristek dipecah menjadi Kemendikdasmen, Kemendiktisaintek, dan Kemenbud).

### SCREEN 06: Institution Catalog (`/institutions`)
- **Tujuan:** Katalog master kementerian, lembaga pemerintah, dan pemda se-Indonesia.
- **Interaksi:** Tab filter jenis instansi (*Kemenko, Kementerian Teknis, Pemda Provinsi*), pencarian kode/nama, dan navigasi profil.

### SCREEN 07: Institution Detail Profile (`/institutions/[id]`)
- **Tujuan:** Profil terpadu instansi kementerian tertentu (contoh: Kemenko Bidang Pangan).
- **Komponen:** Banner hero identitas, tab navigasi (Ringkasan Profil, Daftar Unit Kerja, Tugas dan Fungsi, Dasar Hukum PDF), dan tombol pintas ke kanvas bagan organisasi.

### SCREEN 08: Tugas dan Fungsi Master (`/tupoksi`)
- **Tujuan:** Katalog butir mandat tugas pokok dan rincian fungsi berdasar pasal regulasi.
- **Interaksi:** Filter jenis (Tugas Pokok / Rincian Fungsi), kutipan pasal Perpres (contoh: *Perpres No. 147/2024 Pasal 5 ayat (1)*), dan modal pengajuan usulan butir tugas baru.

### SCREEN 09: Organization Structure (React Flow Canvas) (`/structure`)
- **Tujuan:** Visualisasi kanvas graf pohon hierarki struktur organisasi interaktif.
- **Fitur Kanvas:** Custom node `OrgNode` (menampilkan eselon, pejabat pimpinan, jumlah staf), pengelompokan tingkat (*hierarchy level*), pencarian node dengan *focus pan*, minimap, zoom controls, dan *drawer* lembar rincian unit ketika node diklik.

### SCREEN 10: Submission Management (`/submissions`)
- **Tujuan:** Ruang kerja operator instansi untuk mengelola berkas pengajuan perubahan.
- **Interaksi:** Tab filter status tiket (*Submitted, In Review, Verified, Revision Required, Approved*), modal pembuatan tiket usulan baru lengkap dengan input upload PDF dasar hukum.

### SCREEN 11: Submission Detail & Stepper (`/submissions/[id]`)
- **Tujuan:** Rincian berkas pengajuan usulan lengkap dengan alur visual state machine.
- **Komponen:** Komponen `WorkflowStepper` 5 langkah, draf komparasi data usulan (*DiffViewer*), lampiran PDF, riwayat telaah verifikator, serta tombol kontekstual sesuai role pengguna.

### SCREEN 12A & 12B: Verification Queue & Workspace (`/verifications` & `/verifications/[id]`)
- **Tujuan:** Ruang kerja tim Analis Kelembagaan KemenPANRB untuk meneliti usulan instansi.
- **Fitur Khusus:** Panel komparasi berdampingan (*Side-by-Side Verification Panels*) yang menampilkan data live master di sisi kiri dan usulan baru di sisi kanan, serta bilah aksi putusan (*Pass / Minta Revisi / Tolak*) dengan modal input catatan telaah resmi.

### SCREEN 13: Revision Workflow (`/submissions/[id]/revision`)
- **Tujuan:** Formulir bagi operator pengusul untuk menanggapi dan memperbaiki catatan revisi dari verifikator PANRB.
- **Interaksi:** Kotak sorot catatan perbaikan verifikator, form penyuntingan teks dan pasal regulasi, serta tombol pengiriman ulang (*resubmit*).

### SCREEN 14: Notification Center (`/notifications`)
- **Tujuan:** Pusat pemberitahuan realtime dengan filter kategori alur kerja, master data, dan keamanan akun.

### SCREEN 15: Analytics Intelligence (`/analytics`)
- **Tujuan:** Ruang kerja intelijensi data kelembagaan dan formulasi Proposed KPIs bagi SESDEP.
- **Metrik:** Rasio perampingan jabatan fungsional (delayering), indeks kesiapan 48 K/L Kabinet Merah Putih, rata-rata kecepatan SLA verifikasi, dan distribusi formasi jabatan eselon.

### SCREEN 16: Audit Trail Forensics (`/audit-logs`)
- **Tujuan:** Log audit forensik tak-terhapuskan atas seluruh mutasi data, telaah verifikator, dan pengesahan sistem, dilengkapi modal penampil snapshot JSON nilai lama vs nilai baru (*old values vs new values*).
