<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\Institution\InstitutionService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * InstitutionController
 *
 * REST API Controller for scope-aware Institution data management.
 */
class InstitutionController extends BaseApiController
{
    protected InstitutionService $institutionService;

    public function __construct(?InstitutionService $institutionService = null)
    {
        $this->institutionService = $institutionService ?? new InstitutionService();
    }

    /**
     * GET /api/v1/institutions
     */
    public function index(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $page    = (int)($this->request->getGet('page') ?? 1);
        $perPage = (int)($this->request->getGet('perPage') ?? 20);
        $search  = $this->request->getGet('search');

        $page    = $page > 0 ? $page : 1;
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

        $result = $this->institutionService->listInstitutions($user, $page, $perPage, $search);

        return $this->respondSuccess(
            $result['items'],
            'Institutions list retrieved successfully.',
            200,
            $result['meta']
        );
    }

    /**
     * GET /api/v1/institutions/{id}
     */
    public function show($id = null): ResponseInterface
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
            $institution = $this->institutionService->getInstitutionById($user, $institutionId);
            if (!$institution) {
                return $this->respondNotFound('Institution not found.');
            }

            return $this->respondSuccess($institution, 'Institution details retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to access data for this institution.');
            }
            return $this->respondServerError('An error occurred while retrieving institution.');
        }
    }
}
