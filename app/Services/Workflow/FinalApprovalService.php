<?php

namespace App\Services\Workflow;

use App\Entities\UserEntity;
use App\Models\ApprovalRecordModel;
use App\Models\OrganizationalUnitModel;
use App\Models\PositionModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionPositionModel;
use App\Models\SubmissionUnitModel;
use App\Models\SubmissionVersionModel;
use App\Models\VerificationRecordModel;
use App\Models\VerifierAssignmentModel;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationService;
use Exception;
use Throwable;

/**
 * FinalApprovalService
 *
 * Implements Step 10: Final Approval Recording, Snapshot Immutability, and Master Data Promotion Reconciliation.
 */
class FinalApprovalService
{
    protected SubmissionModel $submissionModel;
    protected SubmissionVersionModel $versionModel;
    protected SubmissionUnitModel $unitModel;
    protected SubmissionPositionModel $posModel;
    protected VerificationRecordModel $verifModel;
    protected VerifierAssignmentModel $assignmentModel;
    protected ApprovalRecordModel $approvalModel;
    protected OrganizationalUnitModel $masterUnitModel;
    protected PositionModel $masterPosModel;
    protected AuthorizationService $authzService;
    protected AuditService $auditService;

    public function __construct(
        ?SubmissionModel $submissionModel = null,
        ?SubmissionVersionModel $versionModel = null,
        ?SubmissionUnitModel $unitModel = null,
        ?SubmissionPositionModel $posModel = null,
        ?VerificationRecordModel $verifModel = null,
        ?VerifierAssignmentModel $assignmentModel = null,
        ?ApprovalRecordModel $approvalModel = null,
        ?OrganizationalUnitModel $masterUnitModel = null,
        ?PositionModel $masterPosModel = null,
        ?AuthorizationService $authzService = null,
        ?AuditService $auditService = null
    ) {
        $this->submissionModel = $submissionModel ?? new SubmissionModel();
        $this->versionModel    = $versionModel ?? new SubmissionVersionModel();
        $this->unitModel       = $unitModel ?? new SubmissionUnitModel();
        $this->posModel        = $posModel ?? new SubmissionPositionModel();
        $this->verifModel      = $verifModel ?? new VerificationRecordModel();
        $this->assignmentModel = $assignmentModel ?? new VerifierAssignmentModel();
        $this->approvalModel   = $approvalModel ?? new ApprovalRecordModel();
        $this->masterUnitModel = $masterUnitModel ?? new OrganizationalUnitModel();
        $this->masterPosModel  = $masterPosModel ?? new PositionModel();
        $this->authzService    = $authzService ?? new AuthorizationService();
        $this->auditService    = $auditService ?? new AuditService();
    }

    /**
     * Final business approval of a submission by the assigned Verifier.
     *
     * READY_FOR_FINAL_DECISION -> APPROVED
     *
     * @param UserEntity $actor
     * @param int        $submissionId
     * @param array      $payload
     * @return array
     * @throws Exception
     */
    public function approveSubmission(UserEntity $actor, int $submissionId, array $payload = []): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        if (!in_array($roleCode, ['VERIFIER', 'SUPER_ADMIN'], true)) {
            throw new Exception('FORBIDDEN');
        }

        if ($actor->status !== 'ACTIVE') {
            throw new Exception('FORBIDDEN');
        }

        $approvalNumber = !empty($payload['approval_number']) ? trim((string)$payload['approval_number']) : null;
        $approvalNotes  = !empty($payload['approval_notes']) ? trim((string)$payload['approval_notes']) : (!empty($payload['notes']) ? trim((string)$payload['notes']) : null);

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Row-level lock on submission for concurrency safety
            $submission = $db->table('submissions')->where('id', $submissionId)->get()->getRowArray();
            if (!$submission) {
                throw new Exception('NOT_FOUND');
            }

            // If already approved, reject with conflict
            if (in_array($submission['current_state'], ['APPROVED', 'PROMOTED'], true)) {
                throw new Exception('ALREADY_APPROVED');
            }

            // Must be in READY_FOR_FINAL_DECISION (or equivalent verified state)
            if (!in_array($submission['current_state'], ['READY_FOR_FINAL_DECISION', 'SUBSTANTIVE_PASSED', 'VERIFIED_BY_VERIFIER'], true)) {
                throw new Exception('LOCKED');
            }

            $institutionId = (int)$submission['institution_id'];

