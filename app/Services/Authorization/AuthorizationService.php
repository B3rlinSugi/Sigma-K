<?php

namespace App\Services\Authorization;

use App\Entities\UserEntity;
use App\Models\PermissionModel;
use App\Models\RoleModel;

/**
 * AuthorizationService
 *
 * Central Zero-Trust Policy Engine for E-SKLD.
 * Evaluates: Role + Permission + Scope + State Machine + SoD.
 */
class AuthorizationService
{
    protected RoleModel $roleModel;
    protected PermissionModel $permissionModel;
    protected ScopeResolver $scopeResolver;

    // Cache per request
    protected array $roleCache = [];
    protected array $permissionCache = [];

    public function __construct(
        ?RoleModel $roleModel = null,
        ?PermissionModel $permissionModel = null,
        ?ScopeResolver $scopeResolver = null
    ) {
        $this->roleModel       = $roleModel ?? new RoleModel();
        $this->permissionModel = $permissionModel ?? new PermissionModel();
        $this->scopeResolver   = $scopeResolver ?? new ScopeResolver();
    }

    /**
     * Get the role code for a given user.
     */
    public function getUserRoleCode(UserEntity $user): string
    {
        $roleId = (int)$user->role_id;
        if (!isset($this->roleCache[$roleId])) {
            $role = $this->roleModel->find($roleId);
            $this->roleCache[$roleId] = $role ? (string)$role['role_code'] : '';
        }
        return $this->roleCache[$roleId];
    }

    /**
     * Check if user possesses a specific role code.
     */
    public function hasRole(UserEntity $user, string $roleCode): bool
    {
        return $this->getUserRoleCode($user) === strtoupper($roleCode);
    }

    /**
     * Get all assigned atomic permission codes for a given user.
     */
    public function getUserPermissions(UserEntity $user): array
    {
        $roleId = (int)$user->role_id;
        if (!isset($this->permissionCache[$roleId])) {
            $this->permissionCache[$roleId] = $this->permissionModel->getPermissionsByRoleId($roleId);
        }
        return $this->permissionCache[$roleId];
    }

    /**
     * Check if user's role possesses a specific atomic permission code.
     */
    public function hasPermission(UserEntity $user, string $permissionCode): bool
    {
        $permissions = $this->getUserPermissions($user);
        return in_array(strtoupper($permissionCode), $permissions, true);
    }

    /**
     * Primary Zero-Trust authorization evaluation method.
     *
     * @param UserEntity  $user
     * @param string      $action Permission code (e.g. 'VIEW', 'EDIT', 'APPROVE')
     * @param int|null    $targetInstitutionId Target institution ID to check scope
     * @param string|null $currentState Current workflow state of resource (if applicable)
     * @param int|null    $resourceId Resource identifier (if applicable)
     * @return bool True if authorized, false otherwise
     */
    public function can(
        UserEntity $user,
        string $action,
        ?int $targetInstitutionId = null,
        ?string $currentState = null,
        ?int $resourceId = null
    ): bool {
        $action = strtoupper($action);
        $roleCode = $this->getUserRoleCode($user);

        // 1. Separation of Duties (SoD) Hard Gates:
        // ONLY VERIFIER may execute VERIFY and APPROVE
        if (in_array($action, ['VERIFY', 'APPROVE'], true) && $roleCode !== 'VERIFIER') {
            return false;
        }

        // 2. Base Permission Check
        // If user's role does not possess the permission, check if there is an active access grant
        $hasBasePermission = $this->hasPermission($user, $action);
        if (!$hasBasePermission) {
            // Check if user has an active Access Grant for this specific action on the target institution
            if ($targetInstitutionId === null || !$this->scopeResolver->resolveScope($user, $targetInstitutionId, $action, $roleCode)) {
                return false;
            }
        }

        // 3. Scope Resolution Check
        if ($targetInstitutionId !== null && !$this->scopeResolver->resolveScope($user, $targetInstitutionId, $action, $roleCode)) {
            return false;
        }

        // 4. State-Aware Dynamic Lock Check (If resource workflow state is provided)
        if ($currentState !== null && !$this->isActionAllowedInState($roleCode, $action, $currentState)) {
            return false;
        }

        return true;
    }

