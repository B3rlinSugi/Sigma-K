<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * HealthController
 * 
 * Liveness and Readiness Probe for E-SKLD Backend.
 * Verifies application boot and database connectivity without leaking sensitive configuration.
 */
class HealthController extends BaseApiController
{
    /**
     * Health Check Endpoint (GET /api/v1/health)
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        try {
            $db = \Config\Database::connect();
            $query = $db->query('SELECT 1 AS alive');
            $row = $query->getRow();

            if (!$row || (int)$row->alive !== 1) {
                return $this->respondServerError('Database readiness check failed.');
            }

            return $this->respondSuccess(
                [
                    'status' => 'ok',
                ],
                'Service is healthy'
            );
        } catch (Throwable $e) {
            log_message('error', '[HealthCheck] Database connection error: ' . $e->getMessage());
            return $this->respondServerError('Service is currently degraded.');
        }
    }
}
