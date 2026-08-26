# FUNCTIONAL REQUIREMENTS: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Senior Business Analyst & Requirements Engineer  

Dokumen ini mendefinisikan kebutuhan fungsional (*Functional Requirements*) sistem SIGMA-K yang dikelompokkan ke dalam 18 domain bisnis inti.

---

## Matriks Rekapitulasi Kebutuhan Fungsional

| No | Domain Bisnis | Rentang FR-ID | Total FR | Status Dominan |
|---|---|---|:---:|:---:|
| 1 | Institution Management | FR-INST-001 s.d FR-INST-004 | 4 | CONFIRMED |
| 2 | Cabinet Management | FR-CAB-001 s.d FR-CAB-003 | 3 | CONFIRMED |
| 3 | Cabinet Period | FR-PER-001 s.d FR-PER-003 | 3 | CONFIRMED |
| 4 | Institution Membership & History | FR-MEM-001 s.d FR-MEM-004 | 4 | CONFIRMED |
| 5 | Institution Profile | FR-PROF-001 s.d FR-PROF-003 | 3 | CONFIRMED / PROPOSED |
| 6 | Tugas & Fungsi (Tupoksi) | FR-TUP-001 s.d FR-TUP-003 | 3 | CONFIRMED / TBD |
| 7 | Organization Structure | FR-ORG-001 s.d FR-ORG-004 | 4 | CONFIRMED / PROPOSED |
| 8 | Position / Eselon | FR-POS-001 s.d FR-POS-003 | 3 | CONFIRMED |
| 9 | User Management | FR-USR-001 s.d FR-USR-003 | 3 | CONFIRMED |
| 10 | Role & RBAC | FR-RBAC-001 s.d FR-RBAC-003 | 3 | CONFIRMED |
| 11 | Submission | FR-SUB-001 s.d FR-SUB-004 | 4 | CONFIRMED |
| 12 | Verification | FR-VER-001 s.d FR-VER-004 | 4 | CONFIRMED / PROPOSED |
| 13 | Approval | FR-APP-001 s.d FR-APP-003 | 3 | CONFIRMED |
| 14 | Revision | FR-REV-001 s.d FR-REV-003 | 3 | CONFIRMED |
| 15 | Realtime Notification | FR-NOT-001 s.d FR-NOT-004 | 4 | CONFIRMED / TBD |
| 16 | Audit Trail | FR-AUD-001 s.d FR-AUD-003 | 3 | CONFIRMED |
| 17 | Executive Dashboard | FR-DSH-001 s.d FR-DSH-004 | 4 | CONFIRMED |
| 18 | Data Analytics | FR-ANA-001 s.d FR-ANA-004 | 4 | CONFIRMED / TBD |
| **Total** | **18 Domain** | | **62 FR** | |

---

## 1. Domain: Institution Management (Master Instansi)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-INST-001** | Institution Management | Sistem harus mampu menampilkan katalog dan daftar seluruh instansi pemerintah (Kementerian, LPNK, LNS, Pemda Provinsi, Kab/Kota) dengan fitur pencarian teks, filter tipe instansi, dan status keaktifan. | All Users | HIGH | **CONFIRMED** | REQ-005, BR-001 | Mendukung pagination dan search instan. |
| **FR-INST-002** | Institution Management | Sistem harus menyediakan form pembuatan instansi baru dengan atribut kode instansi unik, nama resmi, singkatan/akronim, jenis instansi, wilayah, dan status. | Admin | HIGH | **CONFIRMED** | REQ-005, BR-001 | Menggantikan pembuatan data ad-hoc legacy. |
| **FR-INST-003** | Institution Management | Sistem harus menyediakan operasi pembaruan data dasar instansi (nama, akronim, status keaktifan). | Admin, User (Draft) | HIGH | **CONFIRMED** | REQ-005, BR-007 | User instansi hanya dapat membuat draft pembaruan. |
| **FR-INST-004** | Institution Management | Sistem harus menerapkan mekanisme *soft delete* untuk penonaktifan instansi tanpa menghapus catatan transaksi historis. | Admin | HIGH | **PROPOSED** | REQ-023 | Mencegah kerusakan relasi foreign key pada data kabinet. |