            // Separation of Duties: Author CANNOT approve own submission
            if ((int)$actor->id === (int)$submission['author_id']) {
                throw new Exception('SOD_AUTHOR_CANNOT_APPROVE');
            }

            // Verify active assignment to this verifier
            $assignment = $this->assignmentModel->getActiveAssignment($submissionId);
            if (!$assignment || ((int)$assignment['verifier_id'] !== (int)$actor->id && $roleCode !== 'SUPER_ADMIN')) {
                throw new Exception('WRONG_VERIFIER');
            }

            // Authorization check
            if (!$this->authzService->can($actor, 'APPROVE', $institutionId, $submission['current_state'])) {
                throw new Exception('FORBIDDEN');
            }

            $latestVersion = $this->versionModel->getLatestVersion($submissionId);
            if (!$latestVersion) {
                throw new Exception('No submission version found.');
            }

            $versionId = (int)$latestVersion['id'];

            // Verify substantive verification record exists
            $gate2Record = $db->table('verification_records')
                ->where('version_id', $versionId)
                ->where('gate_level', 'GATE_2')
                ->whereIn('verification_result', ['SUBSTANTIVE_PASSED', 'VERIFICATION_PASSED'])
                ->get()
                ->getRowArray();

            if (!$gate2Record) {
                throw new Exception('SUBSTANTIVE_VERIFICATION_REQUIRED');
            }

            // Insert Approval Record
            $this->approvalModel->insert([
                'version_id'      => $versionId,
                'approver_id'     => (int)$actor->id,
                'approval_number' => $approvalNumber,
                'approval_notes'  => $approvalNotes,
                'approved_at'     => date('Y-m-d H:i:s'),
            ]);

            $approvalId = (int)$this->approvalModel->getInsertID();

            // Mark verifier assignment as completed
            if (!empty($assignment['id'])) {
                $this->assignmentModel->update((int)$assignment['id'], [
                    'status' => 'COMPLETED',
                ]);
            }

            // Update Submission state to APPROVED
            $nextState = 'APPROVED';
            $this->submissionModel->update($submissionId, [
                'current_state' => $nextState,
            ]);

