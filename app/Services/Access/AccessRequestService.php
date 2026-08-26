<?php

namespace App\Services\Access;

use App\Entities\UserEntity;
use App\Models\AccessGrantModel;
use App\Models\AccessGrantPermissionModel;
use App\Models\AccessRequestModel;
use App\Models\AccessRequestPermissionModel;
use App\Models\InstitutionModel;
use App\Models\PermissionModel;
use App\Models\UserScopeModel;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationService;
use Exception;
use Throwable;

/**
 * AccessRequestService
 *
 * Handles creation, review, approval, and rejection of cross-institution access requests.
 */
class AccessRequestService
{
    protected AccessRequestModel $requestModel;
    protected AccessRequestPermissionModel $reqPermModel;
    protected AccessGrantModel $grantModel;
    protected AccessGrantPermissionModel $grantPermModel;
    protected InstitutionModel $institutionModel;
    protected PermissionModel $permissionModel;
    protected UserScopeModel $scopeModel;
    protected ApproverResolutionService $approverService;
    protected AuthorizationService $authzService;
    protected AuditService $auditService;

    public function __construct(
        ?AccessRequestModel $requestModel = null,
        ?AccessRequestPermissionModel $reqPermModel = null,
        ?AccessGrantModel $grantModel = null,
        ?AccessGrantPermissionModel $grantPermModel = null,
        ?InstitutionModel $institutionModel = null,
        ?PermissionModel $permissionModel = null,
        ?UserScopeModel $scopeModel = null,
        ?ApproverResolutionService $approverService = null,
        ?AuthorizationService $authzService = null,
        ?AuditService $auditService = null
    ) {
        $this->requestModel     = $requestModel ?? new AccessRequestModel();
        $this->reqPermModel     = $reqPermModel ?? new AccessRequestPermissionModel();
        $this->grantModel       = $grantModel ?? new AccessGrantModel();
        $this->grantPermModel   = $grantPermModel ?? new AccessGrantPermissionModel();
        $this->institutionModel = $institutionModel ?? new InstitutionModel();
        $this->permissionModel  = $permissionModel ?? new PermissionModel();
        $this->scopeModel       = $scopeModel ?? new UserScopeModel();
        $this->approverService  = $approverService ?? new ApproverResolutionService();
        $this->authzService     = $authzService ?? new AuthorizationService();
        $this->auditService     = $auditService ?? new AuditService();
    }

