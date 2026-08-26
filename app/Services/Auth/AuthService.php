<?php

namespace App\Services\Auth;

use App\Entities\UserEntity;
use App\Models\AccessGrantModel;
use App\Models\InstitutionModel;
use App\Models\RoleModel;
use App\Models\UserModel;
use App\Models\UserScopeModel;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationService;
use Exception;

/**
 * AuthService
 *
 * Handles User Authentication, Credential Verification, Token Lifecycle, and Profile Resolution.
 */
class AuthService
{
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected InstitutionModel $institutionModel;
    protected UserScopeModel $scopeModel;
    protected AccessGrantModel $grantModel;
    protected JwtService $jwtService;
    protected AuthorizationService $authzService;
    protected AuditService $auditService;

    public function __construct(
        ?UserModel $userModel = null,
        ?RoleModel $roleModel = null,
        ?InstitutionModel $institutionModel = null,
        ?UserScopeModel $scopeModel = null,
        ?AccessGrantModel $grantModel = null,
        ?JwtService $jwtService = null,
        ?AuthorizationService $authzService = null,
        ?AuditService $auditService = null
    ) {
        $this->userModel        = $userModel ?? new UserModel();
        $this->roleModel        = $roleModel ?? new RoleModel();
        $this->institutionModel = $institutionModel ?? new InstitutionModel();
        $this->scopeModel       = $scopeModel ?? new UserScopeModel();
        $this->grantModel       = $grantModel ?? new AccessGrantModel();
        $this->jwtService       = $jwtService ?? new JwtService();
        $this->authzService     = $authzService ?? new AuthorizationService();
        $this->auditService     = $auditService ?? new AuditService();
    }

    /**
     * Authenticate user credentials and issue access token.
     *
     * @param string      $username
     * @param string      $password
     * @param string|null $ip
     * @param string|null $userAgent
     * @return array
     * @throws Exception If authentication fails
     */
    public function authenticate(string $username, string $password, ?string $ip = null, ?string $userAgent = null): array
    {
        $user = $this->userModel->findByUsername($username);

        // Fail-safe: Uniform response timing against username enumeration
        if (!$user) {
            $this->auditService->log([
                'actor_id'     => null,
                'actor_role'   => null,
                'action_event' => 'AUTH_LOGIN_FAILED',
                'reason'       => 'User not found: ' . $username,
                'ip_address'   => $ip,
                'user_agent'   => $userAgent,
            ]);
            throw new Exception('Invalid credentials or inactive account.');
        }

        if (!$user->isActive()) {
            $this->auditService->log([
                'actor_id'     => $user->id,
                'actor_role'   => null,
                'action_event' => 'AUTH_LOGIN_FAILED',
                'reason'       => 'Account inactive or suspended: ' . $user->status,
                'ip_address'   => $ip,
                'user_agent'   => $userAgent,
            ]);
            throw new Exception('Invalid credentials or inactive account.');
        }

        if (!password_verify($password, $user->password_hash)) {
            $this->auditService->log([
                'actor_id'     => $user->id,
                'actor_role'   => null,
                'action_event' => 'AUTH_LOGIN_FAILED',
                'reason'       => 'Password verification failed',
                'ip_address'   => $ip,
                'user_agent'   => $userAgent,
            ]);
            throw new Exception('Invalid credentials or inactive account.');
        }

        $roleCode = $this->authzService->getUserRoleCode($user);
        $token = $this->jwtService->generateAccessToken($user, $roleCode);

        $institution = $this->institutionModel->find((int)$user->home_institution_id);

        $this->auditService->log([
            'actor_id'        => $user->id,
            'actor_role'      => $roleCode,
            'action_event'    => 'AUTH_LOGIN_SUCCESS',
            'resource_entity' => 'users',
            'resource_id'     => $user->id,
            'ip_address'      => $ip,
            'user_agent'      => $userAgent,
        ]);

        return [
            'accessToken' => $token,
            'tokenType'   => 'Bearer',
            'expiresIn'   => $this->jwtService->getExpirySeconds(),
            'user'        => [
                'id'              => (int)$user->id,
                'username'        => $user->username,
                'email'           => $user->email,
                'fullName'        => $user->full_name,
                'nip'             => $user->nip,
                'role'            => $roleCode,
                'homeInstitution' => $institution ? [
                    'id'   => (int)$institution['id'],
                    'code' => $institution['institution_code'],
                    'name' => $institution['name'],
                ] : null,
            ],
        ];
    }

    /**
     * Get detailed current user profile with active permissions and scopes.
     *
     * @param int $userId
     * @return array
     * @throws Exception If user not found or inactive
     */
    public function getCurrentUserProfile(int $userId): array
    {
        $user = $this->userModel->find($userId);
        if (!$user || !$user->isActive()) {
            throw new Exception('User not found or inactive.');
        }

        $roleCode = $this->authzService->getUserRoleCode($user);
        $permissions = $this->authzService->getUserPermissions($user);
        $institution = $this->institutionModel->find((int)$user->home_institution_id);
        $activeScopes = $this->scopeModel->getActiveInstitutionIds($userId);
        $activeGrants = $this->grantModel->getActiveGrantsForUser($userId);

        return [
            'id'              => (int)$user->id,
            'username'        => $user->username,
            'email'           => $user->email,
            'fullName'        => $user->full_name,
            'nip'             => $user->nip,
            'role'            => $roleCode,
            'homeInstitution' => $institution ? [
                'id'   => (int)$institution['id'],
                'code' => $institution['institution_code'],
                'name' => $institution['name'],
            ] : null,
            'permissions'     => $permissions,
            'activeScopes'    => $activeScopes,
            'activeGrants'    => $activeGrants,
        ];
    }

    /**
     * Invalidate session / record logout audit.
     */
    public function logout(?UserEntity $user = null): void
    {
        if ($user) {
            $roleCode = $this->authzService->getUserRoleCode($user);
            $this->auditService->log([
                'actor_id'        => $user->id,
                'actor_role'      => $roleCode,
                'action_event'    => 'AUTH_LOGOUT',
                'resource_entity' => 'users',
                'resource_id'     => $user->id,
            ]);
        }
    }
}
