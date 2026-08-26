# SIGMA-K — MOCK DATA ARCHITECTURE & CONTRACTS

## 1. Arsitektur Pemisahan Data Mock
Sesuai aturan keamanan arsitektur, seluruh dataset mock diisolasi secara terpisah dari komponen UI dan dikontrakkan menggunakan tipe TypeScript murni:

```
src/
├── types/              # 100% Strict TypeScript Contracts
│   ├── auth.ts
│   ├── cabinet.ts
│   ├── institution.ts
│   ├── organization.ts
│   ├── submission.ts
│   ├── notification.ts
│   ├── analytics.ts
│   └── audit.ts
└── data/mock/          # Typed Isolated Mock Datasets
    ├── users.ts
    ├── cabinets.ts
    ├── institutions.ts
    ├── organizations.ts
    ├── tupoksi.ts
    ├── submissions.ts
    ├── notifications.ts
    ├── analytics.ts
    └── auditLogs.ts
```

---

## 2. Inventaris Kontrak Domain TypeScript (`src/types/`)

| File Kontrak | Tipe / Interface Kunci | Deskripsi Domain |
| :--- | :--- | :--- |
| `auth.ts` | `User`, `UserRole`, `UserScope` | Model pengguna, persona demo, dan lingkup instansi. |
| `cabinet.ts` | `Cabinet`, `CabinetMembership`, `InstitutionLineage`, `CabinetComparisonSummary`, `LineageTransitionType` | Era kabinet kepresidenan, keanggotaan 48 K/L, dan silsilah transformasi delta (*split/merge/rename*). |
| `institution.ts`| `Institution`, `InstitutionProfile`, `InstitutionType`, `Region` | Master kementerian, lembaga, pemda provinsi/kab/kota, dan profil resmi. |
| `organization.ts`| `OrganizationUnit`, `EchelonLevel`, `TupoksiItem` | Unit hierarki struktural (*parent_id*) untuk React Flow dan butir tugas-fungsi berdasar pasal regulasi. |
| `submission.ts` | `SubmissionTicket`, `SubmissionItem`, `WorkflowStatus`, `VerificationLog` | Tiket usulan perubahan berstatus state-machine, snapshot delta JSON sebelum vs sesudah, dan catatan telaah. |
| `notification.ts`| `NotificationItem`, `NotificationCategory` | Notifikasi umpan peristiwa alur kerja, master data, dan keamanan. |
| `analytics.ts` | `KPICandidate`, `EchelonDistribution`, `SubmissionTurnaroundMetric` | 4 Proposed KPIs SESDEP dan metrik postur formasi jabatan ASN. |
| `audit.ts` | `AuditLogEntry`, `AuditActionType` | Rekam forensik mutasi data tak-terhapuskan. |

> [!NOTE]
> Seluruh dataset mock di atas diperlakukan secara formal sebagai **DATA SIMULASI PROTOKOL** dan tidak diklaim sebagai data produksi final sebelum tahapan ETL migrasi resmi disahkan.
