# ACCEPTANCE CRITERIA: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Senior QA Architect & Lead Full-Stack Engineer  

Dokumen ini mendefinisikan kriteria keberterimaan formal (*Acceptance Criteria*) menggunakan format baku **Given / When / Then (Gherkin format)** sebagai fondasi pengujian kualitas (QA/Testing) dan implementasi pengembangan.

---

## Matriks Daftar Kriteria Keberterimaan

| AC-ID | Judul Skenario Pengujian | User Story Terkait | Use Case Terkait |
|---|---|---|---|
| **AC-001** | Akses Lihat Profil & Struktur Instansi Sesuai Scope | [US-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-002** | Penyusunan Draf Struktur Organisasi Instansi | [US-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-003** | Pengelolaan Butir Tugas & Fungsi Terstruktur | [US-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-004** | Pengiriman Tiket Pengajuan (Submission) & Penguncian Draf | [US-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-005** | Penerimaan Catatan Revisi & Pengiriman Ulang (Resubmission) | [US-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-006** | Pengambilan Antrean Tiket Verifikasi oleh Verifikator | [US-006](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-007** | Tampilan Komparasi Perubahan Data (Diff Viewer) | [US-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-008** | Penetapan Status Keputusan Verifikasi | [US-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-009** | Pembuatan Master Kabinet & Penetapan Rentang Periode | [US-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-006](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md), [UC-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-010** | Penambahan & Pelepasan Anggota K/L dalam Kabinet Aktif | [US-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md), [UC-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-011** | Persetujuan Akhir (Approval) & Publikasi Atomik Master Data | [US-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-016](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-012** | Penelusuran Log Audit Mutasi Data | [US-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-018](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-013** | Tampilan Dashboard Eksekutif & Komposisi Kabinet Merah Putih | [US-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-014** | Komparasi Matriks Kelembagaan Antar-Kabinet | [US-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-015** | Eksplorasi Analitik Postur ASN & Sebaran Eselon | [US-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-019](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |
| **AC-016** | Penerimaan & Tampilan Notifikasi Realtime | [US-016](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USER_STORIES.md) | [UC-017](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/USE_CASES.md) |

---

## Rincian Skenario Acceptance Criteria

### AC-001: Akses Lihat Profil & Struktur Instansi Sesuai Scope
- **Given** pengguna telah terautentikasi dengan peran `USER` dan terikat pada instansi target,
- **When** pengguna mengakses menu profil instansi miliknya,
- **Then** sistem menampilkan informasi detail instansi, daftar unit kerja aktif, dan bagan organisasi pohon secara lengkap,
- **And** sistem mengaktifkan tombol edit draf untuk instansi tersebut.

---

### AC-002: Penyusunan Draf Struktur Organisasi Instansi
- **Given** pengguna berada di ruang kerja draf struktur organisasi instansinya,
- **When** pengguna menambahkan unit kerja baru dengan menentukan nama unit, tingkatan eselon, dan unit atasan (*parent*),
- **Then** sistem memvalidasi bahwa unit atasan bukan merupakan unit turunan (*anti-circular dependency*),
- **And** sistem memperbarui tampilan pohon hierarki secara visual seketika,
- **And** perubahan tersimpan di tabel draf tanpa mengubah master data aktif.

---

### AC-003: Pengelolaan Butir Tugas & Fungsi Terstruktur
- **Given** pengguna membuka form tugas dan fungsi untuk unit kerja yang dipilih,
- **When** pengguna menginput rumusan tugas pokok, rincian butir fungsi, dan nomor pasal regulasi hukum,
- **Then** sistem memvalidasi kelengkapan pasal dasar hukum,
- **And** sistem menyimpan butir tugas dan fungsi terstruktur yang terikat pada unit kerja terkait.

---

### AC-004: Pengiriman Tiket Pengajuan (Submission) & Penguncian Draf
- **Given** draf perubahan data instansi telah terisi lengkap dengan dokumen dasar hukum PDF terlampir,
- **When** pengguna menekan tombol "Ajukan Perubahan Data" dan mengonfirmasi pengiriman,
- **Then** sistem membuat tiket pengajuan baru berstatus `SUBMITTED` dengan nomor tiket unik,
- **And** sistem mengunci draf data dari segala perubahan edit oleh pengguna,
- **And** sistem memicu notifikasi realtime ke seluruh Verifikator aktif.

---

### AC-005: Penerimaan Catatan Revisi & Pengiriman Ulang (Resubmission)
- **Given** tiket pengajuan berada dalam status `REVISION_REQUIRED`,
- **When** pengguna membuka detail pengajuan dan memperbaiki data sesuai catatan koreksi verifikator,
- **Then** pengguna dapat menekan tombol "Kirim Ulang" (*Resubmit*),
- **And** sistem mengubah status tiket menjadi `RESUBMITTED`,
- **And** draf kembali terkunci dan notifikasi dikirimkan kembali ke verifikator.

---

### AC-006: Pengambilan Antrean Tiket Verifikasi oleh Verifikator
- **Given** pengguna terautentikasi dengan peran `VERIFIKATOR`,
- **When** pengguna membuka halaman antrean verifikasi (*Verification Queue*),
- **Then** sistem menampilkan seluruh daftar tiket berstatus `SUBMITTED` dan `RESUBMITTED` dengan filter kategori instansi,
- **And** pengguna dapat membuka detail tiket pengajuan untuk memulai peninjauan.

---

### AC-007: Tampilan Komparasi Perubahan Data (Diff Viewer)
- **Given** Verifikator membuka detail tiket pengajuan perubahan struktur organisasi,
- **When** Verifikator memilih tab "Perbandingan Perubahan" (*Diff View*),
- **Then** sistem menyajikan visualisasi berdampingan antara bagan struktur lama (warna merah untuk unit dihapus) vs bagan struktur usulan baru (warna hijau untuk unit ditambah, warna kuning untuk unit diubah/dipindah).

---

### AC-008: Penetapan Status Keputusan Verifikasi
- **Given** Verifikator telah selesai meneliti keabsahan dasar hukum dan kelayakan struktur,
- **When** Verifikator memilih opsi "Verifikasi Lolos" atau "Minta Revisi" (dengan catatan koreksi wajib),
- **Then** sistem memvalidasi keberadaan catatan jika opsi revisi dipilih,
- **And** sistem memperbarui status tiket (`VERIFIED` atau `REVISION_REQUIRED`),
- **And** sistem mencatat riwayat verifikasi ke dalam tabel audit.

---

### AC-009: Pembuatan Master Kabinet & Penetapan Rentang Periode
- **Given** pengguna terautentikasi dengan peran `ADMIN`,
- **When** Admin menginput nama kabinet baru "Kabinet Merah Putih", pimpinan, dan rentang tahun periode 2024-2029,
- **Then** sistem memvalidasi keunikan nama kabinet dan validitas rentang tanggal,
- **And** sistem menyimpan entitas `Cabinet` dan `CabinetPeriod` baru di database,
- **And** sistem mencatat aktivitas tersebut ke dalam log audit.

---

### AC-010: Penambahan & Pelepasan Anggota K/L dalam Kabinet Aktif
- **Given** Admin membuka halaman keanggotaan Kabinet Merah Putih,
- **When** Admin memilih kementerian dari daftar master instansi dan menekan "Tambahkan ke Kabinet",
- **Then** sistem memverifikasi bahwa kementerian tersebut belum terdaftar di periode yang sama,
- **And** sistem membuat relasi `CabinetMembership` baru,
- **And** total jumlah kementerian pada kabinet aktif bertambah secara otomatis di dashboard.

---

### AC-011: Persetujuan Akhir (Approval) & Publikasi Atomik Master Data
- **Given** tiket pengajuan berada dalam status `VERIFIED`,
- **When** Admin menekan tombol "Setujui & Publikasikan" (*Approve*),
- **Then** sistem mengeksekusi transaksi atomik memindahkan seluruh data draf ke Master Data aktif,
- **And** status tiket berubah menjadi `APPROVED`,
- **And** perubahan data resmi tampil di katalog publik dan dashboard pimpinan,
- **And** notifikasi disiarkan ke seluruh aktor terkait.

---

### AC-012: Penelusuran Log Audit Mutasi Data
- **Given** pengguna terautentikasi dengan peran `ADMIN`,
- **When** Admin mengakses halaman Audit Trail dan menerapkan filter rentang tanggal dan nama instansi,
- **Then** sistem menyajikan daftar transaksi lengkap dengan timestamp presisi milidetik, ID aktor, IP address, jenis aksi, serta perbandingan snapshot JSON nilai sebelum vs sesudah.

---

### AC-013: Tampilan Dashboard Eksekutif & Komposisi Kabinet Merah Putih
- **Given** Pimpinan / SESDEP membuka halaman beranda SIGMA-K,
- **When** halaman termuat sempurna,
- **Then** sistem menyajikan kartu statistik utama (Total K/L, Total Pemda, Jumlah Instansi Kabinet Merah Putih), grafik sebaran instansi, kartu ringkasan kementerian koordinator vs teknis, dan umpan aktivitas mutasi data terkini dalam waktu $< 2$ detik.

---

### AC-014: Komparasi Matriks Kelembagaan Antar-Kabinet
- **Given** pengguna membuka modul komparasi kabinet,
- **When** pengguna memilih "Kabinet Indonesia Maju (2019-2024)" dan "Kabinet Merah Putih (2024-2029)",
- **Then** sistem menyajikan matriks perbandingan yang menyoroti instansi yang baru dibentuk, instansi hasil pemecahan kementerian, dan instansi yang bertransformasi nomenklatur.

---

### AC-015: Eksplorasi Analitik Postur ASN & Sebaran Eselon
- **Given** Data Analyst membuka modul analitik kelembagaan,
- **When** analis memilih filter kelompok kementerian koordinator tertentu,
- **Then** sistem menampilkan grafik distribusi tingkatan eselon dan agregat postur ASN (`v_postur_asn`),
- **And** analis dapat mengekspor ringkasan data ke format Excel.

---

### AC-016: Penerimaan & Tampilan Notifikasi Realtime
- **Given** pengguna sedang membuka halaman manapun di aplikasi SIGMA-K,
- **When** terjadi mutasi data (misal: Admin menyetujui pengajuan data baru),
- **Then** sistem memunculkan pop-up toast notifikasi di sudut layar pengguna dalam waktu $\le 1$ detik,
- **And** badge lonceng notifikasi bertambah (+1) tanpa pengguna harus me-refresh peramban.
