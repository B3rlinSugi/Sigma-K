<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\Workflow\VerifierReviewService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * VerifierWorkflowController
 *
 * REST API Controller for Gate 2 Verifier Substantive Review, Revision Notes,
 * Substantive Verification Approval, and Technical Recommendation (Step 7 & Step 9).
 */
class VerifierWorkflowController extends BaseApiController
{
    protected VerifierReviewService $verifierService;

    public function __construct(?VerifierReviewService $verifierService = null)
    {
        $this->verifierService = $verifierService ?? new VerifierReviewService();
    }

    /**
     * GET /api/v1/verifier/submissions/assigned
     * Retrieve assigned submissions for the authenticated verifier.
     */
    public function assigned(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $page    = (int)($this->request->getGet('page') ?? 1);
        $perPage = (int)($this->request->getGet('perPage') ?? $this->request->getGet('per_page') ?? 20);

        $page    = $page > 0 ? $page : 1;
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

        try {
            $queue = $this->verifierService->getAssignedQueue($user, $page, $perPage);
            return $this->respondSuccess(
                $queue['items'],
                'Assigned submissions retrieved successfully.',
                200,
                $queue['meta']
            );
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('Access denied: Verifier role required.');
            }

            return $this->respondServerError('An error occurred while retrieving assigned submissions.');
        }
    }

    /**
     * GET /api/v1/verifier/submissions/{id}/review
     * Retrieve detailed substantive review inspection for the assigned Verifier.
     */
    public function reviewDetails($id = null): ResponseInterface
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
            $details = $this->verifierService->getReviewDetails($user, $submissionId);
            return $this->respondSuccess($details, 'Review details retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'SOD_AUTHOR_CANNOT_VERIFY') {
                return $this->respondForbidden('Separation of Duties violation: Submission author cannot verify their own submission.');
            }
            if ($e->getMessage() === 'WRONG_VERIFIER') {
                return $this->respondForbidden('Access denied: You are not the assigned verifier for this submission.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to review this submission.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/submissions/{id}/verifier-review/start
     * Verifier starts Gate 2 substantive review.
     */
    public function start($id = null): ResponseInterface
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
            $result = $this->verifierService->startReview($user, $submissionId, $notes);
            return $this->respondSuccess($result, 'Substantive review started successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Submission is not in an eligible state or review already started.');
            }
            if ($e->getMessage() === 'SOD_AUTHOR_CANNOT_VERIFY') {
                return $this->respondForbidden('Separation of Duties violation: Submission author cannot verify their own submission.');
            }
            if ($e->getMessage() === 'WRONG_VERIFIER') {
                return $this->respondForbidden('Access denied: You are not the assigned verifier for this submission.');
            }
            if ($e->getMessage() === 'NO_ACTIVE_ASSIGNMENT') {
                return $this->respondConflict('No active verifier assignment exists for this submission.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to start review for this submission.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/submissions/{id}/verifier-review/notes
     * Verifier records substantive review notes.
     */
    public function addNote($id = null): ResponseInterface
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
            $result = $this->verifierService->addReviewNote($user, $submissionId, $input);
            return $this->respondSuccess($result, 'Review note recorded successfully.', 201);
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Submission is not currently in an active review state.');
            }
            if ($e->getMessage() === 'SOD_AUTHOR_CANNOT_VERIFY') {
                return $this->respondForbidden('Separation of Duties violation: Submission author cannot verify their own submission.');
            }
            if ($e->getMessage() === 'WRONG_VERIFIER') {
                return $this->respondForbidden('Access denied: You are not the assigned verifier for this submission.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to add review notes to this submission.');
            }
            if (strpos($e->getMessage(), 'required') !== false || strpos($e->getMessage(), 'characters') !== false) {
                return $this->respondValidationError(['issue_description' => $e->getMessage()]);
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/submissions/{id}/verifier-review/return
     * Verifier returns submission for substantive revision.
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
        $reason = !empty($input['reason']) ? trim((string)$input['reason']) : '';
        $versionUnitId = !empty($input['version_unit_id']) ? (int)$input['version_unit_id'] : null;

        if (empty($reason) || strlen($reason) < 5) {
            return $this->respondValidationError(['reason' => 'Reason for substantive revision is required (min 5 characters).']);
        }

        try {
            $result = $this->verifierService->returnForRevision($user, $submissionId, $reason, $versionUnitId);
            return $this->respondSuccess($result, 'Submission returned for substantive revision successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Submission is not in an eligible state for revision return.');
            }
            if ($e->getMessage() === 'SOD_AUTHOR_CANNOT_VERIFY') {
                return $this->respondForbidden('Separation of Duties violation: Submission author cannot return their own submission.');
            }
            if ($e->getMessage() === 'WRONG_VERIFIER') {
                return $this->respondForbidden('Access denied: You are not the assigned verifier for this submission.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to return this submission.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/submissions/{id}/verifier-review/approve
     * Verifier approves substantive verification and records technical recommendation (Step 9).
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
            $result = $this->verifierService->approveSubstantive($user, $submissionId, $input);
            return $this->respondSuccess($result, 'Substantive verification approved and technical recommendation created successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Submission is not in an eligible state for substantive verification approval.');
            }
            if ($e->getMessage() === 'SOD_AUTHOR_CANNOT_VERIFY') {
                return $this->respondForbidden('Separation of Duties violation: Submission author cannot verify/approve their own submission.');
            }
            if ($e->getMessage() === 'WRONG_VERIFIER') {
                return $this->respondForbidden('Access denied: You are not the assigned verifier for this submission.');
            }
            if ($e->getMessage() === 'UNRESOLVED_REVISIONS') {
                return $this->respondValidationError([
                    'revision_completion' => 'All active revision notes must be resolved before substantive verification can be passed.',
                ]);
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to approve substantive verification for this submission.');
            }
            if (strpos($e->getMessage(), 'required') !== false || strpos($e->getMessage(), 'characters') !== false) {
                return $this->respondValidationError(['recommendation' => $e->getMessage()]);
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/submissions/{id}/recommendation
     * Retrieve technical recommendation for a submission.
     */
    public function recommendation($id = null): ResponseInterface
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
            $rec = $this->verifierService->getRecommendation($user, $submissionId);
            if (!$rec) {
                return $this->respondNotFound('No substantive recommendation found for this submission.');
            }

            return $this->respondSuccess($rec, 'Technical recommendation retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to view this recommendation.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }
}
