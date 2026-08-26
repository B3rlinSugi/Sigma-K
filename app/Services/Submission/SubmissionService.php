<?php

namespace App\Services\Submission;

use App\Entities\UserEntity;
use App\Models\AccessGrantModel;
use App\Models\InstitutionModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionPositionModel;
use App\Models\SubmissionUnitModel;
use App\Models\SubmissionVersionModel;
use App\Models\UserScopeModel;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationService;
use Exception;
use Throwable;

/**
 * SubmissionService
 *
 * Handles creation, listing, detail inspection, and submission of E-SKLD drafts.
 */
class SubmissionService
{
    protected SubmissionModel $submissionModel;
    protected SubmissionVersionModel $versionModel;
    protected SubmissionUnitModel $unitModel;
    protected SubmissionPositionModel $posModel;
    protected InstitutionModel $institutionModel;
    protected UserScopeModel $scopeModel;
    protected AccessGrantModel $grantModel;
    protected AuthorizationService $authzService;
    protected AuditService $auditService;

    public function __construct(
        ?SubmissionModel $submissionModel = null,
        ?SubmissionVersionModel $versionModel = null,
        ?SubmissionUnitModel $unitModel = null,
        ?SubmissionPositionModel $posModel = null,
        ?InstitutionModel $institutionModel = null,
        ?UserScopeModel $scopeModel = null,
        ?AccessGrantModel $grantModel = null,
        ?AuthorizationService $authzService = null,
        ?AuditService $auditService = null
    ) {
        $this->submissionModel  = $submissionModel ?? new SubmissionModel();
        $this->versionModel     = $versionModel ?? new SubmissionVersionModel();
        $this->unitModel        = $unitModel ?? new SubmissionUnitModel();
        $this->posModel         = $posModel ?? new SubmissionPositionModel();
        $this->institutionModel = $institutionModel ?? new InstitutionModel();
        $this->scopeModel       = $scopeModel ?? new UserScopeModel();
        $this->grantModel       = $grantModel ?? new AccessGrantModel();
        $this->authzService     = $authzService ?? new AuthorizationService();
        $this->auditService     = $auditService ?? new AuditService();
    }

