# PROTOTYPE HANDOFF & UI/UX SPECIFICATIONS: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Target Pengguna Dokumen:** UI/UX Designer, Frontend Engineer, & Stakeholder Presentation Team  
> **Author:** Senior Product Analyst & Lead Full-Stack Engineer  
> **Tujuan:** Dokumen ini menjadi **panduan arsitektur antarmuka dan cetak biru desain (*Design Blueprint*)** untuk pembuatan Interactive Prototype (Figma / Web Prototype) yang siap dipresentasikan di hadapan SESDEP dan pimpinan KemenPANRB.

---

## 1. Prinsip Desain Antarmuka SIGMA-K
1. **Executive Aesthetics (Kesan Pertama Pimpinan):** Tampilan harus memancarkan kesan berkelas, modern, dan tepercaya (*Enterprise Grade*), memanfaatkan palet warna institusional yang harmonis, kartu data berbingkai elegan (*subtle border & glassmorphism*), dan tipografi modern (Inter / Outfit).
2. **Dynamic & Responsive Awareness:** Memberikan sensasi antarmuka yang hidup melalui indikator realtime aktif, pembaruan data instan (*no unnecessary full reload*), transisi halus, dan feedback interaktif.
3. **Information Hierarchy & Clean Scannability:** Struktur visual menonjolkan ringkasan metrik utama di bagian atas, diikuti filter eksplorasi, visualisasi bagan interaktif, dan tabel data terstruktur.
4. **Actionable Workflow Interface:** Memisahkan secara visual antara mode lihat (*read-only*), mode draf (*draft staging*), mode peninjauan verifikator (*diff view*), dan mode persetujuan eksekutif.

---

## 2. Peta Halaman & Spesifikasi Layar Antarmuka

