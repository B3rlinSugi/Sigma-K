# SIGMA-K — SESDEP EXECUTIVE DEMO SCRIPT & WALKTHROUGH

## 1. Panduan Skenario Presentasi Eksekutif (Durasi: 15–20 Menit)
Dokumen ini disusun sebagai panduan langkah demi langkah (*step-by-step presentation script*) bagi tim developer (Berlin & Ikhsan) saat mendemonstrasikan prototipe SIGMA-K kepada **SESDEP Kelembagaan dan Tata Laksana, Kementerian PANRB**.

---

## 2. Runtutan Skenario Demonstrasi

### Segmen 1: Pembukaan & Gambaran Umum Eksekutif (3 Menit)
1. **Layar:** `/` (*Executive Overview Dashboard*).
2. **Persona Aktif:** `SESDEP` (Bapak Nanang Khoiruddin).
3. **Poin Paparan:**
   - Sambut pimpinan dan jelaskan bahwa SIGMA-K adalah modernisasi sistem kelembagaan nasional.
   - Sorot metrik utama: **48 Kementerian/Lembaga Kabinet Merah Putih** dan **548 Pemerintah Daerah**.
   - Tunjukkan kartu sorotan Kabinet Merah Putih dengan dasar hukum Keppres 133/P/2024.

### Segmen 2: Fitur Unggulan — Komparasi & Silsilah Antar-Kabinet (4 Menit)
1. **Layar:** `/cabinets/compare` (*Komparasi Antar-Kabinet*).
2. **Poin Paparan:**
   - Tunjukkan bagaimana SIGMA-K secara otomatis menghitung *delta perubahan*: **+7 Instansi Baru, 3 Pemecahan (Split), 1 Penggabungan, 5 Perubahan Nama**.
   - Buka tab **Pemecahan (Split)** untuk memperlihatkan silsilah pemecahan Kemendikbudristek menjadi Kemendikdasmen, Kemendiktisaintek, dan Kementerian Kebudayaan.
   - Jelaskan bahwa data historis kabinet lama tidak hilang dan dapat ditelusuri kembali kapan saja (*backward compatibility*).

### Segmen 3: Visualisasi Struktur Organisasi Interaktif (React Flow) (4 Menit)
1. **Layar:** `/structure` (*Bagan Organisasi Canvas*).
2. **Poin Paparan:**
   - Pilih instansi **Kementerian Koordinator Bidang Pangan**.
   - Tunjukkan interaktivitas kanvas: zoom in/out, panning kanvas, dan minimap.
   - Klik salah satu node eselon (contoh: *Deputi Bidang Koordinasi Ketersediaan dan Stabilisasi Pangan*) untuk membuka *Drawer* samping yang menampilkan pejabat pimpinan, jumlah personel, dan tugas pokok pengampu.
   - Tekankan bahwa struktur ini divalidasi oleh *Anti-Circular Dependency Guard* pada level basis data.

### Segmen 4: Alur Pengajuan, Telaah Berdampingan, & Pengesahan (5 Menit)
1. **Langkah A (Operator):**
   - Gunakan **Persona Switcher** di TopBar untuk beralih ke `USER` (Budi Santoso - Operator Kemenko Pangan).
   - Buka menu `/submissions` dan tunjukkan tiket `TKT-20260825-0042` (Usulan Struktur Biro Perencanaan Baru).
2. **Langkah B (Verifikator PANRB):**
   - Ganti persona ke `VERIFIKATOR` (Siti Rahmawati).
   - Buka `/verifications` dan klik tiket Kemenko Pangan untuk masuk ke **Ruang Telaah Berdampingan** (`/verifications/sub-001`).
   - Tunjukkan bagaimana verifikator memeriksa data master lama di sebelah kiri vs usulan baru di sebelah kanan.
   - Klik tombol **Lolos Verifikasi (Pass)** dan simpan catatan telaah.
3. **Langkah C (Pengesahan Admin):**
   - Ganti persona ke `ADMIN` (Ahmad Fauzi) dan buka tiket yang telah berstatus `VERIFIED`.
   - Klik tombol **Sahkan ke Master Data** untuk mendemonstrasikan pengesahan langsung secara atomik ke basis data produksi.

### Segmen 5: Intelijensi Data & Audit Trail Forensik (4 Menit)
1. **Layar:** `/analytics` (*Analitik & Postur ASN*).
   - Tunjukkan hasil kolaborasi Data Analyst (Ikhsan & Pak Sigit): **Rasio Delayering Eselon (68.4%)**, **Indeks Kesiapan 48 K/L Kabinet Merah Putih (87.5%)**, dan **Kecepatan Verifikasi (1.8 Hari)**.
2. **Layar:** `/audit-logs` (*Audit Trail Forensik*).
   - Tunjukkan catatan mutasi data yang baru saja disahkan beserta modal penampil snapshot JSON nilai lama vs nilai baru (*immutable audit log*).
