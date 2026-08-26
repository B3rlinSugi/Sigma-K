import { StateMachineEngine } from '../src/modules/workflows/domain/state-machine.engine';
import { canPerformWorkflowTransition } from '../src/config/workflow.config';

describe('StateMachineEngine (Configurable Workflow Engine)', () => {
  describe('Standard Workflow 5-Step Transitions', () => {
    it('should allow USER to submit a DRAFT ticket to SUBMITTED', () => {
      const res = StateMachineEngine.validateTransition('DRAFT', 'SUBMITTED', 'USER');
      expect(res.allowed).toBe(true);
    });

    it('should allow VERIFIKATOR to transition SUBMITTED to IN_REVIEW', () => {
      const res = StateMachineEngine.validateTransition('SUBMITTED', 'IN_REVIEW', 'VERIFIKATOR');
      expect(res.allowed).toBe(true);
    });

    it('should allow VERIFIKATOR to request revision with notes (IN_REVIEW -> REVISION_REQUIRED)', () => {
      const res = StateMachineEngine.validateTransition(
        'IN_REVIEW',
        'REVISION_REQUIRED',
        'VERIFIKATOR',
        'Mohon perbaiki nomenklatur pasal regulasi.',
      );
      expect(res.allowed).toBe(true);
    });

    it('should reject revision request if verifier notes are empty', () => {
      const res = StateMachineEngine.validateTransition(
        'IN_REVIEW',
        'REVISION_REQUIRED',
        'VERIFIKATOR',
        '', // Empty note
      );
      expect(res.allowed).toBe(false);
      expect(res.reason).toContain('Catatan resmi wajib diisi');
    });

    it('should allow USER to resubmit from REVISION_REQUIRED to RESUBMITTED with notes', () => {
      const res = StateMachineEngine.validateTransition(
        'REVISION_REQUIRED',
        'RESUBMITTED',
        'USER',
        'Telah disesuaikan sesuai arahan.',
      );
      expect(res.allowed).toBe(true);
    });

    it('should allow VERIFIKATOR to resume review from RESUBMITTED to IN_REVIEW', () => {
      const res = StateMachineEngine.validateTransition('RESUBMITTED', 'IN_REVIEW', 'VERIFIKATOR');
      expect(res.allowed).toBe(true);
    });

    it('should allow VERIFIKATOR to pass substantive verification (IN_REVIEW -> VERIFIED)', () => {
      const res = StateMachineEngine.validateTransition(
        'IN_REVIEW',
        'VERIFIED',
        'VERIFIKATOR',
        'Dokumen memenuhi syarat formal dan materiil.',
      );
      expect(res.allowed).toBe(true);
    });

    it('should allow ADMIN to approve verified submission to master (VERIFIED -> APPROVED)', () => {
      const res = StateMachineEngine.validateTransition('VERIFIED', 'APPROVED', 'ADMIN');
      expect(res.allowed).toBe(true);
    });

    it('should prevent USER from directly approving a submission (VERIFIED -> APPROVED)', () => {
      const res = StateMachineEngine.validateTransition('VERIFIED', 'APPROVED', 'USER');
      expect(res.allowed).toBe(false);
      expect(res.reason).toContain('Hak akses ditolak');
    });

    it('should prevent invalid skipping transitions (DRAFT -> APPROVED)', () => {
      const res = StateMachineEngine.validateTransition('DRAFT', 'APPROVED', 'ADMIN');
      expect(res.allowed).toBe(false);
      expect(res.reason).toContain('Transisi tidak valid');
    });
  });

  describe('Admin Triage Workflow Profile', () => {
    it('should allow ADMIN triage transition (SUBMITTED -> ADMIN_TRIAGED) in triage profile', () => {
      const res = StateMachineEngine.validateTransition(
        'SUBMITTED',
        'ADMIN_TRIAGED',
        'ADMIN',
        undefined,
        'ADMIN_TRIAGE_WORKFLOW',
      );
      expect(res.allowed).toBe(true);
    });

    it('should reject triage transition in standard profile', () => {
      const res = StateMachineEngine.validateTransition(
        'SUBMITTED',
        'ADMIN_TRIAGED',
        'ADMIN',
        undefined,
        'STANDARD_WORKFLOW',
      );
      expect(res.allowed).toBe(false);
    });
  });
});