    /**
     * Create a new E-SKLD submission in DRAFT state with Version 1 initialized.
     *
     * @param UserEntity $author
     * @param array      $data
     * @return array
     * @throws Exception
     */
    public function createSubmission(UserEntity $author, array $data): array
    {
        $institutionId = (int)($data['institution_id'] ?? $author->home_institution_id);
        $title          = trim((string)($data['title'] ?? ''));
        $submissionYear = (int)($data['submission_year'] ?? date('Y'));

        // 1. Verify institution exists
        $institution = $this->institutionModel->find($institutionId);
        if (!$institution) {
            throw new Exception('Target institution not found.');
        }

        // 2. Zero-Trust Authorization: User must have CREATE permission on institution
        if (!$this->authzService->can($author, 'CREATE', $institutionId)) {
            throw new Exception('FORBIDDEN');
        }

        // 3. Validation
        if (empty($title)) {
            throw new Exception('Submission title is required.');
        }

        if ($submissionYear < 2000 || $submissionYear > 2100) {
            throw new Exception('Invalid submission year.');
        }

        // 4. Atomic Transaction: Insert Submission + Version 1
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $this->submissionModel->insert([
                'institution_id'  => $institutionId,
                'author_id'       => (int)$author->id,
                'title'           => $title,
                'submission_year' => $submissionYear,
                'current_state'   => 'DRAFT',
            ]);

            $submissionId = (int)$this->submissionModel->getInsertID();

            $this->versionModel->insert([
                'submission_id'  => $submissionId,
                'version_number' => 1,
                'notes'          => 'Initial Draft Version',
                'submitted_at'   => null,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);

            $versionId = (int)$this->versionModel->getInsertID();

            // Emit Audit Log
            $actorRole = $this->authzService->getUserRoleCode($author);
            $this->auditService->log([
                'actor_id'        => $author->id,
                'actor_role'      => $actorRole,
                'action_event'    => 'CREATE_SUBMISSION',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode([
                    'institution_id'  => $institutionId,
                    'title'           => $title,
                    'submission_year' => $submissionYear,
                    'current_state'   => 'DRAFT',
                    'version_id'      => $versionId,
                ]),
            ]);

            $db->transCommit();

            return [
                'id'              => $submissionId,
                'institutionId'   => $institutionId,
                'institutionCode' => $institution['institution_code'],
                'institutionName' => $institution['name'],
                'authorId'        => (int)$author->id,
                'authorUsername'  => $author->username,
                'title'           => $title,
                'submissionYear'  => $submissionYear,
                'currentState'    => 'DRAFT',
                'currentVersion'  => 1,
                'versionId'       => $versionId,
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * List submissions visible to the authenticated user within their authorized scope.
     *
     * @param UserEntity  $user
     * @param int         $page
     * @param int         $perPage
     * @param string|null $status
     * @return array
     */
    public function listSubmissions(UserEntity $user, int $page = 1, int $perPage = 20, ?string $status = null): array
    {
        $roleCode = $this->authzService->getUserRoleCode($user);
        $db = \Config\Database::connect();
        $builder = $db->table('submissions s')
            ->select('s.*, i.name as institution_name, i.institution_code, u.username as author_username, u.full_name as author_name')
            ->join('institutions i', 's.institution_id = i.id')
            ->join('users u', 's.author_id = u.id');

        // Scope Resolution filtering
        if ($roleCode !== 'SUPER_ADMIN') {
            $accessibleInstIds = [(int)$user->home_institution_id];

            if (in_array($roleCode, ['ADMIN', 'VERIFIER'], true)) {
                $scopedIds = $this->scopeModel->getActiveInstitutionIds((int)$user->id);
                $accessibleInstIds = array_merge($accessibleInstIds, $scopedIds);
            }

            // Grants
            $today = date('Y-m-d');
            $grantRows = $db->table('access_grants ag')
                ->select('ag.target_institution_id')
                ->where('ag.user_id', (int)$user->id)
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

        if (!empty($status)) {
            $builder->where('s.current_state', strtoupper($status));
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
     * Get submission detail including latest units and positions.
     *
     * @param UserEntity $user
     * @param int        $submissionId
     * @return array|null
     * @throws Exception
     */
    public function getSubmissionDetail(UserEntity $user, int $submissionId): ?array
    {
        $submission = $this->submissionModel->getSubmissionWithDetails($submissionId);
        if (!$submission) {
            return null;
        }

        $institutionId = (int)$submission['institution_id'];
        $currentState  = $submission['current_state'];

        // Zero-Trust Authorization
        if (!$this->authzService->can($user, 'VIEW', $institutionId, $currentState)) {
            throw new Exception('FORBIDDEN');
        }

        // Get latest version
        $latestVersion = $this->versionModel->getLatestVersion($submissionId);
        $units = [];
        $positions = [];

        if ($latestVersion) {
            $units = $this->unitModel->getByVersionId((int)$latestVersion['id']);
            $positions = $this->posModel->getByVersionId((int)$latestVersion['id']);
        }

        // Get all historical versions
        $versions = $this->versionModel->where('submission_id', $submissionId)
            ->orderBy('version_number', 'ASC')
            ->findAll();

        return [
            'id'              => (int)$submission['id'],
            'institutionId'   => $institutionId,
            'institutionCode' => $submission['institution_code'],
            'institutionName' => $submission['institution_name'],
            'authorId'        => (int)$submission['author_id'],
            'authorUsername'  => $submission['author_username'],
            'authorName'      => $submission['author_name'],
            'title'           => $submission['title'],
            'submissionYear'  => (int)$submission['submission_year'],
            'currentState'    => $currentState,
            'createdAt'       => $submission['created_at'],
            'updatedAt'       => $submission['updated_at'],
            'latestVersion'   => $latestVersion,
            'units'           => $units,
            'positions'       => $positions,
            'versions'        => $versions,
        ];
    }

    /**
     * Submit a DRAFT submission to the Admin review gate (transition to SUBMITTED_TO_ADMIN).
     *
     * @param UserEntity  $user
     * @param int         $submissionId
     * @param string|null $notes
     * @return array
     * @throws Exception
     */
    public function submitDraft(UserEntity $user, int $submissionId, ?string $notes = null): array
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Concurrency Protection: Lock row for update
            $builder = $db->table('submissions')->where('id', $submissionId);
            $submission = $builder->get()->getRowArray();

            if (!$submission) {
                throw new Exception('NOT_FOUND');
            }

            // Draft Lock Check
            if ($submission['current_state'] !== 'DRAFT') {
                throw new Exception('LOCKED');
            }

            $institutionId = (int)$submission['institution_id'];

            // Authorization: User must have SUBMIT permission on institution in DRAFT state
            if (!$this->authzService->can($user, 'SUBMIT', $institutionId, 'DRAFT')) {
                throw new Exception('FORBIDDEN');
            }

            $latestVersion = $this->versionModel->getLatestVersion($submissionId);
            if (!$latestVersion) {
                throw new Exception('No version found for submission.');
            }

            // Update Version timestamp
            $this->versionModel->update((int)$latestVersion['id'], [
                'submitted_at' => date('Y-m-d H:i:s'),
                'notes'        => $notes ?? $latestVersion['notes'],
            ]);

            // Transition State to SUBMITTED_TO_ADMIN
            $this->submissionModel->update($submissionId, [
                'current_state' => 'SUBMITTED_TO_ADMIN',
            ]);

            // Audit Trail
            $actorRole = $this->authzService->getUserRoleCode($user);
            $this->auditService->log([
                'actor_id'        => $user->id,
                'actor_role'      => $actorRole,
                'action_event'    => 'SUBMIT_SUBMISSION',
                'resource_entity' => 'submissions',
                'resource_id'     => $submissionId,
                'payload_new'     => json_encode([
                    'from_state' => 'DRAFT',
                    'to_state'   => 'SUBMITTED_TO_ADMIN',
                    'version_id' => (int)$latestVersion['id'],
                    'notes'      => $notes,
                ]),
            ]);

            $db->transCommit();

            return [
                'id'           => $submissionId,
                'currentState' => 'SUBMITTED_TO_ADMIN',
                'versionId'    => (int)$latestVersion['id'],
                'submittedAt'  => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
