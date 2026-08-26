<?php

namespace App\Controllers\Api;

use App\Services\Access\AccessGrantService;
use App\Services\Auth\AuthContext;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * AccessGrantController
 *
 * REST API Controller for inspecting and revoking active access grants.
 */
class AccessGrantController extends BaseApiController
{
    protected AccessGrantService $grantService;

    public function __construct(?AccessGrantService $grantService = null)
    {
        $this->grantService = $grantService ?? new AccessGrantService();
    }

    /**
     * GET /api/v1/me/access-grants
     */
    public function myGrants(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $grants = $this->grantService->listMyGrants($user);
        return $this->respondSuccess($grants, 'User access grants retrieved successfully.');
    }

    /**
     * POST /api/v1/access-grants/{id}/revoke
     */
    public function revoke($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $grantId = (int)$id;
        if ($grantId <= 0) {
            return $this->respondNotFound('Invalid grant ID.');
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost() ?: [];
        $revokeReason = trim((string)($input['revoke_reason'] ?? ''));

        if (empty($revokeReason)) {
            return $this->respondValidationError([
                'revoke_reason' => 'Revocation reason is required.',
            ]);
        }

        try {
            $result = $this->grantService->revokeGrant($user, $grantId, $revokeReason);
            return $this->respondSuccess($result, 'Access grant revoked successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to revoke this access grant.');
            }
            return $this->respondConflict($e->getMessage());
        }
    }
}
