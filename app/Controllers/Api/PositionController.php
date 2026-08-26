<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\OrgStructure\PositionService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * PositionController
 *
 * REST API Controller for Master Positions (Jabatan) within organizational units.
 */
class PositionController extends BaseApiController
{
    protected PositionService $positionService;

    public function __construct(?PositionService $positionService = null)
    {
        $this->positionService = $positionService ?? new PositionService();
    }

    /**
     * GET /api/v1/units/{id}/positions
     */
    public function getByUnit($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $unitId = (int)$id;
        if ($unitId <= 0) {
            return $this->respondNotFound('Invalid unit ID.');
        }

        try {
            $result = $this->positionService->getPositionsByUnit($user, $unitId);
            return $this->respondSuccess($result, 'Positions retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Organizational unit not found.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to view positions for this organizational unit.');
            }
            return $this->respondServerError('An error occurred while retrieving positions.');
        }
    }

    /**
     * GET /api/v1/positions/{id}
     */
    public function show($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $positionId = (int)$id;
        if ($positionId <= 0) {
            return $this->respondNotFound('Invalid position ID.');
        }

        try {
            $position = $this->positionService->getPositionDetail($user, $positionId);
            if (!$position) {
                return $this->respondNotFound('Position not found.');
            }

            return $this->respondSuccess($position, 'Position details retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('You are not authorized to view this position.');
            }
            return $this->respondServerError('An error occurred while retrieving position.');
        }
    }
}
