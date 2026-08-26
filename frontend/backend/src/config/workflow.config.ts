import { UserRole } from '../common/interfaces/auth-payload.interface';

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

export type WorkflowProfileType = 'STANDARD_WORKFLOW' | 'ADMIN_TRIAGE_WORKFLOW';

export interface WorkflowTransitionRule {
  transitionId: string;
  fromState: WorkflowStatus;
  toState: WorkflowStatus;
  action: string;
  allowedRoles: UserRole[];
  requiredPermissions: string[];
  requiresNote?: boolean;
}

export interface WorkflowProfileDefinition {
  profileType: WorkflowProfileType;
  name: string;
  description: string;
  states: WorkflowStatus[];
  transitions: WorkflowTransitionRule[];
}

export const STANDARD_WORKFLOW_TRANSITIONS: WorkflowTransitionRule[] = [
  // 1. DRAFT -> SUBMITTED (Operator Submit)
  {
    transitionId: 'TR-STD-01',
    fromState: 'DRAFT',
    toState: 'SUBMITTED',
    action: 'SUBMIT',
    allowedRoles: ['USER', 'ADMIN'],
    requiredPermissions: ['submission.submit'],
    requiresNote: false,
  },
  // 2. SUBMITTED -> IN_REVIEW (Verifier Starts Review)
  {
    transitionId: 'TR-STD-02',
    fromState: 'SUBMITTED',
    toState: 'IN_REVIEW',
    action: 'START_REVIEW',
    allowedRoles: ['VERIFIKATOR', 'ADMIN'],
    requiredPermissions: ['submission.review'],
    requiresNote: false,
  },
  // 3. IN_REVIEW -> VERIFIED (Verifier Passes Substantive Review)
  {
    transitionId: 'TR-STD-03',
    fromState: 'IN_REVIEW',
    toState: 'VERIFIED',
    action: 'PASS_VERIFICATION',
    allowedRoles: ['VERIFIKATOR', 'ADMIN'],
    requiredPermissions: ['submission.verify'],
    requiresNote: true,
  },
  // 4. IN_REVIEW -> REVISION_REQUIRED (Verifier Requests Revision)
  {
    transitionId: 'TR-STD-04',
    fromState: 'IN_REVIEW',
    toState: 'REVISION_REQUIRED',
    action: 'REQUEST_REVISION',
    allowedRoles: ['VERIFIKATOR', 'ADMIN'],
    requiredPermissions: ['submission.request_revision'],
    requiresNote: true,
  },
  // 5. IN_REVIEW -> REJECTED (Verifier Rejects Submission)
  {
    transitionId: 'TR-STD-05',
    fromState: 'IN_REVIEW',
    toState: 'REJECTED',
    action: 'REJECT',
    allowedRoles: ['VERIFIKATOR', 'ADMIN'],
    requiredPermissions: ['submission.reject'],
    requiresNote: true,
  },
  // 6. REVISION_REQUIRED -> RESUBMITTED (Operator Resubmits Revision)
  {
    transitionId: 'TR-STD-06',
    fromState: 'REVISION_REQUIRED',
    toState: 'RESUBMITTED',
    action: 'RESUBMIT',
    allowedRoles: ['USER', 'ADMIN'],
    requiredPermissions: ['submission.resubmit'],
    requiresNote: true,
  },
  // 7. RESUBMITTED -> IN_REVIEW (Verifier Resumes Review on Resubmitted)
  {
    transitionId: 'TR-STD-07',
    fromState: 'RESUBMITTED',
    toState: 'IN_REVIEW',
    action: 'RESUME_REVIEW',
    allowedRoles: ['VERIFIKATOR', 'ADMIN'],
    requiredPermissions: ['submission.review'],
    requiresNote: false,
  },
  // 8. VERIFIED -> APPROVED (Admin Approves Atomically to Master Data)
  {
    transitionId: 'TR-STD-08',
    fromState: 'VERIFIED',
    toState: 'APPROVED',
    action: 'APPROVE_MASTER',
    allowedRoles: ['ADMIN'],
    requiredPermissions: ['submission.approve'],
    requiresNote: false,
  },
];

export const WORKFLOW_PROFILES: Record<WorkflowProfileType, WorkflowProfileDefinition> = {
  STANDARD_WORKFLOW: {
    profileType: 'STANDARD_WORKFLOW',
    name: 'Alur Kerja Standar 5-Tahap',
    description: 'Draf -> Diajukan -> Sedang Ditelaah -> Lolos Telaah -> Disahkan ke Master Data',
    states: [
      'DRAFT',
      'SUBMITTED',
      'IN_REVIEW',
      'REVISION_REQUIRED',
      'RESUBMITTED',
      'VERIFIED',
      'APPROVED',
      'REJECTED',
    ],
    transitions: STANDARD_WORKFLOW_TRANSITIONS,
  },
  ADMIN_TRIAGE_WORKFLOW: {
    profileType: 'ADMIN_TRIAGE_WORKFLOW',
    name: 'Alur Kerja Triase 6-Tahap',
    description: 'Draf -> Diajukan -> Triase Administrasi -> Sedang Ditelaah -> Lolos Telaah -> Disahkan',
    states: [
      'DRAFT',
      'SUBMITTED',
      'ADMIN_TRIAGED',
      'IN_REVIEW',
      'REVISION_REQUIRED',
      'RESUBMITTED',
      'VERIFIED',
      'APPROVED',
      'REJECTED',
    ],
    transitions: [
      ...STANDARD_WORKFLOW_TRANSITIONS,
      {
        transitionId: 'TR-TRG-01',
        fromState: 'SUBMITTED',
        toState: 'ADMIN_TRIAGED',
        action: 'ADMIN_TRIAGE',
        allowedRoles: ['ADMIN'],
        requiredPermissions: ['submission.triage'],
        requiresNote: false,
      },
      {
        transitionId: 'TR-TRG-02',
        fromState: 'ADMIN_TRIAGED',
        toState: 'IN_REVIEW',
        action: 'START_REVIEW_FROM_TRIAGE',
        allowedRoles: ['VERIFIKATOR', 'ADMIN'],
        requiredPermissions: ['submission.review'],
        requiresNote: false,
      },
    ],
  },
};

export function canPerformWorkflowTransition(
  fromState: WorkflowStatus,
  toState: WorkflowStatus,
  userRole: UserRole,
  profile: WorkflowProfileType = 'STANDARD_WORKFLOW',
): boolean {
  const profileDef = WORKFLOW_PROFILES[profile];
  if (!profileDef) return false;

  return profileDef.transitions.some(
    (t) =>
      t.fromState === fromState &&
      t.toState === toState &&
      t.allowedRoles.includes(userRole),
  );
}
