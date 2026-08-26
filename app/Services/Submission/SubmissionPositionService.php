<?php

namespace App\Services\Submission;

use App\Entities\UserEntity;
use App\Models\PositionModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionPositionModel;
use App\Models\SubmissionUnitModel;
use App\Models\SubmissionVersionModel;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationService;
use Exception;

/**
 * SubmissionPositionService
 *
 * Handles management of proposed position changes (NEW, UPDATE, DELETE, UNCHANGED) in DRAFT state.
 */
class SubmissionPositionService
{
    protected SubmissionModel $submissionModel;
    protected SubmissionVersionModel $versionModel;
    protected SubmissionUnitModel $unitModel;
    protected SubmissionPositionModel $posModel;
    protected PositionModel $masterPosModel;
    protected AuthorizationService $authzService;
    protected AuditService $auditService;

    public function __construct(
        ?SubmissionModel $submissionModel = null,
        ?SubmissionVersionModel $versionModel = null,
        ?SubmissionUnitModel $unitModel = null,
        ?SubmissionPositionModel $posModel = null,
        ?PositionModel $masterPosModel = null,
        ?AuthorizationService $authzService = null,
        ?AuditService $auditService = null
    ) {
        $this->submissionModel = $submissionModel ?? new SubmissionModel();
        $this->versionModel    = $versionModel ?? new SubmissionVersionModel();
        $this->unitModel       = $unitModel ?? new SubmissionUnitModel();
        $this->posModel        = $posModel ?? new SubmissionPositionModel();
        $this->masterPosModel  = $masterPosModel ?? new PositionModel();
        $this->authzService    = $authzService ?? new AuthorizationService();
        $this->auditService    = $auditService ?? new AuditService();
    }

    /**
     * Add a proposed position change to a unit in a DRAFT submission.
     *
     * @param UserEntity $user
     * @param int        $submissionId
     * @param array      $data
     * @return array
     * @throws Exception
     */
    public function addPositionChange(UserEntity $user, int $submissionId, array $data): array
    {
        $submission = $this->submissionModel->find($submissionId);
        if (!$submission) {
            throw new Exception('NOT_FOUND');
        }

        // State Locking Check: Allowed in DRAFT and REVISION states
        if (!in_array($submission->current_state, ['DRAFT', 'REVISION_REQUIRED', 'REVISION_REQUIRED_BY_VERIFIER', 'REVISION_BY_ADMIN', 'REVISION_BY_VERIFIER'], true)) {
            throw new Exception('LOCKED');
        }

        $institutionId = (int)$submission->institution_id;
        $roleCode = $this->authzService->getUserRoleCode($user);

        // SoD / Ownership: Only author or SuperAdmin can edit
        if ((int)$user->id !== (int)$submission->author_id && $roleCode !== 'SUPER_ADMIN') {
            throw new Exception('FORBIDDEN');
        }

        if (!$this->authzService->can($user, 'EDIT', $institutionId, $submission->current_state)) {
            throw new Exception('FORBIDDEN');
        }

        $latestVersion = $this->versionModel->getLatestVersion($submissionId);
        if (!$latestVersion) {
            throw new Exception('No active version found for submission.');
        }

        $versionId = (int)$latestVersion['id'];

        $versionUnitId    = (int)($data['version_unit_id'] ?? 0);
        $positionName     = trim((string)($data['position_name'] ?? ''));
        $positionType     = trim((string)($data['position_type'] ?? ''));
        $echelon          = !empty($data['echelon']) ? trim((string)$data['echelon']) : null;
        $formationCount   = (int)($data['formation_count'] ?? 1);
        $changeType       = strtoupper(trim((string)($data['change_type'] ?? 'NEW')));
        $sourcePositionId = !empty($data['source_position_id']) ? (int)$data['source_position_id'] : null;

        if (empty($positionName) || empty($positionType)) {
            throw new Exception('Position name and position type are required.');
        }

        if ($formationCount <= 0) {
            throw new Exception('Formation count must be greater than 0.');
        }

        if (!in_array($changeType, ['NEW', 'UPDATE', 'DELETE', 'UNCHANGED'], true)) {
            throw new Exception("Invalid change type '{$changeType}'.");
        }

        // Verify version unit belongs to this version
        $unit = $this->unitModel->find($versionUnitId);
        $unitVersionId = $unit ? (is_array($unit) ? (int)$unit['version_id'] : (int)$unit->version_id) : null;
        if (!$unit || $unitVersionId !== $versionId) {
            throw new Exception('Target unit does not exist in the current submission version.');
        }

        // Cross-Institution validation on source_position_id
        if ($sourcePositionId !== null) {
            $sourcePos = $this->masterPosModel->getPositionWithUnitAndInstitution($sourcePositionId);
            if (!$sourcePos || (int)$sourcePos['institution_id'] !== $institutionId) {
                throw new Exception('Source position does not exist or belongs to another institution.');
            }
        }

        $this->posModel->insert([
            'version_unit_id'    => $versionUnitId,
            'source_position_id' => $sourcePositionId,
            'position_name'      => $positionName,
            'position_type'      => $positionType,
            'echelon'            => $echelon,
            'formation_count'    => $formationCount,
            'change_type'        => $changeType,
        ]);

        $posId = (int)$this->posModel->getInsertID();

        // Audit Log
        $actorRole = $this->authzService->getUserRoleCode($user);
        $this->auditService->log([
            'actor_id'        => $user->id,
            'actor_role'      => $actorRole,
            'action_event'    => 'CREATE_SUBMISSION_POSITION',
            'resource_entity' => 'submission_positions',
            'resource_id'     => $posId,
            'payload_new'     => json_encode([
                'submission_id'   => $submissionId,
                'version_id'      => $versionId,
                'version_unit_id' => $versionUnitId,
                'position_name'   => $positionName,
                'formation_count' => $formationCount,
                'change_type'     => $changeType,
            ]),
        ]);

        return [
            'id'               => $posId,
            'versionUnitId'    => $versionUnitId,
            'sourcePositionId' => $sourcePositionId,
            'positionName'     => $positionName,
            'positionType'     => $positionType,
            'echelon'          => $echelon,
            'formationCount'   => $formationCount,
            'changeType'       => $changeType,
        ];
    }