---

## 2. Domain: Cabinet Management (Manajemen Kabinet)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-CAB-001** | Cabinet Management | Sistem harus menyediakan fungsi CRUD Master Kabinet pemerintahan Indonesia (misal: Kabinet Indonesia Maju, Kabinet Merah Putih). | Admin | CRITICAL | **CONFIRMED** | REQ-001, BR-002 | Master kabinet berdiri sebagai first-class entity. |
| **FR-CAB-002** | Cabinet Management | Sistem harus mencatat metadata kabinet mencakup nama kabinet, nama Presiden, nama Wakil Presiden, deskripsi, dan status aktif. | Admin | HIGH | **CONFIRMED** | REQ-001, BR-002 | Informasi ditampilkan pada kartu ringkasan kabinet. |
| **FR-CAB-003** | Cabinet Management | Sistem harus menyediakan mekanisme penetapan satu kabinet aktif utama (*Current Active Cabinet*) yang menjadi konteks default dashboard. | Admin | HIGH | **CONFIRMED** | REQ-001, BR-002 | Hanya boleh ada 1 kabinet berstatus active default pada satu waktu. |

---

## 3. Domain: Cabinet Period (Periode Kabinet)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-PER-001** | Cabinet Period | Sistem harus menyediakan pengelolaan periode masa jabatan kabinet dengan atribut tahun mulai, tahun selesai, dan tanggal penetapan dasar hukum. | Admin | HIGH | **CONFIRMED** | REQ-002, BR-002 | Menjamin validasi batasan waktu temporal. |
| **FR-PER-002** | Cabinet Period | Sistem harus memvalidasi bahwa tahun/tanggal selesai periode tidak boleh lebih kecil daripada tahun/tanggal mulai. | System / Admin | HIGH | **CONFIRMED** | REQ-002, BR-002 | Validasi integritas data pada form periode kabinet. |
| **FR-PER-003** | Cabinet Period | Sistem harus mampu mengelola status periode kabinet (`DRAFT`, `ACTIVE`, `COMPLETED`, `ARCHIVED`). | Admin | MEDIUM | **PROPOSED** | REQ-002 | Mendukung pengarsipan kabinet masa lampau. |

---

## 4. Domain: Institution Membership & Historical Tracking

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-MEM-001** | Institution Membership | Sistem harus menyediakan fitur untuk menambahkan, mengedit, dan mengeluarkan kementerian/lembaga ke dalam keanggotaan kabinet/periode tertentu secara relasional. | Admin | CRITICAL | **CONFIRMED** | REQ-003, BR-003 | Menggantikan format denormalized `list_id_kl`. |
| **FR-MEM-002** | Institution Membership | Sistem harus menampilkan daftar kementerian/lembaga anggota resmi dari kabinet yang sedang dipilih (misal: 48 K/L pada Kabinet Merah Putih). | All Users | CRITICAL | **CONFIRMED** | REQ-003, BR-003 | Halaman utama visualisasi komposisi kabinet. |
| **FR-MEM-003** | Historical Tracking | Sistem harus mencatat dan menampilkan riwayat perubahan status kelembagaan antar-periode (pembentukan kementerian baru, pemecahan instansi, merger instansi, pembubaran). | All Users, Admin | HIGH | **CONFIRMED** | REQ-004, BR-004 | Fitur kunci perbandingan kabinet. |
| **FR-MEM-004** | Historical Tracking | Sistem harus menyediakan visualisasi komparasi komposisi instansi antara dua kabinet berbeda (misal: Kabinet Indonesia Maju vs Kabinet Merah Putih). | All Users, SESDEP | HIGH | **CONFIRMED** | REQ-004, REQ-015 | Komponen penting pada Prototype SESDEP. |

---

