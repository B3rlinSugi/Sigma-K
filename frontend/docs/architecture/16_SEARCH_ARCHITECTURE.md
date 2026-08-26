# 16. SEARCH ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Database Engineer  
> **Prinsip:** "Advanced because the problem requires it, not because it looks impressive."

Dokumen ini mengevaluasi kebutuhan kemampuan pencarian teks (*Search Capabilities*) pada sistem SIGMA-K untuk katalog instansi, unit organisasi, dan butir tugas & fungsi.

---

## 1. Evaluasi Komparatif Mesin Pencarian (Search Engines)

| Kriteria Evaluasi | Opsi A: PostgreSQL Full-Text Search (FTS) + `pg_trgm` | Opsi B: Dedicated Elasticsearch / OpenSearch |
|---|---|---|
| **Kesesuaian dengan Volume Data SIGMA-K** | **Sangat Sempurna** (~600 Instansi, ~50.000 Unit Kerja, ~100.000 Tupoksi). | Berlebihan (*Overkill*) untuk volume data puluhan ribu record. |
| **Kebutuhan Resource Server (RAM / CPU)** | **Nol Overhead Tambahan** (Memanfaatkan resource DB yang ada). | Sangat Tinggi (Memerlukan minimal 4-8 GB RAM terpisah per node). |
| **Kompleksitas Sinkronisasi Data** | **Realtime Konsisten (ACID)** — Indeks ter-update seketika. | Kompleks — Memerlukan pipeline sinkronisasi (CDC / Debezium / Logstash). |
| **Pencarian Parsial & Typo Tolerance** | **Sangat Baik** via ekstensi `pg_trgm` (Trigram Similarity). | Sangat Baik (Fuzzy matching). |
| **Kecepatan Pencarian (Latency)** | Sangat Cepat ($< 10$ ms dengan GIN Index). | Sangat Cepat ($< 5$ ms). |
| **Beban Operasional Tim Magang** | **Sangat Rendah** (Native PostgreSQL). | Sangat Tinggi (Maintenance cluster, shard, heap dump). |

---

## 2. Rekomendasi Resmi: PostgreSQL Native Full-Text Search + `pg_trgm`

### Mengapa Dedicated Search Engine (Elasticsearch) Ditolak pada Tahap Ini?
Mengoperasikan cluster Elasticsearch untuk data skala kementerian (~600 instansi) adalah contoh klasik *premature over-engineering*. Hal ini hanya akan menghabiskan memori server dan membebani tim magang dengan isu sinkronisasi data (*dual-write problem*).

### Strategi Pencarian Terpilih:
1. **Pencarian Nama Instansi & Kode (Instant Search):**
   - Memanfaatkan ekstensi PostgreSQL `pg_trgm` dengan indeks GIN:
     `CREATE INDEX idx_inst_name_trgm ON institutions USING GIN (name gin_trgm_ops);`
   - Mendukung pencarian teks parsial dan toleransi salah ketik (*fuzzy search / typo tolerance*) dengan performa instan.
2. **Pencarian Butir Tugas & Fungsi:**
   - Memanfaatkan kolom komposit `tsvector` dan indeks `GIN`:
     `CREATE INDEX idx_tupoksi_fts ON tugas_fungsi USING GIN (to_tsvector('indonesian', content_text));`
   - Memungkinkan pencarian kata kunci fungsi secara cepat pada seluruh kementerian.

---

## 3. Batas Ambang Evaluasi Masa Depan (*Future Threshold*)
Dedicated Search Engine (Elasticsearch / OpenSearch / Meilisearch) hanya akan dipertimbangkan kembali jika:
1. Sistem telah menampung jutaan dokumen regulasi teks penuh di seluruh Indonesia.
2. Fitur analisis semantik NLP tingkat lanjut (REQ-030) resmi diwajibkan oleh pimpinan pada Future Scope.
