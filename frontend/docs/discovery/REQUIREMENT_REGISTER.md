# REQUIREMENT REGISTER: SIGMA-K

> **Status:** DISCOVERY BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  

Register ini mencatat seluruh kebutuhan fungsional dan non-fungsional sistem SIGMA-K yang dikelompokkan ke dalam tiga kategori status:
1. **CONFIRMED:** Kebutuhan yang telah disepakati dan menjadi keharusan (mandatory).
2. **PROPOSED:** Kebutuhan yang diusulkan oleh tim engineer/analyst untuk melengkapi arsitektur terbaik dan menunggu validasi final pimpinan.
3. **TBD (To Be Determined):** Kebutuhan yang masih memerlukan klarifikasi mendalam dari stakeholder (SESDEP / Pembimbing).

---

## 1. Confirmed Requirements

| ID | Domain / Modul | Deskripsi Kebutuhan | Tipe | Prioritas | Sumber |
|---|---|---|---|---|---|
| **REQ-001** | Cabinet Management | Sistem harus menyediakan fitur pengelolaan Master Kabinet (nama kabinet, pimpinan kabinet, status aktif). | Fungsional | CRITICAL | Requirement User |
| **REQ-002** | Cabinet Period | Sistem harus menyediakan pengaturan periode/masa jabatan kabinet dengan rentang waktu formal. | Fungsional | HIGH | Requirement User |
| **REQ-003** | Institution Membership | Sistem harus memetakan keanggotaan instansi (Kementerian/Lembaga) pada setiap periode kabinet secara ternormalisasi. | Fungsional | CRITICAL | Requirement User |
| **REQ-004** | Historical Changes | Sistem harus dapat mencatat dan menampilkan riwayat perubahan kelembagaan antar-periode (pembentukan, pemecahan, merger, pembubaran). | Fungsional | HIGH | Requirement User |
| **REQ-005** | Institution List | Sistem harus menampilkan katalog dan daftar instansi (K/L dan Pemda) dengan fitur pencarian, filter kategori, dan status keaktifan. | Fungsional | HIGH | Requirement User |
| **REQ-006** | Institution Detail | Sistem harus menyediakan halaman detail profil instansi lengkap (informasi umum, kontak, alamat, dasar hukum, visi misi). | Fungsional | HIGH | Requirement User |
| **REQ-007** | Tugas & Fungsi | Sistem harus dapat mengelola butir-butir Tugas dan Fungsi (Tupoksi) resmi pada level instansi maupun unit kerja. | Fungsional | HIGH | Requirement User |
| **REQ-008** | Organization Hierarchy | Sistem harus mengelola hierarki struktur organisasi instansi secara berjenjang (parent-child) dengan visualisasi bagan interaktif. | Fungsional | CRITICAL | Requirement User |
| **REQ-009** | Role-Based Access | Sistem harus mendukung minimal tiga peran utama: `USER`, `ADMIN`, dan `VERIFIKATOR` dengan pembatasan hak akses yang tegas. | Security / IAM | CRITICAL | Requirement User |
| **REQ-010** | Verification Workflow | Sistem harus menyediakan alur kerja pengajuan (Submission) oleh User, peninjauan oleh Verifikator, dan persetujuan oleh Admin. | Fungsional | CRITICAL | Requirement User |
| **REQ-011** | Realtime Notification | Sistem harus mengirimkan notifikasi seketika (*realtime alert/badge*) saat terjadi aktivitas penting (Create, Update, Delete, Submit, Verify, Approve). | Fungsional | HIGH | Requirement User |
| **REQ-012** | Executive Dashboard | Sistem harus menyediakan dashboard eksekutif modern yang menampilkan ringkasan data kabinet, rekapitulasi instansi, dan status workflow. | Fungsional / UI | CRITICAL | Requirement User |
| **REQ-013** | Data Analytics | Sistem harus menyediakan modul analisis data kelembagaan dan postur ASN bekerja sama dengan Data Analyst. | Fungsional | HIGH | Requirement User |
| **REQ-014** | Audit Trail | Sistem harus mencatat seluruh riwayat aktivitas pengguna dan mutasi data (who, what, when, old values, new values). | Security / Governance | HIGH | Requirement User |
| **REQ-015** | Prototype for SESDEP | Tim harus membuat prototype interaktif (Dashboard, Kabinet, Daftar K/L, Detail, Struktur Organisasi, Workflow) untuk presentasi pimpinan/SESDEP. | Delivery / Milestone | CRITICAL | Requirement User |
| **REQ-016** | Data Preservation | Sistem baru harus mempertahankan data valid dari database legacy `eskld` sebagai rujukan dan sumber data migrasi. | Data Architecture | CRITICAL | Requirement User |
| **REQ-017** | Scalable Architecture | Arsitektur sistem harus dirancang dapat diskalakan untuk mengakomodasi seluruh K/L dan Pemda di Indonesia (38 Provinsi, 514 Kab/Kota). | Non-Fungsional | HIGH | Requirement User |
| **REQ-018** | Professional Standards | Project harus dikembangkan dengan standar rekayasa perangkat lunak profesional (Frontend, Backend, DB, API, Testing, Docs) di GitHub. | Engineering | HIGH | Requirement User |

