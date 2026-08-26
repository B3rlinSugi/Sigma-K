<?php

namespace App\Services\Institution;

use App\Entities\UserEntity;
use App\Models\AccessGrantModel;
use App\Models\InstitutionModel;
use App\Models\UserScopeModel;
use App\Services\Authorization\AuthorizationService;
use Exception;

/**
 * InstitutionService
 *
 * Handles domain logic for institutions, scope-filtered listing, and detail retrieval.
 */
class InstitutionService
{
    protected InstitutionModel $institutionModel;
    protected UserScopeModel $scopeModel;
    protected AccessGrantModel $grantModel;
    protected AuthorizationService $authzService;

    public function __construct(
        ?InstitutionModel $institutionModel = null,
        ?UserScopeModel $scopeModel = null,
        ?AccessGrantModel $grantModel = null,
        ?AuthorizationService $authzService = null
    ) {
        $this->institutionModel = $institutionModel ?? new InstitutionModel();
        $this->scopeModel       = $scopeModel ?? new UserScopeModel();
        $this->grantModel       = $grantModel ?? new AccessGrantModel();
        $this->authzService     = $authzService ?? new AuthorizationService();
    }

    /**
     * List institutions accessible within the user's effective scope.
     *
     * @param UserEntity  $user
     * @param int         $page
     * @param int         $perPage
     * @param string|null $search
     * @return array
     */
    public function listInstitutions(UserEntity $user, int $page = 1, int $perPage = 20, ?string $search = null): array
    {
        $roleCode = $this->authzService->getUserRoleCode($user);
        $builder = $this->institutionModel->builder();

        // 1. Scope-based filtering
        if ($roleCode !== 'SUPER_ADMIN') {
            $accessibleIds = [(int)$user->home_institution_id];

            // Add assigned user scopes for ADMIN and VERIFIER
            if (in_array($roleCode, ['ADMIN', 'VERIFIER'], true)) {
                $scopedIds = $this->scopeModel->getActiveInstitutionIds((int)$user->id);
                $accessibleIds = array_merge($accessibleIds, $scopedIds);
            }

            // Add institutions where user has active access grants with VIEW permission
            $today = date('Y-m-d');
            $db = \Config\Database::connect();
            $grantRows = $db->table('access_grants ag')
                ->select('ag.target_institution_id')
                ->join('access_grant_permissions agp', 'ag.id = agp.grant_id')
                ->join('permissions p', 'agp.permission_id = p.id')
                ->where('ag.user_id', (int)$user->id)
                ->where('ag.status', 'ACTIVE')
                ->where('ag.start_date <=', $today)
                ->where('ag.end_date >=', $today)
                ->where('p.permission_code', 'VIEW')
                ->get()
                ->getResultArray();

            $grantInstitutionIds = array_map('intval', array_column($grantRows, 'target_institution_id'));
            $accessibleIds = array_values(array_unique(array_merge($accessibleIds, $grantInstitutionIds)));

            if (empty($accessibleIds)) {
                return [
                    'items' => [],
                    'meta'  => ['page' => $page, 'perPage' => $perPage, 'total' => 0, 'totalPages' => 0],
                ];
            }

            $builder->whereIn('id', $accessibleIds);
        }

        // 2. Search filtering
        if (!empty($search)) {
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('institution_code', $search)
                ->orLike('short_name', $search)
                ->groupEnd();
        }

        // 3. Count total matching rows
        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();

        // 4. Pagination
        $offset = ($page - 1) * $perPage;
        $items = $builder->orderBy('name', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;

        return [
            'items' => $items,
            'meta'  => [
                'page'       => $page,
                'perPage'    => $perPage,
                'total'      => $total,
                'totalPages' => $totalPages,
            ],
        ];
    }

    /**
     * Get specific institution details by ID after evaluating Zero-Trust authorization.
     *
     * @param UserEntity $user
     * @param int        $institutionId
     * @return array|null
     * @throws Exception If institution not found or access forbidden
     */
    public function getInstitutionById(UserEntity $user, int $institutionId): ?array
    {
        $institution = $this->institutionModel->find($institutionId);
        if (!$institution) {
            return null;
        }

        // Strict Zero-Trust Authorization Evaluation
        if (!$this->authzService->can($user, 'VIEW', $institutionId)) {
            throw new Exception('FORBIDDEN');
        }

        return $institution;
    }
}
