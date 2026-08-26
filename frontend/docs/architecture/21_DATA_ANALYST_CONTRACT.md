# 21. DATA ANALYST COLLABORATION CONTRACT: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Pihak Terikat:** Berlin (Lead Full-Stack Engineer) & Ikhsan (Data Analyst)  
> **Reviewer:** Pak Sigit (Mentor / Lead Data Analyst) & Kak Nabila (Mentor)  

Dokumen ini menetapkan kontrak kolaborasi formal (*Collaboration Boundary & Data Contract*) antara disiplin Software Engineering dan Data Analytics pada project SIGMA-K.

---

## 1. Pembagian Batas Tanggung Jawab (Responsibility Boundaries)

```
+-----------------------------------------------------------------------------------+
|               FULL-STACK / SOFTWARE ENGINEERING (Berlin)                          |
+-----------------------------------------------------------------------------------+
| - Mendesain arsitektur database, relasi foreign key, indeks, dan transaksi ACID.  |
| - Membangun backend Core API, WebSocket realtime gateway, dan RBAC security.      |
| - Mengembangkan komponen antarmuka web, Org Chart Canvas, dan dashboard frontend. |
| - Menyediakan pipeline ETL dan skrip eksekusi migrasi data otomatis.              |
| - Menyediakan akses database Read-Only yang aman untuk Data Analyst.              |
+-----------------------------------------------------------------------------------+
                                         ↕ (DATA CONTRACT & API INTERFACE)
+-----------------------------------------------------------------------------------+
|                         DATA ANALYST TEAM (Ikhsan)                                |
+-----------------------------------------------------------------------------------+
| - Melakukan audit kualitas data (*data profiling*) pada database legacy `eskld`.  |
| - Menyusun Kamus Data (*Data Dictionary*) resmi untuk atribut kelembagaan.        |
| - Memetakan silsilah transisi kementerian pada Kabinet Merah Putih (48 K/L).      |
| - Merumuskan formula matematis metrik postur ASN (`v_postur_asn`) & rasio eselon. |
| - Memvalidasi kebenaran data hasil migrasi (*Reconciliation Testing*).            |
+-----------------------------------------------------------------------------------+
```

---

## 2. Spesifikasi Kontrak Antarmuka Data (Data Contracts)

### A. Kontrak Ekstraksi Data Legacy (ETL Ingestion Contract)
- **Input dari Data Analyst:** Spreadsheet/JSON Pemetaan Legacy (`legacy_mapping_v1.json`) yang memuat:
  - Daftar ID `tb_instansi` yang valid vs duplikat.
  - Parsing string delimit `data_kl.list_id_kl` menjadi daftar relasi instansi-kabinet.
  - Matriks silsilah pemecahan/penggabungan kementerian (`institution_lineages`).
- **Output dari Engineering:** Eksekusi script import dan penyajian data bersih pada database staging PostgreSQL.

### B. Kontrak Metrik Dashboard Eksekutif (Analytics View Contract)
- **Input dari Data Analyst:** Spesifikasi agregasi data mencakup rumus persentase keterisian jabatan, rasio perampingan eselon, dan agregasi postur aparatur per instansi.
- **Output dari Engineering:** Pembuatan *Materialized View* (`mv_asn_posture_aggregates`, `mv_echelon_distribution`) dan REST API endpoint `/api/v1/analytics/posture-summary` untuk dikonsumsi oleh komponen chart dashboard.

---

## 3. Protokol Perubahan Skema Data (Schema Change Protocol)
1. Setiap usulan penambahan kolom atau perubahan tipe data master **wajib dikomunikasikan** antara Data Analyst dan Lead Engineer minimal 1 hari kerja sebelum skema Prisma dimodifikasi.
2. Seluruh perubahan model data dicatat dalam dokumen *Data Dictionary Changelog*.
