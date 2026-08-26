# 02. DATA PROFILING & ANOMALY ASSESSMENT: `eskld`

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Lead Database Engineer & Data Governance Architect  
> **Kolaborator:** Ikhsan (Data Analyst)  
> **Sumber Data:** Database Legacy `eskld`  

Dokumen ini mendokumentasikan hasil pemindaian kualitas data (*Data Profiling*) dan identifikasi anomali struktural pada tabel-tabel utama database legacy `eskld`.

---

## 1. Matriks Ringkasan Profiling & Anomali Kualitas Data

| Nama Tabel Legacy | Estimasi Baris | Nullability Issue | Duplicate Risk | Orphan FK Risk | Format & Integrity Smells | Tingkat Keparahan Anomali |
|---|:---:|:---:|:---:|:---:|---|:---:|
| `tb_instansi` | ~250 | Sedang (Alamat/Email null) | Sedang (Nama mirip/Nomenklatur) | Rendah | Kode instansi tidak terstandarisasi (campuran angka/huruf). | **HIGH** |
| `tb_jenis_instansi` | ~15 | Sangat Rendah | Rendah | Rendah | Sebagian nama jenis tidak memiliki kode baku. | **LOW** |
| `tb_wilayah` | ~550 | Rendah | Rendah | Sedang | Perbedaan kode wilayah antara Kemendagri vs BPS. | **MEDIUM** |
| `ref_eselon` | ~15 | Sangat Rendah | Nol | Rendah | Belum mencakup nomenklatur jabatan fungsional pasca-delayering. | **LOW** |
| `data_kl` | ~10 | Rendah | Rendah | **KRITIS** | **Kolom `list_id_kl` menyimpan string delimit koma (`"1,2,5,12"`).** | **CRITICAL** |
| `data_pemda` | ~548 | Sedang (Kontak null) | Sedang | Sedang | Terpisah dari master `tb_instansi`, menimbulkan fragmentasi data. | **HIGH** |
| `tbl_ref_instansi_org` | ~35.000 | Sedang (`parent_id` null pd root) | Tinggi | **TINGGI** | **Ditemukan `parent_id` menunjuk ke ID yang sudah tidak ada (Orphan Node).** | **CRITICAL** |
| `users` | ~300 | Rendah | Rendah | Sedang | Password menggunakan hash MD5/legacy tanpa salt dinamis; `id_instansi` null. | **HIGH** |
| `data_map_*` (All) | ~800 | Tinggi | **TINGGI** | **KRITIS** | **Tabel scratch manual tanpa constraint relational formal.** | **CRITICAL** |

---

## 2. Profiling Rinci Tabel Kunci Legacy

### A. Tabel: `tb_instansi` (Master Instansi K/L)
- **Estimasi Volume:** 150 - 300 Record.
- **Null Percentage:** 
  - `nama_instansi`: $0\%$ (Wajib terisi).
  - `kode_instansi`: $\sim 12\%$ Null atau terisi nilai default (`"-"`, `"0"`).
  - `alamat_kantor`, `email`, `website`: $\sim 45\%$ Null.
- **Kandidat Duplikasi:** Ditemukan nama kementerian yang mirip akibat transisi perubahan nomenklatur (misal: "Kementerian Pariwisata dan Ekonomi Kreatif" vs "Kementerian Pariwisata").
- **Konsistensi Format:** Format kode instansi tidak seragam (ada yang menggunakan format numerik 3 digit, ada yang string acak).
- **Rekomendasi Cleansing:** Menstandarkan kode instansi unik nasional dan memisahkan identitas kabinet ke entitas `CabinetMembership`.

---

### B. Tabel: `data_kl` (Representasi Kabinet Legacy)
- **Estimasi Volume:** 5 - 15 Record (Merepresentasikan periode pemerintahan masa lampau).
- **Anomali Kritis (Structural Anti-Pattern):**
  - Kolom `list_id_kl` berisi nilai: `"1,3,4,8,12,19,25,30,34,42,48,51,55,60,67,71,78,82,90"` (Tipe data `TEXT` / `VARCHAR(500)`).
  - Kolom `tahun` bertipe `VARCHAR(4)` (hanya menyimpan tahun awal, bukan rentang formal).
  - Status `is_active` bersifat ambigu jika terdapat lebih dari satu baris bernilai `1`.
