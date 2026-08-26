# REQUIREMENT GAP ANALYSIS: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Senior Business Analyst & Requirements Engineer  

Dokumen ini mengidentifikasi kesenjangan (*Gaps*), ketidakpastian (*TBD*), informasi yang belum lengkap (*Missing Information*), dan potensi konflik kebutuhan (*Conflicts*) guna memitigasi risiko sebelum melangkah ke tahap desain arsitektur dan implementasi.

---

## 1. Klasifikasi Status Requirement

```
+-----------------------------------------------------------------------------------+
|                        REQUIREMENT MATURITY CLASSIFICATION                        |
+-----------------------------------------------------------------------------------+
|  [CONFIRMED]  (18 Items) : Siap diarsiteksikan dan diimplementasikan               |
|  [PROPOSED]   ( 7 Items) : Usulan desain solid, menunggu validasi stakeholder      |
|  [TBD]        ( 5 Items) : Memerlukan keputusan formal pimpinan/SESDEP            |
|  [CONFLICT]   ( 2 Items) : Perbedaan paradigma legacy vs target arsitektur        |
|  [MISSING]    ( 4 Items) : Detail teknis eksternal yang belum tersedia            |
+-----------------------------------------------------------------------------------+
```

---

## 2. Analisis Gaps & Kesenjangan Informasi Berdasarkan Prioritas

### A. Prioritas CRITICAL (Wajib Diselesaikan Sebelum Finalisasi Arsitektur & Skema DB)

| Gap ID | Kategori | Item Kebutuhan | Deskripsi Masalah / Kesenjangan | Dampak Risiko | Rekomendasi Solusi |
|---|---|---|---|---|---|
| **GAP-CRIT-01** | TBD | [REQ-026](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) (SSO Integration) | Belum ada kepastian apakah rilis awal wajib terhubung ke SSO KemenPANRB / ASN Digital atau menggunakan Database Authentication mandiri. | Jika arsitektur IAM berubah mendadak di tengah jalan, tabel otentikasi dan alur session harus di-refactor. | Rancang arsitektur Auth modular yang mendukung Local Auth (Bcrypt) dengan adaptor OAuth2/OIDC yang siap dipasang (*pluggable auth strategy*). |
| **GAP-CRIT-02** | TBD | [REQ-028](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) (Multi-Tier Verification) | Belum terkonfirmasi apakah alur verifikasi cukup 1 tingkat (`User` $\rightarrow$ `Verifikator` $\rightarrow$ `Admin`) atau bertingkat menurut jenjang eselon di Kedeputian Kelembagaan. | Mempengaruhi kompleksitas state machine workflow dan skema tabel `SubmissionTicket`. | Mengimplementasikan workflow engine berbasis konfigurasi fleksibel (*configurable workflow state*). |
| **GAP-CRIT-03** | Missing Information | [TBD-LEG-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/LEGACY_SYSTEM_INVENTORY.md) (DDL Database `eskld`) | Skema DDL lengkap, tipe data eksak, dan nullability kolom dari database legacy `eskld` belum diekstraksi secara formal. | Risiko kegagalan pemetaan ETL saat fase migrasi data jika ada tipe data yang tidak cocok. | Data Analyst Ikhsan melakukan profiling skema data legacy pada Phase 3. |
| **GAP-CRIT-04** | Conflict | Legacy Cabinet vs Modern Cabinet | Data legacy menyimpan kabinet sebagai string denormalized `list_id_kl`, sedangkan kebutuhan modern menuntut relasi relasional formal `CabinetMembership`. | Terjadi ketidaksesuaian model data jika kode legacy disalin mentah. | Mengadopsi keputusan [DEC-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md) & [DEC-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md): Legacy hanya rujukan, sistem baru membangun skema relasional bersih. |

---

### B. Prioritas HIGH (Mempengaruhi Desain Fitur Utama & Integrasi Data)