## 5. Domain: Institution Profile (Profil Detail Instansi)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-PROF-001** | Institution Profile | Sistem harus menyediakan tampilan detail profil instansi mencakup identitas resmi, alamat kantor, kontak, website, logo, visi, dan misi. | All Users | HIGH | **CONFIRMED** | REQ-006, BR-001 | Halaman Profil Instansi komprehensif. |
| **FR-PROF-002** | Institution Profile | Sistem harus mencatat daftar dokumen regulasi dasar hukum pembentukan instansi (Nomor Perpres/Permen/Perda, Judul, Tahun, Tanggal Pengundangan). | User, Admin | HIGH | **CONFIRMED** | REQ-006, BR-005 | Bukti legalitas instansi. |
| **FR-PROF-003** | Institution Profile | Sistem mendukung pengunggahan (*upload*) berkas digital dasar hukum berformat PDF dengan batas maksimal 10 MB. | User, Admin | MEDIUM | **PROPOSED** | REQ-021 | Memudahkan verifikator memeriksa isi regulasi asli. |

---

## 6. Domain: Tugas & Fungsi (Tupoksi Kelembagaan)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-TUP-001** | Tugas & Fungsi | Sistem harus menyediakan antarmuka pencatatan butir Tugas Pokok dan butir Rincian Fungsi resmi pada tingkat instansi. | User, Admin | HIGH | **CONFIRMED** | REQ-007, BR-005 | Data tupoksi terstruktur per poin/pasal. |
| **FR-TUP-002** | Tugas & Fungsi | Sistem harus mampu mengaitkan butir tugas dan fungsi ke unit kerja spesifik pada bagan organisasi instansi. | User, Admin | HIGH | **CONFIRMED** | REQ-007, BR-005 | Mengetahui tupoksi per Eselon I / Biro / Direktorat. |
| **FR-TUP-003** | Tugas & Fungsi | Sistem menyediakan pencarian teks semantik/kata kunci pada database tupoksi untuk mendeteksi potensi kemiripan/tumpang tindih fungsi antar instansi. | Data Analyst, SESDEP | MEDIUM | **TBD** | REQ-030 | Kebutuhan analisis tingkat lanjut. |

---

## 7. Domain: Organization Structure (Struktur Organisasi)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-ORG-001** | Organization Structure | Sistem harus mengelola unit kerja organisasi instansi dengan model relasi hierarki atasan-bawahan (*parent-child*). | User, Admin | CRITICAL | **CONFIRMED** | REQ-008, BR-006 | Mengadopsi konsep `parent_id` dari `tbl_ref_instansi_org`. |
| **FR-ORG-002** | Organization Structure | Sistem harus menyajikan visualisasi pohon bagan struktur organisasi (*Interactive Org Chart*) yang responsif dan dapat di-zoom/pan. | All Users | CRITICAL | **CONFIRMED** | REQ-008, REQ-015 | Fitur unggulan demonstrasi SESDEP. |
| **FR-ORG-003** | Organization Structure | Sistem harus memiliki mekanisme validasi otomatis untuk menolak relasi melingkar (*circular dependency prevention*) pada pohon hierarki. | System | HIGH | **PROPOSED** | REQ-020 | Menjamin integritas data pohon hierarki. |
| **FR-ORG-004** | Organization Structure | Sistem harus mendukung penambahan, pemindahan (*re-parenting*), dan penonaktifan unit kerja organisasi. | User (Draft), Admin | HIGH | **CONFIRMED** | REQ-008, BR-007 | Perubahan oleh User masuk alur verifikasi. |

---

## 8. Domain: Position / Eselon (Jabatan & Eselon)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-POS-001** | Position / Eselon | Sistem harus menyediakan master data tingkatan jabatan/eselon (Eselon I.a, I.b, II.a, II.b, III, IV, Non-Eselon, Jabatan Fungsional). | Admin | HIGH | **CONFIRMED** | REQ-008, REQ-016 | Berdasarkan data `ref_eselon` legacy. |
| **FR-POS-002** | Position / Eselon | Sistem harus memungkinkan penugasan tingkatan eselon pada setiap unit kerja dalam bagan struktur organisasi. | User, Admin | HIGH | **CONFIRMED** | REQ-008 | Menentukan bobot jabatan unit kerja. |
| **FR-POS-003** | Position / Eselon | Sistem menyediakan pengelompokan rekapitulasi jumlah unit kerja berdasarkan tingkatan eselon pada dashboard. | All Users | MEDIUM | **CONFIRMED** | REQ-012, REQ-013 | Metrik evaluasi perampingan struktur. |

