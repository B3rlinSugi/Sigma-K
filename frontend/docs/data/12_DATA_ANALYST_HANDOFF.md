# 12. DATA ANALYST HANDOFF PACKAGE: BERLIN $\rightarrow$ IKHSAN

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Pemberi Tugas:** Berlin (Lead Software Architect & Database Engineer)  
> **Penerima Tugas:** Ikhsan (Data Analyst / Data Governance)  
> **Supervisor:** Pak Sigit (Mentor / Lead Data Analyst) & Kak Nabila (Mentor)  

Dokumen ini merupakan **Paket Serah Terima Resmi (*Official Handoff Package*)** data kelembagaan dari tim Software Engineering kepada Data Analyst (Ikhsan) untuk memandu pemodelan analitik, formulasi KPI pimpinan, dan validasi data migrasi.

---

## 1. Inventaris Dataset Sumber & Target untuk Data Analyst

```
+-----------------------------------------------------------------------------------+
|                        DATASET SERAH TERIMA UNTUK IKHSAN                          |
+-----------------------------------------------------------------------------------+
| 1. DATASET SUMBER LEGACY:                                                         |
|    - `tb_instansi` (K/L Pusat), `data_pemda` (Pemda 548 wilayah)                  |
|    - `data_kl` (String delimit list_id_kl untuk diuraikan)                        |
|    - `tbl_ref_instansi_org` (Bagan hierarki unit kerja & eselon)                  |
|    - `data_map_*` (Data scratch pemetaan daerah & K/L)                            |
|                                                                                   |
| 2. DATASET TARGET BERSIH (SIGMA-K):                                               |
|    - `institutions` & `institution_profiles` (Master terpadu)                     |
|    - `cabinets`, `cabinet_periods`, `cabinet_memberships` (Kabinet Merah Putih)   |
|    - `institution_lineages` (Graf pemecahan Kemendikbudristek & Kemenko Pangan)   |
|    - `organization_units` (Pohon unit kerja bebas cycle)                          |
+-----------------------------------------------------------------------------------+
```

---

## 2. Usulan Metrik & Indikator Kinerja Utama (PROPOSED KPIs FOR SESDEP)

> [!NOTE]
> Seluruh KPI di bawah ini berstatus **`PROPOSED KPI`** dan membutuhkan perumusan matematis mendalam serta validasi oleh Ikhsan bersama Pak Sigit.

1. **`KPI-01` (Rasio Perampingan Struktur Pasca-Delayering):**
   - *Formula Konseptual:* $\frac{\text{Jumlah Jabatan Fungsional}}{\text{Total Unit Organisasi (Struktural + Fungsional)}} \times 100\%$
   - *Tujuan Bisnis:* Mengukur efektivitas penyederhanaan birokrasi eselon III & IV di kementerian/lembaga.
2. **`KPI-02` (Indeks Kesiapan Kelembagaan Kabinet Merah Putih):**
   - *Formula Konseptual:* $\frac{\text{Kementerian dengan Struktur & Tupoksi Lengkap (48 K/L)}}{\text{Total Kementerian Kabinet Merah Putih (48)}} \times 100\%$
   - *Tujuan Bisnis:* Menyajikan progres pembentukan struktur birokrasi baru di hadapan SESDEP.
3. **`KPI-03` (Kecepatan Penyelesaian Tiket Usulan Perubahan):**
   - *Formula Konseptual:* $\text{Rata-rata Durasi Hari }(\text{Waktu Disetujui} - \text{Waktu Diajukan})$.
   - *Tujuan Bisnis:* Mengukur efisiensi kerja tim verifikator KemenPANRB.

---

## 3. Desain Model Dimensi & Fakta (Dimensional Modeling)

- **Tabel Fakta (*Fact Tables*):**
  - `fact_asn_posture_snapshot` (Jumlah PNS, PPPK, Eselon I s.d IV per instansi per kuartal).
  - `fact_submission_turnaround` (Durasi waktu per tahapan verifikasi).
- **Tabel Dimensi (*Dimension Tables*):**
  - `dim_institution` (SCD Type 2: Melacak perubahan nomenklatur kementerian).
  - `dim_cabinet_period` (Era pemerintahan).
  - `dim_region` (Provinsi, Kabupaten, Kota).
  - `dim_echelon_level` (Tingkatan eselon struktural).

---

## 4. Akses Ruang Kerja Analitik Aman (Safe Read-Only Access)
- Tim Engineering menyediakan kredensial database khusus staging untuk Ikhsan:
  - **User:** `sigma_analyst_readonly`
  - **Hak Akses:** `SELECT` on all tables, views, and materialized views.
  - **Statement Timeout:** 30 detik (mencegah kueri berat mengunci server).
  - **Kandidat Materialized Views:** `mv_asn_posture_aggregates`, `mv_cabinet_composition_summary`, `mv_echelon_distribution`.

---

## 5. Pertanyaan Kunci yang Membutuhkan Validasi Analitik Ikhsan
1. *Validasi 48 K/L Kabinet Merah Putih:* Apakah seluruh 48 kementerian baru telah memiliki pemetaan silsilah (*lineage*) lengkap dari kementerian pendahulunya?
2. *Cleansing Kode Wilayah:* Apakah pemetaan wilayah pada `data_pemda` dan `data_map_pemda_baru` sudah 100% sinkron dengan standar kode wilayah Kemendagri 2024?
3. *Formula Agregasi Postur ASN:* Apakah rumus perhitungan `v_postur_asn` legacy masih relevan pasca kebijakan penataan tenaga honorer?
