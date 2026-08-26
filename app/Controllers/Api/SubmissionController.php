<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\Submission\SubmissionService;
use App\Services\Submission\SubmissionVersionService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * SubmissionController
 *
 * REST API Controller for E-SKLD Submission lifecycle, listing, details, version snapshots, and submission.
 */
class SubmissionController extends BaseApiController
{
    protected SubmissionService $submissionService;
    protected SubmissionVersionService $versionService;

    public function __construct(
        ?SubmissionService $submissionService = null,
        ?SubmissionVersionService $versionService = null
    ) {
        $this->submissionService = $submissionService ?? new SubmissionService();
        $this->versionService    = $versionService ?? new SubmissionVersionService();
    }

    /**
     * POST /api/v1/submissions
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
                'institution_id'  => $this->request->getVar('institution_id'),
                'title'           => $this->request->getVar('title'),
                'submission_year' => $this->request->getVar('submission_year'),
            ];
        }

        $rules = [
            'institution_id'  => 'permit_empty|is_natural_no_zero',
            'title'           => 'required|min_length[5]|max_length[255]',
            'submission_year' => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        try {
            $result = $this->submissionService->createSubmission($user, $input);
            return $this->respondCreated($result, 'Submission created successfully as DRAFT.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to create submissions for this institution.');
            }
            return $this->respondValidationError(['error' => $e->getMessage()], $e->getMessage());
        }
    }

    /**
     * GET /api/v1/submissions
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

        $result = $this->submissionService->listSubmissions($user, $page, $perPage, $status);
        return $this->respondSuccess(
            $result['items'],
            'Submissions list retrieved successfully.',
            200,
            $result['meta']
        );
    }

    /**
     * GET /api/v1/submissions/{id}
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
            $submission = $this->submissionService->getSubmissionDetail($user, $submissionId);
            if (!$submission) {
                return $this->respondNotFound('Submission not found.');
            }

            return $this->respondSuccess($submission, 'Submission details retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to view this submission.');
            }
            return $this->respondServerError('An error occurred while retrieving submission.');
        }
    }

    /**
     * POST /api/v1/submissions/{id}/submit
     */
    public function submit($id = null): ResponseInterface
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
            $result = $this->submissionService->submitDraft($user, $submissionId, $notes);
            return $this->respondSuccess($result, 'Submission successfully submitted to Admin review gate.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Submission is locked and cannot be submitted again.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to submit this submission.');
            }
            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/submissions/{id}/versions
     */
    public function createVersion($id = null): ResponseInterface
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
            $result = $this->versionService->createSnapshot($user, $submissionId, $notes);
            return $this->respondCreated($result, 'Submission version snapshot created successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Cannot create version snapshot because submission is locked.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to create a version snapshot for this submission.');
            }
            return $this->respondServerError($e->getMessage());
        }
    }
}
