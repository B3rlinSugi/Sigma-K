# USE CASES: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Senior Software Architect & Requirements Engineer  

Dokumen ini mendefinisikan skenario interaksi fungsional sistem (*Use Case Specifications*) secara terstruktur dan formal.

---

## Matriks Daftar Use Case

| UC-ID | Nama Use Case | Aktor Utama | Status | Kategori |
|---|---|---|---|---|
| **UC-001** | User Authentication & Login | All Users | **CONFIRMED** | Security / Access |
| **UC-002** | View Executive Dashboard | Pimpinan, Admin, All | **CONFIRMED** | Executive / Insight |
| **UC-003** | View Institution Catalog & Detail | All Users | **CONFIRMED** | Master Data |
| **UC-004** | Create Master Institution | Admin | **CONFIRMED** | Master Data |
| **UC-005** | Update Institution Profile Draft | Operator (User) | **CONFIRMED** | Data Governance |
| **UC-006** | Manage Cabinet Master | Admin | **CONFIRMED** | Cabinet Management |
| **UC-007** | Manage Cabinet Period | Admin | **CONFIRMED** | Cabinet Management |
| **UC-008** | Add Institution to Cabinet Membership | Admin | **CONFIRMED** | Cabinet Management |
| **UC-009** | Remove Institution from Cabinet | Admin | **CONFIRMED** | Cabinet Management |
| **UC-010** | View Historical Cabinet & Lineage | Pimpinan, All | **CONFIRMED** | Historical Analysis |
| **UC-011** | Manage Tugas & Fungsi (Tupoksi) | Operator (User), Admin | **CONFIRMED** | Duty & Function |
| **UC-012** | Manage Organization Structure Tree | Operator (User), Admin | **CONFIRMED** | Org Structure |
| **UC-013** | Submit Change Ticket (Draft-to-Submit) | Operator (User) | **CONFIRMED** | Workflow |
| **UC-014** | Verify Submission & Review Diff | Verifikator | **CONFIRMED** | Workflow |
| **UC-015** | Request Submission Revision | Verifikator | **CONFIRMED** | Workflow |
| **UC-016** | Approve Data & Publish to Master | Admin | **CONFIRMED** | Workflow |
| **UC-017** | Receive Realtime Notification | All Users | **CONFIRMED** | Realtime Event |
| **UC-018** | View Audit Trail & Activity Log | Admin | **CONFIRMED** | Governance & Audit |
| **UC-019** | Executive Posture Monitoring | Pimpinan, Data Analyst | **CONFIRMED** | Executive Analytics |

---

## Rincian Spesifikasi Use Case

### UC-001: User Authentication & Login
- **UC-ID:** UC-001
- **Name:** User Authentication & Login
- **Actor:** All Users (USER, VERIFIKATOR, ADMIN, PIMPINAN)
- **Goal:** Masuk ke dalam sistem SIGMA-K dengan hak akses yang terverifikasi.
- **Precondition:** Pengguna telah memiliki akun aktif di sistem.
- **Trigger:** Pengguna mengakses halaman login dan menginput kredensial.
- **Main Flow:**
  1. Pengguna memasukkan username/email dan password pada form login.
  2. Pengguna menekan tombol "Masuk".
  3. Sistem memvalidasi kredensial pengguna terhadap database otentikasi.
  4. Sistem menghasilkan sesi otentikasi/token aman dan mengambil data role serta scope instansi pengguna.
  5. Sistem mengarahkan pengguna ke halaman Dashboard sesuai perannya.
- **Alternative Flow:**
  - *3a. Integrasi SSO KemenPANRB [PROPOSED TBD]:* Pengguna menekan tombol "Login SSO", sistem mengalihkan ke Identity Provider kementerian, dan menerima token klaim pengguna.