---

## 9. Domain: User Management (Manajemen Pengguna)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-USR-001** | User Management | Sistem harus menyediakan fungsi manajemen akun pengguna (Create, Read, Update, Deactivate, Reset Password). | Admin | HIGH | **CONFIRMED** | REQ-009, REQ-018 | Menggantikan pengelolaan tabel `users` legacy. |
| **FR-USR-002** | User Management | Sistem harus mampu mengikat akun pengguna dengan instansi asalnya (*Institution Scope Binding*). | Admin | CRITICAL | **CONFIRMED** | REQ-009, BR-007 | User hanya berhak mengelola instansinya sendiri. |
| **FR-USR-003** | User Management | Sistem mendukung mekanisme otentikasi login berbasis email/username dan password terenkripsi aman. | All Users | CRITICAL | **CONFIRMED** | REQ-009 | Baseline auth mandiri sebelum integrasi SSO. |

---

## 10. Domain: Role & RBAC (Hak Akses Berbasis Peran)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-RBAC-001** | Role & RBAC | Sistem harus menerapkan otorisasi ketat berbasis peran dengan minimal 3 peran utama: `USER`, `ADMIN`, dan `VERIFIKATOR`. | System | CRITICAL | **CONFIRMED** | REQ-009, BR-007 | Hak akses diproteksi pada level API dan UI. |
| **FR-RBAC-002** | Role & RBAC | Sistem menyediakan peran khusus `PIMPINAN / SESDEP` dengan akses read-only pada seluruh dashboard eksekutif dan modul analitik. | System, Pimpinan | HIGH | **PROPOSED** | REQ-009, REQ-015 | Akun demo/viewer untuk SESDEP. |
| **FR-RBAC-003** | Role & RBAC | Sistem harus memblokir dan menolak setiap permintaan manipulasi data yang tidak sesuai dengan izin role atau scope instansi pengguna (*403 Forbidden*). | System | CRITICAL | **CONFIRMED** | REQ-009 | Keamanan mutlak data antar-kementerian. |

---

## 11. Domain: Submission (Pengajuan Perubahan Data)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-SUB-001** | Submission | Sistem harus menyediakan mekanisme penyimpanan draf (*Draft Mode*) bagi User saat mengedit profil, tupoksi, atau struktur organisasi. | User | HIGH | **CONFIRMED** | REQ-010, BR-007 | Master data aktif tidak berubah sebelum disetujui. |
| **FR-SUB-002** | Submission | Sistem harus menyediakan form pengiriman pengajuan (*Submit Ticket*) dengan catatan penjelasan perubahan dan lampiran dasar hukum. | User | CRITICAL | **CONFIRMED** | REQ-010, BR-007 | Mengubah status draf menjadi `SUBMITTED`. |
| **FR-SUB-003** | Submission | Sistem harus menghasilkan nomor tiket pengajuan unik (*Ticket Number*) untuk setiap berkas pengajuan yang dikirim. | System | HIGH | **CONFIRMED** | REQ-010 | Memudahkan pelacakan disposisi pengajuan. |
| **FR-SUB-004** | Submission | Sistem harus mengunci draf data yang telah berstatus `SUBMITTED` agar tidak dapat diubah oleh User selama proses peninjauan berlangsung. | System | HIGH | **CONFIRMED** | REQ-010, BR-007 | Mencegah *race condition* saat verifikator memeriksa. |

---

