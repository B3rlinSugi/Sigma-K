import {
  WorkflowStatus,
  WorkflowProfileType,
  WORKFLOW_PROFILES,
  canPerformWorkflowTransition,
} from '../../../config/workflow.config';
import { UserRole } from '../../../common/interfaces/auth-payload.interface';

export interface TransitionValidationResult {
  allowed: boolean;
  reason?: string;
}

export class StateMachineEngine {
  static validateTransition(
    fromState: WorkflowStatus,
    toState: WorkflowStatus,
    userRole: UserRole,
    notes?: string,
    profile: WorkflowProfileType = 'STANDARD_WORKFLOW',
  ): TransitionValidationResult {
    const profileDef = WORKFLOW_PROFILES[profile];
    if (!profileDef) {
      return { allowed: false, reason: `Profil alur kerja '${profile}' tidak ditemukan.` };
    }

    const rule = profileDef.transitions.find(
      (t) => t.fromState === fromState && t.toState === toState,
    );

    if (!rule) {
      return {
        allowed: false,
        reason: `Transisi tidak valid: Status '${fromState}' tidak dapat berpindah langsung ke '${toState}'.`,
      };
    }

    if (!rule.allowedRoles.includes(userRole)) {
      return {
        allowed: false,
        reason: `Hak akses ditolak: Peran '${userRole}' tidak diizinkan mengeksekusi transisi '${fromState}' -> '${toState}'.`,
      };
    }

    if (rule.requiresNote && (!notes || !notes.trim())) {
      return {
        allowed: false,
        reason: `Catatan resmi wajib diisi untuk transisi aksi '${rule.action}'.`,
      };
    }

    return { allowed: true };
  }
}
