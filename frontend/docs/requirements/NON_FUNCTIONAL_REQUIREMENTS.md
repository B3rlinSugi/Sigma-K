# NON-FUNCTIONAL REQUIREMENTS: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  
> **Catatan:** Dokumen ini mendefinisikan standar kualitas teknis tanpa menentukan merek framework/teknologi final secara prematur.

---

## 1. Matriks Kategori NFR

```
+-----------------------------------------------------------------------------------+
|                        NON-FUNCTIONAL REQUIREMENTS (NFR)                          |
+-----------------------------------------------------------------------------------+
| 1. Security & Auth (NFR-SEC-*)       | 7. Observability & Logs (NFR-OBS-*)        |
| 2. Performance & Realtime (NFR-PERF-*)| 8. Usability & UI/UX (NFR-UX-*)           |
| 3. Scalability & Capacity (NFR-SCL-*) | 9. Data Integrity & Privacy (NFR-DAT-*)   |
| 4. Reliability & Availability (NFR-REL-*)| 10. Backup & Recovery (NFR-BCK-*)      |
| 5. Auditability & Governance (NFR-AUD-*)| 11. API & Integration (NFR-API-*)       |
| 6. Maintainability (NFR-MNT-*)       | 12. Deployment & Infra (NFR-INF-*)         |
+-----------------------------------------------------------------------------------+
```

---

## 2. Rincian Non-Functional Requirements

### A. Security, Authentication & Authorization

| NFR-ID | Kategori | Spesifikasi Kebutuhan Kualitas | Metrik / Target | Status |
|---|---|---|---|---|
| **NFR-SEC-001** | Authentication | Kredensial password pengguna wajib disimpan menggunakan algoritma hashing standar industri yang memiliki proteksi salt dan *work factor* dinamis. Password tidak boleh disimpan dalam format plain text. | Hashing standar (Bcrypt / Argon2 / PBKDF2) | **CONFIRMED** |
| **NFR-SEC-002** | Session Security | Sesi otentikasi wajib menggunakan token terenkripsi/ditandatangani secara kriptografis (*cryptographically signed*) dengan masa berlaku terbatas dan mekanisme rotasi/invalidation saat logout. | Expiry token maks 8 jam (workday) | **CONFIRMED** |
| **NFR-SEC-003** | Authorization | Setiap pemanggilan antarmuka layanan (API) dan perpindahan halaman wajib memvalidasi izin peran (*Role-Based Access Control*) dan batasan cakupan data instansi (*Data Scoping*). Akses tidak sah wajib ditolak dengan kode status otorisasi standar. | Zero Unauthorized Access (100% Policy Enforced) | **CONFIRMED** |
| **NFR-SEC-004** | Transport Security | Seluruh komunikasi data antara peramban (*client browser*) dan server wajib menggunakan enkripsi protokol transfer aman (*TLS 1.3 / HTTPS*). | 100% Traffic HTTPS | **CONFIRMED** |
| **NFR-SEC-005** | OWASP Protection | Sistem wajib menerapkan mitigasi terhadap kerentanan OWASP Top 10, mencakup SQL Injection, Cross-Site Scripting (XSS), Cross-Site Request Forgery (CSRF), dan Broken Object Level Authorization (BOLA). | Zero Critical Vulnerabilities | **CONFIRMED** |

---

### B. Performance & Realtime Capability

