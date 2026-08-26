import { UserRole } from './auth';

export type WorkflowStatus = 
  | 'DRAFT'
  | 'SUBMITTED'
  | 'ADMIN_TRIAGED'
  | 'IN_REVIEW'
  | 'REVISION_REQUIRED'
  | 'RESUBMITTED'
  | 'VERIFIED'
  | 'APPROVED'
  | 'REJECTED';

// Alias for domain consistency
export type WorkflowState = WorkflowStatus;

export interface WorkflowTransition {
  fromState: WorkflowState;
  toState: WorkflowState;
  actionName: string;
  allowedRoles: UserRole[];
  requiresNote?: boolean;
}

export type SubmissionType = 
  | 'STRUKTUR_ORGANISASI'
  | 'TUGAS_FUNGSI'
  | 'PROFIL_INSTANSI'
  | 'KOMPOSISI_KABINET'
  | 'INSTANSI_BARU';

export interface SubmissionItem {
  id: string;
  submissionTicketId: string;
  targetEntityType: 'ORGANIZATION_UNIT' | 'TUPOKSI' | 'INSTITUTION_PROFILE';
  targetEntityId?: string;
  actionType: 'CREATE' | 'UPDATE' | 'DELETE';
  fieldName?: string;
  label: string;
  payloadBefore?: Record<string, unknown> | null;
  payloadAfter?: Record<string, unknown> | null;
}

export interface VerificationLog {
  id: string;
  submissionTicketId: string;
  verifierUserId: string;
  verifierName: string;
  decision: 'PASS' | 'REVISION_REQUIRED' | 'REJECT';
  notes: string;
  verifiedAt: string;
}

// Alias for domain consistency
export type Verification = VerificationLog;

export interface SubmissionRevision {
  id: string;
  submissionTicketId: string;
  revisionNumber: number;
  verifierNotes: string;
  operatorResponse?: string;
  submittedAt: string;
  revisedAt?: string;
  revisedPayload?: Record<string, unknown>;
}

export interface SubmissionTicket {
  id: string;
  ticketNumber: string; // TKT-20260825-0042
  institutionId: string;
  institutionName: string;
  institutionCode: string;
  submissionType: SubmissionType;
  title: string;
  submissionNotes?: string;
  legalDocPath?: string;
  legalDocName?: string;
  status: WorkflowStatus;
  submitterUserId: string;
  submitterName: string;
  submittedAt: string;
  updatedAt: string;
  approvedAt?: string;
  approvedByUserName?: string;
  itemsCount: number;
  items?: SubmissionItem[];
  verificationLogs?: VerificationLog[];
  revisions?: SubmissionRevision[];
  priority?: 'HIGH' | 'MEDIUM' | 'NORMAL';
}

// Alias for domain consistency
export type Submission = SubmissionTicket;
