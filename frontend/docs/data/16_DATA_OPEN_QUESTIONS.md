# 16. DATA ARCHITECTURE OPEN QUESTIONS & UNCERTAINTIES: SIGMA-K

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Penyusun:** Lead Software Architect & Data Governance Architect  
> **Target Validasi:** SESDEP KemenPANRB, Pak Sigit, Kak Nabila, & Ikhsan  

Dokumen ini memetakan seluruh pertanyaan terbuka (*Open Questions*) dan ketidakpastian kebijakan data yang memerlukan validasi resmi dari stakeholder atau mentor sebelum implementasi fisik.

---

## 1. Matriks Pertanyaan Terbuka Berdasarkan Prioritas

| Q-ID | Domain / Topik | Pertanyaan Kunci (*Key Open Question*) | Dampak Arsitektural / Data | Tingkat Prioritas | Pihak yang Wajib Menjawab |
|---|---|---|---|:---:|---|
| **DOQ-01** | Master Source Authority | Apa sumber data primer resmi yang menjadi *Single Source of Truth* untuk daftar nama dan kode instansi nasional (KemenPANRB, BKN SIASN, atau Kemendagri)? | Penentuan konstrain keunikan kode instansi dan sinkronisasi API eksternal. | **CRITICAL** | SESDEP / Pak Sigit |
| **DOQ-02** | Cabinet Lineage Authority | Apakah dokumen resmi Keppres pembentukan Kabinet Merah Putih telah memuat pemetaan silsilah formal untuk seluruh 48 kementerian baru (termasuk pemecahan kementerian)? | Kelengkapan dataset awal `institution_lineages` untuk demo pimpinan. | **CRITICAL** | SESDEP / Kak Nabila |
| **DOQ-03** | Approval Workflow Policy | Apakah pengajuan data unit kerja dan tupoksi dari Operator Instansi memerlukan penugasan/triage manual oleh Admin sebelum diperiksa Verifikator? | Konfigurasi state machine workflow (`WORKFLOW_CONFIG`). | **CRITICAL** | SESDEP / Kak Nabila |
| **DOQ-04** | Standardisasi Kode Instansi | Apakah SIGMA-K diizinkan menerbitkan format kode instansi mandiri (misal: `KL-042`) jika kode resmi dari BKN/Kemendagri belum terbit untuk kementerian baru? | Desain generator kode fallback pada skrip migrasi. | **HIGH** | Pak Sigit / Ikhsan |
| **DOQ-05** | Historical Versioning Scope | Apakah perubahan profil instansi (alamat, email, kontak) memerlukan *Slowly Changing Dimension (SCD Type 2)* atau cukup mencatat nilai terkini dengan jejak audit? | Kompleksitas tabel `institution_profiles` vs histori audit JSONB. | **HIGH** | Pak Sigit / Berlin |
| **DOQ-06** | SSO Identity Provider Spec | Apakah server Single Sign-On (SSO) ASN Digital menggunakan standar OpenID Connect (OIDC) atau SAML 2.0, dan apa nama klaim NIP/Instansi pada token? | Konfigurasi payload klaim adaptor OIDC SSO. | **HIGH** | Tim Pusdatin KemenPANRB |
| **DOQ-07** | Data Retention Policy | Berapa tahun masa retensi minimum untuk draf pengajuan yang ditolak (`REJECTED`) dan log audit forensik (`audit_logs`) sesuai regulasi kearsipan? | Konfigurasi partisi PostgreSQL dan archiving cron job. | **MEDIUM** | SESDEP / Bagian Hukum |
| **DOQ-08** | Legacy Orphan Unit Action | Untuk ~1.2% unit kerja legacy yang parent_id-nya terputus, apakah diizinkan dikaitkan otomatis ke unit Sekretariat Utama / Root saat migrasi? | Aturan cleansing skrip ETL migrasi pohon organisasi. | **MEDIUM** | Ikhsan / Pak Sigit |
| **DOQ-09** | Postur ASN Integration | Seberapa sering feed data postur kepegawaian ASN akan diperbarui (apakah batch bulanan dari BKN atau manual upload berkala)? | Jadwal refresh `mv_asn_posture_aggregates`. | **MEDIUM** | Pak Sigit / Ikhsan |
| **DOQ-10** | Storage Quota Policy | Apakah ada batas kuota total penyimpanan berkas digital PDF regulasi per kementerian? | Alokasi volume MinIO / filesystem storage. | **LOW** | Tim Infrastruktur |

---

## 2. Rencana Tindak Lanjut Penutupan Pertanyaan Terbuka
- **Diskusi Khusus Tim Teknis & Mentor:** Mengagendakan sesi telaah bersama Kak Nabila dan Pak Sigit untuk memvalidasi `DOQ-01`, `DOQ-04`, `DOQ-05`, dan `DOQ-08`.
- **Rapat Konfirmasi Pimpinan (SESDEP):** Mengajukan `DOQ-02` dan `DOQ-03` pada agenda paparan progress kelembagaan berikutnya.
