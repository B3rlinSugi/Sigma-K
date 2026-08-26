# 15. DATA ARCHITECTURE & GOVERNANCE DECISIONS: SIGMA-K

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Data Architect & Data Governance Specialist  

Dokumen ini mencatat keputusan resmi arsitektur data (*Data Architecture Decisions*) yang memandu desain skema, tata kelola data, dan migrasi sistem SIGMA-K.

---

## 1. Log Keputusan Arsitektur Data Resmi (DATA-DEC-001 s.d DATA-DEC-015)

### DATA-DEC-001: Database Legacy sebagai Sumber Rujukan Murni (*Reference Only*)
- **Context:** Database legacy `eskld` memiliki struktur lama dan data historis berharga.
- **Decision:** Database legacy ditetapkan berstatus **REFERENCE ONLY**. Tidak ada modifikasi, penghapusan, alter table, atau drop table pada database lama.
- **Reason:** Menjaga integritas data historis dan mencegah kehilangan data (*zero data loss*).
- **Impact:** Seluruh pengujian migrasi dijalankan pada database staging terpisah.
- **Status:** **APPROVED**

### DATA-DEC-002: Model Data Target Bersih Ternormalisasi Relasional
- **Context:** Database lama memiliki denormalisasi string dan tabel scratch ad-hoc.
- **Decision:** Skema target SIGMA-K menerapkan model relasional ternormalisasi (3NF) berbasis PostgreSQL 16.
- **Reason:** Memastikan integritas data transaksional ACID dan kemudahan kueri analitik.
- **Status:** **APPROVED**

### DATA-DEC-003: Pemodelan Eksplisit Kabinet Multitahunan
- **Context:** Kolom `data_kl.list_id_kl` menyimpan daftar ID instansi sebagai string delimit koma (`"1,2,5"`).
- **Decision:** Memecah struktur kabinet menjadi `Cabinet`, `CabinetPeriod`, dan tabel relasi `CabinetMembership` dengan konstrain unik.
- **Reason:** Memungkinkan pelacakan komposisi 48 K/L Kabinet Merah Putih dan komparasi antar-kabinet secara instan via SQL Join.
- **Status:** **APPROVED**

### DATA-DEC-004: Pemodelan Graf Silsilah Transisi Kelembagaan (*Lineage*)
- **Context:** Restrukturisasi kementerian (pemecahan Kemendikbudristek, pembentukan Kemenko Pangan) memerlukan pelacakan asal-usul kementerian.
- **Decision:** Membentuk entitas mandiri `InstitutionLineage` (`predecessor_id`, `successor_id`, `transition_type`).
- **Reason:** Menjawab pertanyaan pimpinan mengenai asal-usul fungsi dan aset kementerian baru.
- **Status:** **APPROVED**

### DATA-DEC-005: Unifikasi Master Instansi K/L Pusat dan Pemerintah Daerah
- **Context:** Database legacy memisahkan `tb_instansi` (K/L) dan `data_pemda` (Pemda).
- **Decision:** Menyatukan K/L dan Pemda ke dalam satu entitas master `institutions` dengan pembeda `institution_type_id`.
- **Reason:** Menghilangkan duplikasi fitur dan standarisasi API katalog instansi nasional.
- **Status:** **APPROVED**

### DATA-DEC-006: Model Hierarki Organisasi Berbasis Adjacency List & Anti-Cycle Guard
- **Context:** Bagan organisasi berjenjang (Menteri $\rightarrow$ Eselon I $\rightarrow$ Eselon II $\rightarrow$ Eselon III/Biro).
- **Decision:** Mengadopsi Adjacency List (`parent_id`) dipadukan dengan kueri Recursive CTE dan validasi DFS Cycle Guard.
- **Reason:** Pemindahan unit kerja (*re-parenting*) saat restrukturisasi berjalan cepat ($O(1)$) tanpa write-lock seluruh tabel.
- **Status:** **APPROVED**

### DATA-DEC-007: Deprekasi Tabel Scratch Ad-Hoc (`data_map_*`)
- **Context:** Ditemukan tabel-tabel scratch pemetaan perorangan (`data_map_yudhi_latest`, `data_map_pemda_baru`).
- **Decision:** Mengekstrak nilai relasi valid untuk melengkapi master `institutions` dan `regions`, lalu tabel fisik legacy **DITINGGALKAN / TIDAK DIMIGRASIKAN**.
- **Reason:** Membersihkan skema target dari anti-pattern basis data.
- **Status:** **APPROVED**

