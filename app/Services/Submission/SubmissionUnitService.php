<?php

namespace App\Services\Submission;

use App\Entities\UserEntity;
use App\Models\OrganizationalUnitModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionUnitModel;
use App\Models\SubmissionVersionModel;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationService;
use Exception;

/**
 * SubmissionUnitService
 *
 * Handles management of proposed organizational unit changes (NEW, UPDATE, DELETE, UNCHANGED) in DRAFT state.
 */
class SubmissionUnitService
{
    protected SubmissionModel $submissionModel;
    protected SubmissionVersionModel $versionModel;
    protected SubmissionUnitModel $unitModel;
    protected OrganizationalUnitModel $masterUnitModel;
    protected AuthorizationService $authzService;
    protected AuditService $auditService;

    public function __construct(
        ?SubmissionModel $submissionModel = null,
        ?SubmissionVersionModel $versionModel = null,
        ?SubmissionUnitModel $unitModel = null,
        ?OrganizationalUnitModel $masterUnitModel = null,
        ?AuthorizationService $authzService = null,
        ?AuditService $auditService = null
    ) {
        $this->submissionModel = $submissionModel ?? new SubmissionModel();
        $this->versionModel    = $versionModel ?? new SubmissionVersionModel();
        $this->unitModel       = $unitModel ?? new SubmissionUnitModel();
        $this->masterUnitModel = $masterUnitModel ?? new OrganizationalUnitModel();
        $this->authzService    = $authzService ?? new AuthorizationService();
        $this->auditService    = $auditService ?? new AuditService();
    }

    /**
     * Add a proposed organizational unit change to a DRAFT submission.
     *
     * @param UserEntity $user
     * @param int        $submissionId
     * @param array      $data
     * @return array
     * @throws Exception
     */
    public function addUnitChange(UserEntity $user, int $submissionId, array $data): array
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

        // SoD / Ownership: Only the author (or SuperAdmin) can edit the proposal
        if ((int)$user->id !== (int)$submission->author_id && $roleCode !== 'SUPER_ADMIN') {
            throw new Exception('FORBIDDEN');
        }

        // Authorization Check: User must have EDIT permission on institution in current state
        if (!$this->authzService->can($user, 'EDIT', $institutionId, $submission->current_state)) {
            throw new Exception('FORBIDDEN');
        }

        $latestVersion = $this->versionModel->getLatestVersion($submissionId);
        if (!$latestVersion) {
            throw new Exception('No active version found for submission.');
        }

        $versionId = (int)$latestVersion['id'];

        // Validation
        $unitCode   = trim((string)($data['unit_code'] ?? ''));
        $unitName   = trim((string)($data['unit_name'] ?? ''));
        $unitLevel  = (int)($data['unit_level'] ?? 1);
        $orderIndex = (int)($data['order_index'] ?? 0);
        $changeType = strtoupper(trim((string)($data['change_type'] ?? 'NEW')));
        $tempParentId = !empty($data['temp_parent_id']) ? (int)$data['temp_parent_id'] : null;
        $sourceUnitId = !empty($data['source_unit_id']) ? (int)$data['source_unit_id'] : null;

        if (empty($unitCode) || empty($unitName)) {
            throw new Exception('Unit code and unit name are required.');
        }

        if (!in_array($changeType, ['NEW', 'UPDATE', 'DELETE', 'UNCHANGED'], true)) {
            throw new Exception("Invalid change type '{$changeType}'. Allowed: NEW, UPDATE, DELETE, UNCHANGED.");
        }

        // Cross-Institution Validation on source_unit_id
        if ($sourceUnitId !== null) {
            $sourceUnit = $this->masterUnitModel->find($sourceUnitId);
            if (!$sourceUnit || (int)$sourceUnit->institution_id !== $institutionId) {
                throw new Exception('Source unit does not exist or belongs to another institution.');
            }
        }

        // Parent validation within the same submission version
        if ($tempParentId !== null) {
            $parentUnit = $this->unitModel->find($tempParentId);
            if (!$parentUnit || (int)$parentUnit->version_id !== $versionId) {
                throw new Exception('Parent unit does not exist in the current submission version.');
            }
        }

        $this->unitModel->insert([
            'version_id'     => $versionId,
            'temp_parent_id' => $tempParentId,
            'source_unit_id' => $sourceUnitId,
            'unit_code'      => $unitCode,
            'unit_name'      => $unitName,
            'unit_level'     => $unitLevel,
            'order_index'    => $orderIndex,
            'change_type'    => $changeType,
        ]);

        $unitId = (int)$this->unitModel->getInsertID();

        // Audit Log
        $actorRole = $this->authzService->getUserRoleCode($user);
        $this->auditService->log([
            'actor_id'        => $user->id,
            'actor_role'      => $actorRole,
            'action_event'    => 'CREATE_SUBMISSION_UNIT',
            'resource_entity' => 'submission_units',
            'resource_id'     => $unitId,
            'payload_new'     => json_encode([
                'submission_id' => $submissionId,
                'version_id'    => $versionId,
                'unit_code'     => $unitCode,
                'unit_name'     => $unitName,
                'change_type'   => $changeType,
            ]),
        ]);

