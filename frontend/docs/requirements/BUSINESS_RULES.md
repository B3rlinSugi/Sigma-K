# BUSINESS RULES: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Senior Business Analyst & Software Architect  

Dokumen ini mendokumentasikan aturan bisnis formal (*Business Rules*) yang menjadi acuan logika dan pembatasan operasional sistem SIGMA-K.

---

## Matriks Aturan Bisnis

| Rule ID | Judul Aturan | Aktor Terkait | Kategori | Status |
|---|---|---|---|---|
| **BRULE-001** | Role-Based Access Enforcement | System, All Users | Security & Access | **CONFIRMED** |
| **BRULE-002** | Scoped Institutional Access for Operator | User (Operator) | Data Access | **CONFIRMED** |
| **BRULE-003** | Single Active Cabinet Context | Admin, System | Cabinet Management | **CONFIRMED** |
| **BRULE-004** | Master Data Uniqueness & National Coding | Admin, System | Master Data | **CONFIRMED** |
| **BRULE-005** | Controlled Mutation via Draft Workflow | User, Admin | Data Governance | **CONFIRMED** |
| **BRULE-006** | Soft Delete & Referential Retention | Admin, System | Data Retention | **CONFIRMED** |
| **BRULE-007** | Draft Lock on Submission | User, System | Workflow & Lock | **CONFIRMED** |
| **BRULE-008** | Cabinet Master Integrity & Hierarchy | Admin, System | Cabinet Management | **CONFIRMED** |
| **BRULE-009** | Temporal Validity of Cabinet Period | Admin, System | Temporal Integrity | **CONFIRMED** |
| **BRULE-010** | Normalized Cabinet Membership & Deduplication | Admin, System | Membership Relational | **CONFIRMED** |
| **BRULE-011** | Historical Lineage & Transition Recording | Admin, System | Historical Integrity | **CONFIRMED** |
| **BRULE-012** | Legal Basis Requirement for Tupoksi | User, Verifikator | Governance & Legal | **CONFIRMED** |
| **BRULE-013** | Anti-Circular Dependency in Organization Tree | System, User | Tree Hierarchy | **CONFIRMED** |
| **BRULE-014** | Mandatory Legal Document Attachment on Submit | User, System | Workflow Compliance | **CONFIRMED** |
| **BRULE-015** | Separation of Duties in Verification & Approval | Verifikator, Admin, User | Separation of Duties | **CONFIRMED** |
| **BRULE-016** | Mandatory Feedback on Revision Request | Verifikator, System | Workflow Quality | **CONFIRMED** |
| **BRULE-017** | Atomic Publishing to Master Data on Approval | Admin, System | Transaction Integrity | **CONFIRMED** |
| **BRULE-018** | Universal Realtime Event Emission | System | Realtime Notification | **CONFIRMED** |
| **BRULE-019** | Immutability and Completeness of Audit Logs | System | Security & Audit | **CONFIRMED** |
| **BRULE-020** | Executive Read-Only Scope | Pimpinan / SESDEP | Executive Access | **CONFIRMED** |

---

## Rincian Aturan Bisnis