| Gap ID | Kategori | Item Kebutuhan | Deskripsi Masalah / Kesenjangan | Dampak Risiko | Rekomendasi Solusi |
|---|---|---|---|---|---|
| **GAP-HIGH-01** | Missing Information | [TBD-LEG-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/LEGACY_SYSTEM_INVENTORY.md) (Formula `v_postur_asn`) | Logika query dan sumber update berkala untuk view postur ASN (`v_postur_asn`) belum terdefinisi secara tertulis. | Dashboard analitik dapat menampilkan angka yang tidak sinkron dengan data resmi BKN. | Data Analyst (Ikhsan) menyusun kamus data dan validasi sumber data postur ASN. |
| **GAP-HIGH-02** | Proposed | [REQ-019](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) (Diff Viewer UI) | Kebutuhan antarmuka perbandingan visual (*Diff Viewer*) merupakan usulan teknis yang belum diuji coba ke calon verifikator riil. | Verifikator mungkin terbiasa dengan format form konvensional. | Membuat komponen Diff Viewer interaktif pada High-Fidelity Prototype (Phase 4) untuk divalidasi ke verifikator. |
| **GAP-HIGH-03** | Proposed | [REQ-020](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) (Anti-Circular Hierarchy) | Algoritma pencegahan siklus pada pohon struktur organisasi harus dirancang efisien pada backend. | Potensi *infinite loop* atau degradasi performa query jika pohon organisasi sangat dalam. | Menerapkan algoritma validasi DFS/BFS cycle detection pada layer service saat mutasi `parent_id`. |
| **GAP-HIGH-04** | Conflict | Ad-Hoc Tables vs Clean Schema | Keberadaan tabel `data_map_*` dan `data_map_yudhi_latest` di legacy menunjukkan relasi instansi dilakukan secara manual. | Data di tabel ad-hoc bisa jadi tidak konsisten dengan `tb_instansi`. | Melakukan audit data cleansing oleh Data Analyst dan menghapus tabel scratch tersebut pada skema baru. |

---

### C. Prioritas MEDIUM (Mempengaruhi Fitur Sekunder & Non-Inti)

| Gap ID | Kategori | Item Kebutuhan | Deskripsi Masalah / Kesenjangan | Dampak Risiko | Rekomendasi Solusi |
|---|---|---|---|---|---|
| **GAP-MED-01** | TBD | [REQ-027](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) (External Notification) | Belum ada keputusan apakah diperlukan integrasi SMTP email atau WhatsApp gateway instansi. | Perlu konfigurasi server dan kredensial eksternal tambahan jika diwajibkan. | Memprioritaskan In-App Notification (Toast & Notification Center) pada rilis awal; kanal eksternal diposisikan sebagai plugin sekunder. |
| **GAP-MED-02** | TBD | [REQ-029](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) (GIS Geospatial Mapping) | Ketersediaan data koordinat latitude/longitude kantor instansi K/L dan Pemda belum diverifikasi. | Peta interaktif tidak dapat menampilkan titik lokasi jika koordinat kosong. | Menjadikan kolom koordinat opsional pada master instansi dan menyiapkan fallback peta wilayah administratif standar. |
| **GAP-MED-03** | TBD | [REQ-030](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) (Tupoksi Overlap Engine) | Fitur analisis semantik NLP untuk mendeteksi tumpang tindih tupoksi memerlukan model AI/NLP khusus. | Kompleksitas tinggi yang berpotensi membebani durasi magang jika dipaksakan di fase awal. | Mengakomodasi pencarian teks kata kunci (Full-Text Search) terlebih dahulu pada Fase 1-6; modul NLP dievaluasi pada Future Scope. |
| **GAP-MED-04** | Missing Information | [TBD-ROLE-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/ACTOR_AND_ROLE_DISCOVERY.md) (Partisi Antrean Verifikator) | Pembagian tugas verifikator (apakah per wilayah, per jenis K/L vs Pemda, atau antrean bersama) belum diputuskan. | Desain query filtering antrean tiket verifikasi belum spesifik. | Membuat antrean tiket dengan filter fleksibel yang mendukung tampilan personal maupun tampilan tim (*shared pool*). |

---

### D. Prioritas LOW (Preferensi Visual & Kustomisasi Minor)

| Gap ID | Kategori | Item Kebutuhan | Deskripsi Masalah / Kesenjangan | Dampak Risiko | Rekomendasi Solusi |
|---|---|---|---|---|---|
| **GAP-LOW-01** | Proposed | [REQ-024](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) (Dark/Light Theme) | Opsi tema gelap/terang belum menjadi kebutuhan fungsional wajib. | Hanya preferensi kenyamanan visual pengguna. | Menyediakan toggle tema visual modern berbasis CSS tokens. |
| **GAP-LOW-02** | Missing Information | [Q-LOW-02](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/OPEN_QUESTIONS.md) (Custom Dashboard Widgets) | Kebutuhan pimpinan untuk mengatur posisi widget dashboard secara mandiri (*drag-and-drop*). | Penambahan state persisten konfigurasi dashboard per user. | Menyediakan tata letak dashboard eksekutif standar yang elegan terlebih dahulu. |

---

## 3. Matriks Rekapitulasi Gaps

| Tingkat Prioritas | Confirmed | Proposed | TBD | Conflict | Missing Info | Total Items |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **CRITICAL** | 0 | 0 | 2 | 1 | 1 | **4** |
| **HIGH** | 0 | 2 | 0 | 1 | 1 | **4** |
| **MEDIUM** | 0 | 0 | 3 | 0 | 1 | **4** |
| **LOW** | 0 | 1 | 0 | 0 | 1 | **2** |
| **Total** | **0** | **3** | **5** | **2** | **4** | **14 Gaps** |