### PAGE-001: Layar Otentikasi & Login (Login Page)
- **Page ID:** PAGE-001
- **Page Name:** Login & Sesi Otentikasi
- **Actor:** All Users (USER, VERIFIKATOR, ADMIN, PIMPINAN)
- **Purpose:** Menyediakan pintu masuk yang aman dan elegan bagi pengguna untuk mengakses sistem.
- **Main Information:** Logo resmi SIGMA-K & Kementerian PANRB, form username/email, form kata sandi, opsi remember me, tombol login lokal, dan opsi Login SSO.
- **Primary Action:** Tombol "Masuk ke Sistem".
- **Secondary Action:** Tombol "Masuk via SSO ASN Digital" [PROPOSED TBD], Tautan "Lupa Kata Sandi".
- **Required Components:** Branded Split Hero Card, Floating Input Fields, Password Visibility Toggle, Alert Message Box, Loading Spinner.
- **States:** Default (Kosong), Mengisi Form, Validating/Loading, Error Kredensial Salah.
- **Permission:** Public / Unauthenticated.
- **Related Requirement:** [REQ-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-002: Dashboard Eksekutif Utama (Executive Overview Dashboard)
- **Page ID:** PAGE-002
- **Page Name:** Executive Overview & Cabinet Dashboard
- **Actor:** Pimpinan / SESDEP, Admin, All Users
- **Purpose:** Menyajikan ringkasan visual postur kelembagaan nasional, status Kabinet Merah Putih, dan rekapitulasi data secara instan.
- **Main Information:** Kartu KPI (Total K/L, Total Pemda, Jumlah Instansi Kabinet Aktif, Tiket Menunggu Verifikasi), Banner Sorotan Kabinet Aktif, Grafik Distribusi Kategori K/L, Peta/Grafik Sebaran Wilayah, dan Feed Aktivitas Realtime.
- **Primary Action:** Memilih/Mengganti Filter Periode Kabinet Aktif.
- **Secondary Action:** Menekan kartu statistik untuk menuju ke katalog terkait, Menekan item feed notifikasi.
- **Required Components:** Metric KPI Cards, Cabinet Spotlight Banner, Donut/Bar Chart Visualizations, Realtime Activity Feed Card, Quick Action Shortcuts.
- **States:** Loading Skeletons, Data Loaded, Empty State (jika belum ada data).
- **Permission:** Authenticated Users (`VIEW_DASHBOARD`).
- **Related Requirement:** [REQ-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-003: Katalog & Daftar Master Instansi (Institution List)
- **Page ID:** PAGE-003
- **Page Name:** Katalog Master Instansi Pemerintah
- **Actor:** All Users, Admin
- **Purpose:** Menjelajahi, mencari, dan memfilter seluruh instansi kementerian, lembaga, dan pemerintah daerah.
- **Main Information:** Tabel master instansi (Kode, Nama Resmi, Singkatan, Tipe Instansi, Wilayah, Jumlah Unit Kerja, Status Keaktifan, Tag Anggota Kabinet).
- **Primary Action:** Pencarian Instan (Live Search) & Filter Kategori (Kementerian / LPNK / Pemda).
- **Secondary Action:** Tombol "Tambah Instansi Baru" (Khusus Admin), Tombol "Export Data" (Excel/PDF).
- **Required Components:** Search Bar, Filter Chips / Dropdowns, Data Table dengan Sorting & Paginasi, Status Badges, Action Dropdown Menu.
- **States:** Loading Table Skeleton, Data Present, Filtered Empty Result, Error State.
- **Permission:** Authenticated Users (`READ_INSTITUTION_LIST`).
- **Related Requirement:** [REQ-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-004: Detail Profil Instansi (Institution Detail & Profile)
- **Page ID:** PAGE-004
- **Page Name:** Profil Detail & Legalitas Instansi
- **Actor:** All Users, Operator Instansi (USER)
- **Purpose:** Menampilkan rincian profil identitas, kontak, logo, visi-misi, dan dokumen regulasi pembentukan instansi.
- **Main Information:** Header instansi (Nama, Akronim, Logo, Status, Tipe), Kartu Kontak & Alamat Kantor, Link Website Resmi, Visi & Misi, Daftar Regulasi Dasar Hukum (PDF preview link), Tab Navigasi Sub-Modul (Profil / Struktur Organisasi / Tugas & Fungsi / Riwayat Pengajuan).
- **Primary Action:** Tombol "Edit Profil Draf" (Khusus Operator Instansi Terkait).
- **Secondary Action:** Mengunduh berkas regulasi PDF, Membuka link website resmi instansi.
- **Required Components:** Institution Header Profile Card, Tab Navigation Bar, Document Attachment List, Metadata Grid, Action Status Banner.
- **States:** Active Live View, Draft Pending Review Banner (jika sedang diajukan).
- **Permission:** `READ_INSTITUTION_DETAIL` (All), `EDIT_INSTITUTION_DRAFT` (Scoped User).
- **Related Requirement:** [REQ-006](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md), [UC-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-005: Manajemen Kabinet & Periode (Cabinet List & Detail)
- **Page ID:** PAGE-005
- **Page Name:** Manajemen Master Kabinet & Periodesasi
- **Actor:** Admin, Pimpinan, All Users
- **Purpose:** Mengelola daftar kabinet kepresidenan dan rentang waktu masa jabatan pemerintahan.
- **Main Information:** Daftar kartu kabinet (Nama Kabinet, Presiden/Wapres, Rentang Tahun Periode, Badge Status Aktif, Jumlah K/L Terdaftar).
- **Primary Action:** Tombol "Buat Kabinet Baru" (Admin), Tombol "Jadikan Kabinet Aktif".
- **Secondary Action:** Menekan kartu kabinet untuk membuka komposisi keanggotaan K/L.
- **Required Components:** Cabinet Grid Cards, Active Cabinet Badge, Modal Form Tambah Kabinet, Date Range Picker.
- **States:** Loaded Grid, Form Modal Open, Success Feedback Toast.
- **Permission:** `READ_CABINETS` (All), `MANAGE_CABINETS` (Admin).
- **Related Requirement:** [REQ-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-006](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md), [UC-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-006: Komposisi Anggota Kabinet (Cabinet Membership Page)
- **Page ID:** PAGE-006
- **Page Name:** Komposisi Keanggotaan Kabinet (fokus: Kabinet Merah Putih)
- **Actor:** Admin, SESDEP, All Users
- **Purpose:** Memvisualisasikan seluruh kementerian/lembaga anggota kabinet terpilih dengan pengelompokan klasifikasi.
- **Main Information:** Header Kabinet (Nama Kabinet, Periode Tahun, Dasar Hukum Pembentukan), Filter Kelompok (Kemenko / Kementerian Teknis / LPNK / LNS), Grid/Tabel 48+ Kementerian Anggota, Informasi Tanggal Bergabung.
- **Primary Action:** Tombol "Tambah Anggota K/L" (Admin).
- **Secondary Action:** Tombol "Keluarkan / Catat Transisi K/L" (Admin), Buka Detail Instansi.
- **Required Components:** Category Tab Filters, Institution Membership Cards/Table, Member Enrollment Drawer/Modal, Reshuffle Transition Log.
- **States:** Data Loaded, Member Added Animation, Confirmation Modal.
- **Permission:** `READ_CABINET_MEMBERSHIP` (All), `MANAGE_CABINET_MEMBERSHIP` (Admin).
- **Related Requirement:** [REQ-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md), [UC-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-007: Komparasi Historis Antar-Kabinet (Historical Cabinet Comparison)
- **Page ID:** PAGE-007
- **Page Name:** Komparasi & Silsilah Kelembagaan Antar-Periode
- **Actor:** Pimpinan / SESDEP, Data Analyst, All Users
- **Purpose:** Membandingkan evolusi susunan kementerian antara dua periode pemerintahan dan melacak pemecahan/penggabungan instansi.
- **Main Information:** Pemilih Dual-Kabinet (Kabinet A vs Kabinet B), Matriks Komparasi Delta (Jumlah Kementerian Baru, Kementerian Dipecah, Kementerian Dimerger, Kementerian Bertransformasi Nomenklatur), Bagan Silsilah Alur (*Lineage Flow Chart*).
- **Primary Action:** Memilih dua kabinet pembanding dan menekan "Bandingkan".
- **Secondary Action:** Filter berdasarkan klaster kementerian, Export ringkasan perbandingan.
- **Required Components:** Dual Cabinet Selector Bar, Delta Metric Highlight Cards, Side-by-Side Comparison Table, Lineage Diagram Node Viewer.
- **States:** Selector State, Comparison Loaded, Interactive Node Click.
- **Permission:** `VIEW_HISTORICAL_ANALYTICS`.
- **Related Requirement:** [REQ-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-008: Visualisasi Struktur Organisasi Interaktif (Interactive Org Chart)
- **Page ID:** PAGE-008
- **Page Name:** Bagan Struktur Organisasi & Hierarki Unit
- **Actor:** All Users, Operator Instansi (USER), Admin
- **Purpose:** Menyajikan pohon struktur bagan organisasi instansi secara visual interaktif dengan kemampuan zoom, pan, dan edit hierarki draf.
- **Main Information:** Pohon bagan hierarki (Node Pimpinan Tertinggi $\rightarrow$ Eselon I $\rightarrow$ Eselon II $\rightarrow$ Unit di bawahnya), Label Nama Unit, Badge Eselon, Jumlah Sub-unit, Indikator Status Draf.
- **Primary Action:** Interaksi Visual (Zoom In/Out, Pan, Collapse/Expand Node Tree).
- **Secondary Action:** Tombol "Tambah Unit Bawah", Tombol "Pindah Atasan (Re-parent)" (Mode Draf), Tombol "Toggle Mode Bagan / Mode Tabel".
- **Required Components:** Canvas Org-Chart Viewer, Zoom/Reset Toolbar, Node Detail Popover, Add/Edit Unit Drawer, Tree Search Filter.
- **States:** Canvas Rendering, Node Selected, Edit Mode Active, Circular Dependency Alert Modal.
- **Permission:** `READ_ORG_STRUCTURE` (All), `EDIT_ORG_DRAFT` (Scoped User).
- **Related Requirement:** [REQ-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-020](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-009: Pengelolaan Tugas & Fungsi (Tupoksi Management)
- **Page ID:** PAGE-009
- **Page Name:** Tugas & Fungsi Kelembagaan
- **Actor:** Operator Instansi (USER), Verifikator, All Users
- **Purpose:** Mendata dan mengaitkan butir tugas pokok dan rincian fungsi pada tingkat instansi maupun unit kerja struktural.
- **Main Information:** Rumusan Tugas Pokok Instansi, Accordion/Daftar Rincian Fungsi per Unit Kerja, Rujukan Pasal/Ayat Regulasi Hukum, Status Sinkronisasi Draf.
- **Primary Action:** Tombol "Tambah / Edit Butir Tugas & Fungsi" (Mode Draf).
- **Secondary Action:** Pencarian kata kunci fungsi, Export butir tupoksi.
- **Required Components:** Duty Statement Card, Structured Function List Table, Legal Citation Input Fields, Unit Work Filter.
- **States:** Read-Only View, Inline Editing Mode, Validation Error State.
- **Permission:** `READ_TUPOKSI` (All), `EDIT_TUPOKSI_DRAFT` (Scoped User).
- **Related Requirement:** [REQ-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-010: Pengajuan Perubahan Data (Submission Workspace)
- **Page ID:** PAGE-010
- **Page Name:** Ruang Kerja Pengajuan (Draft to Submit)
- **Actor:** Operator Instansi (USER)
- **Purpose:** Meninjau seluruh draf perubahan yang telah disusun dan mengirimkannya sebagai tiket pengajuan resmi ke Verifikator KemenPANRB.
- **Main Information:** Ringkasan Perubahan Draf (Profil, Perubahan Unit Kerja, Perubahan Tupoksi), Form Catatan Pengajuan, Area Unggah Dokumen Regulasi (PDF), Riwayat Status Tiket.
- **Primary Action:** Tombol "Kirim Pengajuan Perubahan Data" (*Submit Ticket*).
- **Secondary Action:** Tombol "Batalkan / Reset Draf", Unduh Bukti Draft.
- **Required Components:** Change Summary Checklist, File Upload Zone with Drag-and-Drop, Legal Disclaimer Checkbox, Submission Confirmation Dialog.
- **States:** Draft Incomplete, Ready to Submit, Submitting Loader, Success Feedback Modal.
- **Permission:** `SUBMIT_INSTITUTION_CHANGE` (Scoped User).
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-021](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-011: Antrean & Layar Kerja Verifikasi (Verification Queue & Diff Review)
- **Page ID:** PAGE-011
- **Page Name:** Antrean & Layar Peninjauan Verifikator (Diff Viewer)
- **Actor:** Verifikator KemenPANRB
- **Purpose:** Meninjau antrean tiket pengajuan dan memeriksa komparasi perubahan data sebelum vs sesudah.
- **Main Information:** Daftar Antrean Tiket Pengajuan, Detail Pengaju & Instansi Asal, Panel Preview Dokumen Regulasi PDF, Layar Komparasi Berdampingan (*Side-by-Side Diff View*: Merah untuk data lama yang dihapus, Hijau untuk data baru yang ditambah, Kuning untuk data dimutasi), Panel Keputusan Verifikasi.
- **Primary Action:** Tombol "Verifikasi Lolos" (*Pass to Approval*).
- **Secondary Action:** Tombol "Minta Revisi" (dengan Form Catatan Wajib), Tombol "Tolak Pengajuan".
- **Required Components:** Ticket Queue Data Grid, PDF Embedded Viewer, Side-by-Side Visual Diff Component, Feedback Note Textarea, Decision Action Bar.
- **States:** Queue Selection, Diff Loading, Review Active, Decision Submitted.
- **Permission:** `VERIFY_SUBMISSION` (Verifikator).
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-019](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md), [UC-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-012: Persetujuan Akhir Administrator (Approval Workspace)
- **Page ID:** PAGE-012
- **Page Name:** Persetujuan Akhir & Publikasi Master Data
- **Actor:** Admin
- **Purpose:** Memberikan otorisasi final untuk mempublikasikan data terverifikasi ke Master Data aktif.
- **Main Information:** Daftar Tiket Berstatus `VERIFIED`, Rekomendasi Verifikator, Ringkasan Dampak Mutasi Data terhadap Master Kelembagaan.
- **Primary Action:** Tombol "Setujui & Publikasikan ke Master Data" (*Approve & Publish*).
- **Secondary Action:** Mengembalikan tiket ke verifikator jika ditemukan anomali hukum lanjutan.
- **Required Components:** Pending Approval Table, Executive Summary Modal, Atomic Publish Action Button with 2-Factor / Password Confirmation.
- **States:** Pending List, Publishing In Progress, Publish Success Toast.
- **Permission:** `APPROVE_SUBMISSION` (Admin).
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-016](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-013: Pusat Notifikasi Realtime (Notification Center & Bell Hub)
- **Page ID:** PAGE-013
- **Page Name:** Pusat Notifikasi & Riwayat Aktivitas
- **Actor:** All Users
- **Purpose:** Menampilkan daftar seluruh pemberitahuan aktivitas seketika dan riwayat interaksi data.
- **Main Information:** Pop-up Toast realtime di sudut layar, Dropdown Bell Badge di navbar, Halaman Penuh Pusat Notifikasi (Filter: Belum Dibaca / Semua / Mutasi Data / Status Tiket).
- **Primary Action:** Mengklik baris notifikasi untuk langsung melompat (*deep link*) ke tiket / instansi terkait.
- **Secondary Action:** Tombol "Tandai Semua Sudah Dibaca".
- **Required Components:** Realtime Floating Toast, Navbar Notification Bell Icon with Badge Counter, Notification Drawer / Full List Page.
- **States:** Realtime Incoming Toast Animation, Unread List, Empty Notification State.
- **Permission:** Authenticated Users (`RECEIVE_NOTIFICATIONS`).
- **Related Requirement:** [REQ-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-017](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-014: Penelusur Log Audit (Audit Trail Viewer)
- **Page ID:** PAGE-014
- **Page Name:** Audit Trail & Catatan Aktivitas Sistem
- **Actor:** Admin
- **Purpose:** Menyediakan visibilitas penuh dan investigasi kepatuhan atas seluruh aktivitas perubahan data.
- **Main Information:** Tabel Log Audit (Timestamp presisi ms, Nama Aktor, Role, IP Address, Aksi CREATE/UPDATE/DELETE/APPROVE, Entitas Target, Snapshot JSON Perubahan).
- **Primary Action:** Filter Rentang Tanggal, Pencarian Aktor, dan Filter Tipe Aksi.
- **Secondary Action:** Mengklik baris log untuk membuka modal komparasi JSON Payload sebelum vs sesudah.
- **Required Components:** Audit Log Table, Filter Bar, JSON Syntax Highlighter Modal, Immutable Integrity Badge.
- **States:** Loading Table, Data Loaded, JSON Viewer Open.
- **Permission:** `VIEW_AUDIT_LOGS` (Admin).
- **Related Requirement:** [REQ-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-018](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).

---

### PAGE-015: Analitik Kelembagaan & Postur ASN (Analytics Workspace)
- **Page ID:** PAGE-015
- **Page Name:** Analitik Kelembagaan & Postur ASN
- **Actor:** Data Analyst (Ikhsan), SESDEP, Admin
- **Purpose:** Menyediakan instrumen analisis mendalam mengenai distribusi jabatan eselon, postur aparatur (`v_postur_asn`), dan rasio efisiensi struktur organisasi.
- **Main Information:** Grafik Distribusi Eselon I-IV per Kementerian, Matriks Rekapitulasi Postur ASN, Rasio Unit Struktural vs Fungsional, Fitur Filter Dimensi Analitik.
- **Primary Action:** Mengubah Parameter Dimensi Analisis (Kelompok Kemenko, Jenis Instansi, Wilayah).
- **Secondary Action:** Export Laporan Analitik (Excel Spreadsheet / PDF Summary).
- **Required Components:** Multi-Chart Grid (Bar/Line/Treemap), Dimension Filter Sidebar, ASN Posture Summary Cards, Export Button Bar.
- **States:** Analytics Calculating, Charts Rendered, Exporting State.
- **Permission:** `VIEW_ANALYTICS_REPORTS`.
- **Related Requirement:** [REQ-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [UC-019](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md).
