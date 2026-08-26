import { SubmissionTicket, WorkflowStatus } from '@/types/submission';
import { SubmissionDto } from '@/types/dto/submission.dto';

/**
 * Maps Backend current_state to Frontend WorkflowStatus
 */
export function mapBackendStateToWorkflowStatus(state: string): WorkflowStatus {
  switch (state) {
    case 'DRAFT':
      return 'DRAFT';
    case 'SUBMITTED_TO_ADMIN':
      return 'SUBMITTED';
    case 'IN_REVIEW_BY_ADMIN':
    case 'ASSIGNED_TO_VERIFIER':
    case 'IN_REVIEW_BY_VERIFIER':
      return 'IN_REVIEW';
    case 'REVISION_REQUIRED':
    case 'REVISION_REQUIRED_BY_VERIFIER':
      return 'REVISION_REQUIRED';
    case 'RESUBMITTED':
      return 'RESUBMITTED';
    case 'READY_FOR_FINAL_DECISION':
      return 'VERIFIED';
    case 'APPROVED':
    case 'PROMOTED':
      return 'APPROVED';
    case 'REJECTED':
      return 'REJECTED';
    default:
      return 'DRAFT';
  }
}

/**
 * Maps Backend SubmissionDto to Frontend SubmissionTicket Domain Model
 */
export function mapSubmissionDtoToDomain(dto: SubmissionDto): SubmissionTicket {
  return {
    id: String(dto.id),
    ticketNumber: `TKT-${dto.submission_year}-${String(dto.id).padStart(4, '0')}`,
    institutionId: String(dto.institution_id),
    institutionName: dto.institution_name || `Instansi ID: ${dto.institution_id}`,
    institutionCode: dto.institution_code || `KL-${dto.institution_id}`,
    submissionType: 'STRUKTUR_ORGANISASI',
    title: dto.title,
    submissionNotes: dto.description || undefined,
    status: mapBackendStateToWorkflowStatus(dto.current_state),
    submitterUserId: String(dto.author_id),
    submitterName: dto.author_name || `User ID: ${dto.author_id}`,
    submittedAt: dto.created_at,
    updatedAt: dto.updated_at,
    itemsCount: (dto.units_count || 0) + (dto.positions_count || 0),
  };
}

export function mapSubmissionsDtoToDomain(dtos: SubmissionDto[]): SubmissionTicket[] {
  return dtos.map(mapSubmissionDtoToDomain);
}
