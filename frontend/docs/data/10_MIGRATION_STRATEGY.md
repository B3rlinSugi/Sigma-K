# 10. LEGACY DATA MIGRATION & ETL/ELT STRATEGY: `eskld` $\rightarrow$ PostgreSQL

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Migration Architect & Senior Database Engineer  
> **Kolaborator:** Ikhsan (Data Analyst)  
> **Prinsip Utama:** *Zero Modification on Legacy Database, Dry-Run Validation on Staging*.

Dokumen ini mendefinisikan strategi dan prosedur migrasi data (*Data Migration & ETL Strategy*) untuk memindahkan data kelembagaan dari database legacy `eskld` (MySQL) menuju PostgreSQL produksi SIGMA-K secara aman, bertahap, dan tervalidasi.

---

## 1. Sepuluh Tahapan Siklus Migrasi Data (10-Step Migration Pipeline)

```
[ 1. LEGACY DB (eskld) ]
          │
          │ 1. Read-Only Extract Dump
          ▼
[ 2. RAW EXTRACT LAYER ] ───> JSON / SQL Dump Terenkapsulasi
          │
          │ 2. Load to Sandbox
          ▼
[ 3. STAGING SANDBOX DB ] ───> PostgreSQL Staging Schema (`staging_eskld`)
          │
          │ 3. Automated Profiling
          ▼
[ 4. PROFILING & ANOMALY SCAN ] ───> Deteksi Null, Duplikasi, Orphan Nodes
          │
          │ 4. Cleansing Transformations
          ▼
[ 5. DATA CLEANSING ] ───> Normalisasi String, De-duplikasi, Perbaikan Parent
          │
          │ 5. Schema Transformation
          ▼
[ 6. TARGET TRANSFORMATION ] ───> Generate UUID, Split `list_id_kl`, Map Scopes
          │
          │ 6. Integrity Quality Gates (GATE-01 s.d GATE-10)
          ▼
[ 7. PRE-LOAD VALIDATION ] ───> Validasi Rekonsiliasi Record & Cycle Check
          │
          │ 7. Atomic Load Execution
          ▼
[ 8. LOAD TO TARGET DB ] ───> Transaksi ACID ke Schema Produksi
          │
          │ 8. Post-Load Reconciliation
          ▼
[ 9. POST-LOAD RECONCILIATION ] ───> Verifikasi Data oleh Data Analyst (Ikhsan)
          │
          │ 9. Sign-off & Active
          ▼
[ 10. SIGMA-K PRODUCTION DB ] ───> PostgreSQL Live Master Data
```

---

## 2. Penanganan Kasus Transformasi Kompleks

### A. Dekonstruksi String Denormalisasi `data_kl.list_id_kl`
- **Tantangan:** Tabel legacy `data_kl` menyimpan kumpulan ID instansi sebagai string delimit koma (`"1,2,5,12,30"`).
- **Prosedur Transformasi ETL:**
  1. Script membaca baris kabinet dan mem-parsing string `list_id_kl` menjadi array integer: `[1, 2, 5, 12, 30]`.
  2. Melakukan cross-reference ke tabel pemetaan migrasi instansi `_migration_map_institutions` untuk memperoleh `UUID` target instansi.
  3. Meng-insert baris relasional ke tabel target `cabinet_memberships`:
     `INSERT INTO cabinet_memberships (id, cabinet_period_id, institution_id, category, joined_date, is_active_in_cabinet) VALUES ...`
  4. Mencegah duplikasi melalui konstrain unik `(cabinet_period_id, institution_id)`.

### B. Unifikasi Master Instansi K/L dan Pemda
- **Tantangan:** Data K/L tersimpan di `tb_instansi`, sementara data Pemda tersimpan di `data_pemda`.
- **Prosedur Transformasi ETL:**
  1. `tb_instansi` dimigrasikan ke `institutions` dengan tipe `KEMENTERIAN_KOORDINATOR`, `KEMENTERIAN_TEKNIS`, `LPNK`, atau `LNS`.
  2. `data_pemda` dimigrasikan ke `institutions` dengan tipe `PEMDA_PROVINSI`, `PEMDA_KABUPATEN`, atau `PEMDA_KOTA`.
  3. Relasi wilayah pada Pemda dipetakan ke master `regions.id`.

### C. Pemulihan Unit Kerja Orphan pada Pohon Organisasi (`tbl_ref_instansi_org`)
- **Tantangan:** ~1.2% record unit kerja memiliki `parent_id` menunjuk ke ID unit kerja yang sudah tidak eksis.
- **Prosedur Transformasi ETL:**
  1. Script mendeteksi unit yang `parent_id`-nya tidak ditemukan di tabel `organization_units`.
  2. Unit orphan secara otomatis di-reparent ke **Root Unit Instansi** (Unit Pimpinan Tertinggi) dan dicatat dalam log karantina migrasi (*Migration Quarantine Register*) untuk ditinjau oleh Data Analyst.

---

## 3. Strategi Karantina & Rollback (Quarantine & Rollback Protocol)

1. **Quarantine Table (`_migration_quarantine`):**
   Record yang gagal memenuhi standar validasi kualitas (misal: nama instansi kosong, kode duplikat ilegal) **TIDAK DIBUANG**, melainkan di-copy ke tabel karantina beserta alasan kegagalannya untuk dianalisis oleh Ikhsan.
2. **Rollback Protocol:**
   - Seluruh proses migrasi dijalankan dalam single script idempotency berbasis transaksi database.
   - Jika terjadi kegagalan fatal pada Quality Gate (GATE-01 s.d GATE-10), transaksi di-*rollback* penuh ke kondisi awal sebelum load dimulai.