---

## 2. Proposed Requirements

| ID | Domain / Modul | Deskripsi Kebutuhan | Tipe | Prioritas | Justifikasi Arsitektural |
|---|---|---|---|---|---|
| **REQ-019** | Diff Viewer for Verification | Sistem menyediakan antarmuka visual komparasi (*side-by-side diff*) antara data eksisting dan usulan perubahan data saat verifikasi. | Fungsional / UI | MEDIUM | Mempercepat verifikator dalam meneliti perubahan tanpa membaca manual. |
| **REQ-020** | Circular Dependency Check | Sistem secara otomatis menolak dan memvalidasi jika terjadi relasi melingkar (*circular reference*) pada `parent_id` struktur organisasi. | Data Integrity | HIGH | Mencegah kerusakan pohon hierarki (*infinite loop*). |
| **REQ-021** | Document Attachment | Sistem mendukung pengunggahan dan preview dokumen regulasi pendirian/tupoksi berformat PDF (maksimal 10 MB). | Fungsional | MEDIUM | Memastikan dasar hukum instansi dapat diverifikasi langsung. |
| **REQ-022** | Export Engine | Sistem menyediakan fitur ekspor data katalog instansi, struktur, dan tupoksi ke format standar (PDF, Excel, JSON). | Reporting | MEDIUM | Kebutuhan pelaporan berkala bagi pimpinan dan staf kementerian. |
| **REQ-023** | Soft Delete Pattern | Seluruh entitas master data menggunakan mekanisme *soft delete* untuk mencegah kehilangan riwayat data secara permanen. | Data Architecture | HIGH | Mendukung audit trail dan restorasi data jika terjadi kesalahan operasional. |
| **REQ-024** | Dark / Light Theme Mode | Sistem menyediakan tampilan antarmuka modern yang mendukung opsi tema Gelap (*Dark Mode*) dan Terang (*Light Mode*). | UI / UX | LOW | Meningkatkan kenyamanan visual saat presentasi maupun penggunaan harian. |
| **REQ-025** | API Rate Limiting & Security | Seluruh endpoint REST API dilindungi mekanisme throttling / rate limiting dan proteksi CORS & CSRF. | Security | HIGH | Menjaga stabilitas dan keamanan API dari potensi *abuse*. |

---

## 3. TBD (To Be Determined) Requirements

| ID | Domain / Modul | Deskripsi Kebutuhan | Tipe | Pertanyaan Kunci / Kebutuhan Klarifikasi |
|---|---|---|---|---|
| **REQ-026** | Single Sign-On (SSO) | Integrasi otentikasi menggunakan layanan SSO KemenPANRB / ASN Digital Nasional. | IAM / Integrasi | Apakah sistem harus langsung terhubung ke SSO instansi atau cukup database auth lokal terlebih dahulu? |
| **REQ-027** | External Notification Channel | Notifikasi via email instansi atau WhatsApp gateway selain in-app notification. | Fungsional | Apakah diperlukan integrasi SMTP server atau WA gateway kementerian? |
| **REQ-028** | Multi-level Verification | Alur verifikasi berjenjang lebih dari 1 tingkat (misal: Verifikator Wilayah -> Koordinator Bidang -> SESDEP). | Workflow | Apakah alur verifikasi cukup 1 tingkat (Verifikator -> Admin) atau berjenjang per unit kerja? |
| **REQ-029** | Geo-spatial Mapping (GIS) | Visualisasi pemetaan instansi pada peta interaktif Indonesia (koordinat kantor pemda / kementerian). | UI / Analytics | Apakah data spasial (lintang/bujur kantor instansi) tersedia dan dibutuhkan pada dashboard? |
| **REQ-030** | Tupoksi Overlap Semantic Engine | Analisis otomatis kecocokan teks tupoksi antar kementerian untuk mendeteksi tumpang tindih fungsi. | Advanced Analytics | Apakah analisis semantik/NLP tupoksi masuk dalam target evaluasi pimpinan pada fase ini? |

---

## 4. Ringkasan Statistik Kebutuhan
- **Total Confirmed Requirements:** 18
- **Total Proposed Requirements:** 7
- **Total TBD Requirements:** 5
- **Total Seluruh Requirements:** 30
