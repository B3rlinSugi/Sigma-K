# SIGMA-K — SCREEN INVENTORY (16 SCREENS)

## 1. Daftar Lengkap 16 Layar Interaktif Terimplementasi

| No | ID Layar | Rute URL (`Path`) | Modul | Deskripsi & Komponen Kunci |
| :---: | :--- | :--- | :--- | :--- |
| 1 | `SCR-01` | `/` | Dashboard | **Dashboard Eksekutif:** KPI 48 K/L Kabinet Merah Putih, 548 Pemda, spotlight kabinet aktif, antrean telaah, tabel pengajuan terkini, dan kartu Proposed KPIs SESDEP. |
| 2 | `SCR-02` | `/cabinets` | Master Kabinet | **Manajemen Kabinet:** Katalog era pemerintahan (Indonesia Maju, Merah Putih) dengan pencarian, indikator aktif, dan tombol aksi komparasi. |
| 3 | `SCR-03` | `/cabinets/new` | Master Kabinet | **Pendaftaran Kabinet Baru:** Formulir registrasi kabinet baru berdasar Keppres dengan validasi tanggal masa jabatan. |
| 4 | `SCR-04` | `/cabinets/[id]` | Master Kabinet | **Rincian Kabinet & Keanggotaan:** Komposisi 48 K/L (7 Kemenko + Kementerian Teknis) dengan filter tab kategori dan tautan profil instansi. |
| 5 | `SCR-05` | `/cabinets/compare` | Komparasi Kabinet | **Komparasi Antar-Kabinet (Diff Showcase):** Analisis delta perubahan (**+7 Baru, 3 Split, 1 Merge, 5 Rename, 22 Tetap**) yang memperlihatkan pemecahan Kemendikbudristek menjadi Kemendikdasmen, Kemendiktisaintek, dan Kemenbud. |
| 6 | `SCR-06` | `/institutions` | Master Instansi | **Katalog Master Instansi:** Master data kementerian, lembaga pemerintah, dan 548 pemda se-Indonesia dengan filter jenis instansi (*Kemenko, Kementerian Teknis, Pemda Provinsi*). |
| 7 | `SCR-07` | `/institutions/[id]` | Profil Instansi | **Profil Detail Instansi:** Profil terpadu Kemenko Pangan, kontak resmi, dasar hukum Perpres 147/2024, unit kerja struktural, dan butir tupoksi. |
| 8 | `SCR-08` | `/tupoksi` | Kelembagaan | **Tugas dan Fungsi Master:** Katalog butir tugas pokok dan rincian fungsi berdasar pasal-pasal regulasi resmi dengan modal usulan butir baru. |
| 9 | `SCR-09` | `/structure` | Struktur Organisasi | **Bagan Organisasi (React Flow Canvas):** Visualisasi pohon hierarki (*Adjacency List*) dengan minimap, pencarian node, zoom-pan, dan *drawer* lembar rincian unit. |
| 10 | `SCR-10` | `/submissions` | Workflow Usulan | **Pengajuan Usulan Perubahan:** Manajemen tiket usulan perubahan instansi dan modal form pembuatan tiket baru lengkap dengan input upload PDF dasar hukum. |
| 11 | `SCR-11` | `/submissions/[id]` | Workflow Usulan | **Rincian Tiket Usulan:** Komponen visual *WorkflowStepper* 5 tahap, komparasi data draf (*DiffViewer*), lampiran PDF, dan riwayat telaah verifikator. |
| 12 | `SCR-12A`| `/verifications` | Verifikasi | **Antrean Verifikasi:** Antrean berkas pengajuan masuk untuk Analis Kelembagaan KemenPANRB. |
| 13 | `SCR-12B`| `/verifications/[id]` | Verifikasi | **Ruang Telaah Berdampingan:** Panel telaah berdampingan (*Side-by-Side Review*) data master live vs draf usulan dengan keputusan *Pass / Revision / Reject*. |
| 14 | `SCR-13` | `/submissions/[id]/revision` | Workflow Usulan | **Formulir Perbaikan Revisi:** Formulir penyesuaian usulan oleh operator untuk menanggapi catatan revisi verifikator. |
| 15 | `SCR-14` | `/notifications` | Notifikasi | **Pusat Notifikasi Realtime:** Notifikasi alur kerja, master data, dan keamanan akun dengan filter kategori. |
| 16 | `SCR-15` | `/analytics` | Intelijensi Data | **Intelijensi Data & Postur ASN:** Kolaborasi Data Analyst (Ikhsan & Pak Sigit) menampilkan Rasio Delayering (68.4%), Indeks Kesiapan 48 K/L (87.5%), dan Kecepatan Verifikasi (1.8 Hari). |
| 17 | `SCR-16` | `/audit-logs` | Keamanan & Audit | **Audit Trail Forensik:** Log audit tak-terhapuskan dengan modal snapshot JSON nilai sebelum vs sesudah mutasi. |

---

## 2. Struktur Layout Global
Semua layar di atas dibungkus secara konsisten oleh komponen `AppShell` yang mengintegrasikan `TopBar`, `Sidebar`, dan `Drawer` Notifikasi secara terpadu.
