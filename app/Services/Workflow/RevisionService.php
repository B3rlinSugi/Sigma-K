<?php

namespace App\Services\Workflow;

use App\Entities\UserEntity;
use App\Models\InstitutionModel;
use App\Models\PositionModel;
use App\Models\RevisionNoteModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionPositionModel;
use App\Models\SubmissionUnitModel;
use App\Models\SubmissionVersionModel;
use App\Models\UserModel;
use App\Models\VerificationRecordModel;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationService;
use App\Services\Submission\SubmissionVersionService;
use Exception;
use Throwable;

/**
 * RevisionService
 *
 * Implements USER-side Revision View, Revision Version Branching, and Resubmission Workflow (Step 8).
 */
class RevisionService
{
    protected SubmissionModel $submissionModel;
    protected SubmissionVersionModel $versionModel;
    protected SubmissionUnitModel $unitModel;
    protected SubmissionPositionModel $posModel;
    protected VerificationRecordModel $verifModel;
    protected RevisionNoteModel $revisionModel;
    protected UserModel $userModel;
    protected InstitutionModel $instModel;
    protected SubmissionVersionService $versionService;
    protected AuthorizationService $authzService;
    protected AuditService $auditService;

    public function __construct(
        ?SubmissionModel $submissionModel = null,
        ?SubmissionVersionModel $versionModel = null,
        ?SubmissionUnitModel $unitModel = null,
        ?SubmissionPositionModel $posModel = null,
        ?VerificationRecordModel $verifModel = null,
        ?RevisionNoteModel $revisionModel = null,
        ?UserModel $userModel = null,
        ?InstitutionModel $instModel = null,
        ?SubmissionVersionService $versionService = null,
        ?AuthorizationService $authzService = null,
        ?AuditService $auditService = null
    ) {
        $this->submissionModel = $submissionModel ?? new SubmissionModel();
        $this->versionModel    = $versionModel ?? new SubmissionVersionModel();
        $this->unitModel       = $unitModel ?? new SubmissionUnitModel();
        $this->posModel        = $posModel ?? new SubmissionPositionModel();
        $this->verifModel      = $verifModel ?? new VerificationRecordModel();
        $this->revisionModel   = $revisionModel ?? new RevisionNoteModel();
        $this->userModel       = $userModel ?? new UserModel();
        $this->instModel       = $instModel ?? new InstitutionModel();
        $this->versionService  = $versionService ?? new SubmissionVersionService();
        $this->authzService    = $authzService ?? new AuthorizationService();
        $this->auditService    = $auditService ?? new AuditService();
    }

    /**
     * Check if a submission state is a revision state.
     */
    public function isRevisionState(string $state): bool
    {
        return in_array(strtoupper($state), [
            'REVISION_REQUIRED',
            'REVISION_REQUIRED_BY_VERIFIER',
            'REVISION_BY_ADMIN',
            'REVISION_BY_VERIFIER',
        ], true);
    }