    /**
     * Update a proposed position change in a DRAFT submission.
     *
     * @param UserEntity $user
     * @param int        $submissionId
     * @param int        $positionId
     * @param array      $data
     * @return array
     * @throws Exception
     */
    public function updatePositionChange(UserEntity $user, int $submissionId, int $positionId, array $data): array
    {
        $pos = $this->posModel->getPositionWithSubmissionAndInstitution($positionId);
        if (!$pos || (int)$pos['submission_id'] !== $submissionId) {
            throw new Exception('NOT_FOUND');
        }

        // State Locking Check: Allowed in DRAFT and REVISION states
        if (!in_array($pos['current_state'], ['DRAFT', 'REVISION_REQUIRED', 'REVISION_REQUIRED_BY_VERIFIER', 'REVISION_BY_ADMIN', 'REVISION_BY_VERIFIER'], true)) {
            throw new Exception('LOCKED');
        }

        $institutionId = (int)$pos['institution_id'];
        $roleCode = $this->authzService->getUserRoleCode($user);

        // SoD / Ownership: Only author or SuperAdmin can edit
        $submission = $this->submissionModel->find($submissionId);
        if ($submission && (int)$user->id !== (int)$submission->author_id && $roleCode !== 'SUPER_ADMIN') {
            throw new Exception('FORBIDDEN');
        }

        if (!$this->authzService->can($user, 'EDIT', $institutionId, $pos['current_state'])) {
            throw new Exception('FORBIDDEN');
        }

        $updateData = [];
        if (isset($data['position_name'])) {
            $updateData['position_name'] = trim((string)$data['position_name']);
        }
        if (isset($data['position_type'])) {
            $updateData['position_type'] = trim((string)$data['position_type']);
        }
        if (array_key_exists('echelon', $data)) {
            $updateData['echelon'] = !empty($data['echelon']) ? trim((string)$data['echelon']) : null;
        }
        if (isset($data['formation_count'])) {
            $updateData['formation_count'] = (int)$data['formation_count'];
        }
        if (isset($data['change_type'])) {
            $changeType = strtoupper(trim((string)$data['change_type']));
            if (!in_array($changeType, ['NEW', 'UPDATE', 'DELETE', 'UNCHANGED'], true)) {
                throw new Exception("Invalid change type '{$changeType}'.");
            }
            $updateData['change_type'] = $changeType;
        }

        if (empty($updateData)) {
            throw new Exception('No update parameters provided.');
        }

        $this->posModel->update($positionId, $updateData);

        // Audit Log
        $actorRole = $this->authzService->getUserRoleCode($user);
        $this->auditService->log([
            'actor_id'        => $user->id,
            'actor_role'      => $actorRole,
            'action_event'    => 'UPDATE_SUBMISSION_POSITION',
            'resource_entity' => 'submission_positions',
            'resource_id'     => $positionId,
            'payload_new'     => json_encode($updateData),
        ]);

        return array_merge($pos, $updateData);
    }

    /**
     * Delete a proposed position change from a DRAFT submission.
     *
     * @param UserEntity $user
     * @param int        $submissionId
     * @param int        $positionId
     * @return bool
     * @throws Exception
     */
    public function deletePositionChange(UserEntity $user, int $submissionId, int $positionId): bool
    {
        $pos = $this->posModel->getPositionWithSubmissionAndInstitution($positionId);
        if (!$pos || (int)$pos['submission_id'] !== $submissionId) {
            throw new Exception('NOT_FOUND');
        }

        // State Locking Check: Allowed in DRAFT and REVISION states
        if (!in_array($pos['current_state'], ['DRAFT', 'REVISION_REQUIRED', 'REVISION_REQUIRED_BY_VERIFIER', 'REVISION_BY_ADMIN', 'REVISION_BY_VERIFIER'], true)) {
            throw new Exception('LOCKED');
        }

        $institutionId = (int)$pos['institution_id'];
        $roleCode = $this->authzService->getUserRoleCode($user);

        // SoD / Ownership: Only author or SuperAdmin can delete
        $submission = $this->submissionModel->find($submissionId);
        if ($submission && (int)$user->id !== (int)$submission->author_id && $roleCode !== 'SUPER_ADMIN') {
            throw new Exception('FORBIDDEN');
        }

        if (!$this->authzService->can($user, 'EDIT', $institutionId, $pos['current_state'])) {
            throw new Exception('FORBIDDEN');
        }

        $this->posModel->delete($positionId);

        // Audit Log
        $actorRole = $this->authzService->getUserRoleCode($user);
        $this->auditService->log([
            'actor_id'        => $user->id,
            'actor_role'      => $actorRole,
            'action_event'    => 'DELETE_SUBMISSION_POSITION',
            'resource_entity' => 'submission_positions',
            'resource_id'     => $positionId,
            'payload_new'     => json_encode(['deleted_position_id' => $positionId]),
        ]);

        return true;
    }
}
