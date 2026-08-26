# WORKFLOW SPECIFICATION: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  

Dokumen ini memetakan seluruh siklus hidup data (*data lifecycle*) dan alur kerja tata kelola (*governance workflows*) di dalam sistem SIGMA-K.

---

## 1. Ikhtisar Alur Kerja Utama Sistem

```
[OPERATOR INSTANSI (USER)]
      |
      | 1. Input / Edit Profil, Struktur, Tupoksi
      v
+-------------+      2. Submit Tiket       +-----------------+
| DRAFT MODE  | -------------------------> | SUBMITTED STATE |
+-------------+                            +-----------------+
      ^                                             |
      |                                             | 3. Ambil Tiket
      | 4b. Catatan Revisi                          v
      | (REVISION_REQUIRED)                +-----------------+
      +----------------------------------- | IN_REVIEW STATE | <--- [VERIFIKATOR KemenPANRB]
                                           +-----------------+
                                                    |
                                                    | 4a. Verifikasi Lolos
                                                    v
                                           +-----------------+
                                           | VERIFIED STATE  |
                                           +-----------------+
                                                    |
                                                    | 5. Review & Final Approval
                                                    v
                                           +-----------------+
                                           | APPROVED STATE  | <--- [ADMINISTRATOR]
                                           +-----------------+
                                                    |
                                                    | 6. Publikasi Atomik
                                                    v
                                           +-----------------+
                                           | MASTER LIVE DB  | ---> [SESDEP / DASHBOARD]
                                           +-----------------+
```

---

## 2. Rincian Siklus Hidup Alur Kerja

### A. Institution Data Lifecycle (Siklus Hidup Data Instansi)

```
[NEW_REGISTRATION] ---> [ACTIVE] ---> [UNDER_REVISION] ---> [ACTIVE]
                           |
                           +---> [INACTIVE / SOFT_DELETED]
```

- **Current Known Flow:**
  1. **Registrasi Master:** Admin mendaftarkan instansi baru dengan kode unik, tipe, dan wilayah. Status awal menjadi `ACTIVE`.
  2. **Pemeliharaan Profil:** Operator instansi memelihara data alamat, website, kontak, bagan organisasi, dan tupoksi melalui draf staging.
  3. **Penonaktifan / Likuidasi:** Jika instansi dibubarkan atau dimerger berdasarkan dasar hukum baru, Admin mengubah status instansi menjadi `INACTIVE` melalui mekanisme *soft delete*.
- **Open Decision:**
  - `OD-WF-01`: Apakah instansi baru di daerah (misal pemekaran Kabupaten/Kota baru) harus didaftarkan oleh Admin Pusat KemenPANRB atau dapat diajukan secara mandiri oleh admin Pemerintah Provinsi setempat? [TBD]

---

### B. Submission Lifecycle (Siklus Hidup Pengajuan Perubahan Data)

```
[DRAFT] ---> [SUBMITTED] ---> [IN_REVIEW] ---> [APPROVED] ---> [PUBLISHED]
                 ^                 |
                 |                 v
                 +------- [REVISION_REQUIRED]
```

- **Current Known Flow:**
  1. **DRAFT:** Operator menyusun butir perubahan struktur atau tupoksi. Data dapat diubah dan disimpan berkali-kali tanpa batas.
  2. **SUBMITTED:** Operator menekan tombol ajukan dan melampirkan berkas regulasi PDF. Data draf dikunci (*locked*), nomor tiket diterbitkan, dan event dikirim ke Verifikator.
  3. **IN_REVIEW:** Verifikator membuka dan mengklaim tiket untuk diteliti.
  4. **Lolos ke Tahap Approval / Dikembalikan ke Revisi:** Sesuai hasil telaah verifikator.
- **Open Decision:**
  - `OD-WF-02`: Berapa batas waktu maksimal (*timeout*) sebuah tiket `SUBMITTED` sebelum sistem memberikan peringatan otomatis kepada koordinator verifikator? [TBD]

---

### C. Verification Lifecycle (Siklus Hidup Peninjauan Data)

- **Current Known Flow:**
  1. Verifikator menerima notifikasi adanya pengajuan baru di antrean (*Verification Queue*).
  2. Verifikator membuka layar kerja tiket dan memeriksa lampiran dokumen regulasi.
  3. Verifikator membandingkan data lama vs data usulan menggunakan layar perbandingan (*Diff Viewer*).
  4. **Keputusan Verifikasi:**
     - **Option 1 (Lolos):** Verifikator menandai tiket sebagai `VERIFIED`, status diteruskan ke Admin untuk approval.
     - **Option 2 (Perlu Revisi):** Verifikator mengisi butir catatan koreksi dan mengubah status menjadi `REVISION_REQUIRED`.
     - **Option 3 (Tolak Mutlak):** Verifikator menandai tiket sebagai `REJECTED` (pengajuan dibatalkan permanen karena bertentangan dengan hukum).
- **Open Decision:**
  - `OD-WF-03`: Apakah verifikator berwenang melakukan koreksi langsung terhadap kesalahan ketik (*minor typo correction*) pada nama unit kerja tanpa mengembalikan tiket ke user? [TBD]

---

### D. Approval Lifecycle (Siklus Hidup Persetujuan Akhir Data)

