# 18. COLLABORATION WORK PLAN: BERLIN $\times$ IKHSAN

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Tim Pelaksana:** Berlin (Lead Software Architect) & Ikhsan (Data Analyst)  
> **Supervisor:** Pak Sigit (Mentor / Lead Data Analyst) & Kak Nabila (Mentor)  

Dokumen ini mendefinisikan pembagian tugas (*Work Breakdown & Accountability Matrix*) antara peran Software Engineering dan Data Analytics pada project SIGMA-K selama periode magang.

---

## 1. Matriks Pembagian Peran & Tanggung Jawab (RACI Matrix)

```
+-----------------------------------------------------------------------------------+
|                        RACI MATRIX: ENGINEERING vs DATA ANALYTICS                 |
+-----------------------------------------------------------------------------------+
| R = Responsible (Pelaksana Utama), A = Accountable (Penanggung Jawab),            |
| C = Consulted (Pemberi Masukan), I = Informed (Penerima Informasi)               |
+-----------------------------------------------------------------------------------+
```

| Area Kerja / Aktivitas (*Workstream*) | Berlin (Lead Full-Stack / Architect) | Ikhsan (Data Analyst / Governance) | Pak Sigit / Kak Nabila (Mentors) |
|---|:---:|:---:|:---:|
| **Desain Skema Relasional Target & Konstrain DB** | **A / R** | C | C |
| **Penyusunan Kamus Data & Glosarium Bisnis** | C | **A / R** | C |
| **Audit Kualitas Data (*Data Profiling*) Legacy** | C | **A / R** | C |
| **Pemetaan Sumber ke Target (*Source-to-Target Map*)** | **R (Shared)** | **R (Shared)** | A |
| **Validasi 48 Kementerian Kabinet Merah Putih** | **R (Shared)** | **R (Shared)** | A |
| **Pemodelan Silsilah Pemecahan Kementerian (*Lineage*)**| **R (Shared)** | **R (Shared)** | A |
| **Rekayasa Pipeline ETL & Skrip Migrasi Otomatis** | **A / R** | C | I |
| **Validasi Hasil Migrasi (*Reconciliation Quality Gates*)**| C | **A / R** | A |
| **Formulasi Formula Matematis KPI & Metrik SESDEP** | C | **A / R** | A |
| **Desain View Analitik & Materialized Views** | **A / R** | C | C |
| **Keamanan Data, RBAC Scoping, & BOLA Defense** | **A / R** | I | C |
| **Pengujian Kueri Pohon Rekursif & Cycle Guard** | **A / R** | C | I |

---

## 2. Ritme Kerja & Sinkronisasi Harian (Daily Collaboration Rhythm)
1. **Standup Singkat Pagi (15 Menit):** Membahas isu temuan anomali data legacy `eskld` (Ikhsan) dan penyesuaian skema target (Berlin).
2. **Protokol Perubahan Kolom Skema Data:**
   - Ikhsan mengajukan usulan penambahan/perubahan metadata field.
   - Berlin memvalidasi dari sisi indeks database, tipe data PostgreSQL, dan performa DTO API.
   - Perubahan disahkan bersama sebelum dicatat di [03_DATA_DICTIONARY.md](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/data/03_DATA_DICTIONARY.md).
3. **Validasi Bersama Pra-Demo SESDEP:** Ikhsan memvalidasi kebenaran angka agregasi pada dashboard prototype yang dibangun Berlin untuk memastikan tidak ada data fiktif saat dipaparkan di hadapan pimpinan.