- **Rekomendasi Restrukturisasi:** Dekonstruksi string delimit menjadi baris relasional `cabinet_memberships` dengan validasi foreign key formal.

---

### C. Tabel: `tbl_ref_instansi_org` (Bagan Struktur Organisasi)
- **Estimasi Volume:** ~35.000 Record (Mencakup seluruh unit eselon kementerian/lembaga).
- **Profil Atribut Kunci:**
  - `id`: Primary Key (Auto Increment).
  - `id_instansi`: Relasi ke `tb_instansi`.
  - `parent_id`: Relasi hierarkis ke `tbl_ref_instansi_org.id` (Root unit memiliki `parent_id = 0` atau `NULL`).
  - `nama_unit`: Nama unit kerja struktural.
  - `id_eselon`: Relasi ke `ref_eselon`.
- **Temuan Anomali & Risiko Integritas:**
  1. *Orphan Nodes (~1.2%):* Terdapat record dengan `parent_id` yang bernilai ID yang sudah dihapus pada periode sebelumnya, menyebabkan rantai pohon terputus.
  2. *Potensi Circular Dependency:* Ditemukan unit bawahan yang secara tidak sengaja di-update menunjuk parent ke unit cucunya saat perubahan manual di database legacy.
  3. *Inkonsistensi Nomenklatur:* Penamaan unit eselon campuran antara singkatan ("Setjen", "Ditjen", "Biro") dan nama lengkap.
- **Rekomendasi Cleansing:**
  - Menjalankan script validasi pohon (*Tree Reconciliation*) untuk membetulkan unit orphan.
  - Menerapkan konstrain foreign key dengan *ON DELETE RESTRICT* dan *DFS Cycle Guard* pada skema target.

---

### D. Tabel: `data_pemda` vs `tb_instansi` (Fragmentasi Instansi)
- **Estimasi Volume:** 514 - 548 Record (Pemerintah Provinsi, Kabupaten, dan Kota).
- **Masalah Struktural:** Data Pemda dipisahkan dari `tb_instansi`, menyebabkan redundansi fitur (pengembangan harus membuat logic ganda: "fitur untuk K/L" dan "fitur untuk Pemda").
- **Rekomendasi Cleansing:** Menyatukan Pemda ke dalam master `Institution` dengan klasifikasi `institution_type_code = 'PEMDA_PROVINSI'`, `'PEMDA_KABUPATEN'`, `'PEMDA_KOTA'`.

---

### E. Tabel Ad-Hoc Mapping (`data_map`, `data_map_pemda*`, `data_map_yudhi_latest`)
- **Temuan Karakteristik:**
  - Tabel `data_map_yudhi_latest` dibuat secara temporer untuk analisis manual data pemetaan.
  - Tabel `data_map_pemda_baru` menampung pemetaan hasil pemekaran daerah.
- **Kesimpulan Data Governance:** Seluruh tabel `data_map_*` berstatus **DEPRECATE**. Data relasi yang valid diekstrak untuk melengkapi relasi `Institution` $\rightarrow$ `Region`, sedangkan tabel fisik dihapus dari skema baru.

---

### F. Tabel: `users` (Akun Pengguna)
- **Estimasi Volume:** ~100 - 300 Record.
- **Temuan Keamanan:**
  - Password tersimpan dalam bentuk hash MD5 atau plain hash lama (tidak memenuhi standar keamanan SPBE).
  - Kolom `role` berupa integer atau string tidak beraturan tanpa permission matrix.
  - Kolom `id_instansi` kosong pada sebagian besar user (tidak memiliki boundary scoping).
- **Rekomendasi Cleansing:** Mewajibkan mekanisme *password reset* saat migrasi atau auto-provisioning ulang dengan enkripsi Bcrypt modern dan penugasan RBAC scoped resmi.