- **Exception Flow:**
  - *3b. Kredensial Tidak Valid:* Sistem menampilkan pesan error "Kombinasi email/username dan kata sandi salah". Sesi login tidak dibuat.
  - *3c. Akun Nonaktif:* Sistem menolak login dan menampilkan pesan "Akun Anda sedang dinonaktifkan. Hubungi Administrator".
- **Postcondition:** Pengguna berhasil masuk dan memperoleh sesi aktif sesuai role & scope.
- **Business Rules:** [BRULE-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md), [BRULE-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-USR-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [NFR-SEC-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/NON_FUNCTIONAL_REQUIREMENTS.md)

---

### UC-002: View Executive Dashboard
- **UC-ID:** UC-002
- **Name:** View Executive Dashboard
- **Actor:** Pimpinan / SESDEP, Admin, All Users
- **Goal:** Memantau ringkasan metrik kelembagaan nasional, komposisi kabinet aktif, dan status pengajuan.
- **Precondition:** Pengguna telah berhasil login.
- **Trigger:** Pengguna membuka menu Dashboard.
- **Main Flow:**
  1. Pengguna mengakses menu Dashboard.
  2. Sistem memuat kartu ringkasan metrik (Total K/L, Total Pemda, Jumlah Instansi Kabinet Aktif).
  3. Sistem memuat grafik sebaran jenis instansi dan wilayah geografis.
  4. Sistem memuat feed aktivitas terbaru (*Realtime Activity Feed*).
  5. Pengguna melihat dan berinteraksi dengan widget visual dashboard.
- **Alternative Flow:**
  - *2a. Pengguna mengubah filter kabinet aktif:* Sistem memperbarui metrik secara dinamis sesuai kabinet yang dipilih.
- **Exception Flow:**
  - *2b. Gagal memuat data agregat:* Sistem menampilkan placeholder status dengan tombol "Coba Lagi" (*Retry*).
- **Postcondition:** Dashboard ter-render sempurna dengan data mutakhir.
- **Business Rules:** [BRULE-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-DSH-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-003: View Institution Catalog & Detail
- **UC-ID:** UC-003
- **Name:** View Institution Catalog & Detail
- **Actor:** All Users
- **Goal:** Menemukan dan melihat informasi profil lengkap, struktur, dan tupoksi suatu instansi.
- **Precondition:** Pengguna berada di dalam sistem.
- **Trigger:** Pengguna memilih menu "Daftar Instansi" atau mencari nama instansi.
- **Main Flow:**
  1. Pengguna memasukkan kata kunci pencarian atau memilih filter kategori instansi.
  2. Sistem menampilkan tabel/katalog instansi yang cocok.
  3. Pengguna mengklik salah satu baris instansi.
  4. Sistem menampilkan halaman Detail Instansi (Informasi Umum, Dasar Hukum, Tab Struktur Organisasi, Tab Tugas & Fungsi).
- **Alternative Flow:**
  - *3a. Pengguna mengekspor data:* Pengguna menekan tombol "Export PDF/Excel" untuk mengunduh profil instansi.
- **Exception Flow:**
  - *2a. Data tidak ditemukan:* Sistem menampilkan informasi "Tidak ada instansi yang sesuai dengan kriteria pencarian".
- **Postcondition:** Informasi detail profil instansi tersaji lengkap.
- **Business Rules:** [BRULE-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-006](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-INST-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-004: Create Master Institution
- **UC-ID:** UC-004
- **Name:** Create Master Institution
- **Actor:** Admin
- **Goal:** Mendaftarkan instansi baru ke dalam Master Data Nasional.
- **Precondition:** Admin memiliki hak akses `MANAGE_MASTER_REFERENCES`.
- **Trigger:** Admin mengklik tombol "Tambah Instansi Baru".
- **Main Flow:**
  1. Admin mengisi kode instansi unik, nama instansi resmi, singkatan, jenis instansi, wilayah, dan status.
  2. Admin menekan tombol "Simpan".
  3. Sistem memvalidasi keunikan kode dan kelengkapan field wajib.
  4. Sistem menyimpan instansi baru ke database master dan mencatat log audit.
  5. Sistem memicu event notifikasi realtime ke seluruh pengguna.
- **Alternative Flow:** -
- **Exception Flow:**
  - *3a. Kode Instansi Duplikat:* Sistem menolak dan menampilkan peringatan "Kode instansi sudah digunakan".
- **Postcondition:** Instansi baru terdaftar aktif di master data.
- **Business Rules:** [BRULE-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md), [BRULE-006](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-INST-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)

---

### UC-005: Update Institution Profile Draft
- **UC-ID:** UC-005
- **Name:** Update Institution Profile Draft
- **Actor:** Operator Instansi (USER)
- **Goal:** Mengedit informasi kontak, alamat, logo, atau visi misi dalam mode draf.
- **Precondition:** Pengguna terautentikasi dan terikat (*scoped*) dengan instansi tersebut.
- **Trigger:** Pengguna mengklik tombol "Edit Profil".
- **Main Flow:**
  1. Pengguna mengubah data profil pada form editor.
  2. Pengguna mengklik "Simpan Draf".
  3. Sistem menyimpan perubahan pada tabel draf kerja instansi.
  4. Sistem memberikan feedback "Draf profil berhasil disimpan".
- **Alternative Flow:** -
- **Exception Flow:**
  - *1a. Tiket sedang ditinjau (`SUBMITTED`):* Sistem mengunci form dan menampilkan pesan "Data sedang dalam proses verifikasi dan tidak dapat diedit".
- **Postcondition:** Draf data tersimpan tanpa mengubah master data live.
- **Business Rules:** [BRULE-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-006](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-PROF-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-SUB-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)

---

### UC-006: Manage Cabinet Master
- **UC-ID:** UC-006
- **Name:** Manage Cabinet Master
- **Actor:** Admin
- **Goal:** Membuat atau memperbarui data entitas kabinet pemerintahan (misal: Kabinet Merah Putih).
- **Precondition:** Admin memiliki hak akses `MANAGE_CABINETS`.
- **Trigger:** Admin mengakses menu "Manajemen Kabinet".
- **Main Flow:**
  1. Admin menginput nama kabinet, nama Presiden, nama Wakil Presiden, deskripsi, dan status.
  2. Admin menekan tombol "Simpan Kabinet".
  3. Sistem memvalidasi dan menyimpan data kabinet.
  4. Sistem mencatat audit log mutasi kabinet.
- **Alternative Flow:** -
- **Exception Flow:**
  - *2a. Nama kabinet kosong:* Sistem memberikan validasi error field wajib.
- **Postcondition:** Data master kabinet tersimpan di sistem.
- **Business Rules:** [BRULE-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-CAB-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-007: Manage Cabinet Period
- **UC-ID:** UC-007
- **Name:** Manage Cabinet Period
- **Actor:** Admin
- **Goal:** Menentukan periode waktu berlakunya kabinet (tahun mulai dan selesai).
- **Precondition:** Master kabinet telah terdaftar.
- **Trigger:** Admin membuka tab "Periode" pada kabinet terpilih.
- **Main Flow:**
  1. Admin memasukkan tahun/tanggal mulai dan tahun/tanggal selesai.
  2. Admin memasukkan nomor dasar hukum penetapan kabinet (Keppres/Perpres).
  3. Admin menetapkan status periode (`ACTIVE`/`ARCHIVED`).
  4. Admin menekan tombol "Simpan Periode".
  5. Sistem memvalidasi rentang temporal dan menyimpan periode kabinet.
- **Alternative Flow:** -
- **Exception Flow:**
  - *4a. Tahun selesai < Tahun mulai:* Sistem menolak penyimpanan dengan pesan "Tahun selesai tidak valid".
- **Postcondition:** Periode kabinet terdaftar dan tervalidasi secara temporal.
- **Business Rules:** [BRULE-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-PER-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)

---

### UC-008: Add Institution to Cabinet Membership
- **UC-ID:** UC-008
- **Name:** Add Institution to Cabinet Membership
- **Actor:** Admin
- **Goal:** Memasukkan kementerian/lembaga ke dalam daftar anggota kabinet periode tertentu.
- **Precondition:** Kabinet periode aktif dan Master Instansi telah tersedia.
- **Trigger:** Admin mengklik tombol "Tambah Anggota K/L" pada layar detail kabinet.
- **Main Flow:**
  1. Admin memilih kementerian/lembaga dari katalog instansi.
  2. Admin menetapkan tanggal bergabung dan kategori kementerian (Kemenko / Kementerian Teknis / LPNK).
  3. Admin menekan tombol "Tambahkan ke Kabinet".
  4. Sistem membentuk relasi relasional `CabinetMembership` baru.
  5. Sistem memicu notifikasi realtime dan mencatat audit trail.
- **Alternative Flow:** -
- **Exception Flow:**
  - *3a. Instansi sudah terdaftar di kabinet periode yang sama:* Sistem menampilkan peringatan "Instansi sudah menjadi anggota kabinet ini".
- **Postcondition:** Instansi resmi tercatat sebagai anggota kabinet aktif.
- **Business Rules:** [BRULE-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-MEM-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-009: Remove Institution from Cabinet
- **UC-ID:** UC-009
- **Name:** Remove Institution from Cabinet
- **Actor:** Admin
- **Goal:** Mengakhiri keanggotaan kementerian/lembaga dari kabinet tertentu (misal karena pembubaran kementerian).
- **Precondition:** Instansi telah menjadi anggota kabinet.
- **Trigger:** Admin memilih instansi dan menekan "Keluarkan dari Kabinet".
- **Main Flow:**
  1. Admin memasukkan tanggal berakhir dan dasar hukum perubahan kelembagaan.
  2. Admin mengonfirmasi aksi pelepasan keanggotaan.
  3. Sistem memperbarui status membership menjadi *ended* (soft update) dan mencatat riwayat transisi.
  4. Sistem mencatat log audit dan memicu realtime notifikasi.
- **Alternative Flow:** -
- **Exception Flow:** -
- **Postcondition:** Keanggotaan instansi berakhir pada periode kabinet tersebut dengan riwayat tercatat.
- **Business Rules:** [BRULE-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md), [BRULE-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-MEM-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)

---

### UC-010: View Historical Cabinet & Lineage
- **UC-ID:** UC-010
- **Name:** View Historical Cabinet & Lineage
- **Actor:** Pimpinan / SESDEP, All Users
- **Goal:** Menelusuri sejarah perubahan komposisi kementerian dari era ke era kabinet.
- **Precondition:** Terdapat lebih dari 1 periode kabinet di sistem.
- **Trigger:** Pengguna membuka menu "Histori Kelembagaan / Komparasi Kabinet".
- **Main Flow:**
  1. Pengguna memilih dua kabinet pembanding (misal: Kabinet Indonesia Maju vs Kabinet Merah Putih).
  2. Sistem menganalisis perbedaan data keanggotaan.
  3. Sistem menampilkan visualisasi matriks perubahan (Instansi Baru Terbentuk, Instansi Terpecah, Instansi Bergabung, Instansi Berubah Nomenklatur).
  4. Pengguna menelusuri detail silsilah kelembagaan (*lineage*).
- **Alternative Flow:** -
- **Exception Flow:** -
- **Postcondition:** Matriks komparasi historis tersaji interaktif.
- **Business Rules:** [BRULE-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-MEM-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-011: Manage Tugas & Fungsi
- **UC-ID:** UC-011
- **Name:** Manage Tugas & Fungsi
- **Actor:** Operator Instansi (USER), Admin
- **Goal:** Menginput, mengedit butir tugas pokok dan fungsi resmi per unit kerja.
- **Precondition:** Unit kerja organisasi telah terdaftar di draf instansi.
- **Trigger:** Pengguna membuka tab "Tugas & Fungsi" pada profil instansi.
- **Main Flow:**
  1. Pengguna memilih unit organisasi target.
  2. Pengguna menginput rumusan Tugas Pokok.
  3. Pengguna menambahkan butir-butir rincian Fungsi (nomor urut, teks fungsi, pasal rujukan).
  4. Pengguna menekan tombol "Simpan Tupoksi".
  5. Sistem menyimpan draf tupoksi dan mengaitkannya ke unit organisasi terkait.
- **Alternative Flow:** -
- **Exception Flow:** -
- **Postcondition:** Data tugas dan fungsi terstruktur tersimpan dalam draf instansi.
- **Business Rules:** [BRULE-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-TUP-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-012: Manage Organization Structure Tree
- **UC-ID:** UC-012
- **Name:** Manage Organization Structure Tree
- **Actor:** Operator Instansi (USER), Admin
- **Goal:** Membangun bagan hierarki unit kerja organisasi instansi.
- **Precondition:** Draf instansi dalam status terbuka (*editable*).
- **Trigger:** Pengguna membuka tab "Struktur Organisasi" dan mengklik "Tambah Unit Kerja".
- **Main Flow:**
  1. Pengguna memasukkan nama unit kerja, kode unit, memilih unit atasan (*parent unit*), dan memilih tingkatan eselon.
  2. Pengguna menekan tombol "Simpan Unit".
  3. Sistem memvalidasi bahwa penambahan unit tidak menciptakan relasi melingkar (*anti-circular check*).
  4. Sistem menambahkan node unit ke pohon bagan organisasi.
  5. Antarmuka memperbarui visual bagan organisasi secara interaktif.
- **Alternative Flow:**
  - *1a. Pengguna memindahkan unit kerja (re-parenting):* Pengguna mengubah parent unit dari unit kerja yang sudah ada.
- **Exception Flow:**
  - *3a. Terdeteksi Circular Dependency:* Sistem menolak perubahan dan menampilkan peringatan "Unit kerja tidak boleh menjadi atasan bagi unit atasannya sendiri".
- **Postcondition:** Struktur pohon organisasi terbarui dengan integritas valid.
- **Business Rules:** [BRULE-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-020](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-ORG-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-013: Submit Change Ticket (Draft-to-Submit)
- **UC-ID:** UC-013
- **Name:** Submit Change Ticket
- **Actor:** Operator Instansi (USER)
- **Goal:** Mengirimkan berkas pengajuan perubahan data ke tim verifikator KemenPANRB.
- **Precondition:** Draf perubahan data telah diisi lengkap.
- **Trigger:** Pengguna menekan tombol "Ajukan Perubahan Data".
- **Main Flow:**
  1. Pengguna mengisi catatan pengajuan dan melampirkan berkas regulasi dasar hukum (PDF).
  2. Pengguna mengonfirmasi pengiriman.
  3. Sistem membuat tiket submission baru dengan nomor unik (`TKT-YYYYMMDD-XXXX`).
  4. Sistem mengubah status draf menjadi `SUBMITTED` dan mengunci draf dari pengeditan.
  5. Sistem memicu notifikasi realtime kepada seluruh Verifikator dan mencatat audit trail.
- **Alternative Flow:** -
- **Exception Flow:**
  - *1a. Berkas dasar hukum belum dilampirkan:* Sistem meminta pengguna melampirkan dasar hukum sebelum dapat mengirim.
- **Postcondition:** Tiket masuk ke antrean verifikasi dan data terkunci.
- **Business Rules:** [BRULE-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-SUB-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-014: Verify Submission & Review Diff
- **UC-ID:** UC-014
- **Name:** Verify Submission & Review Diff
- **Actor:** Verifikator
- **Goal:** Meneliti keabsahan berkas pengajuan dan membandingkan perubahan data.
- **Precondition:** Tiket dalam status `SUBMITTED` atau `RESUBMITTED`.
- **Trigger:** Verifikator membuka tiket dari daftar antrean verifikasi.
- **Main Flow:**
  1. Verifikator membuka detail tiket pengajuan.
  2. Sistem menampilkan ringkasan pengajuan, preview berkas dasar hukum, dan layar perbandingan (*Diff Viewer*).
  3. Verifikator meneliti perubahan bagan organisasi, profil, atau tupoksi.
  4. Verifikator memilih keputusan "Verifikasi Lolos" (*Pass to Approval*).
  5. Sistem mengubah status tiket menjadi `VERIFIED` dan meneruskan tiket ke antrean Admin.
  6. Sistem memicu event notifikasi realtime dan mencatat log verifikasi.
- **Alternative Flow:**
  - *4a. Data memerlukan perbaikan:* Verifikator memilih "Minta Revisi" (menuju UC-015).
  - *4b. Data tidak sah/ditolak:* Verifikator memilih "Tolak Pengajuan" (`REJECTED`).
- **Exception Flow:** -
- **Postcondition:** Tiket berstatus `VERIFIED` dan siap diproses persetujuan akhir.
- **Business Rules:** [BRULE-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-019](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-VER-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-006](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-015: Request Submission Revision
- **UC-ID:** UC-015
- **Name:** Request Submission Revision
- **Actor:** Verifikator
- **Goal:** Mengembalikan pengajuan ke Operator Instansi dengan catatan koreksi.
- **Precondition:** Tiket sedang dalam proses peninjauan oleh verifikator.
- **Trigger:** Verifikator mengklik tombol "Minta Revisi".
- **Main Flow:**
  1. Verifikator menuliskan butir-butir catatan perbaikan secara spesifik pada form feedback.
  2. Verifikator menekan tombol "Kirim Catatan Revisi".
  3. Sistem mengubah status tiket menjadi `REVISION_REQUIRED`.
  4. Sistem membuka kembali kunci edit pada draf kerja Operator Instansi terkait.
  5. Sistem memicu notifikasi realtime kepada Operator Instansi pengaju dan mencatat log audit.
- **Alternative Flow:** -
- **Exception Flow:**
  - *1a. Catatan perbaikan kosong:* Sistem mewajibkan verifikator mengisi catatan sebelum mengirim revisi.
- **Postcondition:** Pengajuan berstatus `REVISION_REQUIRED` dan dapat diedit kembali oleh User.
- **Business Rules:** [BRULE-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md), [BRULE-016](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-REV-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-016: Approve Data & Publish to Master
- **UC-ID:** UC-016
- **Name:** Approve Data & Publish to Master
- **Actor:** Admin
- **Goal:** Menyetujui pengajuan yang telah terverifikasi sehingga data resmi masuk ke Master Data aktif.
- **Precondition:** Tiket dalam status `VERIFIED`.
- **Trigger:** Admin meninjau tiket dan menekan tombol "Setujui & Publikasikan" (*Approve*).
- **Main Flow:**
  1. Admin memeriksa rekomendasi verifikasi dari Verifikator.
  2. Admin menekan tombol "Approve Data".
  3. Sistem secara atomik (*atomic transaction*) menerapkan seluruh perubahan draf ke tabel Master Data aktif.
  4. Sistem menandai status tiket menjadi `APPROVED` dan status draf menjadi `PUBLISHED`.
  5. Sistem mencatat log audit permanen dengan detail user pengesah.
  6. Sistem menyiarkan notifikasi realtime keberhasilan publikasi kepada seluruh pihak terkait.
- **Alternative Flow:** -
- **Exception Flow:**
  - *3a. Terjadi kesalahan transaksi database:* Sistem melakukan *rollback* penuh dan mengembalikan error tanpa merusak master data.
- **Postcondition:** Master data aktif resmi terbarui dengan data terkini.
- **Business Rules:** [BRULE-017](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-APP-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-APP-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-017: Receive Realtime Notification
- **UC-ID:** UC-017
- **Name:** Receive Realtime Notification
- **Actor:** All Users
- **Goal:** Menerima pemberitahuan seketika saat terjadi mutasi data penting tanpa menyegarkan halaman browser.
- **Precondition:** Pengguna sedang aktif membuka aplikasi SIGMA-K.
- **Trigger:** Server memancarkan event mutasi data (Create/Update/Delete/Submit/Verify/Approve).
- **Main Flow:**
  1. Pipeline event server menyiarkan payload notifikasi ke kanal pengguna target.
  2. Antarmuka client menerima event dan menampilkan pop-up Toast notifikasi.
  3. Angka indikator badge lonceng notifikasi bertambah secara otomatis (+1).
  4. Pengguna dapat mengklik notifikasi untuk langsung menuju ke halaman/tiket terkait.
- **Alternative Flow:** -
- **Exception Flow:**
  - *1a. Koneksi jaringan terputus:* Client secara otomatis mencoba menyambung ulang (*reconnect*) dan menyinkronkan notifikasi tertunda saat koneksi pulih.
- **Postcondition:** Pengguna terinformasi seketika atas aktivitas sistem.
- **Business Rules:** [BRULE-018](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-NOT-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-016](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-018: View Audit Trail & Activity Log
- **UC-ID:** UC-018
- **Name:** View Audit Trail & Activity Log
- **Actor:** Admin
- **Goal:** Memeriksa riwayat jejak audit aktivitas pengguna dan perubahan data.
- **Precondition:** Admin memiliki hak akses `VIEW_AUDIT_LOGS`.
- **Trigger:** Admin mengakses menu "Audit Trail".
- **Main Flow:**
  1. Admin membuka halaman Audit Trail.
  2. Admin memfilter riwayat berdasarkan nama aktor, tanggal, jenis aksi (CREATE/UPDATE/DELETE), atau nama instansi.
  3. Sistem menampilkan daftar log audit dengan detail timestamp milidetik dan IP address.
  4. Admin mengklik salah satu baris log untuk melihat payload JSON perbedaan nilai sebelum vs sesudah.
- **Alternative Flow:** -
- **Exception Flow:** -
- **Postcondition:** Riwayat audit tersaji transparan dan tidak dapat diubah.
- **Business Rules:** [BRULE-019](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-AUD-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)

---

### UC-019: Executive Posture Monitoring
- **UC-ID:** UC-019
- **Name:** Executive Posture Monitoring
- **Actor:** Pimpinan / SESDEP, Data Analyst
- **Goal:** Meninjau postur agregasi kelembagaan ASN, sebaran jabatan eselon, dan tren penataan struktur organisasi.
- **Precondition:** Data agregat analitik telah diproses.
- **Trigger:** Pengguna membuka menu "Analitik & Postur Kelembagaan".
- **Main Flow:**
  1. Pengguna memilih instansi atau filter kelompok kementerian.
  2. Sistem menyajikan grafik postur ASN (`v_postur_asn`), matriks perbandingan eselon, dan rasio unit kerja.
  3. Pengguna mengeksplorasi data untuk kebutuhan bahan rapat pimpinan / kajian kebijakan.
- **Alternative Flow:**
  - *2a. Pengguna mengekspor laporan:* Pengguna mengunduh ringkasan kajian dalam bentuk file PDF/Excel.
- **Exception Flow:** -
- **Postcondition:** Analisis data kelembagaan tersaji interaktif untuk pimpinan.
- **Business Rules:** [BRULE-020](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)
- **Related Requirement:** [REQ-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-ANA-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [US-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md)
