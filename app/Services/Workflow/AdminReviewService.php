<?php

namespace App\Services\Workflow;

use App\Entities\UserEntity;
use App\Models\AccessGrantModel;
use App\Models\RevisionNoteModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionVersionModel;
use App\Models\UserModel;
use App\Models\UserScopeModel;
use App\Models\VerificationRecordModel;
use App\Models\VerifierAssignmentModel;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationService;
use Exception;
use Throwable;

/**
 * AdminReviewService
 *
 * Implements Gate 1: Admin Review Queue, Acceptance, Return for Revision, and Verifier Assignment.
 */
class AdminReviewService
{
    protected SubmissionModel $submissionModel;
    protected SubmissionVersionModel $versionModel;
    protected VerificationRecordModel $verifModel;
    protected RevisionNoteModel $revisionModel;
    protected VerifierAssignmentModel $assignmentModel;
    protected UserModel $userModel;
    protected UserScopeModel $scopeModel;
    protected AccessGrantModel $grantModel;
    protected AuthorizationService $authzService;
    protected AuditService $auditService;

    public function __construct(
        ?SubmissionModel $submissionModel = null,
        ?SubmissionVersionModel $versionModel = null,
        ?VerificationRecordModel $verifModel = null,
        ?RevisionNoteModel $revisionModel = null,
        ?VerifierAssignmentModel $assignmentModel = null,
        ?UserModel $userModel = null,
        ?UserScopeModel $scopeModel = null,
        ?AccessGrantModel $grantModel = null,
        ?AuthorizationService $authzService = null,
        ?AuditService $auditService = null
    ) {
        $this->submissionModel  = $submissionModel ?? new SubmissionModel();
        $this->versionModel     = $versionModel ?? new SubmissionVersionModel();
        $this->verifModel       = $verifModel ?? new VerificationRecordModel();
        $this->revisionModel    = $revisionModel ?? new RevisionNoteModel();
        $this->assignmentModel  = $assignmentModel ?? new VerifierAssignmentModel();
        $this->userModel        = $userModel ?? new UserModel();
        $this->scopeModel       = $scopeModel ?? new UserScopeModel();
        $this->grantModel       = $grantModel ?? new AccessGrantModel();
        $this->authzService     = $authzService ?? new AuthorizationService();
        $this->auditService     = $auditService ?? new AuditService();
    }

    /**
     * Get Admin Review Queue: Submissions in SUBMITTED_TO_ADMIN state within Admin's scope.
     *
     * @param UserEntity $actor
     * @param int        $page
     * @param int        $perPage
     * @return array
     * @throws Exception
     */
    public function getAdminQueue(UserEntity $actor, int $page = 1, int $perPage = 20): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        if (!in_array($roleCode, ['ADMIN', 'SUPER_ADMIN'], true)) {
            throw new Exception('FORBIDDEN');
        }

        $db = \Config\Database::connect();
        $builder = $db->table('submissions s')
            ->select('s.*, i.name as institution_name, i.institution_code, u.username as author_username, u.full_name as author_name')
            ->join('institutions i', 's.institution_id = i.id')
            ->join('users u', 's.author_id = u.id')
            ->where('s.current_state', 'SUBMITTED_TO_ADMIN');