## 12. Domain: Verification (Verifikasi Data)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-VER-001** | Verification | Sistem harus menyediakan antarmuka daftar antrean tiket pengajuan (*Verification Queue*) bagi Verifikator. | Verifikator | CRITICAL | **CONFIRMED** | REQ-010, BR-007 | Menampilkan tiket masuk dengan filter status. |
| **FR-VER-002** | Verification | Sistem harus menyediakan layar perbandingan data (*Diff Viewer*) yang memperlihatkan perbedaan data eksisting vs data usulan perubahan secara berdampingan. | Verifikator | HIGH | **PROPOSED** | REQ-019, REQ-010 | Mempercepat verifikasi perubahan struktur. |
| **FR-VER-003** | Verification | Sistem harus memungkinkan Verifikator memberikan keputusan verifikasi: Menyetujui (*Pass to Admin*), Meminta Revisi (*Request Revision*), atau Menolak (*Reject*). | Verifikator | CRITICAL | **CONFIRMED** | REQ-010, BR-007 | Disertai form catatan verifikator wajib. |
| **FR-VER-004** | Verification | Sistem mendukung pembagian antrean verifikasi berdasarkan kategori instansi (Tim K/L vs Tim Pemda). | Verifikator, Admin | MEDIUM | **TBD** | REQ-028 | Menunggu konfirmasi SOP KemenPANRB. |

---

## 13. Domain: Approval (Persetujuan Akhir Data)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-APP-001** | Approval | Sistem harus menyediakan daftar pengajuan yang telah lolos tahap verifikasi untuk ditinjau oleh Admin/Pimpinan. | Admin | CRITICAL | **CONFIRMED** | REQ-010, BR-007 | Tahap finalisasi perubahan ke master data. |
| **FR-APP-002** | Approval | Sistem harus melakukan pengubahan data master secara otomatis dan atomik (*atomic transaction*) seketika saat Admin menekan tombol Approve. | System, Admin | CRITICAL | **CONFIRMED** | REQ-010, BR-007 | Data live langsung ter-update dengan versi baru. |
| **FR-APP-003** | Approval | Sistem mencatat timestamp, user ID pemberi persetujuan, dan nomor SK pengesahan saat proses approval selesai. | System | HIGH | **CONFIRMED** | REQ-010, REQ-014 | Akuntabilitas legal formal. |

---

## 14. Domain: Revision (Revisi Pengajuan)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-REV-001** | Revision | Sistem harus mengubah status pengajuan menjadi `REVISION_REQUIRED` dan membuka kembali akses edit bagi User saat verifikator meminta revisi. | System | HIGH | **CONFIRMED** | REQ-010, BR-007 | User dapat memperbaiki field yang keliru. |
| **FR-REV-002** | Revision | Sistem harus menampilkan rincian catatan revisi dari verifikator secara jelas pada layar kerja pengajuan User. | User | HIGH | **CONFIRMED** | REQ-010 | Memastikan user memahami apa yang harus dikoreksi. |
| **FR-REV-003** | Revision | Sistem harus menyediakan fitur pengiriman ulang (*Resubmit*) setelah user selesai melakukan perbaikan. | User | HIGH | **CONFIRMED** | REQ-010 | Status berubah menjadi `RESUBMITTED`. |

---

## 15. Domain: Realtime Notification (Notifikasi Realtime)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-NOT-001** | Realtime Notification | Sistem harus memicu dan menyiarkan notifikasi seketika (*realtime event*) ketika terjadi mutasi data penting: Create, Update, Delete, Submit, Verify, Request Revision, Approve, Reject. | System | HIGH | **CONFIRMED** | REQ-011, BR-008 | Komponen realtime wajib tanpa refresh browser. |
| **FR-NOT-002** | Realtime Notification | Sistem harus menampilkan pop-up toast notifikasi realtime di pojok layar pengguna yang sedang aktif. | All Users | HIGH | **CONFIRMED** | REQ-011 | Feedback visual instan. |
| **FR-NOT-003** | Realtime Notification | Sistem harus menyediakan Pusat Notifikasi (*Notification Center / Bell Badge*) dengan daftar riwayat notifikasi yang belum dan sudah dibaca. | All Users | HIGH | **CONFIRMED** | REQ-011 | User dapat mengecek notifikasi terdahulu. |
| **FR-NOT-004** | Realtime Notification | Sistem mendukung pengiriman notifikasi melalui saluran eksternal (Email / WhatsApp Gateway). | System | LOW | **TBD** | REQ-027, BR-015 | Tergantung ketersediaan infrastruktur mail/WA. |

---

