<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\Workflow\RevisionService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * RevisionController
 *
 * REST API Controller for USER Revision View, Revision Version Branching, and Resubmission Workflow (Step 8).
 */
class RevisionController extends BaseApiController
{
    protected RevisionService $revisionService;

    public function __construct(?RevisionService $revisionService = null)
    {
        $this->revisionService = $revisionService ?? new RevisionService();
    }

    /**
     * GET /api/v1/submissions/{id}/revision
     * Inspect active revision notes, affected units/positions, and version history.
     */
    public function show($id = null): ResponseInterface
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
            $result = $this->revisionService->getRevisionView($user, $submissionId);
            return $this->respondSuccess($result, 'Revision details retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to view revision details for this submission.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/submissions/{id}/revision
     * Branch a new revision version (v2, v3) from the previous submitted snapshot.
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
            $result = $this->revisionService->startRevisionVersion($user, $submissionId, $notes);
            $statusCode = !empty($result['isExisting']) ? 200 : 201;
            return $this->respondSuccess($result, 'Revision version initialized successfully.', $statusCode);
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Submission is not in an active revision state.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to revise this submission.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/submissions/{id}/resubmit
     * Resubmit the corrected revision version into the workflow.
     */
    public function resubmit($id = null): ResponseInterface
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
            $result = $this->revisionService->resubmit($user, $submissionId, $notes);
            return $this->respondSuccess($result, 'Corrected version resubmitted successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Submission is not in an active revision state.');
            }
            if ($e->getMessage() === 'NO_NEW_REVISION_VERSION') {
                return $this->respondConflict('A new unsubmitted revision version is required before resubmitting.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to resubmit this submission.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }
}