        // Scope Filtering
        if ($roleCode !== 'SUPER_ADMIN') {
            $accessibleInstIds = [(int)$actor->home_institution_id];
            $scopedIds = $this->scopeModel->getActiveInstitutionIds((int)$actor->id);
            $accessibleInstIds = array_merge($accessibleInstIds, $scopedIds);

            // Grants
            $today = date('Y-m-d');
            $grantRows = $db->table('access_grants ag')
                ->select('ag.target_institution_id')
                ->where('ag.user_id', (int)$actor->id)
                ->where('ag.status', 'ACTIVE')
                ->where('ag.start_date <=', $today)
                ->where('ag.end_date >=', $today)
                ->get()
                ->getResultArray();

            $grantInstIds = array_map('intval', array_column($grantRows, 'target_institution_id'));
            $accessibleInstIds = array_values(array_unique(array_merge($accessibleInstIds, $grantInstIds)));

            if (empty($accessibleInstIds)) {
                return [
                    'items' => [],
                    'meta'  => ['page' => $page, 'perPage' => $perPage, 'total' => 0, 'totalPages' => 0],
                ];
            }

            $builder->whereIn('s.institution_id', $accessibleInstIds);
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();

        $offset = ($page - 1) * $perPage;
        $items = $builder->orderBy('s.id', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return [
            'items' => $items,
            'meta'  => [
                'page'       => $page,
                'perPage'    => $perPage,
                'total'      => $total,
                'totalPages' => $perPage > 0 ? (int)ceil($total / $perPage) : 1,
            ],
        ];
    }

    /**
     * Admin accepts a submission for Gate 1 Review (SUBMITTED_TO_ADMIN -> IN_REVIEW_BY_ADMIN).
     *
     * @param UserEntity  $actor
     * @param int         $submissionId
     * @param string|null $notes
     * @return array
     * @throws Exception
     */
    public function acceptReview(UserEntity $actor, int $submissionId, ?string $notes = null): array
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Lock row for concurrency protection
            $submission = $db->table('submissions')->where('id', $submissionId)->get()->getRowArray();
            if (!$submission) {
                throw new Exception('NOT_FOUND');
            }

            if ($submission['current_state'] !== 'SUBMITTED_TO_ADMIN') {
                throw new Exception('LOCKED');
            }

            $institutionId = (int)$submission['institution_id'];

            // Separation of Duties: Author cannot review their own submission
            if ((int)$actor->id === (int)$submission['author_id']) {
                throw new Exception('SOD_AUTHOR_CANNOT_REVIEW');
            }

            // Zero-Trust Authorization
            if (!$this->authzService->can($actor, 'REVIEW', $institutionId, 'SUBMITTED_TO_ADMIN')) {
                throw new Exception('FORBIDDEN');
            }

            $latestVersion = $this->versionModel->getLatestVersion($submissionId);
            $versionId = $latestVersion ? (int)$latestVersion['id'] : 0;

            // Insert Verification Record
            $this->verifModel->insert([
                'version_id'          => $versionId,
                'reviewer_id'         => (int)$actor->id,
                'gate_level'          => 'GATE_1',
                'verification_result' => 'IN_REVIEW',
                'general_notes'       => $notes,
                'verified_at'         => date('Y-m-d H:i:s'),
            ]);

            // Update Submission State
            $this->submissionModel->update($submissionId, [
                'current_state' => 'IN_REVIEW_BY_ADMIN',
            ]);

            // Audit Trail
            $actorRole = $this->authzService->getUserRoleCode($actor);
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $actorRole,
                'action_event'    => 'ADMIN_REVIEW_ACCEPT',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode([
                    'from_state' => 'SUBMITTED_TO_ADMIN',
                    'to_state'   => 'IN_REVIEW_BY_ADMIN',
                    'notes'      => $notes,
                ]),
            ]);

            $db->transCommit();

            return [
                'submissionId' => $submissionId,
                'currentState' => 'IN_REVIEW_BY_ADMIN',
                'reviewerId'   => (int)$actor->id,
                'acceptedAt'   => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Admin returns submission for revision (SUBMITTED_TO_ADMIN or IN_REVIEW_BY_ADMIN -> REVISION_REQUIRED).
     *
     * @param UserEntity $actor
     * @param int        $submissionId
     * @param string     $reason
     * @param int|null   $versionUnitId
     * @return array
     * @throws Exception
     */
    public function returnForRevision(UserEntity $actor, int $submissionId, string $reason, ?int $versionUnitId = null): array
    {
        $reason = trim($reason);
        if (empty($reason) || strlen($reason) < 5) {
            throw new Exception('Reason for revision is required and must be at least 5 characters.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $submission = $db->table('submissions')->where('id', $submissionId)->get()->getRowArray();
            if (!$submission) {
                throw new Exception('NOT_FOUND');
            }

            if (!in_array($submission['current_state'], ['SUBMITTED_TO_ADMIN', 'IN_REVIEW_BY_ADMIN'], true)) {
                throw new Exception('LOCKED');
            }

            $institutionId = (int)$submission['institution_id'];

            // Separation of Duties
            if ((int)$actor->id === (int)$submission['author_id']) {
                throw new Exception('SOD_AUTHOR_CANNOT_REVIEW');
            }

            // Zero-Trust Authorization
            if (!$this->authzService->can($actor, 'RETURN_REVISION', $institutionId, $submission['current_state'])) {
                throw new Exception('FORBIDDEN');
            }

            $latestVersion = $this->versionModel->getLatestVersion($submissionId);
            $versionId = $latestVersion ? (int)$latestVersion['id'] : 0;

            // Insert Verification Record
            $this->verifModel->insert([
                'version_id'          => $versionId,
                'reviewer_id'         => (int)$actor->id,
                'gate_level'          => 'GATE_1',
                'verification_result' => 'RETURNED_FOR_REVISION',
                'general_notes'       => $reason,
                'verified_at'         => date('Y-m-d H:i:s'),
            ]);

            $verifId = (int)$this->verifModel->getInsertID();

            // Insert Revision Note
            $this->revisionModel->insert([
                'verification_id'   => $verifId,
                'version_unit_id'   => $versionUnitId,
                'issue_description' => $reason,
                'is_resolved'       => 0,
                'created_at'        => date('Y-m-d H:i:s'),
            ]);

            $revNoteId = (int)$this->revisionModel->getInsertID();

            // Update Submission State
            $this->submissionModel->update($submissionId, [
                'current_state' => 'REVISION_REQUIRED',
            ]);

            // Audit Trail
            $actorRole = $this->authzService->getUserRoleCode($actor);
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $actorRole,
                'action_event'    => 'ADMIN_REVIEW_RETURN',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode([
                    'from_state'       => $submission['current_state'],
                    'to_state'         => 'REVISION_REQUIRED',
                    'reason'           => $reason,
                    'revision_note_id' => $revNoteId,
                ]),
            ]);

            $db->transCommit();

            return [
                'submissionId'   => $submissionId,
                'currentState'   => 'REVISION_REQUIRED',
                'reason'         => $reason,
                'revisionNoteId' => $revNoteId,
                'returnedAt'     => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Admin assigns an authorized Verifier (IN_REVIEW_BY_ADMIN -> ASSIGNED_TO_VERIFIER).
     *
     * @param UserEntity  $actor
     * @param int         $submissionId
     * @param int         $verifierId
     * @param string|null $notes
     * @return array
     * @throws Exception
     */
    public function assignVerifier(UserEntity $actor, int $submissionId, int $verifierId, ?string $notes = null): array
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $submission = $db->table('submissions')->where('id', $submissionId)->get()->getRowArray();
            if (!$submission) {
                throw new Exception('NOT_FOUND');
            }

            if ($submission['current_state'] !== 'IN_REVIEW_BY_ADMIN') {
                throw new Exception('LOCKED');
            }

            $institutionId = (int)$submission['institution_id'];

            // Authorization: Admin must have ASSIGN_VERIFIER permission
            if (!$this->authzService->can($actor, 'ASSIGN_VERIFIER', $institutionId, 'IN_REVIEW_BY_ADMIN')) {
                throw new Exception('FORBIDDEN');
            }

            // Verifier Eligibility Checks
            $verifierUser = $this->userModel->find($verifierId);
            if (!$verifierUser) {
                throw new Exception('Target verifier user not found.');
            }

            if ($verifierUser->status !== 'ACTIVE') {
                throw new Exception('Target verifier user is not active.');
            }

            // SoD: Verifier cannot be the submission author
            if ($verifierId === (int)$submission['author_id']) {
                throw new Exception('SOD_AUTHOR_CANNOT_BE_VERIFIER');
            }

            $verifierRoleCode = $this->authzService->getUserRoleCode($verifierUser);
            if ($verifierRoleCode !== 'VERIFIER') {
                throw new Exception('TARGET_NOT_VERIFIER');
            }

            // Check if active assignment already exists
            $existingAssignment = $this->assignmentModel->getActiveAssignment($submissionId);
            if ($existingAssignment) {
                throw new Exception('LOCKED');
            }

            // Insert Verifier Assignment
            $this->assignmentModel->insert([
                'submission_id'    => $submissionId,
                'verifier_id'      => $verifierId,
                'assigned_by'      => (int)$actor->id,
                'status'           => 'ASSIGNED',
                'assignment_notes' => $notes,
                'assigned_at'      => date('Y-m-d H:i:s'),
            ]);

            $assignmentId = (int)$this->assignmentModel->getInsertID();

            // Update Submission State
            $this->submissionModel->update($submissionId, [
                'current_state' => 'ASSIGNED_TO_VERIFIER',
            ]);

            // Audit Trail
            $actorRole = $this->authzService->getUserRoleCode($actor);
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $actorRole,
                'action_event'    => 'ASSIGN_VERIFIER',
                'resource_entity' => 'verifier_assignments',
                'resource_id'     => $assignmentId,
                'payload_new'     => json_encode([
                    'submission_id' => $submissionId,
                    'verifier_id'   => $verifierId,
                    'assigned_by'   => (int)$actor->id,
                    'from_state'    => 'IN_REVIEW_BY_ADMIN',
                    'to_state'      => 'ASSIGNED_TO_VERIFIER',
                ]),
            ]);

            $db->transCommit();

            return [
                'submissionId' => $submissionId,
                'assignmentId' => $assignmentId,
                'verifierId'   => $verifierId,
                'verifierName' => $verifierUser->full_name,
                'currentState' => 'ASSIGNED_TO_VERIFIER',
                'assignedAt'   => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
