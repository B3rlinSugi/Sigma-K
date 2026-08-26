# LEGACY SYSTEM INVENTORY: E-SKLD / SIGMA-K LEGACY

> **Status:** DISCOVERY BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  
> **Prinsip Utama:** Legacy Repository & Database adalah *REFERENCE ONLY*, bukan source code final yang disalin mentah.

---

## 1. Ringkasan Sistem Legacy
Sistem legacy dikenal dengan nama **E-SKLD** atau **SIGMA-K Legacy**, yang dikembangkan untuk mendata instansi pemerintah, struktur organisasi, dan rekapitulasi postur ASN.

---

## 2. Inventaris Database Legacy (`eskld`)
Database legacy yang menjadi rujukan bernama `eskld`. Berdasarkan discovery awal, tabel dan view yang teridentifikasi dikelompokkan sebagai berikut:

### A. Tabel Utama (Core Tables)
| Nama Tabel | Peran Bisnis yang Diketahui | Bukti / Atribut Kunci | Status Evaluasi |
|---|---|---|---|
| `tb_instansi` | Master data instansi pemerintah. | ID instansi, nama instansi, kode instansi, jenis instansi. | **Candidate Core Entity** (Perlu standardisasi & perapihan skema). |
| `tb_jenis_instansi` | Klasifikasi jenis instansi (Kementerian, LPNK, LNS, Pemda, dll.). | ID jenis, nama jenis/kategori instansi. | **Candidate Reference Entity** (Valid, perlu pembersihan kode). |
| `tb_wilayah` | Data referensi wilayah administratif (Provinsi, Kab/Kota). | Kode wilayah, nama wilayah. | **Candidate Reference Entity** (Sinkronisasi dengan standar nasional). |
| `ref_eselon` | Referensi tingkatan eselon struktural. | ID eselon, nama eselon (I.a, I.b, II.a, dll.), level. | **Candidate Reference Entity** (Perlu update jabatan fungsional). |
| `data_kl` | Data kementerian/lembaga dan representasi kabinet. | `tahun`, `is_active`, `list_id_kl` (string delimit ID instansi). | **Needs Redesign** (Model kabinet denormalized, perlu dirombak). |
| `data_pemda` | Data spesifik instansi pemerintah daerah. | ID pemda, nama daerah, relasi wilayah. | **Needs Normalization** (Sebaiknya menyatu dalam skema Master Instansi yang terpadu). |
| `tbl_ref_instansi_org` | Data struktur bagan organisasi instansi. | ID unit kerja, ID instansi, `parent_id`, nama unit. | **Candidate Core Entity** (Perlu pembersihan integritas hierarki). |
| `users` | Akun pengguna sistem legacy. | Username, password hash, role dasar, status aktif. | **Needs Modern Redesign** (Kebutuhan RBAC, audit, security modern). |

### B. Tabel Ad-Hoc / Scratch Mapping Tables
| Nama Tabel | Indikasi / Dugaan Fungsi | Analisis Masalah Arsitektur | Status Rekomendasi |
|---|---|---|---|
| `data_map` | Pemetaan manual instansi/data. | Tabel mapping ad-hoc tanpa constraint relational formal. | **To Be Deprecated / Migrated cleanly** |
| `data_map_pemda` | Pemetaan tambahan untuk data Pemda. | Redundansi pemetaan relasi Pemda. | **To Be Deprecated / Consolidated** |
| `data_map_pemda_baru` | Pemetaan revisi/tambahan Pemda baru. | Scratch table hasil penyesuaian pemekaran/perubahan manual. | **To Be Deprecated / Consolidated** |
| `data_map_yudhi_latest` | Pemetaan temporer perorangan/analis (*ad-hoc table*). | *Anti-pattern* di production DB (tabel penamaan personal). | **To Be Deprecated & Cleansed** |

> [!WARNING]
> Keberadaan tabel `data_map_*` dan `data_map_yudhi_latest` menunjukkan bahwa pada sistem legacy terdapat keterbatasan model data sehingga dilakukan penyesuaian manual secara ad-hoc. Pada SIGMA-K modern, seluruh relasi pemetaan harus diselesaikan melalui skema relasional yang formal, bersih, dan melalui migration engine terkontrol.

---

