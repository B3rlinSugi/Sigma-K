# 01. LEGACY DATABASE INVENTORY: `eskld`

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Database Sumber:** `eskld` (Legacy MySQL Database)  
> **Peran Penulis:** Senior Data Architect & Database Engineer  
> **Prinsip Utama:** Database legacy adalah **REFERENCE ONLY / LEGACY SOURCE OF TRUTH**. Tidak ada penghapusan, alter table, atau drop table pada database legacy.

---

## 1. Ikhtisar Database Legacy (`eskld`)

- **Nama Database:** `eskld` (Elektronik Struktur Kelembagaan dan Layanan Daerah / SIGMA-K Legacy)
- **Engine Database:** MySQL (InnoDB / MyISAM legacy mixture)
- **Karakteristik Skema:** Skema warisan dengan campuran tabel master, tabel transaksional dasar, tabel pemetaan ad-hoc (*scratch tables*), dan database views agregat.
- **Batasan & Temuan Teknis:**
  1. *Ketiadaan Foreign Key Constraints:* Sebagian besar relasi tabel tidak dilindungi FK formal di tingkat basis data, melainkan hanya dihandle pada level kode aplikasi lama.
  2. *Denormalisasi Kolom Kabinet:* Kolom `data_kl.list_id_kl` menyimpan daftar ID instansi sebagai teks delimit koma (melanggar 1NF).
  3. *Tabel Duplikasi & Scratch Tables:* Ditemukan tabel-tabel pemetaan perorangan (`data_map_yudhi_latest`) dan tabel pemetaan sementara (`data_map_pemda_baru`).
  4. *Struktur Hierarki Tanpa Cycle Guard:* Tabel `tbl_ref_instansi_org` mengandalkan `parent_id` tanpa batasan siklus (*circular reference*).

---

## 2. Inventaris Lengkap Tabel Database Legacy

| Table Name | Purpose / Makna Bisnis | Primary Key | Foreign Keys (Logical) | Approx Rows | Storage Engine | Evaluasi Status | Rationale & Status Detail |
|---|---|---|---|:---:|:---:|:---:|---|
| `tb_instansi` | Master data instansi pemerintah pusat & kementerian/lembaga. | `id` (INT Auto) | `id_jenis` $\rightarrow$ `tb_jenis_instansi.id`, `id_wilayah` $\rightarrow$ `tb_wilayah.id` | ~150-300 | InnoDB | **TRANSFORM** | Menjadi entitas inti `Institution`. Perlu cleansing kodefikasi unik dan integrasi profil. |
| `tb_jenis_instansi` | Master kategori tipe instansi (Kementerian, LPNK, LNS, Pemda, dll). | `id` (INT Auto) | None | ~10-20 | InnoDB | **TRANSFORM** | Menjadi referensi `InstitutionType` dengan standarisasi kode enum/master baku. |
| `tb_wilayah` | Referensi wilayah administratif geografis (Provinsi, Kab/Kota). | `id` (INT Auto) | `parent_id` (Provinsi $\rightarrow$ Kab/Kota) | ~550 | InnoDB | **TRANSFORM** | Menjadi referensi `Region` berstandar kode wilayah nasional BPS / Kemendagri. |
| `ref_eselon` | Referensi tingkatan jabatan struktural/eselon birokrasi. | `id` (INT Auto) | None | ~15 | InnoDB | **TRANSFORM** | Menjadi referensi `PositionLevel` disesuaikan dengan nomenklatur penyetaraan fungsional. |
| `data_kl` | Data kabinet, tahun pemerintahan, dan daftar instansi anggota kabinet. | `id` (INT Auto) | None (Kolom string `list_id_kl`) | ~5-15 | InnoDB | **TRANSFORM** | **Redesign Total:** Dipecah menjadi entitas `Cabinet`, `CabinetPeriod`, dan `CabinetMembership`. |
| `data_pemda` | Data instansi pemerintah daerah (Provinsi, Kab/Kota). | `id` (INT Auto) | `id_wilayah` $\rightarrow$ `tb_wilayah.id` | ~514-548 | InnoDB | **TRANSFORM** | **Konsolidasi:** Digabungkan ke dalam entitas terpadu `Institution` dengan tipe `PEMDA_*`. |
| `tbl_ref_instansi_org` | Bagan struktur unit kerja hierarkis instansi. | `id` (INT Auto) | `id_instansi` $\rightarrow$ `tb_instansi.id`, `parent_id` $\rightarrow$ `self.id` | ~10.000-50.000 | InnoDB | **TRANSFORM** | Menjadi entitas `OrganizationUnit` dengan penambahan proteksi integritas pohon rekursif. |
| `users` | Akun pengguna dan kredensial otentikasi legacy. | `id` (INT Auto) | `id_instansi` $\rightarrow$ `tb_instansi.id` | ~100-500 | InnoDB | **TRANSFORM** | Menjadi entitas `User` dengan hashing modern (Bcrypt), RBAC, dan scoped permissions. |
| `data_map` | Pemetaan manual kustom data instansi/wilayah legacy. | `id` (INT Auto) | None | ~100-300 | MyISAM / InnoDB | **DEPRECATE** | Tabel scratch ad-hoc. Data valid diekstraksi maknanya, tabel ditinggalkan. |
| `data_map_pemda` | Pemetaan manual instansi pemda legacy. | `id` (INT Auto) | None | ~500 | MyISAM / InnoDB | **DEPRECATE** | Tabel scratch ad-hoc. Dikonsolidasikan ke relasi master `Institution` dan `Region`. |
| `data_map_pemda_baru` | Pemetaan revisi pemekaran/perubahan pemda baru. | `id` (INT Auto) | None | ~50-100 | MyISAM / InnoDB | **DEPRECATE** | Scratch table. Diintegrasikan ke master data bersih, tabel fisik di-deprecate. |
| `data_map_yudhi_latest` | Tabel temporer analisa perorangan analis legacy (*ad-hoc scratch*). | `id` (INT Auto) | None | ~100-200 | MyISAM | **DEPRECATE** | Anti-pattern production. Ditinggalkan dan tidak dimigrasikan ke skema target. |

