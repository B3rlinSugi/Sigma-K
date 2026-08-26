# 05. BACKEND ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Backend Architect & Principal Engineer  
> **Stack Terpilih:** NestJS 10+ (Node.js LTS / TypeScript Strict) + Prisma ORM + PostgreSQL 16 + Redis 7  

Dokumen ini mendefinisikan arsitektur backend inti (*Core Backend Architecture*) SIGMA-K dengan pola **Modular Monolith Layered Clean Architecture** yang dirancang untuk menjaga integritas data tinggi, performa cepat, dan keamanan menyeluruh.

---

## 1. Lapisan Arsitektur Backend (Layered Clean Architecture)

Setiap modul di backend diorganisasikan ke dalam 5 lapisan yang terisolasi dengan aturan ketergantungan searah (*Dependency Rule*):

```
+-----------------------------------------------------------------------------------+
| 1. CONTROLLER LAYER (Interface Adapters / REST & WebSocket Gateways)              |
|    - Menerima HTTP Request / WSS Event, validasi DTO, memanggil Use Case Service.  |
+-----------------------------------------------------------------------------------+
                                         │ (Calls)
                                         ▼
+-----------------------------------------------------------------------------------+
| 2. APPLICATION SERVICE / USE CASE LAYER (Business Orchestration)                  |
|    - Mengorkestrasi aturan bisnis, mengelola transaksi ACID, memancarkan event.   |
+-----------------------------------------------------------------------------------+
                                         │ (Calls)
                                         ▼
+-----------------------------------------------------------------------------------+
| 3. DOMAIN LOGIC LAYER (Core Business Entities & Domain Rules)                     |
|    - Logika validasi siklus pohon, aturan temporal kabinet, state machine tiket.   |
+-----------------------------------------------------------------------------------+
                                         │ (Uses)
                                         ▼
+-----------------------------------------------------------------------------------+
| 4. REPOSITORY & DATA ACCESS LAYER (Persistence / Prisma ORM)                      |
|    - Abstraksi query PostgreSQL, recursive CTE, indeks, JSONB snapshots.          |
+-----------------------------------------------------------------------------------+
                                         │ (Interacts)
                                         ▼
+-----------------------------------------------------------------------------------+
| 5. INFRASTRUCTURE & CROSS-CUTTING LAYER (External Drivers & Utilities)            |
|    - Redis Pub/Sub, MinIO Storage Client, Audit Logger, Realtime Dispatcher.     |
+-----------------------------------------------------------------------------------+
```

---

## 2. Struktur Modul Backend (`src/modules/`)

```
src/
├── app.module.ts                 # Root Module (Penyatu seluruh modul)
├── main.ts                       # Entrypoint (Bootstrap, Global Pipes, Filters, Swagger)
├── common/                       # Shared Kernel (Cross-cutting infrastructure)
│   ├── decorators/               # @CurrentUser(), @Roles(), @ScopedInstitution()
│   ├── filters/                  # HttpExceptionFilter, PrismaExceptionFilter
│   ├── guards/                   # JwtAuthGuard, RolesGuard, InstitutionScopeGuard
│   ├── interceptors/             # ResponseTransformInterceptor, AuditLogInterceptor
│   ├── pipes/                    # GlobalValidationPipe, ParseUUIDPipe
│   └── interfaces/               # ApiResponse<T>, PaginationQuery, AuditPayload
├── modules/                      # Domain Feature Modules (Modular Monolith)
│   ├── auth/                     # AuthController, AuthService, JwtStrategy, LocalStrategy
│   ├── institutions/             # InstitutionController, InstitutionService, InstitutionRepo
│   ├── cabinets/                 # CabinetController, CabinetService, CabinetPeriodRepo
│   ├── organizations/            # OrgController, OrgTreeService (Cycle Detection), OrgRepo
│   ├── tupoksi/                  # TupoksiController, TupoksiService, TupoksiRepo
│   ├── workflows/                # SubmissionController, WorkflowService, StateMachineEngine
│   ├── notifications/            # NotificationGateway (Socket.io), NotificationService
│   ├── audit/                    # AuditLogController, AuditLogService, AuditRepo
│   └── analytics/                # AnalyticsController, AnalyticsService (Posture Aggregator)
└── database/                     # Prisma Schema, Seeders, Migration Logic Plans
```

