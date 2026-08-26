# 23. TECHNOLOGY DECISION MATRIX: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  

Dokumen ini menyajikan matriks penilaian keputusan teknologi (*Weighted Technology Decision Matrix*) dengan skala 1-5 berdasarkan kriteria rekayasa perangkat lunak objektif.

---

## 1. Bobot Kriteria Penilaian (*Evaluation Weighting*)

| Kode Kriteria | Nama Kriteria Evaluasi | Bobot (1-5) | Rasional Bobot bagi Project SIGMA-K |
|---|---|:---:|---|
| **K1** | Kesesuaian Tim Magang & Dev Velocity | **5** | Waktu pelaksanaan magang terbatas; kecepatan delivery prototype SESDEP sangat krusial. |
| **K2** | Integritas Data & Konsistensi Transaksional | **5** | Master data kelembagaan kementerian wajib 100% akurat dan ACID compliant. |
| **K3** | Dukungan Komponen UI Kompleks (Org Chart & Diff) | **4** | Kebutuhan rendering kanvas struktur pohon dan komparasi delta data. |
| **K4** | Keamanan, RBAC, & Scoping Multi-Instansi | **4** | Data instansi pemerintah wajib diproteksi dari akses tidak sah. |
| **K5** | Performa Realtime & Responsivitas Sistem | **4** | Target latensi notifikasi realtime $< 1$ detik dan load dashboard $< 2$ detik. |
| **K6** | Portabilitas Kontainer & Kemudahan Deployment | **3** | Harus siap dijalankan di PDN / VM KemenPANRB tanpa cloud lock-in. |
| **K7** | Skalabilitas Jangka Panjang (600+ Instansi) | **3** | Mampu menampung seluruh Pemda di Indonesia di masa depan. |

---

## 2. Matriks Penilaian Tiap Lapisan Teknologi

### A. Frontend Framework Matrix

| Kandidat Teknologi | K1 (x5) | K2 (x5) | K3 (x4) | K4 (x4) | K5 (x4) | K6 (x3) | K7 (x3) | Total Skor (Maks 140) | Peringkat & Keputusan |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Next.js 14+ (React + TS)** | 5 (25) | 5 (25) | 5 (20) | 5 (20) | 5 (20) | 5 (15) | 5 (15) | **140 / 140** | **#1 TERPILIH** |
| **Vite + React (SPA)** | 5 (25) | 4 (20) | 5 (20) | 4 (16) | 4 (16) | 5 (15) | 4 (12) | **124 / 140** | #2 Alternatif |
| **Nuxt 3 (Vue.js + TS)** | 3 (15) | 4 (20) | 3 (12) | 4 (16) | 4 (16) | 5 (15) | 4 (12) | **106 / 140** | #3 Ditolak |
| **Traditional Blade/Twig** | 3 (15) | 4 (20) | 1 (4) | 4 (16) | 2 (8) | 5 (15) | 3 (9) | **87 / 140** | #4 Ditolak |

---

### B. Backend Framework Matrix

| Kandidat Teknologi | K1 (x5) | K2 (x5) | K3 (x4) | K4 (x4) | K5 (x4) | K6 (x3) | K7 (x3) | Total Skor (Maks 140) | Peringkat & Keputusan |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **NestJS 10+ (TypeScript)** | 5 (25) | 5 (25) | 5 (20) | 5 (20) | 5 (20) | 5 (15) | 5 (15) | **140 / 140** | **#1 REKOMENDASI UTAMA** |
| **Laravel 11 (PHP 8.3+)** | 4 (20) | 5 (25) | 4 (16) | 5 (20) | 4 (16) | 5 (15) | 4 (12) | **124 / 140** | #2 Alternatif Kuat |
| **Spring Boot 3 (Java)** | 2 (10) | 5 (25) | 3 (12) | 5 (20) | 5 (20) | 4 (12) | 5 (15) | **114 / 140** | #3 Over-complex untuk tim magang |
| **Express.js (Minimalist)** | 4 (20) | 3 (15) | 3 (12) | 3 (12) | 4 (16) | 5 (15) | 3 (9) | **99 / 140** | #4 Ditolak (Kurang terstruktur) |

---

### C. Database Engine Matrix

| Kandidat Teknologi | K1 (x5) | K2 (x5) | K3 (x4) | K4 (x4) | K5 (x4) | K6 (x3) | K7 (x3) | Total Skor (Maks 140) | Peringkat & Keputusan |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **PostgreSQL 16** | 5 (25) | 5 (25) | 5 (20) | 5 (20) | 5 (20) | 5 (15) | 5 (15) | **140 / 140** | **#1 TERPILIH (Recursive CTE & JSONB)** |
| **MySQL 8.0** | 4 (20) | 4 (20) | 3 (12) | 4 (16) | 4 (16) | 5 (15) | 4 (12) | **111 / 140** | #2 Alternatif Legacy |
| **MongoDB (NoSQL)** | 3 (15) | 2 (10) | 2 (8) | 3 (12) | 4 (16) | 5 (15) | 3 (9) | **85 / 140** | #3 Ditolak (Relasi kaku lemah) |
