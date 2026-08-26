# 20. LEGACY DATA MIGRATION ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Database Architect & Data Engineer  
> **Kolaborator Utama:** Ikhsan (Data Analyst)  
> **Prinsip Utama:** *Zero Data Loss on Valid Records, Zero Copy-Paste on Scratch Tables*.

Dokumen ini mendefinisikan strategi dan arsitektur pipeline migrasi data (*ETL Migration Pipeline*) untuk mentransformasikan data valid dari database legacy `eskld` ke skema produksi SIGMA-K.

---

## 1. Diagram Pipeline Migrasi Data (ETL Architecture)

```
[ DATABASE LEGACY: `eskld` (MySQL) ]
  - `tb_instansi`, `data_pemda`, `data_kl`, `tbl_ref_instansi_org`, `ref_eselon`
                 │
                 │ 1. Extract & Audit Dump
                 ▼
+-----------------------------------------------------------------------------------+
| STEP 1: DATA PROFILING & AUDIT (Data Analyst Ikhsan)                              |
| - Identifikasi data duplikat, null value ilegal, orphan foreign keys.             |
| - Analisis isi tabel scratch: `data_map_*` dan `data_map_yudhi_latest`.           |
+-----------------------------------------------------------------------------------+
                 │
                 │ 2. Cleansing Rules
                 ▼
+-----------------------------------------------------------------------------------+
| STEP 2: DATA CLEANSING & NORMALIZATION (Staging Sandbox)                          |
| - De-duplikasi nama kementerian/pemda.                                            |
| - Dekonstruksi string delimit `data_kl.list_id_kl = "1,2,5"` menjadi baris relasi.|
| - Perbaikan relasi `parent_id` yang putus (*orphan units*).                       |
+-----------------------------------------------------------------------------------+
                 │
                 │ 3. Target Schema Mapping
                 ▼
+-----------------------------------------------------------------------------------+
| STEP 3: DATA TRANSFORMATION (ETL Transformation Scripts)                          |
| - Generate UUID Primary Keys untuk entitas master.                                |
| - Pemetaan tipe instansi: K/L Pusat vs Pemda Provinsi vs Pemda Kab/Kota.          |
| - Pembentukan relasi `Cabinet` -> `CabinetPeriod` -> `CabinetMembership`.         |
+-----------------------------------------------------------------------------------+
                 │
                 │ 4. Dry-Run & Reconciliation
                 ▼
+-----------------------------------------------------------------------------------+
| STEP 4: RECONCILIATION & INTEGRITY VALIDATION                                     |
| - Validasi Total Record: Jumlah instansi sebelum vs sesudah wajib sinkron.        |
| - Validasi pohon hierarki: Bebas dari siklus melingkar (*zero cycle*).           |
+-----------------------------------------------------------------------------------+
                 │
                 │ 5. Atomic Load
                 ▼
[ SIGMA-K MASTER DATA (PostgreSQL 16) ]
```

---

## 2. Prinsip & Kebijakan Migrasi Data
1. **Zero Copy-Paste on Scratch Tables:** Tabel temporer ad-hoc `data_map`, `data_map_pemda`, `data_map_pemda_baru`, dan `data_map_yudhi_latest` diekstraksi maknanya untuk melengkapi data master, kemudian tabel-tabel tersebut **DITINGGALKAN / TIDAK DIMIGRASIKAN** ke skema baru.
2. **Normalisasi Menyeluruh Data Kabinet:** Kolom string `list_id_kl` diubah menjadi baris-baris relasional formal pada tabel `cabinet_memberships`.
3. **Preservasi Identitas Historis:** Tanggal pembentukan instansi dan dasar hukum yang tercatat di legacy tetap dipertahankan pada atribut profil instansi.
4. **Dry-Run & Rollback Capability:** Seluruh proses migrasi dijalankan dalam mode uji coba (*Dry-Run*) terlebih dahulu pada environment Staging sebelum dieksekusi di database produksi.