### DATA-DEC-008: Strategi Dual Identifier (UUIDv4 + BIGSERIAL)
- **Context:** Kebutuhan keamanan API vs performa throughput penulisan log.
- **Decision:** Menggunakan UUIDv4 untuk entitas master/transaksional publik, dan BIGSERIAL untuk tabel `audit_logs`.
- **Reason:** Mencegah serangan BOLA/IDOR dan mengoptimalkan kecepatan pencatatan audit trail sekuensial.
- **Status:** **APPROVED**

### DATA-DEC-009: Penyimpanan Snapshot Delta Pengajuan Berbasis JSONB
- **Context:** Verifikator membutuhkan tampilan komparasi *Side-by-Side Diff* usulan perubahan data.
- **Decision:** Menyimpan payload perubahan data draf sebelum dan sesudah dalam kolom `JSONB` pada `submission_items`.
- **Reason:** Fleksibilitas menampung variasi atribut perubahan dan rendering visual diff berkecepatan tinggi.
- **Status:** **APPROVED**

### DATA-DEC-010: Log Audit Kepatuhan Bersifat Immutable & Terpartisi
- **Context:** Rekam jejak perubahan data kelembagaan kementerian wajib berkekuatan hukum dan tidak dapat dimanipulasi.
- **Decision:** Tabel `audit_logs` bersifat *append-only* (dilarang UPDATE/DELETE via database rule) dan dipartisi per rentang tahun.
- **Reason:** Menjamin non-repudiation dan performa kueri jangka panjang.
- **Status:** **APPROVED**

### DATA-DEC-011: Pemisahan Beban Analitik via Materialized Views
- **Context:** Kueri dashboard eksekutif SESDEP tidak boleh memperlambat transaksi operasional.
- **Decision:** Memanfaatkan *Indexed Materialized Views* (`mv_cabinet_composition_summary`, `mv_asn_posture_aggregates`) dan Redis Cache.
- **Reason:** Performa baca dashboard $< 50$ ms tanpa perlu membangun Data Warehouse terpisah yang mahal.
- **Status:** **APPROVED**

### DATA-DEC-012: Abstraksi Driver Penyimpanan Berkas Regulasi
- **Context:** Berkas PDF dasar hukum regulasi maksimal 10 MB harus disimpan aman.
- **Decision:** Menggunakan arsitektur pluggable storage driver (Local Disk dev, MinIO/S3 private bucket dengan pre-signed URL di staging/prod).
- **Reason:** Zero cloud lock-in dan perlindungan dokumen privat.
- **Status:** **APPROVED**

### DATA-DEC-013: Retensi Master Data Menggunakan Soft Delete (`deleted_at`)
- **Context:** Penonaktifan kementerian/lembaga atau unit kerja lama tidak boleh merusak relasi historis transaksi masa lalu.
- **Decision:** Seluruh entitas master menerapkan soft-delete menggunakan kolom `deleted_at TIMESTAMPTZ`.
- **Reason:** Menjaga keutuhan data forensik dan silsilah kabinet.
- **Status:** **APPROVED**

### DATA-DEC-014: Validasi Format Email Resmi Pemerintahan (.go.id)
- **Context:** Kontak resmi instansi pemerintah wajib menggunakan domain negara.
- **Decision:** Menerapkan constraint validasi format email berakhiran domain `.go.id` pada profil instansi.
- **Reason:** Standarisasi tata kelola data SPBE.
- **Status:** **APPROVED**

### DATA-DEC-015: Sepuluh Gerbang Kualitas Migrasi (GATE-01 s.d GATE-10)
- **Context:** Migrasi dari MySQL legacy ke PostgreSQL target tidak boleh menimbulkan cacat integritas data.
- **Decision:** Mewajibkan kelulusan 10 Gerbang Kualitas Migrasi (Rekonsiliasi baris, PK/FK integrity, zero cycle, zero duplicate).
- **Reason:** Menjamin kebersihan data 100% sebelum masuk ke fase live produksi.
- **Status:** **APPROVED**
