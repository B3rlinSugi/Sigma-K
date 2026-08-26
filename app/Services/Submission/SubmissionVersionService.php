<?php

namespace App\Services\Submission;

use App\Entities\UserEntity;
use App\Models\SubmissionModel;
use App\Models\SubmissionPositionModel;
use App\Models\SubmissionUnitModel;
use App\Models\SubmissionVersionModel;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationService;
use Exception;
use Throwable;

/**
 * SubmissionVersionService
 *
 * Handles creation and deep-copying of immutable submission version snapshots.
 */
class SubmissionVersionService
{
    protected SubmissionModel $submissionModel;
    protected SubmissionVersionModel $versionModel;
    protected SubmissionUnitModel $unitModel;
    protected SubmissionPositionModel $posModel;
    protected AuthorizationService $authzService;
    protected AuditService $auditService;

    public function __construct(
        ?SubmissionModel $submissionModel = null,
        ?SubmissionVersionModel $versionModel = null,
        ?SubmissionUnitModel $unitModel = null,
        ?SubmissionPositionModel $posModel = null,
        ?AuthorizationService $authzService = null,
        ?AuditService $auditService = null
    ) {
        $this->submissionModel = $submissionModel ?? new SubmissionModel();
        $this->versionModel    = $versionModel ?? new SubmissionVersionModel();
        $this->unitModel       = $unitModel ?? new SubmissionUnitModel();
        $this->posModel        = $posModel ?? new SubmissionPositionModel();
        $this->authzService    = $authzService ?? new AuthorizationService();
        $this->auditService    = $auditService ?? new AuditService();
    }

    /**
     * Create an immutable deep-copy version snapshot of a DRAFT submission.
     *
     * @param UserEntity  $user
     * @param int         $submissionId
     * @param string|null $notes
     * @return array
     * @throws Exception
     */
    public function createSnapshot(UserEntity $user, int $submissionId, ?string $notes = null): array
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

        // SoD / Ownership: Only author or SuperAdmin can snapshot
        if ((int)$user->id !== (int)$submission->author_id && $roleCode !== 'SUPER_ADMIN') {
            throw new Exception('FORBIDDEN');
        }

        if (!$this->authzService->can($user, 'EDIT', $institutionId, $submission->current_state)) {
            throw new Exception('FORBIDDEN');
        }

        $latestVersion = $this->versionModel->getLatestVersion($submissionId);
        if (!$latestVersion) {
            throw new Exception('No existing version found to snapshot.');
        }

        $oldVersionId = (int)$latestVersion['id'];
        $nextVersionNum = $this->versionModel->getNextVersionNumber($submissionId);

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // 1. Insert new Version header
            $this->versionModel->insert([
                'submission_id'  => $submissionId,
                'version_number' => $nextVersionNum,
                'notes'          => $notes ?? ("Snapshot Version {$nextVersionNum}"),
                'submitted_at'   => null,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);

            $newVersionId = (int)$this->versionModel->getInsertID();

            // 2. Fetch existing units from old version
            $oldUnits = $this->unitModel->getByVersionId($oldVersionId);
            $unitMap = []; // old_id => new_id

            // Pass 1: Insert units with temp_parent_id = null
            foreach ($oldUnits as $u) {
                $uArray = is_array($u) ? $u : $u->toArray();
                $oldId = (int)$uArray['id'];

                $this->unitModel->insert([
                    'version_id'     => $newVersionId,
                    'temp_parent_id' => null,
                    'source_unit_id' => $uArray['source_unit_id'] ? (int)$uArray['source_unit_id'] : null,
                    'unit_code'      => $uArray['unit_code'],
                    'unit_name'      => $uArray['unit_name'],
                    'unit_level'     => (int)$uArray['unit_level'],
                    'order_index'    => (int)$uArray['order_index'],
                    'change_type'    => $uArray['change_type'],
                ]);

                $newUnitId = (int)$this->unitModel->getInsertID();
                $unitMap[$oldId] = $newUnitId;
            }

            // Pass 2: Re-link temp_parent_id using the unitMap
            foreach ($oldUnits as $u) {
                $uArray = is_array($u) ? $u : $u->toArray();
                $oldId = (int)$uArray['id'];
                $oldParentId = !empty($uArray['temp_parent_id']) ? (int)$uArray['temp_parent_id'] : null;

                if ($oldParentId !== null && isset($unitMap[$oldParentId])) {
                    $newUnitId = $unitMap[$oldId];
                    $newParentId = $unitMap[$oldParentId];

                    $this->unitModel->update($newUnitId, [
                        'temp_parent_id' => $newParentId,
                    ]);
                }
            }

            // 3. Clone existing positions
            $oldPositions = $this->posModel->getByVersionId($oldVersionId);
            foreach ($oldPositions as $p) {
                $oldUnitId = (int)$p['version_unit_id'];
                if (isset($unitMap[$oldUnitId])) {
                    $newUnitId = $unitMap[$oldUnitId];
                    $this->posModel->insert([
                        'version_unit_id'    => $newUnitId,
                        'source_position_id' => !empty($p['source_position_id']) ? (int)$p['source_position_id'] : null,
                        'position_name'      => $p['position_name'],
                        'position_type'      => $p['position_type'],
                        'echelon'            => $p['echelon'],
                        'formation_count'    => (int)$p['formation_count'],
                        'change_type'        => $p['change_type'],
                    ]);
                }
            }

            // 4. Emit Audit Log
            $actorRole = $this->authzService->getUserRoleCode($user);
            $this->auditService->log([
                'actor_id'        => $user->id,
                'actor_role'      => $actorRole,
                'action_event'    => 'CREATE_SUBMISSION_VERSION',
                'resource_entity' => 'submission_versions',
                'resource_id'     => $newVersionId,
                'payload_new'     => json_encode([
                    'submission_id'  => $submissionId,
                    'version_number' => $nextVersionNum,
                    'cloned_units'   => count($oldUnits),
                    'cloned_pos'     => count($oldPositions),
                ]),
            ]);

            $db->transCommit();

            return [
                'submissionId'  => $submissionId,
                'versionId'     => $newVersionId,
                'versionNumber' => $nextVersionNum,
                'notes'         => $notes,
                'createdAt'     => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