| NFR-ID | Kategori | Spesifikasi Kebutuhan Kualitas | Metrik / Target | Status |
|---|---|---|---|---|
| **NFR-PRF-001** | Response Time | Waktu respons pemanggilan API untuk pembacaan data master dan daftar instansi ber-paginasi harus sangat cepat di bawah beban kerja normal. | $95^{\text{th}}$ percentile response time $\le 500$ ms | **CONFIRMED** |
| **NFR-PRF-002** | Dashboard Latency | Waktu pemuatan (*load time*) dashboard eksekutif dan pohon hierarki organisasi visual tidak boleh melebihi batas toleransi kenyamanan presentasi pimpinan. | Pemuatan awal $\le 2$ detik | **CONFIRMED** |
| **NFR-PRF-003** | Realtime Latency | Jeda waktu antara terjadinya peristiwa mutasi data (Create/Update/Delete/Verify) di server hingga diterimanya notifikasi di layar pengguna lain harus seketika (*realtime delivery*). | Jeda propagasi notifikasi $\le 1000$ ms | **CONFIRMED** |
| **NFR-PRF-004** | Resource Footprint | Penggunaan resource memori pada sisi peramban (*client browser*) saat me-render pohon bagan organisasi besar (ratusan node) harus dioptimasi agar tidak terjadi *freezing* / *memory leak*. | Frame rate rendering $\ge 60$ FPS | **CONFIRMED** |

---

### C. Scalability & Capacity

| NFR-ID | Kategori | Spesifikasi Kebutuhan Kualitas | Metrik / Target | Status |
|---|---|---|---|---|
| **NFR-SCL-001** | Institutional Capacity | Skema arsitektur data harus mampu menampung seluruh instansi pemerintah pusat (Kementerian, LPNK, LNS) dan seluruh pemerintah daerah di Indonesia tanpa penurunan performa struktural. | Target: 50+ K/L Pusat, 38 Pemda Provinsi, 514 Pemda Kab/Kota ($>600$ Instansi Utama) | **CONFIRMED** |
| **NFR-SCL-002** | Unit Hierarchy Capacity | Sistem harus mampu mengelola puluhan ribu node unit kerja hierarkis (dari unit eselon I hingga unit terkecil di seluruh instansi). | Kapasitas: $\ge 50.000$ Organization Units | **CONFIRMED** |
| **NFR-SCL-003** | Concurrent Users | Sistem harus mampu menangani pengguna konkuren (*concurrent users*) yang mengakses sistem dan menerima event realtime secara bersamaan. | Baseline: $\ge 100$ concurrent active users (dapat ditingkatkan) | **CONFIRMED** |

---

### D. Reliability, Availability & Fault Tolerance

| NFR-ID | Kategori | Spesifikasi Kebutuhan Kualitas | Metrik / Target | Status |
|---|---|---|---|---|
| **NFR-REL-001** | Service Availability | Layanan sistem ditargetkan memiliki ketersediaan tinggi selama jam kerja operasional kementerian. | Target Availability: $99.5\%$ pada jam kerja | **CONFIRMED** |
| **NFR-REL-002** | Graceful Degradation | Jika saluran realtime (koneksi event) terputus sementara, aplikasi frontend harus secara elegan mencoba menyambung kembali (*auto-reconnect*) tanpa menyebabkan halaman crash. | Auto-retry dengan exponential backoff | **CONFIRMED** |
| **NFR-REL-003** | Transactional ACID | Seluruh proses mutasi data gabungan (misal: persetujuan pengajuan yang mengubah master data dan menutup tiket) wajib dijalankan dalam transaksi atomik database (ACID compliant). | 100% Transaksi Atomik (Rollback on Error) | **CONFIRMED** |

---

### E. Auditability & Observability

| NFR-ID | Kategori | Spesifikasi Kebutuhan Kualitas | Metrik / Target | Status |
|---|---|---|---|---|
| **NFR-AUD-001** | Immutability | Data catatan audit (*audit trail*) harus tersimpan pada tabel append-only yang tidak mengizinkan operasi UPDATE maupun DELETE secara aplikasi. | Zero Modification on Audit Table | **CONFIRMED** |
| **NFR-AUD-002** | Completeness | Setiap log audit wajib merekam konteks lengkap: User ID, Timestamp presisi milidetik, Jenis Aksi, ID Entitas, Snapshot Nilai Lama (JSON), Snapshot Nilai Baru (JSON), dan IP Address. | 100% Audit Metadata Captured | **CONFIRMED** |
| **NFR-OBS-001** | Application Logging | Aplikasi backend wajib menghasilkan log aplikasi terstruktur (JSON format) yang mencatat level log (`INFO`, `WARN`, `ERROR`) untuk keperluan debugging dan pemantauan kesehatan sistem. | Structured Logging Standard | **CONFIRMED** |

