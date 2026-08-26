<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\Submission\SubmissionPositionService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * SubmissionPositionController
 *
 * REST API Controller for managing proposed position changes in DRAFT submissions.
 */
class SubmissionPositionController extends BaseApiController
{
    protected SubmissionPositionService $posService;

    public function __construct(?SubmissionPositionService $posService = null)
    {
        $this->posService = $posService ?? new SubmissionPositionService();
    }

    /**
     * POST /api/v1/submissions/{id}/positions
     */
    public function create($id = null): ResponseInterface
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
                'version_unit_id'    => $this->request->getVar('version_unit_id'),
                'position_name'      => $this->request->getVar('position_name'),
                'position_type'      => $this->request->getVar('position_type'),
                'echelon'            => $this->request->getVar('echelon'),
                'formation_count'    => $this->request->getVar('formation_count'),
                'change_type'        => $this->request->getVar('change_type'),
                'source_position_id' => $this->request->getVar('source_position_id'),
            ];
        }

        $rules = [
            'version_unit_id'    => 'required|is_natural_no_zero',
            'position_name'      => 'required|min_length[3]|max_length[255]',
            'position_type'      => 'required|min_length[2]|max_length[50]',
            'echelon'            => 'permit_empty|max_length[20]',
            'formation_count'    => 'permit_empty|is_natural_no_zero',
            'change_type'        => 'permit_empty|in_list[NEW,UPDATE,DELETE,UNCHANGED]',
            'source_position_id' => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        try {
            $result = $this->posService->addPositionChange($user, $submissionId, $input);
            return $this->respondCreated($result, 'Proposed position change added successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Cannot modify position changes because submission is locked.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to modify positions in this submission.');
            }
            return $this->respondValidationError(['error' => $e->getMessage()], $e->getMessage());
        }
    }

    /**
     * PUT /api/v1/submissions/{id}/positions/{positionId}
     */
    public function update($id = null, $positionId = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $submissionId = (int)$id;
        $positionId   = (int)$positionId;

        if ($submissionId <= 0 || $positionId <= 0) {
            return $this->respondNotFound('Invalid submission or position ID.');
        }

        $input = $this->request->getJSON(true) ?: [];
        if (empty($input)) {
            $raw = $this->request->getRawInput();
            if (!empty($raw)) {
                $input = $raw;
            }
        }
        if (empty($input)) {
            $body = $this->request->getBody();
            if (!empty($body) && is_string($body)) {
                $json = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $input = $json;
                } else {
                    parse_str($body, $parsed);
                    if (!empty($parsed)) {
                        $input = $parsed;
                    }
                }
            }
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?: $_POST ?: $_REQUEST ?: [];
        }

        try {
            $result = $this->posService->updatePositionChange($user, $submissionId, $positionId, $input);
            return $this->respondSuccess($result, 'Proposed position change updated successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Proposed position not found in this submission.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Cannot update position change because submission is locked.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to update positions in this submission.');
            }
            return $this->respondValidationError(['error' => $e->getMessage()], $e->getMessage());
        }
    }

    /**
     * DELETE /api/v1/submissions/{id}/positions/{positionId}
     */
    public function delete($id = null, $positionId = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $submissionId = (int)$id;
        $positionId   = (int)$positionId;

        if ($submissionId <= 0 || $positionId <= 0) {
            return $this->respondNotFound('Invalid submission or position ID.');
        }

        try {
            $this->posService->deletePositionChange($user, $submissionId, $positionId);
            return $this->respondSuccess(['deleted' => true], 'Proposed position change deleted successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Proposed position not found in this submission.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Cannot delete position change because submission is locked.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to delete positions in this submission.');
            }
            return $this->respondServerError($e->getMessage());
        }
    }
}
