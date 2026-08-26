# 24. ARCHITECTURE RISKS & MITIGATION MATRIX: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** DevSecOps Architect & Lead Full-Stack Engineer  

Dokumen ini memetakan seluruh risiko teknis dan arsitektural (*Architecture Risk Register*) pada pengembangan SIGMA-K beserta strategi mitigasi terstrukturnya.

---

## 1. Matriks Risiko Arsitektural & Strategi Mitigasi

| Risk ID | Deskripsi Risiko Arsitektur | Kategori | Tingkat Keparahan | Probabilitas | Strategi Mitigasi Terstruktur |
|---|---|---|:---:|:---:|---|
| **RISK-01** | **Kendala Koneksi WebSocket di Jaringan Intranet:** Proxy atau firewall intranet kementerian memblokir koneksi WSS persisten. | Network / Realtime | HIGH | MEDIUM | Menggunakan Socket.io dengan konfigurasi *Long-Polling fallback* otomatis dan opsi Server-Sent Events (SSE). |
| **RISK-02** | **Konflik Urutan Alur Kerja (Workflow Conflict):** Terjadi perbedaan persepsi alur verifikasi (Phase 1 vs Konsep Legacy) di kemudian hari. | Business Logic | HIGH | HIGH | Mengimplementasikan **Configurable State Machine Engine** di backend sehingga transisi urutan approval dapat diubah via file konfigurasi tanpa coding ulang. |
| **RISK-03** | **Anomali Integritas Data Legacy saat Migrasi:** Data `tbl_ref_instansi_org` legacy memiliki relasi putus (*orphan*) atau format string `data_kl` tidak konsisten. | Data Integrity | HIGH | MEDIUM | Menjalankan fase *Data Profiling & Cleansing Sandbox* bersama Data Analyst (Ikhsan) sebelum eksekusi migrasi ke PostgreSQL produksi. |
| **RISK-04** | **Degradasi Kinerja Render Pohon Organisasi Besar:** Me-render 1.000+ unit kerja kementerian sekaligus menyebabkan peramban lambat. | Frontend Performance | MEDIUM | MEDIUM | Menerapkan *Virtual Viewport Rendering* (React Flow) dan fitur *Collapsible Sub-trees* (cabang eselon tertutup secara default). |
| **RISK-05** | **Ketergantungan Eksternal SSO Mandat:** Jika pimpinan mendadak mewajibkan SSO ASN Digital di tengah masa pengembangan. | IAM / Security | MEDIUM | LOW | Menerapkan *Pluggable Auth Strategy Pattern* (ADR-005) sehingga adaptor OIDC siap dipasang dalam hitungan hari. |
| **RISK-06** | **Single Developer Bottleneck:** Beban implementasi penuh berada pada 1 Lead Full-Stack Engineer (Berlin). | Team Execution | HIGH | MEDIUM | Mengadopsi Monorepo dengan shared types `@sigma/types`, Modular Monolith yang terisolasi, dan scaffolding otomatis NestJS & Next.js. |

---

## 2. Rencana Tindakan Kontinjensi (Contingency Action Plans)
1. **Jika WebSocket Mengalami Kendala:** Beralih seketika ke Server-Sent Events (SSE) untuk broadcast notifikasi satu arah, yang 100% kompatibel dengan protokol HTTP/HTTPS standar.
2. **Jika Ekstraksi Data Legacy Mengalami Delay:** Tim Frontend dapat terus membangun prototype visual menggunakan *Mock Fixture Data* yang sesuai dengan kontrak tipe `@sigma/types` tanpa terhambat database legacy.
