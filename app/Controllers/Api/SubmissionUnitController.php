<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\Submission\SubmissionUnitService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * SubmissionUnitController
 *
 * REST API Controller for managing proposed organizational unit changes in DRAFT submissions.
 */
class SubmissionUnitController extends BaseApiController
{
    protected SubmissionUnitService $unitService;

    public function __construct(?SubmissionUnitService $unitService = null)
    {
        $this->unitService = $unitService ?? new SubmissionUnitService();
    }

    /**
     * POST /api/v1/submissions/{id}/units
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
                'unit_code'      => $this->request->getVar('unit_code'),
                'unit_name'      => $this->request->getVar('unit_name'),
                'unit_level'     => $this->request->getVar('unit_level'),
                'order_index'    => $this->request->getVar('order_index'),
                'change_type'    => $this->request->getVar('change_type'),
                'temp_parent_id' => $this->request->getVar('temp_parent_id'),
                'source_unit_id' => $this->request->getVar('source_unit_id'),
            ];
        }

        $rules = [
            'unit_code'      => 'required|min_length[2]|max_length[50]',
            'unit_name'      => 'required|min_length[3]|max_length[255]',
            'unit_level'     => 'permit_empty|is_natural_no_zero',
            'order_index'    => 'permit_empty|is_natural',
            'change_type'    => 'permit_empty|in_list[NEW,UPDATE,DELETE,UNCHANGED]',
            'temp_parent_id' => 'permit_empty|is_natural_no_zero',
            'source_unit_id' => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        try {
            $result = $this->unitService->addUnitChange($user, $submissionId, $input);
            return $this->respondCreated($result, 'Proposed unit change added successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Submission not found.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Cannot modify unit changes because submission is locked.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to modify units in this submission.');
            }
            return $this->respondValidationError(['error' => $e->getMessage()], $e->getMessage());
        }
    }

    /**
     * PUT /api/v1/submissions/{id}/units/{unitId}
     */
    public function update($id = null, $unitId = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $submissionId = (int)$id;
        $unitId       = (int)$unitId;

        if ($submissionId <= 0 || $unitId <= 0) {
            return $this->respondNotFound('Invalid submission or unit ID.');
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
            $result = $this->unitService->updateUnitChange($user, $submissionId, $unitId, $input);
            return $this->respondSuccess($result, 'Proposed unit change updated successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Proposed unit not found in this submission.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Cannot update unit change because submission is locked.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to update units in this submission.');
            }
            return $this->respondValidationError(['error' => $e->getMessage()], $e->getMessage());
        }
    }

    /**
     * DELETE /api/v1/submissions/{id}/units/{unitId}
     */
    public function delete($id = null, $unitId = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $submissionId = (int)$id;
        $unitId       = (int)$unitId;

        if ($submissionId <= 0 || $unitId <= 0) {
            return $this->respondNotFound('Invalid submission or unit ID.');
        }

        try {
            $this->unitService->deleteUnitChange($user, $submissionId, $unitId);
            return $this->respondSuccess(['deleted' => true], 'Proposed unit change deleted successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Proposed unit not found in this submission.');
            }
            if ($e->getMessage() === 'LOCKED') {
                return $this->respondConflict('Cannot delete unit change because submission is locked.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to delete units in this submission.');
            }
            return $this->respondServerError($e->getMessage());
        }
    }
}
