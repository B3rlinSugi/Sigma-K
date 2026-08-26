# 04. LEGACY TO SIGMA-K DATA MAPPING: `eskld` $\rightarrow$ PostgreSQL

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Lead Database Engineer & Migration Architect  
> **Kolaborator:** Ikhsan (Data Analyst)  

Dokumen ini mendefinisikan pemetaan data rinci (*Source-to-Target Data Mapping*) dari tabel-tabel database legacy `eskld` menuju skema relasional modern SIGMA-K.

---

## 1. Matriks Pemetaan Sumber ke Target (*Source-to-Target Mapping Matrix*)

| Legacy Table & Field | Target Entity & Field | Transformasi Data (*Transformation Logic*) | Potensi Masalah Kualitas Data (*DQ Issue*) | Aturan Migrasi (*Migration Rule*) | Tingkat Keyakinan (*Confidence*) | Catatan & Rujukan |
|---|---|---|---|---|:---:|---|
| `tb_instansi.id` | `Institution.id` | Konversi integer PK legacy menjadi UUIDv4 baru; simpan ID lama di tabel pemetaan migrasi. | Tipe data numerik sequential rawan enumerasi. | Generate UUID deterministik atau UUIDv4 baru dengan mapping table `_migration_map_institutions`. | **HIGH** | Menjamin integritas referensi lama. |
| `tb_instansi.kode_instansi` | `Institution.code` | Trim spasi, kapitalisasi, validasi keunikan nasional. | Kode null (~12%) atau bernilai default `"-"`. | Jika null, generate kode sementara berformat `KL-LEGACY-{id}` dan tandai flag review. | **HIGH** | Butuh validasi Ikhsan. |
| `tb_instansi.nama_instansi` | `Institution.name` | Trim whitespace, standardisasi format Title Case resmi. | Duplikasi nama mirip pada era kabinet berbeda. | Nama resmi instansi wajib unik untuk tipe dan periode yang sama. | **HIGH** | Entitas master utama. |
| `tb_instansi.singkatan` | `Institution.short_name` | Trim spasi, uppercase akronim. | Null (~30%). | Set null jika tidak tersedia di legacy. | **HIGH** | - |
| `tb_instansi.id_jenis` | `Institution.institution_type_id` | Map integer `id_jenis` legacy ke lookup `institution_types.id`. | Relasi foreign key putus pada beberapa baris. | Lookup ke master jenis instansi; fallback ke `LAINNYA` jika tidak cocok. | **HIGH** | - |
| `tb_instansi.id_wilayah` | `Institution.region_id` | Map ke master `regions.id`. | Kode wilayah legacy berbeda format dengan BPS/Kemendagri. | Pemetaan kode wilayah melalui tabel referensi `regions`. | **MEDIUM** | Butuh cleansing wilayah. |
| `tb_instansi.alamat` | `InstitutionProfile.address` | Pindahkan ke entitas 1-to-1 `InstitutionProfile`. | Teks tidak terstruktur, null (~45%). | Simpan sebagai plain text di tabel profil. | **HIGH** | Pemisahan master vs profil. |
| `tb_instansi.telepon`, `email`, `website` | `InstitutionProfile.phone`, `email`, `website_url` | Pindahkan ke entitas `InstitutionProfile`; validasi format URL & email. | Format nomor telepon tidak standar, email kosong. | Simpan data yang ada, set null jika tidak valid. | **HIGH** | - |
| `data_pemda.id` | `Institution.id` (Consolidated) | Digabungkan ke tabel `institutions`; generate UUIDv4 baru. | Pemda terpisah dari K/L pada database legacy. | Migrasikan Pemda ke `institutions` dengan `institution_type_id` = `PEMDA_PROV` / `PEMDA_KAB` / `PEMDA_KOTA`. | **HIGH** | Unifikasi Master Instansi. |
| `data_pemda.nama_pemda` | `Institution.name` | Standardisasi nama: "Pemerintah Provinsi X", "Pemerintah Kabupaten Y". | Inkonsistensi kata awalan ("Pemkab", "Pemerintah Kab."). | Normalisasi awalan nama resmi Pemda. | **HIGH** | - |
| `data_kl.id` | `Cabinet.id` | Generate UUIDv4 untuk setiap era kabinet. | Entitas kabinet bercampur dengan data membership. | Ekstrak entitas `Cabinet` unik (Nama Presiden, Nama Kabinet). | **HIGH** | Normalisasi Kabinet. |
| `data_kl.nama_kabinet` | `Cabinet.name` | Nama kabinet kepresidenan (misal: 'Kabinet Indonesia Maju'). | Teks tidak baku. | Standardisasi nama resmi kabinet kepresidenan. | **HIGH** | - |
| `data_kl.tahun` | `CabinetPeriod.start_date`, `end_date` | Konversi string tahun ("2019") menjadi rentang tanggal formal (`2019-10-20` s.d `2024-10-20`). | Hanya menyimpan string tahun awal tanpa tanggal spesifik. | Set `start_date` dan `end_date` berdasarkan tanggal pelantikan resmi Keppres. | **HIGH** | Validasi temporal kabinet. |
| `data_kl.list_id_kl` | `CabinetMembership.*` | **TRANSFORMASI KRITIS:** Pecah string delimit koma (`"1,2,5,12"`) menjadi baris-baris relasi `(cabinet_period_id, institution_id)`. | **String denormalisasi berat, ada ID instansi yang sudah tidak aktif.** | Loop parsing split string `list_id_kl`, validasi keberadaan `institution_id`, insert ke `cabinet_memberships`. | **HIGH** | [DEC-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md) Mandat. |
| `tbl_ref_instansi_org.id` | `OrganizationUnit.id` | Generate UUIDv4 baru; catat relasi ID lama di tabel staging mapping. | Tipe data numerik auto increment. | Migrasikan seluruh unit kerja; pertahankan pohon hierarki. | **HIGH** | - |
| `tbl_ref_instansi_org.parent_id` | `OrganizationUnit.parent_id` | Konversi ID integer parent ke UUID parent baru; set `NULL` jika root (`parent_id = 0`). | **Orphan parent_id (~1.2%) menunjuk ID terhapus.** | Jalankan algoritma rekonsiliasi orphan; jika parent tidak ditemukan, kaitkan ke Root Unit instansi. | **MEDIUM** | Cleansing pohon wajib. |
| `tbl_ref_instansi_org.nama_unit` | `OrganizationUnit.unit_name` | Trim whitespace dan standardisasi huruf. | Singkatan tidak beraturan ("Setjen", "Ditjen"). | Pertahankan teks resmi nama unit. | **HIGH** | - |
| `tbl_ref_instansi_org.id_eselon` | `OrganizationUnit.echelon_level_id` | Map ke lookup `position_levels.id`. | Relasi eselon null pada beberapa unit non-struktural. | Set FK eselon; jika null, set ke `NON_ESELON`. | **HIGH** | - |
| `users.id` | `User.id` | Generate UUIDv4 baru. | User legacy tidak memiliki RBAC granular. | Migrasikan akun; assign role default `USER` / `ADMIN`. | **HIGH** | - |
| `users.password` | `User.password_hash` | Hash ulang menggunakan Bcrypt; tandai flag `must_reset_password = TRUE`. | Password lama berformat MD5 / plain hash tidak aman. | Pengguna diwajibkan melakukan reset password saat pertama kali login. | **HIGH** | Standar keamanan data. |
| `data_map_*` (All tables) | *Deprecated* | Ekstraksi relasi wilayah dan instansi yang valid untuk melengkapi master. | Tabel scratch ad-hoc tanpa integritas foreign key. | Nilai valid diserap ke `institutions.region_id`, tabel fisik legacy **DITINGGALKAN**. | **HIGH** | [DEC-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md) & [DEC-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md). |
