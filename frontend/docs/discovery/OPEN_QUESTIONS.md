# OPEN QUESTIONS: SIGMA-K

> **Status:** DISCOVERY BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  

Dokumen ini memuat daftar pertanyaan terbuka (*Open Questions*) yang perlu diklarifikasi bersama User, Pembimbing, dan Pimpinan/SESDEP sebelum melangkah ke fase arsitektur dan implementasi teknis berikutnya.

---

## 1. Prioritas CRITICAL (Memblokir Desain Arsitektur & Model Data)

| ID | Pertanyaan Kunci | Stakeholder Terkait | Dampak Arsitektural jika Belum Terjawab |
|---|---|---|---|
| **Q-CRIT-01** | **Mekanisme Autentikasi:** Apakah sistem SIGMA-K harus terintegrasi langsung dengan Single Sign-On (SSO) KemenPANRB / ASN Digital Nasional, atau menggunakan otentikasi mandiri berbasis database pada rilis awal? | Tim IT KemenPANRB / SESDEP | Menentukan arsitektur IAM, protokol OAuth2/OIDC, dan struktur tabel user. |
| **Q-CRIT-02** | **Alur Formal SOP Verifikasi:** Apakah alur verifikasi data instansi hanya 1 tingkat (`User` $\rightarrow$ `Verifikator` $\rightarrow$ `Admin`) atau bertingkat menurut unit kedeputian (misal: Verifikator Asdep $\rightarrow$ Koordinator $\rightarrow$ SESDEP)? | SESDEP / Analis Kelembagaan | Menentukan kompleksitas state machine workflow dan skema tabel `SubmissionTicket`. |
| **Q-CRIT-03** | **Lingkungan Server & Hosting Target:** Apakah sistem akan di-deploy pada Pusat Data Nasional (PDN), Cloud KemenPANRB, atau Virtual Machine On-Premise? Apakah ada batasan teknologi/port terbuka (misal port WebSocket)? | Tim Infrastruktur / Pusdatin | Mempengaruhi pemilihan stack backend, konfigurasi WebSocket/SSE, dan containerization (Docker). |
| **Q-CRIT-04** | **Cakupan Keanggotaan Kabinet:** Pada penetapan kabinet (misal Kabinet Merah Putih), apakah yang dicatat hanya Kementerian Koordinator dan Kementerian Teknis, atau seluruh Lembaga Pemerintah Non-Kementerian (LPNK) dan Lembaga Non-Struktural (LNS)? | SESDEP / Data Analyst | Menentukan logika filter keanggotaan kabinet pada database dan dashboard pimpinan. |

---

## 2. Prioritas HIGH (Mempengaruhi Desain Fitur Utama & Integrasi Data)

| ID | Pertanyaan Kunci | Stakeholder Terkait | Dampak pada Sistem |
|---|---|---|---|
| **Q-HIGH-01** | **Sumber & Frekuensi Pembaruan Postur ASN:** Bagaimana mekanisme pembaruan data `v_postur_asn`? Apakah melalui sinkronisasi terjadwal dari API SIASN BKN, atau melalui impor batch berkala oleh Data Analyst? | Data Analyst (Ikhsan) / BKN | Mempengaruhi perancangan modul analitik dan arsitektur ETL data. |
| **Q-HIGH-02** | **Kewenangan Mutasi Verifikator:** Apakah Verifikator diperbolehkan mengoreksi kesalahan ketik (*typo*) secara langsung saat memeriksa draf, atau wajib mengembalikannya sebagai catatan revisi ke User? | Koordinator Verifikator | Mempengaruhi hak akses permission dan alur state approval pada UI. |
| **Q-HIGH-03** | **Kanal Notifikasi Eksternal:** Apakah notifikasi realtime cukup dalam bentuk *in-app notifications* (web bell badge & toast), atau wajib dilengkapi notifikasi Email / WhatsApp resmi kementerian? | SESDEP / Admin | Menentukan dependensi terhadap mail server SMTP atau gateway pihak ketiga. |
| **Q-HIGH-04** | **Kebijakan Partisi Verifikator:** Apakah penugasan tiket verifikasi dibagi berdasarkan jenis instansi (K/L vs Pemda), wilayah geografis (Barat/Tengah/Timur), atau sistem antrean terbuka (*pooled queue*)? | Koordinator Verifikator | Menentukan logika penugasan (*assignment engine*) pada backend. |

---

## 3. Prioritas MEDIUM (Mempengaruhi Spesifikasi Fungsional Sekunder)

| ID | Pertanyaan Kunci | Stakeholder Terkait | Dampak pada Sistem |
|---|---|---|---|
| **Q-MED-01** | **Spesifikasi Lampiran Regulasi:** Berapa batas ukuran maksimum file regulasi dasar hukum (PDF Perpres/Permen/Perda) yang dapat diunggah instansi? | Tim IT / Admin | Menentukan kebijakan kapasitas storage objek / file storage engine. |
| **Q-MED-02** | **Kebutuhan Data Geospasial (Peta):** Apakah pada dashboard instansi pemda dan K/L dibutuhkan visualisasi peta interaktif GIS dengan titik koordinat kantor? | SESDEP / Data Analyst | Menentukan kebutuhan data koordinat latitude/longitude pada master instansi. |
| **Q-MED-03** | **Format Ekspor Laporan:** Format dokumen pelaporan apa saja yang menjadi standar wajib pimpinan (PDF resmi berkop surat, Excel spreadsheet, atau infografis PNG)? | SESDEP / Staf Pimpinan | Menentukan library report generator pada backend/frontend. |
| **Q-MED-04** | **Kebijakan Retensi Log Audit:** Berapa lama riwayat log aktivitas pengguna wajib disimpan aktif di database utama sebelum diarsipkan? | Tim Keamanan IT / Admin | Menentukan strategi partisi tabel dan kebijakan pengarsipan data. |

---

## 4. Prioritas LOW (Penyesuaian Visual & Preferensi UI)

| ID | Pertanyaan Kunci | Stakeholder Terkait | Dampak pada Sistem |
|---|---|---|---|
| **Q-LOW-01** | **Preferensi Tema UI Pimpinan:** Apakah pimpinan lebih menyukai tampilan default bertema *Corporate Modern Light* atau *Executive Dark Glassmorphism* untuk sesi presentasi? | Tim Presentasi / User | Penyesuaian tema visual utama pada prototype. |
| **Q-LOW-02** | **Kustomisasi Tata Letak Widget Dashboard:** Apakah posisi widget metrik di dashboard utama perlu dibuat dapat dikustomisasi (*drag-and-drop widgets*) per akun pimpinan? | SESDEP / User | Penambahan fleksibilitas grid layout pada dashboard. |

---

## 5. Ringkasan Status Pertanyaan
- **Pertanyaan CRITICAL:** 4
- **Pertanyaan HIGH:** 4
- **Pertanyaan MEDIUM:** 4
- **Pertanyaan LOW:** 2
- **Total Open Questions:** 14
