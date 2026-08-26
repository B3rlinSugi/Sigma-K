<?php

namespace App\Controllers\Api;

use App\Services\Access\AccessRequestService;
use App\Services\Auth\AuthContext;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * AccessRequestController
 *
 * REST API Controller for cross-institution access requests.
 */
class AccessRequestController extends BaseApiController
{
    protected AccessRequestService $requestService;

    public function __construct(?AccessRequestService $requestService = null)
    {
        $this->requestService = $requestService ?? new AccessRequestService();
    }

    /**
     * POST /api/v1/access-requests
     */
    public function create(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost() ?: [];
        if (empty($input)) {
            $input = [
                'target_institution_id' => $this->request->getVar('target_institution_id'),
                'requested_start_date'  => $this->request->getVar('requested_start_date'),
                'requested_end_date'    => $this->request->getVar('requested_end_date'),
                'reason'                => $this->request->getVar('reason'),
                'permissions'           => $this->request->getVar('permissions'),
            ];
        }

        $rules = [
            'target_institution_id' => 'required|is_natural_no_zero',
            'requested_start_date'  => 'required|valid_date[Y-m-d]',
            'requested_end_date'    => 'required|valid_date[Y-m-d]',
            'reason'                => 'required|min_length[5]|max_length[1000]',
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        if (empty($input['permissions']) || !is_array($input['permissions'])) {
            return $this->respondValidationError([
                'permissions' => 'At least one permission must be specified in permissions array.',
            ]);
        }

        try {
            $created = $this->requestService->createRequest($user, $input);
            return $this->respondCreated($created, 'Access request submitted successfully.');
        } catch (Throwable $e) {
            return $this->respondValidationError([
                'error' => $e->getMessage(),
            ], $e->getMessage());
        }
    }

    /**
     * GET /api/v1/access-requests
     */
    public function index(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $page    = (int)($this->request->getGet('page') ?? 1);
        $perPage = (int)($this->request->getGet('perPage') ?? 20);
        $status  = $this->request->getGet('status');

        $page    = $page > 0 ? $page : 1;
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

        $result = $this->requestService->listRequests($user, $page, $perPage, $status);
        return $this->respondSuccess(
            $result['items'],
            'Access requests retrieved successfully.',
            200,
            $result['meta']
        );
    }

    /**
     * GET /api/v1/access-requests/{id}
     */
    public function show($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $requestId = (int)$id;
        if ($requestId <= 0) {
            return $this->respondNotFound('Invalid request ID.');
        }

        try {
            $request = $this->requestService->getRequestById($user, $requestId);
            if (!$request) {
                return $this->respondNotFound('Access request not found.');
            }

            return $this->respondSuccess($request, 'Access request details retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to view this access request.');
            }
            return $this->respondServerError('An error occurred while retrieving access request.');
        }
    }

    /**
     * POST /api/v1/access-requests/{id}/approve
     */
    public function approve($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $requestId = (int)$id;
        if ($requestId <= 0) {
            return $this->respondNotFound('Invalid request ID.');
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost() ?: [];
        $grantedPermissions = isset($input['granted_permissions']) ? (array)$input['granted_permissions'] : null;
        $notes = isset($input['notes']) ? (string)$input['notes'] : null;

        try {
            $result = $this->requestService->approveRequest($user, $requestId, $grantedPermissions, $notes);
            return $this->respondSuccess($result, 'Access request approved and grant issued successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to approve this access request.');
            }
            return $this->respondConflict($e->getMessage());
        }
    }

    /**
     * POST /api/v1/access-requests/{id}/reject
     */
    public function reject($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $requestId = (int)$id;
        if ($requestId <= 0) {
            return $this->respondNotFound('Invalid request ID.');
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost() ?: [];
        $reason = trim((string)($input['reason'] ?? ''));

        if (empty($reason)) {
            return $this->respondValidationError([
                'reason' => 'Rejection reason is required.',
            ]);
        }

        try {
            $result = $this->requestService->rejectRequest($user, $requestId, $reason);
            return $this->respondSuccess($result, 'Access request rejected.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to reject this access request.');
            }
            return $this->respondConflict($e->getMessage());
        }
    }
}