    /**
     * Evaluate State-Aware Dynamic Authorization Matrix.
     */
    protected function isActionAllowedInState(string $roleCode, string $action, string $state): bool
    {
        $state = strtoupper($state);

        switch ($state) {
            case 'DRAFT':
                if ($roleCode === 'USER') {
                    return in_array($action, ['VIEW', 'EDIT', 'DELETE_DRAFT', 'SUBMIT', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                return in_array($action, ['VIEW', 'VIEW_HISTORY', 'EXPORT'], true);

            case 'SUBMITTED_TO_ADMIN':
            case 'ADMIN_REVIEW':
            case 'IN_REVIEW_BY_ADMIN':
            case 'RESUBMITTED':
                if ($roleCode === 'USER') {
                    // Read-only lock for User while under Admin/Verifier review
                    return in_array($action, ['VIEW', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                if (in_array($roleCode, ['ADMIN', 'SUPER_ADMIN'], true)) {
                    return in_array($action, ['VIEW', 'REVIEW', 'RETURN_REVISION', 'FORWARD_TO_VERIFIER', 'ASSIGN_VERIFIER', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                if ($roleCode === 'VERIFIER') {
                    return in_array($action, ['VIEW', 'REVIEW', 'RETURN_REVISION', 'VERIFY', 'APPROVE', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                return in_array($action, ['VIEW', 'VIEW_HISTORY', 'EXPORT'], true);

            case 'REVISION_BY_ADMIN':
            case 'REVISION_BY_VERIFIER':
            case 'REVISION_REQUIRED':
            case 'REVISION_REQUIRED_BY_VERIFIER':
                if ($roleCode === 'USER') {
                    return in_array($action, ['VIEW', 'EDIT', 'SUBMIT', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                if (in_array($roleCode, ['ADMIN', 'SUPER_ADMIN'], true)) {
                    return in_array($action, ['VIEW', 'FORWARD_TO_USER', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                return in_array($action, ['VIEW', 'VIEW_HISTORY', 'EXPORT'], true);

            case 'ADMIN_PASSED':
            case 'ASSIGNED_TO_VERIFIER':
            case 'IN_REVIEW_BY_VERIFIER':
            case 'VERIFIER_REVIEW':
                if ($roleCode === 'USER') {
                    return in_array($action, ['VIEW', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                if (in_array($roleCode, ['ADMIN', 'SUPER_ADMIN'], true)) {
                    return in_array($action, ['VIEW', 'ASSIGN_VERIFIER', 'REASSIGN_VERIFIER', 'FORWARD_TO_VERIFIER', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                if ($roleCode === 'VERIFIER') {
                    return in_array($action, ['VIEW', 'REVIEW', 'RETURN_REVISION', 'VERIFY', 'APPROVE', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                return in_array($action, ['VIEW', 'REASSIGN_VERIFIER', 'EXPORT'], true);

            case 'READY_FOR_FINAL_DECISION':
            case 'SUBSTANTIVE_PASSED':
            case 'VERIFIED_BY_VERIFIER':
                if ($roleCode === 'USER') {
                    return in_array($action, ['VIEW', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                if (in_array($roleCode, ['ADMIN', 'SUPER_ADMIN', 'VERIFIER'], true)) {
                    return in_array($action, ['VIEW', 'APPROVE', 'FINAL_APPROVE', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                return in_array($action, ['VIEW', 'VIEW_HISTORY', 'EXPORT'], true);

            case 'APPROVED':
                if ($roleCode === 'USER') {
                    return in_array($action, ['VIEW', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                if (in_array($roleCode, ['ADMIN', 'SUPER_ADMIN', 'VERIFIER'], true)) {
                    return in_array($action, ['VIEW', 'PROMOTE', 'VIEW_HISTORY', 'EXPORT'], true);
                }
                return in_array($action, ['VIEW', 'VIEW_HISTORY', 'EXPORT'], true);

            case 'PROMOTED':
                // Immutable read-only for all roles
                return in_array($action, ['VIEW', 'VIEW_HISTORY', 'EXPORT'], true);

            default:
                return false;
        }
    }
}
