<?php

namespace App\Services\Workflow;

use App\Entities\UserEntity;
use App\Models\RevisionNoteModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionPositionModel;
use App\Models\SubmissionUnitModel;
use App\Models\SubmissionVersionModel;
use App\Models\UserModel;
use App\Models\VerificationRecordModel;
use App\Models\VerifierAssignmentModel;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationService;
use Exception;
use Throwable;

/**
 * VerifierReviewService
 *
 * Implements Gate 2: Verifier Substantive Review, Revision Notes, Return for Revision,
 * Substantive Verification Approval, and Technical Recommendation Workflow (Step 7 & Step 9).
 */
class VerifierReviewService
{
    protected SubmissionModel $submissionModel;
    protected SubmissionVersionModel $versionModel;
    protected SubmissionUnitModel $unitModel;
    protected SubmissionPositionModel $posModel;
    protected VerificationRecordModel $verifModel;
    protected RevisionNoteModel $revisionModel;
    protected VerifierAssignmentModel $assignmentModel;
    protected UserModel $userModel;
    protected AuthorizationService $authzService;
    protected AuditService $auditService;

    public function __construct(
        ?SubmissionModel $submissionModel = null,
        ?SubmissionVersionModel $versionModel = null,
        ?SubmissionUnitModel $unitModel = null,
        ?SubmissionPositionModel $posModel = null,
        ?VerificationRecordModel $verifModel = null,
        ?RevisionNoteModel $revisionModel = null,
        ?VerifierAssignmentModel $assignmentModel = null,
        ?UserModel $userModel = null,
        ?AuthorizationService $authzService = null,
        ?AuditService $auditService = null
    ) {
        $this->submissionModel = $submissionModel ?? new SubmissionModel();
        $this->versionModel    = $versionModel ?? new SubmissionVersionModel();
        $this->unitModel       = $unitModel ?? new SubmissionUnitModel();
        $this->posModel        = $posModel ?? new SubmissionPositionModel();
        $this->verifModel      = $verifModel ?? new VerificationRecordModel();
        $this->revisionModel   = $revisionModel ?? new RevisionNoteModel();
        $this->assignmentModel = $assignmentModel ?? new VerifierAssignmentModel();
        $this->userModel       = $userModel ?? new UserModel();
        $this->authzService    = $authzService ?? new AuthorizationService();
        $this->auditService    = $auditService ?? new AuditService();
    }

    /**
     * Get Verifier Queue: Submissions assigned to the authenticated verifier.
     *
     * @param UserEntity $actor
     * @param int        $page
     * @param int        $perPage
     * @return array
     * @throws Exception
     */
    public function getAssignedQueue(UserEntity $actor, int $page = 1, int $perPage = 20): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        if (!in_array($roleCode, ['VERIFIER', 'SUPER_ADMIN'], true)) {
            throw new Exception('FORBIDDEN');
        }

        if ($actor->status !== 'ACTIVE') {
            throw new Exception('FORBIDDEN');
        }