---

## 3. Inventaris Views Database Legacy

| View Name | Source Tables | Deskripsi Fungsi Legacy | Status Evaluasi | Rekomendasi Target SIGMA-K |
|---|---|---|:---:|---|
| `v_postur_asn` | `tb_instansi`, `tbl_ref_instansi_org`, `data_pegawai/rekap` | Menghitung agregasi postur jumlah ASN dan sebaran eselon per instansi. | **TRANSFORM** | Didesain ulang sebagai **Materialized View terindeks** (`mv_asn_posture_aggregates`) di PostgreSQL. |
| `v_rekap_instansi` | `tb_instansi`, `tb_jenis_instansi`, `tb_wilayah` | Rekapitulasi jumlah instansi berdasarkan jenis dan wilayah. | **REFERENCE** | Diakomodasi melalui query agregasi ter-cache pada Application/Analytics Layer. |
| `v_rekap_kabinet` | `data_kl`, `tb_instansi` | Menguraikan string `list_id_kl` untuk menampilkan nama-nama kementerian kabinet. | **TRANSFORM** | Digantikan oleh kueri relasional `CabinetMembership` JOIN `Institution`. |

---

## 4. Analisis Trigger, Stored Procedure, & Function
- **Stored Procedures:** Tidak ditemukan stored procedure bisnis yang kompleks pada legacy `eskld` (seluruh logika bisnis sebelumnya berada di script aplikasi monolitik).
- **Triggers:** Tidak ditemukan trigger otomatis pada database legacy.
- **Audit Logging:** Database legacy **TIDAK MEMILIKI** tabel audit log atau mekanisme pencatatan riwayat perubahan data (*zero audit capability*).

---

## 5. Ringkasan Status Tabel Legacy

- **Total Tabel Teridentifikasi:** 12 Tabel
- **Total View Teridentifikasi:** 3 View
- **Status TRANSFORM (Dimigrasikan dengan restrukturisasi):** 8 Tabel (`tb_instansi`, `tb_jenis_instansi`, `tb_wilayah`, `ref_eselon`, `data_kl`, `data_pemda`, `tbl_ref_instansi_org`, `users`)
- **Status DEPRECATE (Diekstraksi nilainya lalu ditinggalkan):** 4 Tabel (`data_map`, `data_map_pemda`, `data_map_pemda_baru`, `data_map_yudhi_latest`)
- **Status REFERENCE (Rujukan logika query):** 1 View (`v_rekap_instansi`)