    /**
     * Inspect revision details, notes, affected items, and version history.
     *
     * @param UserEntity $user
     * @param int        $submissionId
     * @return array
     * @throws Exception
     */
    public function getRevisionView(UserEntity $user, int $submissionId): array
    {
        $submission = $this->submissionModel->find($submissionId);
        if (!$submission) {
            throw new Exception('NOT_FOUND');
        }

        $institutionId = (int)$submission->institution_id;
        $roleCode = $this->authzService->getUserRoleCode($user);

        // Access Control: Author or authorized administrative / review actor
        if ((int)$user->id !== (int)$submission->author_id && $roleCode === 'USER') {
            throw new Exception('FORBIDDEN');
        }

        if (!$this->authzService->can($user, 'VIEW', $institutionId, $submission->current_state)) {
            throw new Exception('FORBIDDEN');
        }

        // Fetch Revision Notes
        $notes = $this->revisionModel->getBySubmissionId($submissionId);

        // Fetch Versions History
        $versions = $this->versionModel->getVersionsBySubmissionId($submissionId);
        $latestVersion = $this->versionModel->getLatestVersion($submissionId);

        $latestUnits = [];
        $latestPositions = [];
        if ($latestVersion) {
            $latestUnits = $this->unitModel->getByVersionId((int)$latestVersion['id']);
            $latestPositions = $this->posModel->getByVersionId((int)$latestVersion['id']);
        }

        $isAuthor = ((int)$user->id === (int)$submission->author_id);
        $canRevise = $isAuthor && $this->isRevisionState($submission->current_state);

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
            'isRevisionState' => $this->isRevisionState($submission->current_state),
            'canRevise'       => $canRevise,
            'revisionNotes'   => $notes,
            'versions'        => $versions,
            'currentVersion'  => $latestVersion,
            'proposedUnits'   => $latestUnits,
            'proposedPositions' => $latestPositions,
            'availableActions'  => $canRevise ? [
                'create_revision_version',
                'edit_proposed_units',
                'edit_proposed_positions',
                'resubmit',
            ] : ['view'],
        ];
    }

    /**
     * Start a new revision version branched from the previous version snapshot.
     *
     * @param UserEntity  $user
     * @param int         $submissionId
     * @param string|null $notes
     * @return array
     * @throws Exception
     */
    public function startRevisionVersion(UserEntity $user, int $submissionId, ?string $notes = null): array
    {
        $submission = $this->submissionModel->find($submissionId);
        if (!$submission) {
            throw new Exception('NOT_FOUND');
        }

        if (!$this->isRevisionState($submission->current_state)) {
            throw new Exception('LOCKED');
        }

        $institutionId = (int)$submission->institution_id;
        $roleCode = $this->authzService->getUserRoleCode($user);

        // SoD / Ownership: Only author or SuperAdmin can create revision version
        if ((int)$user->id !== (int)$submission->author_id && $roleCode !== 'SUPER_ADMIN') {
            throw new Exception('FORBIDDEN');
        }

        if (!$this->authzService->can($user, 'EDIT', $institutionId, $submission->current_state)) {
            throw new Exception('FORBIDDEN');
        }

        $latestVersion = $this->versionModel->getLatestVersion($submissionId);
        if (!$latestVersion) {
            throw new Exception('No existing version found to branch from.');
        }

        // If the latest version is already an unsubmitted draft version (submitted_at === null and version_number > 1), return it
        if ($latestVersion['submitted_at'] === null && (int)$latestVersion['version_number'] > 1) {
            return [
                'submissionId'  => $submissionId,
                'versionId'     => (int)$latestVersion['id'],
                'versionNumber' => (int)$latestVersion['version_number'],
                'notes'         => $latestVersion['notes'],
                'isExisting'    => true,
                'createdAt'     => $latestVersion['created_at'],
            ];
        }

        // Create new immutable snapshot version
        $snapshot = $this->versionService->createSnapshot($user, $submissionId, $notes ?? 'Revision Version');

        // Audit Trail
        $this->auditService->log([
            'actor_id'        => $user->id,
            'actor_role'      => $roleCode,
            'action_event'    => 'REVISION_VERSION_CREATED',
            'resource_entity' => 'submission_versions',
            'resource_id'     => $snapshot['versionId'],
            'payload_new'     => json_encode([
                'submission_id'  => $submissionId,
                'version_number' => $snapshot['versionNumber'],
                'notes'          => $notes,
            ]),
        ]);

        return array_merge($snapshot, ['isExisting' => false]);
    }

    /**
     * Resubmit a corrected revision version.
     *
     * @param UserEntity  $user
     * @param int         $submissionId
     * @param string|null $notes
     * @return array
     * @throws Exception
     */
    public function resubmit(UserEntity $user, int $submissionId, ?string $notes = null): array
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Lock row for concurrency protection
            $submission = $db->table('submissions')->where('id', $submissionId)->get()->getRowArray();
            if (!$submission) {
                throw new Exception('NOT_FOUND');
            }

            if (!$this->isRevisionState($submission['current_state'])) {
                throw new Exception('LOCKED');
            }

            $institutionId = (int)$submission['institution_id'];
            $roleCode = $this->authzService->getUserRoleCode($user);

            // SoD / Ownership: Only author or SuperAdmin can resubmit
            if ((int)$user->id !== (int)$submission['author_id'] && $roleCode !== 'SUPER_ADMIN') {
                throw new Exception('FORBIDDEN');
            }

            if (!$this->authzService->can($user, 'SUBMIT', $institutionId, $submission['current_state'])) {
                throw new Exception('FORBIDDEN');
            }

            $latestVersion = $this->versionModel->getLatestVersion($submissionId);
            if (!$latestVersion) {
                throw new Exception('No version found for submission.');
            }

            // Check if latest version has already been submitted
            if ($latestVersion['submitted_at'] !== null) {
                throw new Exception('NO_NEW_REVISION_VERSION');
            }

            $versionId = (int)$latestVersion['id'];

            // 1. Finalize and lock new revision version
            $this->versionModel->update($versionId, [
                'submitted_at' => date('Y-m-d H:i:s'),
                'notes'        => $notes ?? $latestVersion['notes'],
            ]);

            // 2. Transition Submission State
            $nextState = 'RESUBMITTED';
            $this->submissionModel->update($submissionId, [
                'current_state' => $nextState,
            ]);

            // 3. Emit Audit Log
            $this->auditService->log([
                'actor_id'        => $user->id,
                'actor_role'      => $roleCode,
                'action_event'    => 'REVISION_SUBMITTED',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode([
                    'from_state'     => $submission['current_state'],
                    'to_state'       => $nextState,
                    'version_id'     => $versionId,
                    'version_number' => (int)$latestVersion['version_number'],
                    'notes'          => $notes,
                ]),
            ]);

            $db->transCommit();

            return [
                'id'            => $submissionId,
                'currentState'  => $nextState,
                'versionId'     => $versionId,
                'versionNumber' => (int)$latestVersion['version_number'],
                'resubmittedAt' => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