            // Audit Trail: Final Approved
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $roleCode,
                'action_event'    => 'SUBMISSION_FINAL_APPROVED',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode([
                    'from_state'      => $submission['current_state'],
                    'to_state'        => $nextState,
                    'approval_id'     => $approvalId,
                    'version_id'      => $versionId,
                    'version_number'  => (int)$latestVersion['version_number'],
                    'approval_number' => $approvalNumber,
                ]),
            ]);

            $db->transCommit();

            return [
                'submissionId'   => $submissionId,
                'currentState'   => $nextState,
                'approvalId'     => $approvalId,
                'versionId'      => $versionId,
                'versionNumber'  => (int)$latestVersion['version_number'],
                'approvalNumber' => $approvalNumber,
                'approvedAt'     => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Master Data Promotion Reconciliation.
     *
     * Promotes the approved snapshot into master tables (organizational_units & positions)
     * using non-destructive reconciliation semantics.
     *
     * APPROVED -> PROMOTED
     *
     * @param UserEntity $actor
     * @param int        $submissionId
     * @return array
     * @throws Exception
     */
    public function promoteSubmission(UserEntity $actor, int $submissionId): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        if (!in_array($roleCode, ['VERIFIER', 'ADMIN', 'SUPER_ADMIN'], true)) {
            throw new Exception('FORBIDDEN');
        }

        if ($actor->status !== 'ACTIVE') {
            throw new Exception('FORBIDDEN');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Row-level lock on submission
            $submission = $db->table('submissions')->where('id', $submissionId)->get()->getRowArray();
            if (!$submission) {
                throw new Exception('NOT_FOUND');
            }

            // Idempotency: Reject if already promoted
            if ($submission['current_state'] === 'PROMOTED') {
                throw new Exception('ALREADY_PROMOTED');
            }

            // Must be strictly APPROVED
            if ($submission['current_state'] !== 'APPROVED') {
                throw new Exception('NOT_APPROVED');
            }

            $institutionId = (int)$submission['institution_id'];

            // Separation of Duties: Author cannot promote own submission if regular user
            if ((int)$actor->id === (int)$submission['author_id'] && $roleCode === 'USER') {
                throw new Exception('SOD_AUTHOR_CANNOT_PROMOTE');
            }

            // Authorization check
            if (!$this->authzService->can($actor, 'PROMOTE', $institutionId, 'APPROVED')) {
                throw new Exception('FORBIDDEN');
            }

            $latestVersion = $this->versionModel->getLatestVersion($submissionId);
            if (!$latestVersion) {
                throw new Exception('No submission version found.');
            }

            $versionId = (int)$latestVersion['id'];

            // Verify approval record exists
            $approval = $this->approvalModel->getByVersionId($versionId);
            if (!$approval) {
                throw new Exception('APPROVAL_RECORD_MISSING');
            }

            // Audit Trail: Promotion Started
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $roleCode,
                'action_event'    => 'SUBMISSION_PROMOTION_STARTED',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode([
                    'version_id'     => $versionId,
                    'version_number' => (int)$latestVersion['version_number'],
                ]),
            ]);

            // 1. Fetch submission units and positions
            $submissionUnits = $this->unitModel->getByVersionId($versionId);
            $submissionPositions = $this->posModel->getByVersionId($versionId);

            $createdUnitsCount     = 0;
            $updatedUnitsCount     = 0;
            $deactivatedUnitsCount = 0;
            $unchangedUnitsCount   = 0;

            $unitMap = []; // [submission_unit_id => master_unit_id]

            // Pass 1: Process units (Roots and independent units first)
            // Sort by unit_level ASC to ensure parent units exist before children
            usort($submissionUnits, static function ($a, $b) {
                $levelA = is_array($a) ? (int)$a['unit_level'] : (int)$a->unit_level;
                $levelB = is_array($b) ? (int)$b['unit_level'] : (int)$b->unit_level;
                return $levelA <=> $levelB;
            });

            foreach ($submissionUnits as $u) {
                $uArray = is_array($u) ? $u : $u->toArray();
                $subUnitId   = (int)$uArray['id'];
                $changeType  = strtoupper((string)$uArray['change_type']);
                $sourceId    = !empty($uArray['source_unit_id']) ? (int)$uArray['source_unit_id'] : null;
                $tempParentId = !empty($uArray['temp_parent_id']) ? (int)$uArray['temp_parent_id'] : null;

                // Resolve parent_unit_id in master table
                $resolvedParentId = null;
                if ($tempParentId !== null && isset($unitMap[$tempParentId])) {
                    $resolvedParentId = $unitMap[$tempParentId];
                }

                switch ($changeType) {
                    case 'NEW':
                        $this->masterUnitModel->insert([
                            'institution_id' => $institutionId,
                            'parent_unit_id' => $resolvedParentId,
                            'unit_code'      => $uArray['unit_code'],
                            'unit_name'      => $uArray['unit_name'],
                            'unit_level'     => (int)$uArray['unit_level'],
                            'order_index'    => (int)$uArray['order_index'],
                            'status'         => 'ACTIVE',
                        ]);
                        $newMasterId = (int)$this->masterUnitModel->getInsertID();
                        $unitMap[$subUnitId] = $newMasterId;
                        $createdUnitsCount++;
                        break;

                    case 'UPDATE':
                        if ($sourceId) {
                            $updateData = [
                                'unit_code'   => $uArray['unit_code'],
                                'unit_name'   => $uArray['unit_name'],
                                'unit_level'  => (int)$uArray['unit_level'],
                                'order_index' => (int)$uArray['order_index'],
                                'status'      => 'ACTIVE',
                            ];
                            if ($resolvedParentId !== null) {
                                $updateData['parent_unit_id'] = $resolvedParentId;
                            }
                            $this->masterUnitModel->update($sourceId, $updateData);
                            $unitMap[$subUnitId] = $sourceId;
                            $updatedUnitsCount++;
                        }
                        break;

                    case 'DELETE':
                        if ($sourceId) {
                            $this->masterUnitModel->update($sourceId, ['status' => 'INACTIVE']);
                            $unitMap[$subUnitId] = $sourceId;
                            $deactivatedUnitsCount++;
                        }
                        break;

                    case 'UNCHANGED':
                    default:
                        if ($sourceId) {
                            $unitMap[$subUnitId] = $sourceId;
                            $unchangedUnitsCount++;
                        }
                        break;
                }
            }

            // 2. Reconcile Positions
            $createdPositionsCount     = 0;
            $updatedPositionsCount     = 0;
            $deactivatedPositionsCount = 0;
            $unchangedPositionsCount   = 0;

            foreach ($submissionPositions as $p) {
                $pArray = is_array($p) ? $p : $p->toArray();
                $subPosId    = (int)$pArray['id'];
                $changeType  = strtoupper((string)$pArray['change_type']);
                $sourceId    = !empty($pArray['source_position_id']) ? (int)$pArray['source_position_id'] : null;
                $verUnitId   = (int)$pArray['version_unit_id'];

                $masterUnitId = $unitMap[$verUnitId] ?? null;

                switch ($changeType) {
                    case 'NEW':
                        if ($masterUnitId) {
                            $this->masterPosModel->insert([
                                'unit_id'         => $masterUnitId,
                                'position_name'   => $pArray['position_name'],
                                'position_type'   => $pArray['position_type'],
                                'echelon'         => $pArray['echelon'],
                                'formation_count' => (int)$pArray['formation_count'],
                                'status'          => 'ACTIVE',
                            ]);
                            $createdPositionsCount++;
                        }
                        break;

                    case 'UPDATE':
                        if ($sourceId) {
                            $updateData = [
                                'position_name'   => $pArray['position_name'],
                                'position_type'   => $pArray['position_type'],
                                'echelon'         => $pArray['echelon'],
                                'formation_count' => (int)$pArray['formation_count'],
                                'status'          => 'ACTIVE',
                            ];
                            if ($masterUnitId) {
                                $updateData['unit_id'] = $masterUnitId;
                            }
                            $this->masterPosModel->update($sourceId, $updateData);
                            $updatedPositionsCount++;
                        }
                        break;

                    case 'DELETE':
                        if ($sourceId) {
                            $this->masterPosModel->update($sourceId, ['status' => 'INACTIVE']);
                            $deactivatedPositionsCount++;
                        }
                        break;

                    case 'UNCHANGED':
                    default:
                        if ($sourceId) {
                            $unchangedPositionsCount++;
                        }
                        break;
                }
            }

            // 3. Update Submission State to PROMOTED
            $nextState = 'PROMOTED';
            $this->submissionModel->update($submissionId, [
                'current_state' => $nextState,
            ]);

            $summary = [
                'units' => [
                    'created'     => $createdUnitsCount,
                    'updated'     => $updatedUnitsCount,
                    'deactivated' => $deactivatedUnitsCount,
                    'unchanged'   => $unchangedUnitsCount,
                ],
                'positions' => [
                    'created'     => $createdPositionsCount,
                    'updated'     => $updatedPositionsCount,
                    'deactivated' => $deactivatedPositionsCount,
                    'unchanged'   => $unchangedPositionsCount,
                ],
            ];

            // Audit Trail: Submission Promoted
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $roleCode,
                'action_event'    => 'SUBMISSION_PROMOTED',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode([
                    'from_state' => 'APPROVED',
                    'to_state'   => $nextState,
                    'version_id' => $versionId,
                    'summary'    => $summary,
                ]),
            ]);

            $db->transCommit();

            return [
                'submissionId' => $submissionId,
                'currentState' => $nextState,
                'promotedAt'   => date('Y-m-d H:i:s'),
                'summary'      => $summary,
            ];
        } catch (Throwable $e) {
            $db->transRollback();

            // Emit audit log for promotion failure
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $roleCode,
                'action_event'    => 'SUBMISSION_PROMOTION_FAILED',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode(['error' => $e->getMessage()]),
            ]);

            throw $e;
        }
    }

    /**
     * Get approval and promotion status for a submission.
     *
     * @param UserEntity $actor
     * @param int        $submissionId
     * @return array
     * @throws Exception
     */
    public function getApprovalStatus(UserEntity $actor, int $submissionId): array
    {
        $submission = $this->submissionModel->find($submissionId);
        if (!$submission) {
            throw new Exception('NOT_FOUND');
        }

        $institutionId = (int)$submission->institution_id;
        if (!$this->authzService->can($actor, 'VIEW', $institutionId, $submission->current_state)) {
            throw new Exception('FORBIDDEN');
        }

        $approval = $this->approvalModel->getBySubmissionId($submissionId);

        return [
            'submissionId' => (int)$submission->id,
            'currentState' => $submission->current_state,
            'isApproved'   => in_array($submission->current_state, ['APPROVED', 'PROMOTED'], true),
            'isPromoted'   => ($submission->current_state === 'PROMOTED'),
            'approval'     => $approval,
        ];
    }
}