        return $this->assignmentModel->getAssignedSubmissionsForVerifier((int)$actor->id, $page, $perPage);
    }

    /**
     * Get comprehensive review inspection details for the assigned Verifier.
     *
     * @param UserEntity $actor
     * @param int        $submissionId
     * @return array
     * @throws Exception
     */
    public function getReviewDetails(UserEntity $actor, int $submissionId): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        if (!in_array($roleCode, ['VERIFIER', 'SUPER_ADMIN'], true)) {
            throw new Exception('FORBIDDEN');
        }

        if ($actor->status !== 'ACTIVE') {
            throw new Exception('FORBIDDEN');
        }

        $submission = $this->submissionModel->find($submissionId);
        if (!$submission) {
            throw new Exception('NOT_FOUND');
        }

        $institutionId = (int)$submission->institution_id;

        // Separation of Duties: Author cannot review
        if ((int)$actor->id === (int)$submission->author_id) {
            throw new Exception('SOD_AUTHOR_CANNOT_VERIFY');
        }

        // Verify active assignment
        $assignment = $this->assignmentModel->getActiveAssignment($submissionId);
        if (!$assignment || ((int)$assignment['verifier_id'] !== (int)$actor->id && $roleCode !== 'SUPER_ADMIN')) {
            throw new Exception('WRONG_VERIFIER');
        }

        if (!$this->authzService->can($actor, 'VIEW', $institutionId, $submission->current_state)) {
            throw new Exception('FORBIDDEN');
        }

        // Fetch Versions & Items
        $versions = $this->versionModel->getVersionsBySubmissionId($submissionId);
        $latestVersion = $this->versionModel->getLatestVersion($submissionId);

        $latestUnits = [];
        $latestPositions = [];
        if ($latestVersion) {
            $latestUnits = $this->unitModel->getByVersionId((int)$latestVersion['id']);
            $latestPositions = $this->posModel->getByVersionId((int)$latestVersion['id']);
        }

        // Fetch Revision Notes & Verifications
        $revisionNotes = $this->revisionModel->getBySubmissionId($submissionId);
        $verificationRecords = $this->verifModel->where('reviewer_id', $actor->id)
            ->orWhere('gate_level', 'GATE_2')
            ->orderBy('id', 'DESC')
            ->findAll();

        $revisionStatus = $this->validateRevisionCompletion($submissionId);

        return [
            'submission' => [
                'id'             => (int)$submission->id,
                'title'          => $submission->title,
                'institutionId'  => $institutionId,
                'authorId'       => (int)$submission->author_id,
                'submissionYear' => (int)$submission->submission_year,
                'currentState'   => $submission->current_state,
                'createdAt'      => $submission->created_at,
                'updatedAt'      => $submission->updated_at,
            ],
            'assignment'          => $assignment,
            'currentVersion'      => $latestVersion,
            'versions'            => $versions,
            'proposedUnits'       => $latestUnits,
            'proposedPositions'   => $latestPositions,
            'revisionNotes'       => $revisionNotes,
            'verificationRecords' => $verificationRecords,
            'revisionStatus'      => $revisionStatus,
            'canApprove'          => in_array($submission->current_state, ['IN_REVIEW_BY_VERIFIER', 'RESUBMITTED', 'VERIFIER_REVIEW', 'ASSIGNED_TO_VERIFIER'], true),
        ];
    }

    /**
     * Validate whether all revision notes for a submission have been resolved.
     *
     * @param int $submissionId
     * @return array
     */
    public function validateRevisionCompletion(int $submissionId): array
    {
        $notes = $this->revisionModel->getBySubmissionId($submissionId);
        $unresolved = [];

        foreach ($notes as $note) {
            if (empty($note['is_resolved']) || (int)$note['is_resolved'] === 0) {
                $unresolved[] = $note;
            }
        }

        return [
            'isComplete'      => empty($unresolved),
            'totalNotes'      => count($notes),
            'unresolvedCount' => count($unresolved),
            'unresolvedNotes' => $unresolved,
        ];
    }

    /**
     * Verifier starts Gate 2 substantive review (ASSIGNED_TO_VERIFIER/RESUBMITTED -> IN_REVIEW_BY_VERIFIER).
     *
     * @param UserEntity  $actor
     * @param int         $submissionId
     * @param string|null $notes
     * @return array
     * @throws Exception
     */
    public function startReview(UserEntity $actor, int $submissionId, ?string $notes = null): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        if (!in_array($roleCode, ['VERIFIER', 'SUPER_ADMIN'], true)) {
            throw new Exception('FORBIDDEN');
        }

        if ($actor->status !== 'ACTIVE') {
            throw new Exception('FORBIDDEN');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $submission = $db->table('submissions')->where('id', $submissionId)->get()->getRowArray();
            if (!$submission) {
                throw new Exception('NOT_FOUND');
            }

            if (!in_array($submission['current_state'], ['ASSIGNED_TO_VERIFIER', 'RESUBMITTED', 'VERIFIER_REVIEW'], true)) {
                throw new Exception('LOCKED');
            }

            $institutionId = (int)$submission['institution_id'];

            // Separation of Duties: Author cannot verify own submission
            if ((int)$actor->id === (int)$submission['author_id']) {
                throw new Exception('SOD_AUTHOR_CANNOT_VERIFY');
            }

            // Verify active verifier assignment
            $assignment = $this->assignmentModel->getActiveAssignment($submissionId);
            if (!$assignment) {
                throw new Exception('NO_ACTIVE_ASSIGNMENT');
            }

            if ((int)$assignment['verifier_id'] !== (int)$actor->id && $roleCode !== 'SUPER_ADMIN') {
                throw new Exception('WRONG_VERIFIER');
            }

            // Authorization check
            if (!$this->authzService->can($actor, 'REVIEW', $institutionId, $submission['current_state'])) {
                throw new Exception('FORBIDDEN');
            }

            $latestVersion = $this->versionModel->getLatestVersion($submissionId);
            $versionId = $latestVersion ? (int)$latestVersion['id'] : 0;

            // Insert Verification Record for Gate 2 review
            $this->verifModel->insert([
                'version_id'          => $versionId,
                'reviewer_id'         => (int)$actor->id,
                'gate_level'          => 'GATE_2',
                'verification_result' => 'IN_REVIEW',
                'general_notes'       => $notes ?? 'Substantive review started.',
                'verified_at'         => date('Y-m-d H:i:s'),
            ]);

            $verificationId = (int)$this->verifModel->getInsertID();

            // Update Submission State to IN_REVIEW_BY_VERIFIER
            $this->submissionModel->update($submissionId, [
                'current_state' => 'IN_REVIEW_BY_VERIFIER',
            ]);

            // Audit Trail
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $roleCode,
                'action_event'    => 'VERIFIER_REVIEW_START',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode([
                    'from_state'      => $submission['current_state'],
                    'to_state'        => 'IN_REVIEW_BY_VERIFIER',
                    'verification_id' => $verificationId,
                    'version_id'      => $versionId,
                ]),
            ]);

            $db->transCommit();

            return [
                'submissionId'   => $submissionId,
                'currentState'   => 'IN_REVIEW_BY_VERIFIER',
                'verificationId' => $verificationId,
                'startedAt'      => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Verifier adds substantive review notes/findings to a specific version unit.
     *
     * @param UserEntity $actor
     * @param int        $submissionId
     * @param array      $data
     * @return array
     * @throws Exception
     */
    public function addReviewNote(UserEntity $actor, int $submissionId, array $data): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        if (!in_array($roleCode, ['VERIFIER', 'SUPER_ADMIN'], true)) {
            throw new Exception('FORBIDDEN');
        }

        $submission = $this->submissionModel->find($submissionId);
        if (!$submission) {
            throw new Exception('NOT_FOUND');
        }

        if (!in_array($submission->current_state, ['IN_REVIEW_BY_VERIFIER', 'RESUBMITTED', 'VERIFIER_REVIEW'], true)) {
            throw new Exception('LOCKED');
        }

        $institutionId = (int)$submission->institution_id;

        // Separation of Duties
        if ((int)$actor->id === (int)$submission->author_id) {
            throw new Exception('SOD_AUTHOR_CANNOT_VERIFY');
        }

        // Verify active assignment
        $assignment = $this->assignmentModel->getActiveAssignment($submissionId);
        if (!$assignment || ((int)$assignment['verifier_id'] !== (int)$actor->id && $roleCode !== 'SUPER_ADMIN')) {
            throw new Exception('WRONG_VERIFIER');
        }

        // Authorization check
        if (!$this->authzService->can($actor, 'REVIEW', $institutionId, $submission->current_state)) {
            throw new Exception('FORBIDDEN');
        }

        $issueDescription = trim((string)($data['issue_description'] ?? ''));
        $versionUnitId    = !empty($data['version_unit_id']) ? (int)$data['version_unit_id'] : null;

        if (empty($issueDescription)) {
            throw new Exception('Issue description is required.');
        }

        $latestVersion = $this->versionModel->getLatestVersion($submissionId);
        $versionId = $latestVersion ? (int)$latestVersion['id'] : 0;

        // Find or create Gate 2 verification record
        $gate2Record = $this->verifModel->getLatestGate2Record($versionId, (int)$actor->id);
        if (!$gate2Record) {
            $this->verifModel->insert([
                'version_id'          => $versionId,
                'reviewer_id'         => (int)$actor->id,
                'gate_level'          => 'GATE_2',
                'verification_result' => 'IN_REVIEW',
                'general_notes'       => 'Substantive review in progress.',
                'verified_at'         => date('Y-m-d H:i:s'),
            ]);
            $verifId = (int)$this->verifModel->getInsertID();
        } else {
            $verifId = (int)$gate2Record['id'];
        }

        // Insert Revision Note
        $this->revisionModel->insert([
            'verification_id'   => $verifId,
            'version_unit_id'   => $versionUnitId,
            'issue_description' => $issueDescription,
            'is_resolved'       => 0,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);

        $noteId = (int)$this->revisionModel->getInsertID();

        // Audit Trail
        $this->auditService->log([
            'actor_id'        => $actor->id,
            'actor_role'      => $roleCode,
            'action_event'    => 'VERIFIER_REVIEW_NOTE',
            'resource_entity' => 'revision_notes',
            'resource_id'     => $noteId,
            'payload_new'     => json_encode([
                'submission_id'     => $submissionId,
                'verification_id'   => $verifId,
                'version_unit_id'   => $versionUnitId,
                'issue_description' => $issueDescription,
            ]),
        ]);

        return [
            'id'               => $noteId,
            'submissionId'     => $submissionId,
            'verificationId'   => $verifId,
            'versionUnitId'    => $versionUnitId,
            'issueDescription' => $issueDescription,
            'isResolved'       => false,
            'createdAt'        => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Verifier returns submission for substantive revision.
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
        $roleCode = $this->authzService->getUserRoleCode($actor);
        if (!in_array($roleCode, ['VERIFIER', 'SUPER_ADMIN'], true)) {
            throw new Exception('FORBIDDEN');
        }

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

            if (!in_array($submission['current_state'], ['IN_REVIEW_BY_VERIFIER', 'RESUBMITTED', 'VERIFIER_REVIEW'], true)) {
                throw new Exception('LOCKED');
            }

            $institutionId = (int)$submission['institution_id'];

            // Separation of Duties
            if ((int)$actor->id === (int)$submission['author_id']) {
                throw new Exception('SOD_AUTHOR_CANNOT_VERIFY');
            }

            // Verify active assignment
            $assignment = $this->assignmentModel->getActiveAssignment($submissionId);
            if (!$assignment || ((int)$assignment['verifier_id'] !== (int)$actor->id && $roleCode !== 'SUPER_ADMIN')) {
                throw new Exception('WRONG_VERIFIER');
            }

            // Authorization check
            if (!$this->authzService->can($actor, 'RETURN_REVISION', $institutionId, $submission['current_state'])) {
                throw new Exception('FORBIDDEN');
            }

            $latestVersion = $this->versionModel->getLatestVersion($submissionId);
            $versionId = $latestVersion ? (int)$latestVersion['id'] : 0;

            // Insert Verification Record
            $this->verifModel->insert([
                'version_id'          => $versionId,
                'reviewer_id'         => (int)$actor->id,
                'gate_level'          => 'GATE_2',
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
                'current_state' => 'REVISION_REQUIRED_BY_VERIFIER',
            ]);

            // Audit Trail
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $roleCode,
                'action_event'    => 'VERIFIER_REVIEW_RETURN',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode([
                    'from_state'       => $submission['current_state'],
                    'to_state'         => 'REVISION_REQUIRED_BY_VERIFIER',
                    'reason'           => $reason,
                    'revision_note_id' => $revNoteId,
                ]),
            ]);

            $db->transCommit();

            return [
                'submissionId'   => $submissionId,
                'currentState'   => 'REVISION_REQUIRED_BY_VERIFIER',
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
     * Verifier Approves Substantive Verification & Issues Technical Recommendation (Step 9).
     *
     * Transitions submission from IN_REVIEW_BY_VERIFIER / RESUBMITTED -> READY_FOR_FINAL_DECISION.
     *
     * @param UserEntity $actor
     * @param int        $submissionId
     * @param array      $payload
     * @return array
     * @throws Exception
     */
    public function approveSubstantive(UserEntity $actor, int $submissionId, array $payload): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        if (!in_array($roleCode, ['VERIFIER', 'SUPER_ADMIN'], true)) {
            throw new Exception('FORBIDDEN');
        }

        if ($actor->status !== 'ACTIVE') {
            throw new Exception('FORBIDDEN');
        }

        $summary = trim((string)($payload['recommendation_summary'] ?? $payload['notes'] ?? ''));
        if (empty($summary) || strlen($summary) < 5) {
            throw new Exception('Recommendation summary is required and must be at least 5 characters.');
        }

        $findings          = !empty($payload['substantive_findings']) ? trim((string)$payload['substantive_findings']) : null;
        $regulatoryAspects = !empty($payload['regulatory_considerations']) ? trim((string)$payload['regulatory_considerations']) : null;
        $recommendedAction = !empty($payload['recommended_action']) ? trim((string)$payload['recommended_action']) : 'PROCEED_TO_FINAL_APPROVAL';
        $resolveAllNotes   = !empty($payload['resolve_all_notes']);
        $resolvedNoteIds   = !empty($payload['resolved_note_ids']) && is_array($payload['resolved_note_ids']) ? $payload['resolved_note_ids'] : [];

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Concurrency: Lock submission row
            $submission = $db->table('submissions')->where('id', $submissionId)->get()->getRowArray();
            if (!$submission) {
                throw new Exception('NOT_FOUND');
            }

            // Valid current states for substantive verification pass
            if (!in_array($submission['current_state'], ['IN_REVIEW_BY_VERIFIER', 'RESUBMITTED', 'VERIFIER_REVIEW', 'ASSIGNED_TO_VERIFIER'], true)) {
                throw new Exception('LOCKED');
            }

            $institutionId = (int)$submission['institution_id'];

            // Separation of Duties: Author CANNOT verify/approve own submission
            if ((int)$actor->id === (int)$submission['author_id']) {
                throw new Exception('SOD_AUTHOR_CANNOT_VERIFY');
            }

            // Verify active assignment to this verifier
            $assignment = $this->assignmentModel->getActiveAssignment($submissionId);
            if (!$assignment || ((int)$assignment['verifier_id'] !== (int)$actor->id && $roleCode !== 'SUPER_ADMIN')) {
                throw new Exception('WRONG_VERIFIER');
            }

            // Authorization Check
            if (!$this->authzService->can($actor, 'APPROVE', $institutionId, $submission['current_state']) &&
                !$this->authzService->can($actor, 'VERIFY', $institutionId, $submission['current_state'])) {
                throw new Exception('FORBIDDEN');
            }

            $latestVersion = $this->versionModel->getLatestVersion($submissionId);
            if (!$latestVersion) {
                throw new Exception('No version found for submission.');
            }

            $versionId = (int)$latestVersion['id'];

            // Handle resolution of revision notes if indicated
            if ($resolveAllNotes) {
                $allNotes = $this->revisionModel->getBySubmissionId($submissionId);
                foreach ($allNotes as $n) {
                    $this->revisionModel->update((int)$n['id'], ['is_resolved' => 1]);
                }
            } elseif (!empty($resolvedNoteIds)) {
                foreach ($resolvedNoteIds as $nId) {
                    $this->revisionModel->update((int)$nId, ['is_resolved' => 1]);
                }
            }

            // Check Revision Completion: Ensure no unresolved notes remain
            $revStatus = $this->validateRevisionCompletion($submissionId);
            if (!$revStatus['isComplete']) {
                throw new Exception('UNRESOLVED_REVISIONS');
            }

            // Construct Recommendation Details Payload
            $recommendationData = [
                'recommendation_summary'    => $summary,
                'substantive_findings'      => $findings,
                'regulatory_considerations' => $regulatoryAspects,
                'recommended_action'        => $recommendedAction,
                'verifier_id'               => (int)$actor->id,
                'verifier_username'         => $actor->username,
                'version_id'                => $versionId,
                'version_number'            => (int)$latestVersion['version_number'],
                'verified_at'               => date('Y-m-d H:i:s'),
            ];

            // Insert Verification Record (Gate 2 Passed)
            $this->verifModel->insert([
                'version_id'          => $versionId,
                'reviewer_id'         => (int)$actor->id,
                'gate_level'          => 'GATE_2',
                'verification_result' => 'SUBSTANTIVE_PASSED',
                'general_notes'       => json_encode($recommendationData),
                'verified_at'         => date('Y-m-d H:i:s'),
            ]);

            $verificationId = (int)$this->verifModel->getInsertID();

            // Next State: READY_FOR_FINAL_DECISION (Step 9 boundary)
            $nextState = 'READY_FOR_FINAL_DECISION';
            $this->submissionModel->update($submissionId, [
                'current_state' => $nextState,
            ]);

            // Audit Trail 1: Substantive Verification Approved
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $roleCode,
                'action_event'    => 'VERIFIER_SUBSTANTIVE_APPROVED',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode([
                    'from_state'          => $submission['current_state'],
                    'to_state'            => $nextState,
                    'version_id'          => $versionId,
                    'verification_id'     => $verificationId,
                    'verification_result' => 'SUBSTANTIVE_PASSED',
                ]),
            ]);

            // Audit Trail 2: Technical Recommendation Created
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $roleCode,
                'action_event'    => 'VERIFIER_RECOMMENDATION_CREATED',
                'resource_entity' => 'verification_records',
                'resource_id'     => $verificationId,
                'payload_new'     => json_encode($recommendationData),
            ]);

            $db->transCommit();

            return [
                'submissionId'          => $submissionId,
                'currentState'          => $nextState,
                'verificationId'        => $verificationId,
                'versionId'             => $versionId,
                'versionNumber'         => (int)$latestVersion['version_number'],
                'verificationResult'    => 'SUBSTANTIVE_PASSED',
                'technicalRecommendation' => $recommendationData,
                'verifiedAt'            => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Retrieve the latest substantive technical recommendation for a submission.
     *
     * @param UserEntity $actor
     * @param int        $submissionId
     * @return array|null
     * @throws Exception
     */
    public function getRecommendation(UserEntity $actor, int $submissionId): ?array
    {
        $submission = $this->submissionModel->find($submissionId);
        if (!$submission) {
            throw new Exception('NOT_FOUND');
        }

        $institutionId = (int)$submission->institution_id;
        if (!$this->authzService->can($actor, 'VIEW', $institutionId, $submission->current_state)) {
            throw new Exception('FORBIDDEN');
        }

        $db = \Config\Database::connect();
        $record = $db->table('verification_records vr')
            ->select('vr.*, u.username as verifier_username, u.full_name as verifier_name, sv.version_number')
            ->join('users u', 'vr.reviewer_id = u.id')
            ->join('submission_versions sv', 'vr.version_id = sv.id')
            ->where('sv.submission_id', $submissionId)
            ->where('vr.gate_level', 'GATE_2')
            ->whereIn('vr.verification_result', ['SUBSTANTIVE_PASSED', 'VERIFICATION_PASSED'])
            ->orderBy('vr.id', 'DESC')
            ->get()
            ->getRowArray();

        if (!$record) {
            return null;
        }

        $parsed = json_decode((string)$record['general_notes'], true);

        return [
            'id'                 => (int)$record['id'],
            'submissionId'       => $submissionId,
            'versionId'          => (int)$record['version_id'],
            'versionNumber'      => (int)$record['version_number'],
            'verifierId'         => (int)$record['reviewer_id'],
            'verifierUsername'   => $record['verifier_username'],
            'verifierName'       => $record['verifier_name'],
            'verificationResult' => $record['verification_result'],
            'verifiedAt'         => $record['verified_at'],
            'recommendation'     => is_array($parsed) ? $parsed : ['notes' => $record['general_notes']],
        ];
    }
}
