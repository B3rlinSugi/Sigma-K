# 09. REALTIME NOTIFICATION ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  
> **Kebutuhan Terkait:** [REQ-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-NOT-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [BRULE-018](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)  

Dokumen ini mendefinisikan arsitektur penyiaran event realtime (*Realtime Event & Notification Pipeline*) yang dirancang untuk menyampaikan notifikasi mutasi data secara instan ($< 1000$ ms) ke peramban pengguna.

---

## 1. Alur Pipeline Notifikasi Realtime (End-to-End Event Pipeline)

```
[ MUTASI DATA BERHASIL DI DATABASE ]
                │
                │ 1. Transaction Committed
                ▼
+-----------------------------------------------------------------------------------+
| 1. DOMAIN EVENT PRODUCER (Application Service Layer)                              |
|    - Memancarkan internal event: `emit('institution.mutated', payload)`           |
+-----------------------------------------------------------------------------------+
                │
                │ 2. In-Process Dispatch / Redis PubSub
                ▼
+-----------------------------------------------------------------------------------+
| 2. NOTIFICATION ENGINE & PERSISTENCE (NotificationService)                        |
|    - Menentukan daftar penerima (Target Recipients berdasarkan Role & Scope).     |
|    - Menyimpan catatan notifikasi ke tabel PostgreSQL `notifications` (Unread).   |
|    - Membungkus payload event dengan Event ID unik (UUID) & Timestamp.            |
+-----------------------------------------------------------------------------------+
                │
                │ 3. Push to Realtime Gateway
                ▼
+-----------------------------------------------------------------------------------+
| 3. REALTIME TRANSPORT LAYER (Socket.io Gateway / WSS over TLS 1.3)                |
|    - Menyiarkan ke Room terkait: `io.to('user:123').emit('notification', data)`    |
|    - Menyiarkan ke Global Stream: `io.to('events:global').emit('activity', feed)` |
+-----------------------------------------------------------------------------------+
                │
                │ 4. WSS Protocol Push
                ▼
+-----------------------------------------------------------------------------------+
| 4. CLIENT PRESENTATION (Next.js Web Client)                                       |
|    - useSocket Hook menerima event.                                               |
|    - Menampilkan floating Toast pop-up instan.                                    |
|    - Menambah angka badge counter lonceng (+1) di Zustand Store.                  |
|    - Menjalankan TanStack Query Cache Invalidation (Data tabel ter-update).       |
+-----------------------------------------------------------------------------------+
```

---

## 2. Struktur Payload Event Terstandardisasi

Setiap event realtime dikirim dengan skema JSON baku:

```json
{
  "eventId": "e3b0c442-98fc-1c14-9af3-4be27063f912",
  "eventType": "TICKET_SUBMITTED",
  "category": "WORKFLOW",
  "title": "Pengajuan Struktur Baru",
  "message": "Operator Kementerian Koordinator Bidang Pangan telah mengajukan usulan struktur organisasi baru.",
  "metadata": {
    "ticketId": "tkt-8812",
    "ticketNumber": "TKT-20260825-0042",
    "institutionId": "inst-pangan-01",
    "institutionName": "Kemenko Bidang Pangan",
    "actionByUserId": "usr-operator-01",
    "actionByUserName": "Budi Santoso",
    "timestamp": "2026-08-25T20:15:30.000Z"
  },
  "actionUrl": "/verifications/tkt-8812"
}
```

---

## 3. Pencegahan Duplikasi & Ketahanan Sambungan (Fault-Tolerance)

1. **Idempotency & Duplicate Prevention:**
   - Setiap payload event membawa `eventId` (UUIDv4).
   - Pada sisi client, Zustand Notification Store menyimpan cache 50 ID event terakhir. Jika event dengan ID yang sama diterima dua kali (misal akibat reconnect), client secara otomatis mengabaikan event duplikat.
2. **Reconnection & Catch-Up Strategy:**
   - Jika koneksi WebSocket terputus akibat gangguan jaringan client, Socket.io secara otomatis mencoba menyambung kembali dengan *exponential backoff* ($1\text{s} \rightarrow 2\text{s} \rightarrow 5\text{s} \rightarrow 10\text{s}$).
   - Saat berhasil *reconnect*, client mengeksekusi sinkronisasi HTTP query (`GET /api/v1/notifications/unread-count`) untuk menarik notifikasi yang tertinggal saat offline.
3. **Pengelolaan Status Baca (Read / Unread):**
   - Pengguna dapat menandai satu notifikasi atau semua notifikasi sebagai sudah dibaca via operasi `PATCH /api/v1/notifications/:id/read` atau langsung mengirim event socket `mark_read`.
