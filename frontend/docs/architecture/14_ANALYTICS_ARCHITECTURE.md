# 14. ANALYTICS & EXECUTIVE INTELLIGENCE ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Data Architect & Solutions Architect  
> **Kolaborator Utama:** Ikhsan (Data Analyst) & Pak Sigit (Mentor Lead Data Analyst)  
> **Kebutuhan Terkait:** [REQ-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-ANA-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [DATA_ANALYST_HANDOFF.md](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/DATA_ANALYST_HANDOFF.md)  

Dokumen ini mendefinisikan arsitektur analitik (*Analytics Architecture*) yang memisahkan beban kerja transaksional (OLTP) dari komputasi analitik (OLAP/Read Models) agar operasional sistem tetap stabil.

---

## 1. Strategi Pemisahan Beban Transaksional vs Analitik

```
+-----------------------------------------------------------------------------------+
|                        TRANSACTIONAL (OLTP) WORKLOAD                              |
|  [ Primary PostgreSQL 16 ]                                                        |
|  - Write Operations: Master CRUD, Drafts, Ticket Verification, Atomic Approval.   |
+-----------------------------------------------------------------------------------+
                                         │
                                         │ Asynchronous Refresh / Read Replica
                                         ▼
+-----------------------------------------------------------------------------------+
|                        ANALYTICAL (READ MODEL) WORKLOAD                           |
|  [ Materialized Views & Analytical Aggregates ]                                   |
|  - `mv_cabinet_composition_summary` (Statistik K/L per Kabinet)                  |
|  - `mv_asn_posture_aggregates` (Agregasi Postur Eselon dari v_postur_asn)        |
|  - `mv_echelon_distribution` (Distribusi Jabatan Struktural vs Fungsional)        |
+-----------------------------------------------------------------------------------+
                                         │
                                         ▼
+-----------------------------------------------------------------------------------+
|                        CACHE & PRESENTATION TIER                                  |
|  - Redis Analytical Key Cache (TTL: 1 Jam / Refresh on Approval Event)            |
|  - Executive Dashboard (SESDEP) & Data Analyst Workspace (Ikhsan)                 |
+-----------------------------------------------------------------------------------+
```

---

## 2. Mengapa Data Warehouse Khusus (DWH) Belum Diperlukan pada Tahap Ini?
1. **Volume Data Terukur:** Total data instansi (~600 instansi) dan ribuan unit kerja dapat dianalisis dalam fraksi detik pada PostgreSQL menggunakan *Materialized Views* dan indeks yang tepat.
2. **Efisiensi Tim Magang:** Membangun pipeline DWH terpisah (Snowflake/BigQuery/ClickHouse) akan menambah kompleksitas infrastruktur dan biaya yang tidak sebanding (*premature over-engineering*).
3. **Pola Materialized Views + Redis Cache:** Menyediakan performa baca instan ($< 50$ ms) untuk dashboard pimpinan tanpa membebani tabel transaksional.

---

## 3. Re-Engineering View Postur ASN (`v_postur_asn`)

Pada sistem legacy, `v_postur_asn` adalah view database komposit yang lambat dieksekusi. Pada arsitektur SIGMA-K modern:
1. View tersebut didefinisikan ulang sebagai **Materialized View** terindeks:
   `CREATE MATERIALIZED VIEW mv_asn_posture_aggregates AS ...`
2. **Jadwal Refresh Otomatis:**
   - Di-refresh secara berkala (misal setiap malam via Cron Job / BullMQ worker: `REFRESH MATERIALIZED VIEW CONCURRENTLY mv_asn_posture_aggregates;`).
   - Atau dipicu secara instan saat terjadi persetujuan (*approval*) perubahan unit kerja.

---

## 4. Fasilitas Ruang Kerja Data Analyst (Ikhsan's Safe Query Workspace)
- Untuk memfasilitasi eksplorasi data oleh Data Analyst (Ikhsan):
  1. Disediakan kredensial database khusus dengan peran **Read-Only** (`sigma_analyst_readonly`).
  2. Read-Only user ini memiliki *Statement Timeout* maksimal 30 detik untuk mencegah query berat mengunci resource CPU database.
  3. Analis dapat mengintegrasikan tools analitik langsung (Python/Jupyter, DBeaver, Tableau/Metabase) ke database lokal/staging.

---

## 5. Pipeline Ekspor Dataset (Streaming Export Engine)
- Untuk kebutuhan ekspor laporan resmi kementerian (PDF dan Excel Spreadsheet):
  - Backend menggunakan **Streaming Response** untuk file berukuran besar guna mencegah lonjakan memori (*heap out-of-memory*).
  - Format Excel dihasilkan menggunakan library `exceljs`, sedangkan PDF resmi berkop surat KemenPANRB dihasilkan menggunakan template engine `pdfmake` / `puppeteer-core`.