## 16. Domain: Audit Trail (Pencatatan Jejak Audit)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-AUD-001** | Audit Trail | Sistem secara otomatis merekam seluruh aktivitas mutasi data ke dalam log audit permanen (*who, when, what entity, action type, IP address, user agent, old values JSON, new values JSON*). | System | HIGH | **CONFIRMED** | REQ-014, BR-009 | Mencegah manipulasi data tanpa jejak. |
| **FR-AUD-002** | Audit Trail | Sistem menyediakan antarmuka penelusuran log audit (*Audit Log Viewer*) dengan filter berdasarkan aktor, rentang tanggal, jenis aksi, dan entitas. | Admin | HIGH | **CONFIRMED** | REQ-014 | Keperluan audit investigasi. |
| **FR-AUD-003** | Audit Trail | Log audit bersifat *immutable* (hanya bisa dibaca dan ditambahkan/append-only, tidak dapat diedit atau dihapus oleh siapapun). | System | CRITICAL | **CONFIRMED** | REQ-014, BR-009 | Standar integritas keamanan informasi. |

---

## 17. Domain: Executive Dashboard (Dashboard Eksekutif)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-DSH-001** | Executive Dashboard | Sistem harus menyediakan dashboard eksekutif modern dengan kartu statistik (Total K/L, Total Pemda, Komposisi Kabinet Aktif, Tiket Antrean Verifikasi). | SESDEP, Admin, All | CRITICAL | **CONFIRMED** | REQ-012, BR-010 | Halaman utama saat pimpinan membuka SIGMA-K. |
| **FR-DSH-002** | Executive Dashboard | Sistem menampilkan ringkasan visual keanggotaan Kabinet Merah Putih (distribusi kementerian koordinator vs kementerian teknis vs lembaga). | SESDEP, All | HIGH | **CONFIRMED** | REQ-012, REQ-015 | Demonstrasi ke pimpinan. |
| **FR-DSH-003** | Executive Dashboard | Sistem menampilkan grafik sebaran instansi berdasarkan jenis instansi dan wilayah geografis. | SESDEP, All | HIGH | **CONFIRMED** | REQ-012 | Visualisasi persebaran data nasional. |
| **FR-DSH-004** | Executive Dashboard | Sistem menampilkan feed aktivitas terkini (*Recent Activity Feed*) dari notifikasi mutasi data kelembagaan secara realtime. | All Users | HIGH | **CONFIRMED** | REQ-011, REQ-012 | Memberikan kesan aplikasi hidup dan dinamis. |

---

## 18. Domain: Data Analytics (Analisis Data Kelembagaan)

| FR-ID | Domain | Requirement Description | Actor | Priority | Status | Source | Notes |
|---|---|---|---|---|---|---|---|
| **FR-ANA-001** | Data Analytics | Sistem harus menyediakan modul analitik untuk memvisualisasikan data agregat postur ASN (`v_postur_asn`) per instansi dan per tingkatan eselon. | Data Analyst, SESDEP | HIGH | **CONFIRMED** | REQ-013, BR-010 | Kolaborasi bersama Data Analyst Ikhsan. |
| **FR-ANA-002** | Data Analytics | Sistem harus menyediakan perbandingan matriks statistik struktur kelembagaan antar-kabinet (misal: jumlah kementerian, jumlah unit eselon I). | Data Analyst, SESDEP | HIGH | **CONFIRMED** | REQ-013, REQ-004 | Mendukung perumusan kebijakan penataan birokrasi. |
| **FR-ANA-003** | Data Analytics | Sistem menyediakan fitur ekspor data analitik dan tabel rekapitulasi ke format Excel dan PDF. | Data Analyst, Admin | MEDIUM | **PROPOSED** | REQ-022 | Kebutuhan laporan cetak untuk pimpinan. |
| **FR-ANA-004** | Data Analytics | Sistem menyediakan analisis korelasi antara alokasi tugas fungsi dengan jumlah unit kerja pada instansi. | Data Analyst, SESDEP | MEDIUM | **TBD** | REQ-030 | Kebutuhan kajian lanjutan data analyst. |
