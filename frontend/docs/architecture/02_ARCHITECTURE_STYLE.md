# 02. ARCHITECTURE STYLE EVALUATION: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Principal Engineer  

Dokumen ini menyajikan evaluasi komparatif mendalam mengenai gaya arsitektur sistem (*Architecture Styles*) guna menentukan fondasi struktural terbaik untuk SIGMA-K.

---

## 1. Opsi Gaya Arsitektur yang Dievaluasi

1. **Opsi 1: Traditional Monolith (Monolitik Tradisional / Spageti Monolith)**
   - *Karakteristik:* Seluruh fitur, tampilan UI, dan logika bisnis digabung dalam satu basis kode tanpa isolasi modul yang tegas. Seluruh tabel berelasi langsung tanpa pemisahan domain.
2. **Opsi 2: Modular Monolith (Monolitik Termodulasi Murni)**
   - *Karakteristik:* Kode aplikasi berada dalam satu runtime/repositori terpadu, namun secara internal dibagi menjadi modul-modul independen (*bounded contexts*) dengan batasan antarmuka publik yang tegas dan modul *shared kernel*.
3. **Opsi 3: Microservices Architecture (Arsitektur Layanan Mikro Terdistribusi)**
   - *Karakteristik:* Setiap domain (Auth, Institution, Cabinet, Workflow, Notification) dipecah menjadi service terpisah dengan basis data independen (database-per-service), berkomunikasi melalui gRPC/HTTP API dan message broker.
4. **Opsi 4: Modular Monolith with Event-Driven Components (RECOMMENDED)**
   - *Karakteristik:* Fondasi aplikasi adalah *Modular Monolith* yang kokoh (memudahkan transaksi ACID dan pemeliharaan tim kecil), diperkaya dengan *In-Process / Light Broker Event Dispatcher* (misal Redis Pub/Sub / Event Bus) untuk menangani event realtime mutasi data dan audit logging asinkron.
5. **Opsi 5: Distributed Modular Architecture (BFF + Service Mesh)**
   - *Karakteristik:* Frontend memanggil Backend-for-Frontend (BFF) yang mendistribusikan request ke serangkaian modul terdistribusi dengan service mesh.

---

## 2. Matriks Evaluasi Komparatif

| Kriteria Evaluasi (Bobot 1-5) | Bobot | Opsi 1: Trad. Monolith | Opsi 2: Pure Mod. Monolith | Opsi 3: Microservices | Opsi 4: Mod. Monolith + Event-Driven | Opsi 5: Distributed BFF |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Kesesuaian Ukuran Tim (1 Eng + 1 DA)** | 5 | 4 / 5 | 5 / 5 | 1 / 5 | **5 / 5** | 2 / 5 |
| **Kecepatan Rilis Prototype (SESDEP)** | 5 | 4 / 5 | 5 / 5 | 1 / 5 | **5 / 5** | 2 / 5 |
| **Konsistensi Data Transaksional (ACID)** | 5 | 4 / 5 | 5 / 5 | 2 / 5 (2PC/Saga) | **5 / 5** (Atomic Single DB) | 3 / 5 |
| **Dukungan Mutasi Realtime & Event** | 4 | 2 / 5 | 3 / 5 | 4 / 5 | **5 / 5** (Event Bus/Redis) | 4 / 5 |
| **Kemudahan Pemeliharaan (Maintainability)**| 4 | 2 / 5 | 4 / 5 | 2 / 5 | **5 / 5** (Clear Boundary) | 3 / 5 |
| **Kompleksitas Operasional & DevOps** | 4 | 4 / 5 | 4 / 5 | 1 / 5 (K8s, Mesh) | **4 / 5** (Single Container) | 2 / 5 |
| **Skalabilitas Data Nasional (600+ K/L/Pemda)**| 4 | 2 / 5 | 4 / 5 | 5 / 5 | **4.5 / 5** (Read Replica Ready) | 4.5 / 5 |
| **Keamanan & Scoping RBAC Terpadu** | 4 | 3 / 5 | 4 / 5 | 3 / 5 (Distributed Auth) | **5 / 5** (Centralized Guard) | 4 / 5 |
| **Biaya Infrastruktur & Resource Footprint** | 3 | 4 / 5 | 4 / 5 | 1 / 5 (Multi-VM/Pods) | **4.5 / 5** (Low Footprint) | 2 / 5 |
| **Total Skor Tertimbang (Maks 185)** | | **123** | **156** | **78** | **176.5** | **106.5** |

---

## 3. Analisis & Pembahasan Mendalam

### Mengapa Microservices DITOLAK Secara Tegas?
1. **Beban Operasional (*Operational Overhead*) Terlalu Tinggi:** Microservices menuntut orkestrasi kontainer kompleks (Kubernetes), distributed tracing, distributed transaction management (Saga pattern), dan CI/CD pipeline multi-service. Hal ini tidak realistis dan tidak bertanggung jawab bagi tim inti magang (1 Lead Engineer + 1 Data Analyst).
2. **Kompleksitas Transaksi Approval Workflow:** SIGMA-K memiliki workflow persetujuan data draf ke master data aktif yang wajib bersifat atomik (*atomic transaction*). Pada microservices, melakukan transaksi yang mencakup modul instansi, struktur organisasi, tupoksi, tiket approval, dan audit log akan memicu *Distributed Transaction / Eventual Consistency* yang rawan kegagalan sinkronisasi.
3. **Volume Data Tidak Membutuhkan Microservices:** Total instansi pemerintah di Indonesia berjumlah ~600 instansi utama dengan puluhan ribu unit kerja. Skala data ini sangat terkelola dengan performa luar biasa pada database relasional tunggal yang dioptimasi dengan baik.

---

## 4. Rekomendasi Arsitektur Resmi: Modular Monolith + Event-Driven Components

### Rasional Arsitektur Terpilih:
1. **Struktur Modul Terisolasi (*Domain Bounded Contexts*):** Setiap modul (Kabinet, Instansi, Struktur, Tupoksi, Workflow, Audit, Analitik) memiliki domain logic dan repository sendiri. Modul dilarang mengakses database modul lain secara sembarangan, melainkan melalui service interface resmi.
2. **Kekuatan Transaksi ACID Penuh:** Seluruh mutasi master data dan approval tiket dieksekusi dalam satu transaksi database lokal tanpa risiko *split-brain* atau inkonsistensi data antar-layanan.
3. **Pipeline Event Realtime Terintegrasi:** Mutasi data memancarkan domain event internal yang ditangkap oleh Event Dispatcher. Event ini diteruskan ke transport realtime (WebSocket / SSE) untuk disiarkan ke client peramban secara seketika.
4. **Evolusi Tanpa Batas (*Future-Proof*):** Jika di masa depan suatu modul tertentu (misal modul Analitik atau NLP Semantic) memerlukan komputasi berat, modul tersebut dapat dipecah menjadi standalone service secara mulus karena batas antarmuka modul sudah terisolasi sejak awal.

---

## 5. Keputusan Resmi Gaya Arsitektur
- **Gaya Arsitektur Terpilih:** `Modular Monolith with Event-Driven Realtime Components`
- **Tingkat Kesiapan Implementasi:** **APPROVED FOR PHASE 2 BLUEPRINT**
- **Referensi ADR:** [ADR-001: Architecture Style Selection](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-001.md)