### C. Database Views (Agregasi & Postur)
| Nama View | Fungsi yang Diketahui | Analisis Penggunaan | Status Evaluasi |
|---|---|---|---|
| `v_postur_asn` | View komposit untuk menampilkan data postur ASN per instansi/unit. | Menggabungkan data instansi dengan ringkasan jumlah/kondisi aparatur. | **Candidate Analytics View** (Perlu dikaji bersama Data Analyst Ikhsan). |
| `VIEW rekap/dashboard` | Beberapa database view untuk menghitung jumlah instansi, rekapitulasi eselon, dll. | View agregat untuk mempercepat query dashboard legacy. | **Candidate Analytics Optimization** (Perlu dirancang ulang pada query layer / caching layer). |

---

## 3. Evaluasi Konsep-Konsep Kunci pada Legacy

### A. Konsep Kabinet (`data_kl`)
- **Mekanisme Legacy:** Informasi kabinet tersimpan pada tabel `data_kl` dengan kolom `tahun`, `is_active`, dan `list_id_kl`.
- **Kelemahan Arsitektural:** Kolom `list_id_kl` menyimpan daftar ID instansi sebagai string teks (misal: `"1,2,5,12,30"`). Pendekatan ini melanggar kaidah normalisasi database (1NF), menyulitkan query relasional (join), tidak mendukung indexing yang efisien, dan rawan integritas data rusak (*data corruption*).
- **Arah Perbaikan:** Membentuk tabel first-class: `Cabinets`, `CabinetPeriods`, dan `CabinetMemberships` yang berelasi foreign key formal.

### B. Konsep Struktur Organisasi (`tbl_ref_instansi_org`)
- **Mekanisme Legacy:** Hierarki organisasi dikelola menggunakan kolom `parent_id` (Adjacency List Model).
- **Evaluasi:** Konsep `parent_id` adalah standar yang baik untuk hierarki sederhana, namun memerlukan proteksi integritas:
  - Mencegah siklus berulang (*circular dependency*, misal A parent B, B parent A).
  - Penanganan cascade delete / soft delete jika unit atasan dinonaktifkan.
  - Kebutuhan visualisasi org chart modern yang responsif.

### C. Konsep Pemerintahan Daerah (`data_pemda` & `tb_wilayah`)
- **Mekanisme Legacy:** Data Pemda terpisah antara master instansi, `data_pemda`, dan tabel-tabel pemetaan `data_map_pemda*`.
- **Kelemahan Arsitektural:** Terjadi fragmentasi data instansi pusat vs daerah.
- **Arah Perbaikan:** Menyatukan konsep instansi di bawah satu entitas `Institution` dengan klasifikasi tipe instansi yang jelas (`PUSAT_KEMENTERIAN`, `PUSAT_LPNK`, `PUSAT_LNS`, `PEMDA_PROVINSI`, `PEMDA_KABUPATEN`, `PEMDA_KOTA`), serta relasi wilayah geografis yang tersandarisasi.

### D. Konsep Referensi Eselon (`ref_eselon`)
- **Mekanisme Legacy:** Tabel `ref_eselon` mendata klasifikasi eselon konvensional.
- **Evaluasi:** Perlu penyesuaian dengan kebijakan reformasi birokrasi terkini (penyetaraan jabatan fungsional) agar relevan dengan kondisi birokrasi saat ini.

### E. Konsep Otentikasi & Pengguna (`users`)
- **Mekanisme Legacy:** Autentikasi sederhana berbasis tabel `users`.
- **Kelemahan Arsitektural:** Belum mendukung fine-grained permissions, belum terikat secara ketat dengan scope instansi pengguna, dan belum memiliki audit logging otomatis.

---

## 4. Known Facts vs Unknown/TBD

### Known Facts
1. Database legacy `eskld` memiliki data riil instansi, wilayah, eselon, dan hierarki organisasi yang valid untuk dijadikan rujukan data.
2. Mekanisme kabinet legacy tidak mampu menangani pencatatan sejarah perubahan struktur secara elegan tanpa modifikasi struktur data.
3. Tabel-tabel `data_map_*` adalah artefak ad-hoc yang harus dibersihkan pada sistem baru.

### Unknown / TBD
1. `TBD-LEG-001`: Skema detail DDL lengkap dari database `eskld` (tipe data persis, nullability, character set).
2. `TBD-LEG-002`: Formula kalkulasi eksak di balik view `v_postur_asn` dan sumber integrasi data dasarnya.
3. `TBD-LEG-003`: Jumlah total record aktif pada `tb_instansi`, `tbl_ref_instansi_org`, dan `data_pemda` yang siap dimigrasikan.
4. `TBD-LEG-004`: Logika enkripsi password lama pada tabel `users` (apakah MD5, Bcrypt, atau plain hash legacy).
