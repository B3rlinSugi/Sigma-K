<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\Scope\ScopeService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ScopeController
 *
 * REST API Controller for inspecting user institutional scope and authority.
 */
class ScopeController extends BaseApiController
{
    protected ScopeService $scopeService;

    public function __construct(?ScopeService $scopeService = null)
    {
        $this->scopeService = $scopeService ?? new ScopeService();
    }

    /**
     * GET /api/v1/me/scopes
     */
    public function myScopes(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $scopes = $this->scopeService->getEffectiveScopes($user);
        return $this->respondSuccess($scopes, 'User effective scopes retrieved successfully.');
    }
}
