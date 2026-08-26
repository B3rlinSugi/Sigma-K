<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\OrgStructure\OrgHierarchyService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * OrganizationalUnitController
 *
 * REST API Controller for Institution Organizational Unit hierarchy and unit details.
 */
class OrganizationalUnitController extends BaseApiController
{
    protected OrgHierarchyService $hierarchyService;

    public function __construct(?OrgHierarchyService $hierarchyService = null)
    {
        $this->hierarchyService = $hierarchyService ?? new OrgHierarchyService();
    }

    /**
     * GET /api/v1/institutions/{id}/units
     */
    public function getInstitutionTree($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $institutionId = (int)$id;
        if ($institutionId <= 0) {
            return $this->respondNotFound('Invalid institution ID.');
        }

        try {
            $tree = $this->hierarchyService->getInstitutionHierarchy($user, $institutionId);
            return $this->respondSuccess($tree, 'Organizational structure retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Institution not found.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to view the organizational structure of this institution.');
            }
            return $this->respondServerError('An error occurred while retrieving organizational structure.');
        }
    }

    /**
     * GET /api/v1/units/{id}
     */
    public function show($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $unitId = (int)$id;
        if ($unitId <= 0) {
            return $this->respondNotFound('Invalid unit ID.');
        }

        try {
            $unit = $this->hierarchyService->getUnitDetail($user, $unitId);
            if (!$unit) {
                return $this->respondNotFound('Organizational unit not found.');
            }

            return $this->respondSuccess($unit, 'Organizational unit details retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to view this organizational unit.');
            }
            return $this->respondServerError('An error occurred while retrieving organizational unit.');
        }
    }
}