        return [
            'id'           => $unitId,
            'versionId'    => $versionId,
            'tempParentId' => $tempParentId,
            'sourceUnitId' => $sourceUnitId,
            'unitCode'     => $unitCode,
            'unitName'     => $unitName,
            'unitLevel'    => $unitLevel,
            'orderIndex'   => $orderIndex,
            'changeType'   => $changeType,
        ];
    }

    /**
     * Update a proposed organizational unit change in a DRAFT submission.
     *
     * @param UserEntity $user
     * @param int        $submissionId
     * @param int        $unitId
     * @param array      $data
     * @return array
     * @throws Exception
     */
    public function updateUnitChange(UserEntity $user, int $submissionId, int $unitId, array $data): array
    {
        $unit = $this->unitModel->getUnitWithSubmissionAndInstitution($unitId);
        if (!$unit || (int)$unit['submission_id'] !== $submissionId) {
            throw new Exception('NOT_FOUND');
        }

        // State Locking Check: Allowed in DRAFT and REVISION states
        if (!in_array($unit['current_state'], ['DRAFT', 'REVISION_REQUIRED', 'REVISION_REQUIRED_BY_VERIFIER', 'REVISION_BY_ADMIN', 'REVISION_BY_VERIFIER'], true)) {
            throw new Exception('LOCKED');
        }

        $institutionId = (int)$unit['institution_id'];
        $roleCode = $this->authzService->getUserRoleCode($user);

        // SoD / Ownership: Only author or SuperAdmin can edit
        $submission = $this->submissionModel->find($submissionId);
        if ($submission && (int)$user->id !== (int)$submission->author_id && $roleCode !== 'SUPER_ADMIN') {
            throw new Exception('FORBIDDEN');
        }

        if (!$this->authzService->can($user, 'EDIT', $institutionId, $unit['current_state'])) {
            throw new Exception('FORBIDDEN');
        }

        $updateData = [];
        if (isset($data['unit_code'])) {
            $updateData['unit_code'] = trim((string)$data['unit_code']);
        }
        if (isset($data['unit_name'])) {
            $updateData['unit_name'] = trim((string)$data['unit_name']);
        }
        if (isset($data['unit_level'])) {
            $updateData['unit_level'] = (int)$data['unit_level'];
        }
        if (isset($data['order_index'])) {
            $updateData['order_index'] = (int)$data['order_index'];
        }
        if (isset($data['change_type'])) {
            $changeType = strtoupper(trim((string)$data['change_type']));
            if (!in_array($changeType, ['NEW', 'UPDATE', 'DELETE', 'UNCHANGED'], true)) {
                throw new Exception("Invalid change type '{$changeType}'.");
            }
            $updateData['change_type'] = $changeType;
        }
        if (array_key_exists('temp_parent_id', $data)) {
            $updateData['temp_parent_id'] = !empty($data['temp_parent_id']) ? (int)$data['temp_parent_id'] : null;
        }

        if (empty($updateData)) {
            throw new Exception('No update parameters provided.');
        }

        $this->unitModel->update($unitId, $updateData);

        // Audit Log
        $actorRole = $this->authzService->getUserRoleCode($user);
        $this->auditService->log([
            'actor_id'        => $user->id,
            'actor_role'      => $actorRole,
            'action_event'    => 'UPDATE_SUBMISSION_UNIT',
            'resource_entity' => 'submission_units',
            'resource_id'     => $unitId,
            'payload_new'     => json_encode($updateData),
        ]);

        return array_merge($unit, $updateData);
    }

    /**
     * Delete a proposed organizational unit change from a DRAFT submission.
     *
     * @param UserEntity $user
     * @param int        $submissionId
     * @param int        $unitId
     * @return bool
     * @throws Exception
     */
    public function deleteUnitChange(UserEntity $user, int $submissionId, int $unitId): bool
    {
        $unit = $this->unitModel->getUnitWithSubmissionAndInstitution($unitId);
        if (!$unit || (int)$unit['submission_id'] !== $submissionId) {
            throw new Exception('NOT_FOUND');
        }

        // State Locking Check: Allowed in DRAFT and REVISION states
        if (!in_array($unit['current_state'], ['DRAFT', 'REVISION_REQUIRED', 'REVISION_REQUIRED_BY_VERIFIER', 'REVISION_BY_ADMIN', 'REVISION_BY_VERIFIER'], true)) {
            throw new Exception('LOCKED');
        }

        $institutionId = (int)$unit['institution_id'];
        $roleCode = $this->authzService->getUserRoleCode($user);

        // SoD / Ownership: Only author or SuperAdmin can delete
        $submission = $this->submissionModel->find($submissionId);
        if ($submission && (int)$user->id !== (int)$submission->author_id && $roleCode !== 'SUPER_ADMIN') {
            throw new Exception('FORBIDDEN');
        }

        if (!$this->authzService->can($user, 'EDIT', $institutionId, $unit['current_state'])) {
            throw new Exception('FORBIDDEN');
        }

        $this->unitModel->delete($unitId);

        // Audit Log
        $actorRole = $this->authzService->getUserRoleCode($user);
        $this->auditService->log([
            'actor_id'        => $user->id,
            'actor_role'      => $actorRole,
            'action_event'    => 'DELETE_SUBMISSION_UNIT',
            'resource_entity' => 'submission_units',
            'resource_id'     => $unitId,
            'payload_old'     => json_encode($unit),
        ]);

        return true;
    }
}