### BRULE-001: Role-Based Access Enforcement
- **Rule Statement:** Setiap aksi pada sistem wajib melewati validasi izin peran (*RBAC*). Aksi manipulasi data hanya dapat dilakukan oleh aktor yang memiliki peran dan izin yang sesuai.
- **Actor:** System, All Users
- **Condition:** Pengguna melakukan *request* ke endpoint sistem atau membuka rute antarmuka.
- **Expected Behavior:** Sistem mengizinkan akses jika role pengguna memiliki permission terkait. Jika tidak, sistem menolak dengan kode *403 Forbidden* dan mencatat insiden keamanan.
- **Status:** **CONFIRMED**
- **Source:** [REQ-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [ACTOR_AND_ROLE_DISCOVERY.md](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/ACTOR_AND_ROLE_DISCOVERY.md)

---

### BRULE-002: Scoped Institutional Access for Operator
- **Rule Statement:** Pengguna dengan peran `USER` (Operator Instansi) hanya memiliki izin untuk mengedit draf data instansi yang secara eksplisit diikatkan (*scoped*) ke akunnya.
- **Actor:** User (Operator Instansi)
- **Condition:** User mencoba membuka form edit draf atau mengirim pengajuan data suatu instansi.
- **Expected Behavior:** Sistem memverifikasi `user.institution_id == target.institution_id`. Jika berbeda, sistem menolak operasi dan memblokir modifikasi data instansi lain.
- **Status:** **CONFIRMED**
- **Source:** [REQ-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [ACTOR_AND_ROLE_DISCOVERY.md](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/ACTOR_AND_ROLE_DISCOVERY.md)

---

### BRULE-003: Single Active Cabinet Context
- **Rule Statement:** Pada satu waktu operasional sistem, hanya boleh ada tepat **1 (satu)** kabinet berstatus `is_active = TRUE` yang menjadi konteks kabinet aktif default pada dashboard.
- **Actor:** Admin, System
- **Condition:** Admin mengaktifkan suatu kabinet baru sebagai kabinet aktif.
- **Expected Behavior:** Sistem secara otomatis menonaktifkan penanda `is_active` pada kabinet sebelumnya dan menetapkan kabinet baru sebagai kabinet aktif utama.
- **Status:** **CONFIRMED**
- **Source:** [REQ-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [DEC-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md)

---

### BRULE-004: Master Data Uniqueness & National Coding
- **Rule Statement:** Setiap instansi pemerintah wajib memiliki Kode Referensi Instansi yang unik di seluruh sistem SIGMA-K.
- **Actor:** Admin, System
- **Condition:** Admin menambahkan instansi baru atau mengedit kode instansi.
- **Expected Behavior:** Sistem memeriksa keunikan kode instansi. Jika ditemukan duplikasi kode, sistem menolak penyimpanan dan menampilkan pesan validasi duplikasi.
- **Status:** **CONFIRMED**
- **Source:** [REQ-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [DATA_DOMAIN_DISCOVERY.md](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DATA_DOMAIN_DISCOVERY.md)

---

### BRULE-005: Controlled Mutation via Draft Workflow
- **Rule Statement:** Pengguna berstatus `USER` dilarang mengubah Master Data aktif secara langsung. Seluruh perubahan profil, tugas fungsi, dan struktur organisasi wajib melalui ruang kerja draf (*Draft Workspace*).
- **Actor:** User (Operator), Admin
- **Condition:** User melakukan modifikasi data kelembagaan.
- **Expected Behavior:** Perubahan disimpan di tabel staging/draf. Master data yang aktif melayani publik dan dashboard eksekutif tetap tidak berubah hingga pengajuan disetujui (Approve) oleh Admin.
- **Status:** **CONFIRMED**
- **Source:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [DEC-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md)

---

### BRULE-006: Soft Delete & Referential Retention
- **Rule Statement:** Entitas Master Instansi, Unit Organisasi, dan Kabinet tidak boleh dihapus secara fisik dari database (*Hard Delete*), melainkan ditandai nonaktif (*Soft Delete*).
- **Actor:** Admin, System
- **Condition:** Admin mengeksekusi aksi hapus instansi atau unit kerja.
- **Expected Behavior:** Sistem mengisi kolom `deleted_at = NOW()`. Data disembunyikan dari daftar aktif namun relasi historis pada kabinet masa lampau tetap utuh.
- **Status:** **CONFIRMED**
- **Source:** [REQ-023](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-016](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md)

---

### BRULE-007: Draft Lock on Submission
- **Rule Statement:** Draf data instansi yang telah diajukan ke tim verifikasi (berstatus `SUBMITTED` atau `IN_REVIEW`) terkunci secara otomatis dari segala bentuk pengeditan oleh User.
- **Actor:** User, System
- **Condition:** User mencoba mengubah data draf saat tiket pengajuan sedang diproses verifikator.
- **Expected Behavior:** Sistem menonaktifkan seluruh input form dan menolak API update dengan pesan bahwa data sedang dalam proses verifikasi.
- **Status:** **CONFIRMED**
- **Source:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [WORKFLOW_SPECIFICATION.md](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/WORKFLOW_SPECIFICATION.md)

---

### BRULE-008: Cabinet Master Integrity & Hierarchy
- **Rule Statement:** Suatu kabinet hanya dapat memiliki entitas periode dan keanggotaan jika data induk kabinet berstatus valid dan tidak terhapus.
- **Actor:** Admin, System
- **Condition:** Admin mengelola sub-relasi kabinet.
- **Expected Behavior:** Relasi database wajib mengikat foreign key ke Master Cabinet dengan validasi cascade logis.
- **Status:** **CONFIRMED**
- **Source:** [REQ-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [DEC-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md)

---

### BRULE-009: Temporal Validity of Cabinet Period
- **Rule Statement:** Rentang waktu periode kabinet wajib mematuhi kaidah temporal: `start_date <= end_date` (atau `end_date IS NULL` untuk kabinet yang sedang aktif berjalan).
- **Actor:** Admin, System
- **Condition:** Admin menyimpan data periode kabinet.
- **Expected Behavior:** Jika `end_date < start_date`, sistem menolak penyimpanan dan menampilkan error validasi tanggal.
- **Status:** **CONFIRMED**
- **Source:** [REQ-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md)

---

### BRULE-010: Normalized Cabinet Membership & Deduplication
- **Rule Statement:** Suatu instansi kementerian/lembaga hanya boleh terdaftar maksimal **1 (satu) kali** sebagai anggota aktif pada periode kabinet yang sama.
- **Actor:** Admin, System
- **Condition:** Admin menambahkan instansi ke dalam keanggotaan kabinet.
- **Expected Behavior:** Sistem menerapkan konstrain unik komposit `(cabinet_period_id, institution_id)`. Jika terjadi duplikasi penambahan instansi yang sama pada periode yang sama, sistem menolak permintaan.
- **Status:** **CONFIRMED**
- **Source:** [REQ-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [DEC-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md)

---

### BRULE-011: Historical Lineage & Transition Recording
- **Rule Statement:** Setiap pemecahan instansi (misal 1 kementerian dipecah menjadi 3 kementerian baru), penggabungan instansi, atau perubahan nomenklatur antar-kabinet wajib mencatat relasi asal-usul instansi pendahulu (*Predecessor Institution*).
- **Actor:** Admin, System
- **Condition:** Terjadi mutasi status kelembagaan pada kabinet baru.
- **Expected Behavior:** Sistem menyimpan entitas `InstitutionLineage` (`predecessor_id`, `successor_id`, `transition_type`, `legal_basis_id`).
- **Status:** **CONFIRMED**
- **Source:** [REQ-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [BR-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_REQUIREMENTS.md)

---

### BRULE-012: Legal Basis Requirement for Tupoksi
- **Rule Statement:** Setiap butir tugas dan fungsi yang didaftarkan wajib mencantumkan referensi pasal dan nomor regulasi hukum dasar pembentukannya.
- **Actor:** User, Verifikator
- **Condition:** User menyimpan butir tugas atau fungsi kelembagaan.
- **Expected Behavior:** Form mewajibkan pengisian pasal rujukan. Pengajuan tanpa dasar hukum akan menjadi catatan koreksi utama verifikator.
- **Status:** **CONFIRMED**
- **Source:** [REQ-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-TUP-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)

---

### BRULE-013: Anti-Circular Dependency in Organization Tree
- **Rule Statement:** Struktur organisasi hierarki tidak boleh memiliki relasi melingkar (*circular reference*, contoh: Unit A adalah atasan Unit B, dan Unit B dijadikan atasan Unit A).
- **Actor:** System, User
- **Condition:** User membuat atau mengubah parent suatu unit kerja (`re-parenting`).
- **Expected Behavior:** Sistem melakukan algoritma *cycle detection* menelusuri pohon leluhur (*ancestor tree*). Jika terdeteksi siklus, operasi dibatalkan dan sistem mengembalikan pesan error.
- **Status:** **CONFIRMED**
- **Source:** [REQ-020](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-ORG-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)

---

### BRULE-014: Mandatory Legal Document Attachment on Submit
- **Rule Statement:** Pengiriman berkas usulan perubahan data kelembagaan wajib menyertakan minimal 1 (satu) lampiran dokumen regulasi resmi (PDF).
- **Actor:** User, System
- **Condition:** User menekan tombol submit tiket pengajuan.
- **Expected Behavior:** Sistem memeriksa keberadaan dokumen lampiran. Jika belum ada file yang diunggah, pengiriman ditolak dengan instruksi untuk melampirkan dasar hukum.
- **Status:** **CONFIRMED**
- **Source:** [REQ-021](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-SUB-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)

---

### BRULE-015: Separation of Duties in Verification & Approval
- **Rule Statement:** Pengguna yang membuat/mengajukan draf (`USER`) dilarang memverifikasi atau menyetujui pengajuannya sendiri. Verifikator hanya berhak memvalidasi dan merekomendasikan, sedangkan persetujuan akhir (*Approval*) berada pada kewenangan Admin/Pimpinan.
- **Actor:** Verifikator, Admin, User
- **Condition:** Aksi pemrosesan status tiket pengajuan.
- **Expected Behavior:** Sistem memvalidasi ID aktor terhadap role dan ownership tiket. Pelanggaran *separation of duties* diblokir oleh sistem.
- **Status:** **CONFIRMED**
- **Source:** [REQ-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [DEC-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md)

---

### BRULE-016: Mandatory Feedback on Revision Request
- **Rule Statement:** Verifikator wajib menyertakan minimal 1 (satu) catatan koreksi yang jelas dan terperinci saat memilih status "Minta Revisi" (*Revision Required*).
- **Actor:** Verifikator, System
- **Condition:** Verifikator mengklik aksi "Minta Revisi".
- **Expected Behavior:** Tombol submit revisi dinonaktifkan jika kolom catatan koreksi masih kosong atau kurang dari panjang minimal teks.
- **Status:** **CONFIRMED**
- **Source:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-REV-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)

---

### BRULE-017: Atomic Publishing to Master Data on Approval
- **Rule Statement:** Persetujuan akhir atas tiket pengajuan wajib menerapkan seluruh data draf ke Master Data dalam satu transaksi basis data yang atomik (*single ACID transaction*).
- **Actor:** Admin, System
- **Condition:** Admin menyetujui tiket pengajuan.
- **Expected Behavior:** Jika salah satu unit kerja gagal disimpan, seluruh perubahan draf dibatalkan (*rollback*) dan status tiket tetap pada kondisi sebelumnya tanpa merusak integritas master data.
- **Status:** **CONFIRMED**
- **Source:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-APP-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [NFR-REL-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/NON_FUNCTIONAL_REQUIREMENTS.md)

---

### BRULE-018: Universal Realtime Event Emission
- **Rule Statement:** Setiap mutasi data berstatus sukses (Create, Update, Delete, Submit, Verify, Approve, Reject) wajib memicu *realtime event* ke broker/pipeline notifikasi.
- **Actor:** System
- **Condition:** Transaksi mutasi data berhasil di-commit pada database.
- **Expected Behavior:** Sistem menyiarkan event dengan payload metadata (ID mutasi, tipe aksi, aktor, timestamp, instansi terkait) kepada klien yang terhubung.
- **Status:** **CONFIRMED**
- **Source:** [REQ-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-NOT-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [DEC-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md)

---

### BRULE-019: Immutability and Completeness of Audit Logs
- **Rule Statement:** Log audit yang telah tercatat tidak boleh diubah (*UPDATE*), dihapus (*DELETE*), atau ditimpa oleh siapapun termasuk Administrator Sistem.
- **Actor:** System
- **Condition:** Pencatatan log audit mutasi data.
- **Expected Behavior:** Tabel log audit bersifat *append-only* dengan hak akses aplikasi terbatas hanya untuk operasi `INSERT` dan `SELECT`.
- **Status:** **CONFIRMED**
- **Source:** [REQ-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-AUD-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [NFR-AUD-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/NON_FUNCTIONAL_REQUIREMENTS.md)

---

### BRULE-020: Executive Read-Only Scope
- **Rule Statement:** Peran `PIMPINAN / SESDEP` memiliki akses baca (*Read-Only*) penuh terhadap seluruh modul dashboard, katalog instansi, kabinet, dan analitik, namun tidak dibebani kewajiban operasional pengetikan data harian.
- **Actor:** Pimpinan / SESDEP, System
- **Condition:** Pimpinan mengakses antarmuka sistem.
- **Expected Behavior:** Antarmuka menyajikan visualisasi data eksekutif yang bersih tanpa tombol edit operasional draf.
- **Status:** **CONFIRMED**
- **Source:** [REQ-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [ACTOR_AND_ROLE_DISCOVERY.md](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/ACTOR_AND_ROLE_DISCOVERY.md)
