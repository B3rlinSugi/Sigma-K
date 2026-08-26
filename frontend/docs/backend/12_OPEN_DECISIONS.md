# SIGMA-K — BACKEND ARCHITECTURE OPEN DECISIONS REGISTER

> **Dokumen:** `12_OPEN_DECISIONS.md`  
> **Status:** `OPEN DECISIONS REGISTER (PHASE 5A DESIGN - REVIEWED)`  
> **Prinsip Kritis:** Keputusan bisnis dan tata kelola stakeholder yang belum final **TIDAK BOLEH** diputuskan sepihak di kode. Seluruh opsi wajib didokumentasikan untuk konfirmasi pimpinan.

---

## 1. Register Keputusan Terbuka (Open Decisions Register)

### OPEN-001: Model Otorisasi Produksi untuk SESDEP / PIMPINAN
- **Konteks:** Pada prototipe Phase 4, SESDEP berperan sebagai *Executive Perspective / Prototype Persona*.
- **Pertanyaan Arsitektur:** Bagaimana pemodelan hak akses SESDEP/Pimpinan pada database RBAC produksi?
- **Opsi Solusi:**
  - **Opsi A (Dedicated Role `SESDEP`):** Membuat role permanen `SESDEP` di tabel `roles` dengan hak akses spesifik membaca seluruh data nasional, melihat seluruh audit trail, dan mengakses layar analitik intelijensi tanpa izin mutasi draf.
  - **Opsi B (Permission-Based Supervisory Model):** Menggunakan role standar dengan flag izin khusus `supervisory:read_all`, `analytics:executive_view`, dan `audit:full_access` tanpa membuat role khusus statis di database.
- **Dampak Teknis:** Opsi B lebih fleksibel untuk penambahan pejabat eselon I/II lain di masa depan.
- **Status:** `AKTIF — MENUNGGU KONFIRMASI STAKEHOLDER (SESDEP / MENTOR)`

---

### OPEN-002: Urutan Tahapan Alur Kerja Resmi (Standard vs Admin Triage Workflow)
- **Konteks:** Terdapat dua konsep alur kerja pengajuan data kelembagaan yang valid pada Phase 1 & Phase 2:
  - **Standard Workflow (5 Tahap):** `DRAFT` $\rightarrow$ `SUBMITTED` $\rightarrow$ `IN_REVIEW` $\rightarrow$ `VERIFIED` $\rightarrow$ `APPROVED`.
  - **Admin Triage Workflow (6 Tahap):** `DRAFT` $\rightarrow$ `SUBMITTED` $\rightarrow$ `ADMIN_TRIAGED` $\rightarrow$ `IN_REVIEW` $\rightarrow$ `VERIFIED` $\rightarrow$ `APPROVED`.
- **Pertanyaan Arsitektur:** Apakah setiap usulan dari kementerian harus melewati verifikasi administrasi awal (*triase*) oleh Admin Pusat sebelum masuk ke analis verifikator, atau langsung masuk ke antrean analis verifikator?
- **Strategi Backend Phase 5A:** Backend dirancang dengan **Data-Driven State Machine** yang mendukung kedua model ini melalui konfigurasi `WorkflowProfile` tanpa perlu mengubah kode sumber backend.
- **Status:** `AKTIF — MENUNGGU KONFIRMASI SOP BISNIS KEMENPANRB`

---

### OPEN-003: Integrasi Penyedia Otentikasi Produksi (SSO / OIDC vs Internal Provisional JWT)
- **Konteks:** Arsitektur saat ini menggunakan kandidat otentikasi sementara (*Provisional Architecture Candidate*) berbasis NIP/Username dan JWT token.
- **Pertanyaan Arsitektur:** Apakah pada lingkungan produksi Kementerian PANRB sistem SIGMA-K akan diintegrasikan dengan Single Sign-On (SSO) KemenPANRB, Portal ASN Digital (BKN), atau akun OIDC terpusat?
- **Strategi Backend Phase 5A:** Modul `AuthModule` menggunakan pola *Passport Strategy* (`JwtStrategy`, `LocalStrategy`, dan stub `OidcStrategy`) sehingga peralihan ke IdP eksternal di masa depan tidak mengganggu modul bisnis lainnya. Skema JWT internal 15m + Refresh Token ditegaskan berstatus *provisional candidate*.
- **Status:** `AKTIF — MENUNGGU KOORDINASI INFRASTRUKTUR & PUSDATIN KEMENPANRB`

---

### OPEN-004: Pilihan Infrastruktur Penyimpanan Berkas Produksi (MinIO On-Premise vs Cloud S3)
- **Konteks:** Berkas salinan PDF dasar hukum (Perpres/Permen) memerlukan penyimpanan yang aman dan berintegritas.
- **Pertanyaan Arsitektur:** Apakah deployment server produksi di Data Center KemenPANRB / PDN menyediakan MinIO On-Premise S3-compatible storage, atau shared filesystem / block storage?
- **Strategi Backend Phase 5A:** Menggunakan pola **Pluggable Storage Driver** (`StorageDriver` interface) pada `FilesModule` yang saat ini mendukung *Local Disk* dan siap beralih ke *MinIO/S3 Driver* melalui konfigurasi file `.env`.
- **Status:** `AKTIF — MENUNGGU SPESIFIKASI INFRASTRUKTUR SERVER PUSAT`

---

### OPEN-005: Protokol Pengiriman Realtime Produksi (Socket.io WebSocket vs Server-Sent Events)
- **Konteks:** Notifikasi pengajuan dan telaah memerlukan pembaruan realtime pada bilah peramban pengguna.
- **Pertanyaan Arsitektur:** Apakah jaringan intranet pemerintah / firewall proxy KemenPANRB mengizinkan koneksi WebSocket dua arah persisten, atau lebih stabil menggunakan HTTP-based Server-Sent Events (SSE)?
- **Strategi Backend Phase 5A:** Modul `NotificationsModule` mengisolasi transport pengiriman di balik `NotificationDispatcher` yang dapat beralih antara WebSocket Gateway atau SSE endpoint tanpa mengubah logika pemancaran event di service layer.
- **Status:** `AKTIF — MENUNGGU HASIL UJI PENETRASI JARINGAN SERVER`