    /**
     * Create a new cross-institution access request.
     *
     * @param UserEntity $user
     * @param array      $data
     * @return array
     * @throws Exception
     */
    public function createRequest(UserEntity $user, array $data): array
    {
        $targetInstId = (int)($data['target_institution_id'] ?? 0);
        $startDate    = (string)($data['requested_start_date'] ?? '');
        $endDate      = (string)($data['requested_end_date'] ?? '');
        $reason       = trim((string)($data['reason'] ?? ''));
        $permissions  = (array)($data['permissions'] ?? []);

        // 1. Validation: Target institution exists
        $targetInst = $this->institutionModel->find($targetInstId);
        if (!$targetInst) {
            throw new Exception('Target institution does not exist.');
        }

        // 2. Validation: Cannot request access to own home institution
        if ($targetInstId === (int)$user->home_institution_id) {
            throw new Exception('Cannot request cross-institution access for your own home institution.');
        }

        // 3. Validation: Date range
        if (strtotime($startDate) > strtotime($endDate)) {
            throw new Exception('Requested start date cannot be after end date.');
        }

        // 4. Validation: Permissions list
        if (empty($permissions)) {
            throw new Exception('At least one permission must be requested.');
        }

        $validPerms = $this->permissionModel->findAll();
        $validMap = [];
        foreach ($validPerms as $vp) {
            $validMap[$vp['permission_code']] = (int)$vp['id'];
        }

        $permIdsToInsert = [];
        foreach ($permissions as $pCode) {
            $pCodeUpper = strtoupper(trim($pCode));

            // Hard SoD Gate: Never allow requesting VERIFY or APPROVE via Access Grant
            if (in_array($pCodeUpper, ['VERIFY', 'APPROVE'], true)) {
                throw new Exception("Permission '{$pCodeUpper}' cannot be delegated via Access Grant.");
            }

            if (!isset($validMap[$pCodeUpper])) {
                throw new Exception("Invalid permission code: '{$pCode}'.");
            }

            $permIdsToInsert[] = $validMap[$pCodeUpper];
        }

        // 5. Database Insert in Transaction
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $this->requestModel->insert([
                'user_id'               => (int)$user->id,
                'target_institution_id' => $targetInstId,
                'reason'                => $reason,
                'requested_start_date'  => $startDate,
                'requested_end_date'    => $endDate,
                'status'                => 'PENDING',
            ]);

            $requestId = (int)$this->requestModel->getInsertID();

            foreach ($permIdsToInsert as $pId) {
                $this->reqPermModel->insert([
                    'request_id'    => $requestId,
                    'permission_id' => $pId,
                ]);
            }

            $this->auditService->log([
                'actor_id'        => $user->id,
                'actor_role'      => $this->authzService->getUserRoleCode($user),
                'action_event'    => 'REQUEST_ACCESS',
                'resource_entity' => 'access_requests',
                'resource_id'     => $requestId,
                'payload_new'     => json_encode([
                    'target_institution_id' => $targetInstId,
                    'permissions'           => $permissions,
                    'start_date'            => $startDate,
                    'end_date'              => $endDate,
                    'reason'                => $reason,
                ]),
            ]);

            $db->transCommit();
            return $this->requestModel->getRequestWithDetails($requestId);
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * List access requests based on user scope authority.
     */
    public function listRequests(UserEntity $user, int $page = 1, int $perPage = 20, ?string $status = null): array
    {
        $roleCode = $this->authzService->getUserRoleCode($user);
        $builder = $this->requestModel->builder();

        if ($roleCode === 'USER') {
            // USER sees own requests
            $builder->where('user_id', (int)$user->id);
        } elseif ($roleCode === 'ADMIN') {
            // ADMIN sees requests targeting institutions in their active user_scopes
            $scopedIds = $this->scopeModel->getActiveInstitutionIds((int)$user->id);
            if (empty($scopedIds)) {
                $builder->where('user_id', (int)$user->id); // Fallback to own requests if no scopes
            } else {
                $builder->groupStart()
                    ->whereIn('target_institution_id', $scopedIds)
                    ->orWhere('user_id', (int)$user->id)
                    ->groupEnd();
            }
        } elseif ($roleCode === 'VERIFIER') {
            // VERIFIER sees own requests
            $builder->where('user_id', (int)$user->id);
        }
        // SUPER_ADMIN sees all

        if (!empty($status)) {
            $builder->where('status', strtoupper($status));
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();

        $offset = ($page - 1) * $perPage;
        $items = $builder->orderBy('id', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        // Attach target institution and requester details
        foreach ($items as &$item) {
            $detailed = $this->requestModel->getRequestWithDetails((int)$item['id']);
            if ($detailed) {
                $item['target_institution_name'] = $detailed['target_institution_name'];
                $item['requester_username']      = $detailed['requester_username'];
                $item['requester_name']          = $detailed['requester_name'];
                $item['permission_codes']        = $detailed['permission_codes'];
            }
        }

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
     * Get access request details by ID with authorization verification.
     */
    public function getRequestById(UserEntity $user, int $requestId): ?array
    {
        $request = $this->requestModel->getRequestWithDetails($requestId);
        if (!$request) {
            return null;
        }

        // Verify if user is requester or has authority to review
        $isRequester = ((int)$user->id === (int)$request['user_id']);
        $canReview   = $this->approverService->canReviewRequest($user, $request);

        if (!$isRequester && !$canReview && !$this->authzService->hasRole($user, 'SUPER_ADMIN')) {
            throw new Exception('FORBIDDEN');
        }

        return $request;
    }

    /**
     * Approve an access request and issue active access grant.
     */
    public function approveRequest(
        UserEntity $actor,
        int $requestId,
        ?array $grantedPermissions = null,
        ?string $notes = null
    ): array {
        $request = $this->requestModel->getRequestWithDetails($requestId);
        if (!$request) {
            throw new Exception('Access request not found.');
        }

        if ($request['status'] !== 'PENDING') {
            throw new Exception("Access request cannot be approved because current status is '{$request['status']}'.");
        }

        // 1. Authorization: Verify actor has authority over target institution
        if (!$this->approverService->canReviewRequest($actor, $request)) {
            throw new Exception('FORBIDDEN');
        }

        // 2. Resolve permissions to grant
        $validPerms = $this->permissionModel->findAll();
        $validMap = [];
        foreach ($validPerms as $vp) {
            $validMap[$vp['permission_code']] = (int)$vp['id'];
        }

        $permCodesToGrant = $grantedPermissions ?? $request['permission_codes'];
        if (empty($permCodesToGrant)) {
            throw new Exception('At least one permission must be granted.');
        }

        $grantPermIds = [];
        foreach ($permCodesToGrant as $pCode) {
            $pCodeUpper = strtoupper(trim($pCode));
            if (in_array($pCodeUpper, ['VERIFY', 'APPROVE'], true)) {
                continue; // Guardrail: Skip illegal permissions
            }
            if (isset($validMap[$pCodeUpper])) {
                $grantPermIds[] = $validMap[$pCodeUpper];
            }
        }

        if (empty($grantPermIds)) {
            throw new Exception('No valid delegatable permissions provided for grant.');
        }

        // 3. Execute Atomic Database Transaction
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Update request status
            $this->requestModel->update($requestId, [
                'status'       => 'APPROVED',
                'reviewed_by'  => (int)$actor->id,
                'reviewed_at'  => date('Y-m-d H:i:s'),
                'review_notes' => $notes,
            ]);

            // Insert access grant
            $this->grantModel->insert([
                'request_id'            => $requestId,
                'user_id'               => (int)$request['user_id'],
                'target_institution_id' => (int)$request['target_institution_id'],
                'start_date'            => $request['requested_start_date'],
                'end_date'              => $request['requested_end_date'],
                'status'                => 'ACTIVE',
                'granted_by'            => (int)$actor->id,
                'grant_reason'          => $request['reason'],
            ]);

            $grantId = (int)$this->grantModel->getInsertID();

            // Insert grant permissions
            foreach ($grantPermIds as $pId) {
                $this->grantPermModel->insert([
                    'grant_id'      => $grantId,
                    'permission_id' => $pId,
                ]);
            }

            // Emit Audit Log
            $actorRole = $this->authzService->getUserRoleCode($actor);
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $actorRole,
                'action_event'    => 'APPROVE_ACCESS',
                'resource_entity' => 'access_requests',
                'resource_id'     => $requestId,
                'payload_new'     => json_encode(['status' => 'APPROVED', 'review_notes' => $notes]),
            ]);

            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $actorRole,
                'action_event'    => 'GRANT_ACCESS',
                'resource_entity' => 'access_grants',
                'resource_id'     => $grantId,
                'payload_new'     => json_encode([
                    'grant_id'              => $grantId,
                    'user_id'               => (int)$request['user_id'],
                    'target_institution_id' => (int)$request['target_institution_id'],
                    'granted_permissions'   => $permCodesToGrant,
                ]),
            ]);

            $db->transCommit();
            return [
                'requestId' => $requestId,
                'grantId'   => $grantId,
                'status'    => 'APPROVED',
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Reject an access request.
     */
    public function rejectRequest(UserEntity $actor, int $requestId, string $reason): array
    {
        $request = $this->requestModel->getRequestWithDetails($requestId);
        if (!$request) {
            throw new Exception('Access request not found.');
        }

        if ($request['status'] !== 'PENDING') {
            throw new Exception("Access request cannot be rejected because current status is '{$request['status']}'.");
        }

        if (!$this->approverService->canReviewRequest($actor, $request)) {
            throw new Exception('FORBIDDEN');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $this->requestModel->update($requestId, [
                'status'       => 'REJECTED',
                'reviewed_by'  => (int)$actor->id,
                'reviewed_at'  => date('Y-m-d H:i:s'),
                'review_notes' => $reason,
            ]);

            $actorRole = $this->authzService->getUserRoleCode($actor);
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $actorRole,
                'action_event'    => 'REJECT_ACCESS',
                'resource_entity' => 'access_requests',
                'resource_id'     => $requestId,
                'payload_new'     => json_encode(['status' => 'REJECTED', 'review_notes' => $reason]),
            ]);

            $db->transCommit();
            return [
                'requestId' => $requestId,
                'status'    => 'REJECTED',
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
