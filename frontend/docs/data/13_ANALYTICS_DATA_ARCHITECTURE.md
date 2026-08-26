# 13. ANALYTICS DATA PIPELINE ARCHITECTURE: SIGMA-K

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Data Architect & Solutions Architect  
> **Kolaborator:** Ikhsan (Data Analyst)  

Dokumen ini mendefinisikan arsitektur pipeline data analitik (*Analytics Data Pipeline Architecture*) yang dirancang secara efisien (*minimum necessary architecture*) untuk melayani dashboard eksekutif SESDEP dan eksplorasi data tanpa membebani transaksi operasional harian.

---

## 1. Alur Pemrosesan Beban Kerja Analitik (Workload Separation Flow)

```
[ OPERATIONAL OLTP DATABASE (PostgreSQL 16) ]
  - Transaksi Master Instansi, Draf Pengajuan, Persetujuan Berkas
                      │
                      │ 1. Asynchronous Event-Driven Trigger / Scheduled Cron
                      ▼
[ READ MODEL & MATERIALIZED VIEWS LAYER ]
  - `mv_cabinet_composition_summary` (Agregasi 48 K/L Kabinet Merah Putih)
  - `mv_asn_posture_aggregates` (Agregasi Postur Eselon dari v_postur_asn)
  - `mv_echelon_distribution` (Rasio Struktural vs Fungsional)
                      │
                      │ 2. Caching & Fast Retrieval
                      ▼
[ REDIS ANALYTICS KEY CACHE (TTL: 1 Jam) ]
  - Cache JSON hasil agregasi dashboard untuk akses $< 30$ ms
                      │
                      │ 3. API Consumption
                      ▼
[ EXECUTIVE DASHBOARD (SESDEP) & ANALYST WORKBENCH (Ikhsan) ]
```

---

## 2. Definisi Materialized Views Kunci

```sql
-- Blueprint SQL: Materialized View Komposisi Kabinet & Status Kelembagaan
CREATE MATERIALIZED VIEW mv_cabinet_composition_summary AS
SELECT 
    c.id AS cabinet_id,
    c.name AS cabinet_name,
    cp.id AS period_id,
    cp.start_date,
    cp.end_date,
    cm.category,
    COUNT(i.id) AS total_institutions,
    COUNT(ou.id) AS total_organization_units
FROM cabinets c
JOIN cabinet_periods cp ON cp.cabinet_id = c.id
JOIN cabinet_memberships cm ON cm.cabinet_period_id = cp.id
JOIN institutions i ON i.id = cm.institution_id
LEFT JOIN organization_units ou ON ou.institution_id = i.id AND ou.deleted_at IS NULL
WHERE i.deleted_at IS NULL AND cm.is_active_in_cabinet = TRUE
GROUP BY c.id, c.name, cp.id, cp.start_date, cp.end_date, cm.category;

-- Indeks unik untuk memungkinkan REFRESH CONCURRENTLY
CREATE UNIQUE INDEX idx_mv_cab_comp_pk ON mv_cabinet_composition_summary (cabinet_id, period_id, category);
```

---

## 3. Strategi Refresh & Skalabilitas Masa Depan
1. **Refresh Policy:** Materialized views di-refresh secara periodik setiap malam via Cron Job, atau secara instan (*concurrently*) saat terjadi persetujuan (*approval*) perubahan data instansi besar.
2. **Kapan Beralih ke Data Warehouse (DWH) Terpisah?**
   Arsitektur ini dapat ditingkatkan ke pipeline ETL eksternal (PostgreSQL $\rightarrow$ DuckDB / ClickHouse / BigQuery) di masa depan jika jumlah rekaman data transaksi melebihi 10 juta baris atau saat integrasi log presensi jutaan ASN nasional diberlakukan.
