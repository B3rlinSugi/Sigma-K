# DISCOVERY DECISIONS: SIGMA-K

> **Status:** DISCOVERY BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  

Dokumen ini mencatat seluruh keputusan arsitektural dan manajerial yang telah disepakati bersama pada fase Discovery.

---

## Daftar Keputusan (Decisions Log)

### DEC-001: Legacy System sebagai Legacy Reference, Bukan Basis Salinan Mentah
- **Decision:** Sistem dan repositori lama (E-SKLD / SIGMA-K Legacy) serta database `eskld` ditetapkan sebagai **Legacy Reference**. Tidak diperbolehkan menyalin mentah (*copy-paste*) codebase legacy ke dalam project baru.
- **Reason:** Codebase legacy memiliki technical debt, arsitektur monolitik lawas, denormalisasi data berat, dan tidak memenuhi standar skalabilitas serta modern UI/UX yang ditargetkan.
- **Status:** **APPROVED**
- **Impact:** Sistem SIGMA-K akan dibangun dengan arsitektur modern dari fondasi yang bersih (*clean-slate modern architecture*), namun aturan bisnis dan data esensial tetap diadopsi.

---

### DEC-002: Moratorium Penulisan Source Code Aplikasi pada Fase Discovery
- **Decision:** Menahan pembuatan source code aplikasi (Laravel, Next.js, React, NestJS, script backend/frontend, skema database, atau migration) selama fase Discovery berlangsung.
- **Reason:** Mencegah pemborosan resource akibat perubahan arsitektur mendadak sebelum requirement, domain model, dan ekspektasi pimpinan (SESDEP) terdefinisi secara matang.
- **Status:** **APPROVED**
- **Impact:** Seluruh aktivitas pada fase ini difokuskan pada penyusunan dokumen Discovery Baseline yang komprehensif di `docs/discovery/`.

---

### DEC-003: Isolasi Ketat Direktori dan Proteksi Folder `KemenPANRB_LEGACY`
- **Decision:** Pengembangan hanya dilakukan di dalam workspace `SIGMA-K`. Folder `KemenPANRB_LEGACY` dan direktori di luar workspace tidak boleh diakses, diubah, atau dihapus.
- **Reason:** Menjamin integritas data referensi legacy dan mencegah risiko kehilangan data historis kementerian.
- **Status:** **APPROVED**
- **Impact:** Keamanan data legacy 100% terjaga dan scope pengerjaan tim terisolasi secara aman.

---

### DEC-004: Penangguhan Pemilihan Final Tech Stack
- **Decision:** Keputusan final mengenai stack teknologi (misal: Next.js vs React Vite, Laravel vs NestJS, PostgreSQL vs MySQL, Redis vs SSE) ditangguhkan hingga seluruh dokumen discovery divalidasi.
- **Reason:** Pemilihan teknologi harus didasarkan pada kebutuhan non-fungsional riil (skalabilitas, dukungan server kementerian, kemudahan deployment, dan ketersediaan resource tim), bukan preferensi awal yang belum teruji.
- **Status:** **APPROVED**
- **Impact:** Analisis komparasi teknologi akan disusun secara objektif pada Fase Arsitektur (Phase 2).

---

### DEC-005: Pendekatan Prototype Interaktif untuk Presentasi SESDEP
- **Decision:** Mengembangkan Prototype interaktif berstandar enterprise yang mencakup modul Dashboard, Manajemen Kabinet, Daftar K/L & Pemda, Detail Profil Instansi, Struktur Organisasi Visual, dan Workflow Verifikasi sebelum peluncuran full-scale.
- **Reason:** Pimpinan (SESDEP) membutuhkan demonstrasi konkret visual dan fungsional mengenai bagaimana sistem baru menyelesaikan permasalahan tata kelola kelembagaan (misal: penataan Kabinet Merah Putih).
- **Status:** **APPROVED**
- **Impact:** Tim memiliki milestone yang jelas untuk validasi UX/UI ke pimpinan sebelum menyelesaikan seluruh backend edge-cases.

---

### DEC-006: Pembagian Peran Kerja Tim yang Terstruktur
- **Decision:** Pembagian tanggung jawab tim ditetapkan secara profesional:
  - **Ikhsan:** Data Analyst (Analisis data legacy `eskld`, formulasi metriks kelembagaan & postur ASN, verifikasi kualitas data).
  - **Lead / User:** Senior Software Architect & Lead Full-Stack Engineer (Arsitektur sistem, backend API, frontend application, realtime notification, integrasi database, testing, dokumentasi).
- **Reason:** Meningkatkan efisiensi kerja dan spesialisasi fokus selama periode magang.
- **Status:** **APPROVED**
- **Impact:** Kolaborasi data analyst dan full-stack engineer berjalan secara sinergis melalui kontrak antarmuka data yang jelas.

---

### DEC-007: Normalisasi Entitas Kabinet dan Pelacakan Histori Kelembagaan
- **Decision:** Merombak model data kabinet legacy dari kolom string `list_id_kl` menjadi struktur relasional ternormalisasi (`Cabinet`, `CabinetPeriod`, `CabinetMembership`) yang dilengkapi pelacak histori perubahan kelembagaan antar-kabinet.
- **Reason:** Memungkinkan perbandingan komposisi instansi antar periode pemerintahan secara akurat, cepat, dan terukur secara relasional.
- **Status:** **APPROVED**
- **Impact:** Mendukung fleksibilitas penataan kabinet dinamis (seperti Kabinet Merah Putih) tanpa merusak integritas data periode sebelumnya.

---

### DEC-008: Implementasi 3 Peran Utama (User, Admin, Verifikator) dengan Alur Verifikasi
- **Decision:** Menetapkan 3 role awal terkonfirmasi: `USER` (Operator Instansi), `VERIFIKATOR` (Peninjau), dan `ADMIN` (Administrator) dengan mekanisme pengajuan berbasis draft (*Draft-to-Publish Workflow*).
- **Reason:** Mencegah manipulasi data langsung pada production master data dan menjamin akuntabilitas data kelembagaan kementerian/pemda.
- **Status:** **APPROVED**
- **Impact:** Master data selalu dalam status terverifikasi dan tervalidasi secara hukum.

---

### DEC-009: Kewajiban Audit Trail dan Realtime Notification
- **Decision:** Setiap aktivitas pembuatan, pengubahan, penghapusan, pengajuan, dan verifikasi data wajib menghasilkan log audit (*immutable record*) dan memicu notifikasi seketika (*realtime alert*).
- **Reason:** Transparansi, keamanan data instansi pemerintah, dan kemudahan kolaborasi antar-aktor.
- **Status:** **APPROVED**
- **Impact:** Membutuhkan modul audit engine dan arsitektur websocket/SSE yang efisien.

---

### DEC-010: Preservasi Penuh Data Valid Legacy
- **Decision:** Data instansi, hierarki struktur organisasi, dan wilayah pada database legacy `eskld` akan dipertahankan dan dimigrasikan melalui proses cleansing & transformation ke skema baru.
- **Reason:** Menghindari penginputan ulang manual ribuan data instansi dan struktur kelembagaan yang sudah terdata di kementerian.
- **Status:** **APPROVED**
- **Impact:** Diperlukan pipeline migrasi/ETL data bersih pada fase implementasi database.
