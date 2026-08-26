<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\Workflow\AdminReviewService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * AdminWorkflowController
 *
 * REST API Controller for Gate 1 Admin Review Queue, Acceptance, Return for Revision, and Verifier Assignment.
 */
class AdminWorkflowController extends BaseApiController
{
    protected AdminReviewService $adminService;

    public function __construct(?AdminReviewService $adminService = null)
    {
        $this->adminService = $adminService ?? new AdminReviewService();
    }

    /**
     * GET /api/v1/admin/submissions/queue
     */
    public function queue(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $page    = (int)($this->request->getGet('page') ?? 1);
        $perPage = (int)($this->request->getGet('perPage') ?? 20);

        $page    = $page > 0 ? $page : 1;
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

        try {
            $result = $this->adminService->getAdminQueue($user, $page, $perPage);
            return $this->respondSuccess(
                $result['items'],
                'Admin review queue retrieved successfully.',
                200,
                $result['meta']
            );
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to view the Admin review queue.');
            }
            return $this->respondServerError('An error occurred while retrieving Admin queue.');
        }
    }

    /**
     * POST /api/v1/submissions/{id}/admin-review/accept
     */
    public function accept($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $submissionId = (int)$id;
        if ($submissionId <= 0) {
            return $this->respondNotFound('Invalid submission ID.');
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost() ?: [];
        $notes = !empty($input['notes']) ? trim((string)$input['notes']) : null;

        try {
            $result = $this->adminService->acceptReview($user, $submissionId, $notes);
            return $this->respondSuccess($result, 'Submission accepted for Admin review successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Submission is not in SUBMITTED_TO_ADMIN state or already accepted.');
            }
            if ($e->getMessage() === 'SOD_AUTHOR_CANNOT_REVIEW') {
                return $this->respondForbidden('Separation of Duties violation: Submission author cannot review their own submission.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to review this submission.');
            }
            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/submissions/{id}/admin-review/return
     */
    public function returnRevision($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $submissionId = (int)$id;
        if ($submissionId <= 0) {
            return $this->respondNotFound('Invalid submission ID.');
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost() ?: [];
        if (empty($input)) {
            $input = [
                'reason'          => $this->request->getVar('reason'),
                'version_unit_id' => $this->request->getVar('version_unit_id'),
            ];
        }

        $rules = [
            'reason'          => 'required|min_length[5]',
            'version_unit_id' => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $reason = (string)$input['reason'];
        $versionUnitId = !empty($input['version_unit_id']) ? (int)$input['version_unit_id'] : null;

        try {
            $result = $this->adminService->returnForRevision($user, $submissionId, $reason, $versionUnitId);
            return $this->respondSuccess($result, 'Submission returned for revision successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Submission is not in a returnable state.');
            }
            if ($e->getMessage() === 'SOD_AUTHOR_CANNOT_REVIEW') {
                return $this->respondForbidden('Separation of Duties violation: Submission author cannot review their own submission.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to return this submission.');
            }
            return $this->respondValidationError(['error' => $e->getMessage()], $e->getMessage());
        }
    }

    /**
     * POST /api/v1/submissions/{id}/assign-verifier
     */
    public function assignVerifier($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $submissionId = (int)$id;
        if ($submissionId <= 0) {
            return $this->respondNotFound('Invalid submission ID.');
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost() ?: [];
        if (empty($input)) {
            $input = [
                'verifier_id' => $this->request->getVar('verifier_id'),
                'notes'       => $this->request->getVar('notes'),
            ];
        }

        $rules = [
            'verifier_id' => 'required|is_natural_no_zero',
            'notes'       => 'permit_empty',
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $verifierId = (int)$input['verifier_id'];
        $notes = !empty($input['notes']) ? trim((string)$input['notes']) : null;

        try {
            $result = $this->adminService->assignVerifier($user, $submissionId, $verifierId, $notes);
            return $this->respondSuccess($result, 'Verifier assigned successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Verifier can only be assigned when submission is in IN_REVIEW_BY_ADMIN state.');
            }
            if ($e->getMessage() === 'TARGET_NOT_VERIFIER') {
                return $this->respondValidationError(['verifier_id' => 'Target user does not have the VERIFIER role.'], 'Target user does not have the VERIFIER role.');
            }
            if ($e->getMessage() === 'SOD_AUTHOR_CANNOT_BE_VERIFIER') {
                return $this->respondForbidden('Separation of Duties violation: Submission author cannot be assigned as verifier.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to assign verifiers for this submission.');
            }
            return $this->respondValidationError(['error' => $e->getMessage()], $e->getMessage());
        }
    }
}