---

### F. Usability, Accessibility & Aesthetics

| NFR-ID | Kategori | Spesifikasi Kebutuhan Kualitas | Metrik / Target | Status |
|---|---|---|---|---|
| **NFR-UX-001** | Executive Aesthetics | Antarmuka pengguna (*UI*) harus dirancang modern, elegan, profesional, dan berstandar *Executive Grade* (menggunakan tipografi modern, palet warna harmonis, glassmorphism halus, dan kartu data informatif) untuk presentasi pimpinan/SESDEP. | Standard: Wow-Factor for Leadership | **CONFIRMED** |
| **NFR-UX-002** | Responsiveness | Seluruh tampilan halaman harus responsif dan dapat diakses dengan tata letak optimal pada layar Desktop/Laptop (resolusi $1366\times 768$ hingga $1920\times 1080$) serta Tablet. | Responsive Layout Standard | **CONFIRMED** |
| **NFR-UX-003** | Intuitive Navigation | Tata letak menu, breadcrumbs, dan indikator status pengajuan harus intuitif sehingga operator instansi baru dapat mengajukan data tanpa pelatihan intensif. | Task Completion Rate $\ge 90\%$ | **CONFIRMED** |

---

### G. Data Integrity, Privacy & Backup

| NFR-ID | Kategori | Spesifikasi Kebutuhan Kualitas | Metrik / Target | Status |
|---|---|---|---|---|
| **NFR-DAT-001** | Referential Integrity | Integritas data antar-relasi (Kabinet, Periode, Keanggotaan, Instansi, Unit Organisasi, Eselon) wajib dijaga menggunakan foreign key constraints yang ketat. | Zero Orphan Records | **CONFIRMED** |
| **NFR-DAT-002** | Soft Delete Policy | Data master instansi dan unit organisasi tidak boleh dihapus fisik (*hard delete*), melainkan menggunakan penanda waktu penghapusan (*soft delete flag*) untuk menjaga histori relasional. | 100% Soft Delete on Core Entities | **CONFIRMED** |
| **NFR-BCK-001** | Backup Capability | Arsitektur data harus mendukung prosedur pencadangan rutin (*scheduled backup*) dan pemulihan data (*disaster recovery restore*) dengan integritas penuh. | RPO $\le 24$ jam, RTO $\le 4$ jam | **CONFIRMED** |

---

### H. API Standards, Maintainability & Infrastructure

| NFR-ID | Kategori | Spesifikasi Kebutuhan Kualitas | Metrik / Target | Status |
|---|---|---|---|---|
| **NFR-API-001** | Standard Protocols | Layanan backend harus menyediakan antarmuka API berbasis protokol standar (RESTful API / JSON over HTTP) dengan dokumentasi antarmuka interaktif yang selalu sinkron. | OpenAPI / Swagger Documentation Standard | **CONFIRMED** |
| **NFR-API-002** | Rate Limiting | Endpoint publik dan otentikasi harus dilengkapi pembatasan laju permintaan (*rate limiting*) untuk mencegah serangan *brute force* dan *Denial of Service (DoS)*. | Rate Limit: 60 req/min per IP on Auth | **CONFIRMED** |
| **NFR-MNT-001** | Clean Architecture | Struktur kode backend dan frontend wajib menerapkan pemisahan lapisan tanggung jawab yang jelas (*Clean Architecture / Separation of Concerns*) guna memudahkan pemeliharaan jangka panjang. | Modular & Decoupled Codebase | **CONFIRMED** |
| **NFR-INF-001** | Containerization | Seluruh komponen aplikasi (Backend, Frontend, Service pendukung) harus dapat dijalankan dalam kontainer standar (*Dockerized environment*) untuk kemudahan portabilitas deployment. | Dockerfile & Docker Compose Support | **CONFIRMED** |