- **Current Known Flow:**
  1. Admin membuka daftar tiket yang telah berstatus `VERIFIED`.
  2. Admin memeriksa rekomendasi verifikator dan ringkasan dampak perubahan pada postur instansi.
  3. Admin menekan tombol "Setujui & Publikasikan" (*Approve*).
  4. Sistem mengeksekusi transaksi basis data atomik:
     - Meng-copy data dari draf ke Master Data aktif.
     - Memperbarui status tiket menjadi `APPROVED`.
     - Mencatat log audit dengan identitas Admin pengesah.
     - Memicu notifikasi realtime ke Operator instansi pengaju dan seluruh pengguna aktif.
- **Open Decision:**
  - `OD-WF-04`: Apakah hak approval dapat didelegasikan secara otomatis kepada Verifikator Senior untuk kategori perubahan data non-struktural (misal perubahan nomor telepon atau link website)? [TBD]

---

### E. Revision Lifecycle (Siklus Hidup Revisi Pengajuan)

- **Current Known Flow:**
  1. Saat tiket berstatus `REVISION_REQUIRED`, sistem membuka kembali kunci edit pada draf kerja Operator Instansi.
  2. Operator menerima notifikasi dan membaca catatan koreksi verifikator.
  3. Operator melakukan penyesuaian pada unit kerja, tupoksi, atau mengunggah dokumen pendukung tambahan.
  4. Operator menekan tombol "Kirim Ulang" (*Resubmit*).
  5. Status tiket berubah menjadi `RESUBMITTED` dan draf terkunci kembali.
  6. Tiket kembali masuk ke antrean Verifikator yang sebelumnya menangani.
- **Open Decision:**
  - `OD-WF-05`: Berapa batas maksimum siklus pengiriman ulang revisi (*maximum revision iterations*) sebelum tiket otomatis dibatalkan? [TBD]

---

### F. Cabinet Lifecycle (Siklus Hidup Master Kabinet)

```
[CABINET_DRAFT] ---> [CABINET_ACTIVE] ---> [CABINET_COMPLETED / ARCHIVED]
```

- **Current Known Flow:**
  1. **Pembentukan Kabinet Baru:** Admin mendaftarkan nama kabinet baru (misal: Kabinet Merah Putih), nama Presiden/Wapres, dan periode tahun.
  2. **Aktivasi Kabinet:** Admin menetapkan kabinet tersebut sebagai Kabinet Aktif Utama (`is_active = TRUE`). Sistem otomatis menonaktifkan status aktif pada kabinet sebelumnya.
  3. **Pengarsipan:** Ketika masa jabatan kabinet berakhir di masa depan, status kabinet diubah menjadi `COMPLETED`/`ARCHIVED`. Data historis kabinet tetap dapat dibuka dan dijadikan bahan komparasi.
- **Open Decision:**
  - `OD-WF-06`: Apakah perubahan kabinet aktif memerlukan persetujuan formal pimpinan (SESDEP) melalui tanda tangan digital di sistem atau cukup aksi Admin? [TBD]

---

### G. Cabinet Membership Lifecycle (Siklus Hidup Keanggotaan K/L dalam Kabinet)

```
[INSTITUTION_ADDED_TO_CABINET] ---> [ACTIVE_IN_CABINET] ---> [TRANSITIONED / REMOVED]
```

- **Current Known Flow:**
  1. Admin membuka halaman detail kabinet aktif terpilih.
  2. Admin memilih kementerian/lembaga dari master instansi dan menambahkannya ke dalam keanggotaan kabinet.
  3. Sistem membuat relasi `CabinetMembership` dengan tanggal bergabung.
  4. Jika terjadi perombakan (*reshuffle*) atau restrukturisasi kementerian di tengah periode kabinet, Admin memperbarui status membership instansi terkait dan mencatat relasi silsilah instansi penerus (*lineage successor*).
- **Open Decision:**
  - `OD-WF-07`: Bagaimana penanganan otomatis terhadap unit organisasi eselon I ketika suatu kementerian dipecah menjadi 2 kementerian baru dalam keanggotaan kabinet? [TBD]

---

### H. Realtime Notification Lifecycle (Siklus Hidup Notifikasi Realtime)

```
[EVENT_TRIGGERED] ---> [EVENT_BROADCAST] ---> [CLIENT_RECEIVED] ---> [TOAST_DISPLAYED]
                                                      |
                                                      +---> [BADGE_INCREMENTED]
                                                      +---> [SAVED_TO_DB_HISTORY]
```

- **Current Known Flow:**
  1. Terjadi mutasi data di backend (misal: pengajuan tiket baru, perubahan status verifikasi, persetujuan admin).
  2. Backend menerbitkan payload event ke broker realtime.
  3. Layanan realtime menyiarkan event ke peramban (*client*) pengguna target secara seketika.
  4. Antarmuka client menampilkan pop-up Toast notifikasi dan menambah angka badge lonceng (+1).
  5. Pesan notifikasi tersimpan di tabel `notifications` pengguna sehingga dapat dibaca kembali di Pusat Notifikasi.
- **Open Decision:**
  - `OD-WF-08`: Protokol transport realtime apa yang paling optimal dan didukung penuh oleh infrastruktur server KemenPANRB (WebSocket vs Server-Sent Events/SSE)? *(Keputusan teknis ditangguhkan ke Phase 2).*
