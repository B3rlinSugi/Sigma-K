import { WorkflowStatus, WorkflowTransition } from '@/types/submission';
import { UserRole } from '@/types/auth';

export type WorkflowModelType = 'STANDARD' | 'TRIAGE';

export interface WorkflowStateDefinition {
  state: WorkflowStatus;
  label: string;
  badgeVariant: 'secondary' | 'info' | 'warning' | 'primary' | 'success' | 'danger';
  description: string;
  orderStandard: number;
  orderTriage: number;
}

export const WORKFLOW_STATES_MAP: Record<WorkflowStatus, WorkflowStateDefinition> = {
  DRAFT: {
    state: 'DRAFT',
    label: 'Draf Usulan',
    badgeVariant: 'secondary',
    description: 'Penyusunan data perubahan oleh Operator Instansi.',
    orderStandard: 0,
    orderTriage: 0,
  },
  SUBMITTED: {
    state: 'SUBMITTED',
    label: 'Diajukan',
    badgeVariant: 'info',
    description: 'Usulan terkirim ke antrean pusat KemenPANRB.',
    orderStandard: 1,
    orderTriage: 1,
  },
  ADMIN_TRIAGED: {
    state: 'ADMIN_TRIAGED',
    label: 'Telah Ditriase',
    badgeVariant: 'info',
    description: 'Admin Pusat memverifikasi kelengkapan berkas sebelum telaah substantif.',
    orderStandard: -1,
    orderTriage: 2,
  },
  IN_REVIEW: {
    state: 'IN_REVIEW',
    label: 'Sedang Ditelaah',
    badgeVariant: 'warning',
    description: 'Pemeriksaan kesesuaian dokumen regulasi oleh Verifikator Analis Kelembagaan.',
    orderStandard: 2,
    orderTriage: 3,
  },
  REVISION_REQUIRED: {
    state: 'REVISION_REQUIRED',
    label: 'Perlu Revisi',
    badgeVariant: 'danger',
    description: 'Verifikator mengembalikan berkas dengan catatan perbaikan.',
    orderStandard: 2,
    orderTriage: 3,
  },
  RESUBMITTED: {
    state: 'RESUBMITTED',
    label: 'Revisi Terkirim',
    badgeVariant: 'info',
    description: 'Operator telah mengirimkan perbaikan draf usulan.',
    orderStandard: 1,
    orderTriage: 1,
  },
  VERIFIED: {
    state: 'VERIFIED',
    label: 'Lolos Verifikasi',
    badgeVariant: 'primary',
    description: 'Telaah substantif selesai dengan rekomendasi pengesahan.',
    orderStandard: 3,
    orderTriage: 4,
  },
  APPROVED: {
    state: 'APPROVED',
    label: 'Disahkan',
    badgeVariant: 'success',
    description: 'Perubahan telah disahkan dan diterapkan ke Master Data SIGMA-K.',
    orderStandard: 4,
    orderTriage: 5,
  },
  REJECTED: {
    state: 'REJECTED',
    label: 'Ditolak',
    badgeVariant: 'danger',
    description: 'Pengajuan usulan ditolak dengan alasan resmi.',
    orderStandard: -1,
    orderTriage: -1,
  },
};

export const PROTOTYPE_WORKFLOW_TRANSITIONS: WorkflowTransition[] = [
  // Draft to Submitted (Operator)
  {
    fromState: 'DRAFT',
    toState: 'SUBMITTED',
    actionName: 'Kirim Pengajuan',
    allowedRoles: ['USER', 'ADMIN'],
  },
  // Submitted to In Review (Verifikator)
  {
    fromState: 'SUBMITTED',
    toState: 'IN_REVIEW',
    actionName: 'Mulai Telaah',
    allowedRoles: ['VERIFIKATOR', 'ADMIN'],
  },
  // In Review to Verified (Pass)
  {
    fromState: 'IN_REVIEW',
    toState: 'VERIFIED',
    actionName: 'Lolos Verifikasi (Pass)',
    allowedRoles: ['VERIFIKATOR', 'ADMIN'],
    requiresNote: true,
  },
  // In Review to Revision Required
  {
    fromState: 'IN_REVIEW',
    toState: 'REVISION_REQUIRED',
    actionName: 'Minta Revisi',
    allowedRoles: ['VERIFIKATOR', 'ADMIN'],
    requiresNote: true,
  },
  // In Review to Rejected
  {
    fromState: 'IN_REVIEW',
    toState: 'REJECTED',
    actionName: 'Tolak Usulan',
    allowedRoles: ['VERIFIKATOR', 'ADMIN'],
    requiresNote: true,
  },
  // Revision Required to Submitted (Resubmit)
  {
    fromState: 'REVISION_REQUIRED',
    toState: 'SUBMITTED',
    actionName: 'Kirim Ulang Perbaikan',
    allowedRoles: ['USER', 'ADMIN'],
  },
  // Verified to Approved (Admin)
  {
    fromState: 'VERIFIED',
    toState: 'APPROVED',
    actionName: 'Sahkan ke Master Data',
    allowedRoles: ['ADMIN'],
  },
];

export function getAvailableTransitions(
  currentState: WorkflowStatus,
  userRole: UserRole,
  transitions: WorkflowTransition[] = PROTOTYPE_WORKFLOW_TRANSITIONS
): WorkflowTransition[] {
  return transitions.filter(
    (t) => t.fromState === currentState && t.allowedRoles.includes(userRole)
  );
}

export function canPerformWorkflowAction(
  currentState: WorkflowStatus,
  targetState: WorkflowStatus,
  userRole: UserRole,
  transitions: WorkflowTransition[] = PROTOTYPE_WORKFLOW_TRANSITIONS
): boolean {
  return transitions.some(
    (t) =>
      t.fromState === currentState &&
      t.toState === targetState &&
      t.allowedRoles.includes(userRole)
  );
}
