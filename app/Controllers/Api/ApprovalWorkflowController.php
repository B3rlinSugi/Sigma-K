<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\Workflow\FinalApprovalService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * ApprovalWorkflowController
 *
 * REST API Controller for Step 10: Final Approval Recording, Approval Inspection,
 * and Master Data Promotion Reconciliation.
 */
class ApprovalWorkflowController extends BaseApiController
{
    protected FinalApprovalService $approvalService;

    public function __construct(?FinalApprovalService $approvalService = null)
    {
        $this->approvalService = $approvalService ?? new FinalApprovalService();
    }

    /**
     * POST /api/v1/submissions/{id}/approve
     * Final business approval of a verified submission by the assigned Verifier.
     */
    public function approve($id = null): ResponseInterface
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

        try {
            $result = $this->approvalService->approveSubmission($user, $submissionId, $input);
            return $this->respondSuccess($result, 'Submission final approval recorded successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'ALREADY_APPROVED') {
                return $this->respondConflict('Submission is already approved or promoted.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Submission is not in READY_FOR_FINAL_DECISION state.');
            }
            if ($e->getMessage() === 'SUBSTANTIVE_VERIFICATION_REQUIRED') {
                return $this->respondValidationError([
                    'verification' => 'Submission must pass Gate 2 substantive verification before final approval.',
                ]);
            }
            if ($e->getMessage() === 'SOD_AUTHOR_CANNOT_APPROVE') {
                return $this->respondForbidden('Separation of Duties violation: Submission author cannot approve their own submission.');
            }
            if ($e->getMessage() === 'WRONG_VERIFIER') {
                return $this->respondForbidden('Access denied: You are not the assigned verifier for this submission.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to approve this submission.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/submissions/{id}/promote
     * Master Data Promotion Reconciliation: Promotes the approved snapshot into master tables.
     */
    public function promote($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $submissionId = (int)$id;
        if ($submissionId <= 0) {
            return $this->respondNotFound('Invalid submission ID.');
        }

        try {
            $result = $this->approvalService->promoteSubmission($user, $submissionId);
            return $this->respondSuccess($result, 'Approved submission promoted to master organizational data successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'ALREADY_PROMOTED') {
                return $this->respondConflict('Submission has already been promoted.');
            }
            if ($e->getMessage() === 'NOT_APPROVED') {
                return $this->respondConflict('Submission must be in APPROVED state before promotion.');
            }
            if ($e->getMessage() === 'APPROVAL_RECORD_MISSING') {
                return $this->respondValidationError([
                    'approval' => 'No valid approval record found for the approved submission version.',
                ]);
            }
            if ($e->getMessage() === 'SOD_AUTHOR_CANNOT_PROMOTE') {
                return $this->respondForbidden('Separation of Duties violation: Submission author cannot promote their own submission.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to promote this submission.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/submissions/{id}/approval
     * Get final approval record and promotion status for a submission.
     */
    public function status($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $submissionId = (int)$id;
        if ($submissionId <= 0) {
            return $this->respondNotFound('Invalid submission ID.');
        }

        try {
            $result = $this->approvalService->getApprovalStatus($user, $submissionId);
            return $this->respondSuccess($result, 'Approval and promotion status retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to view this submission.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }
}