---

## 3. Manajemen Transaksi Atomik (Atomic Transaction Management)

Untuk memenuhi kebutuhan [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) dan [BRULE-017](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md) (Persetujuan data draf ke Master Data aktif secara atomik), backend menerapkan pola **Unit of Work Transaction**:

```typescript
// Konsep Pola Transaksi Atomik pada Approval Data (Conceptual Blueprint)
async approveSubmissionTicket(ticketId: string, adminUser: User): Promise<void> {
  await this.prisma.$transaction(async (tx) => {
    // 1. Validasi status tiket (wajib VERIFIED)
    const ticket = await tx.submissionTicket.findUniqueOrThrow({ where: { id: ticketId } });
    if (ticket.status !== 'VERIFIED') throw new InvalidWorkflowStateException();

    // 2. Terapkan seluruh item perubahan draf ke Master Data aktif
    const items = await tx.submissionItem.findMany({ where: { submissionTicketId: ticketId } });
    for (const item of items) {
      await this.applyDraftToMaster(item, tx);
    }

    // 3. Ubah status tiket menjadi APPROVED
    await tx.submissionTicket.update({
      where: { id: ticketId },
      data: { status: 'APPROVED', approvedAt: new Date(), approvedByUserId: adminUser.id }
    });

    // 4. Catat jejak audit permanen di dalam transaksi yang sama
    await tx.auditLog.create({
      data: {
        userId: adminUser.id,
        actionType: 'APPROVE',
        entityName: 'InstitutionMaster',
        entityId: ticket.institutionId,
        newValuesJson: { ticketNumber: ticket.ticketNumber, approvedAt: new Date() }
      }
    });
  });

  // 5. Setelah transaksi DB berhasil commit, pancarkan realtime event
  this.eventDispatcher.emit('DATA_MUTATED', { ticketId, type: 'APPROVAL_PUBLISHED' });
}
```

---

## 4. Pipeline Validasi Input & Standar Respon API

### A. Validasi DTO Ketat (*Strict Input Validation*)
- Seluruh endpoint menerima data melalui DTO (*Data Transfer Object*) yang divalidasi menggunakan `class-validator` dan `class-transformer`.
- Parameter yang tidak terdaftar otomatis dibuang (*whitelist mode: true*) untuk mencegah serangan *Mass Assignment*.

### B. Format Standar Respon API (*Uniform Response Wrapper*)
Seluruh respon sukses dan error dibungkus secara konsisten oleh `ResponseTransformInterceptor` dan `HttpExceptionFilter`:

```json
// Format Standar Respon Sukses (200 / 201)
{
  "success": true,
  "statusCode": 200,
  "message": "Institution profile retrieved successfully",
  "data": { ... },
  "meta": {
    "page": 1,
    "limit": 20,
    "totalItems": 48,
    "totalPages": 3
  },
  "timestamp": "2026-08-25T20:00:00.000Z"
}

// Format Standar Respon Error (400 / 403 / 404 / 500)
{
  "success": false,
  "statusCode": 403,
  "error": "ForbiddenException",
  "message": "Anda tidak memiliki izin untuk mengedit instansi di luar scope penugasan Anda",
  "timestamp": "2026-08-25T20:00:00.000Z",
  "path": "/api/v1/institutions/draft/12"
}
```

---

## 5. Event Dispatcher & Realtime WebSocket Gateway

1. **In-Process Domain Event Emitter:** Menggunakan `@nestjs/event-emitter` untuk menghubungkan mutasi di Application Service dengan modul Notification dan Audit tanpa kopling langsung.
2. **WebSocket Gateway (`NotificationGateway`):**
   - Menggunakan namespace `/realtime` terproteksi JWT token handshake.
   - Mengelola room per user (`user:{id}`) dan per instansi (`institution:{id}`).
   - Mendukung broadcast multi-node menggunakan Redis Adapter (`@socket.io/redis-adapter`) jika aplikasi di-scale secara horizontal di masa depan.
