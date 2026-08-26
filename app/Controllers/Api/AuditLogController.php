<?php

namespace App\Controllers\Api;

use App\Services\Audit\AuditQueryService;
use App\Services\Auth\AuthContext;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * AuditLogController
 *
 * REST API Controller for Step 11: Comprehensive Scoped Audit Log Viewing & Export.
 */
class AuditLogController extends BaseApiController
{
    protected AuditQueryService $auditQueryService;

    public function __construct(?AuditQueryService $auditQueryService = null)
    {
        $this->auditQueryService = $auditQueryService ?? new AuditQueryService();
    }

    /**
     * GET /api/v1/audit-logs
     * List paginated audit records with role-based multi-tenant scoping.
     */
    public function index(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $params = $this->request->getGet() ?: [];
        if (!empty($_SERVER['REQUEST_URI'])) {
            $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
            if (!empty($queryString)) {
                parse_str($queryString, $parsedParams);
                $params = array_merge($params, $parsedParams);
            }
        }

        try {
            $result = $this->auditQueryService->getLogs($user, $params);
            return $this->respondSuccess($result, 'Audit logs retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('Access denied: You are not authorized to view audit logs for the requested scope.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/audit-logs/{id}
     * View detailed audit log record with old/new payloads.
     */
    public function show($id = null): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $logId = (int)$id;
        if ($logId <= 0) {
            return $this->respondNotFound('Invalid audit log ID.');
        }

        try {
            $log = $this->auditQueryService->getLogById($user, $logId);
            return $this->respondSuccess($log, 'Audit log details retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'NOT_FOUND') {
                return $this->respondNotFound('Audit log not found.');
            }
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('Access denied: You are not authorized to view this audit log.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/audit-logs/export
     * Export filtered audit trail in CSV or JSON format.
     */
    public function export(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $params = $this->request->getGet() ?: [];
        if (!empty($_SERVER['REQUEST_URI'])) {
            $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
            if (!empty($queryString)) {
                parse_str($queryString, $parsedParams);
                $params = array_merge($params, $parsedParams);
            }
        }

        $format = strtolower((string)($params['format'] ?? 'csv'));

        try {
            $data = $this->auditQueryService->exportLogs($user, $params);

            if ($format === 'json') {
                return $this->respondSuccess([
                    'exportedAt' => date('Y-m-d H:i:s'),
                    'count'      => count($data),
                    'data'       => $data,
                ], 'Audit logs exported successfully.');
            }

            // Default CSV stream output
            $filename = 'audit_logs_' . date('Ymd_His') . '.csv';
            $output = fopen('php://temp', 'r+');

            // Header row
            fputcsv($output, [
                'ID',
                'Timestamp',
                'Actor ID',
                'Actor Username',
                'Actor Name',
                'Actor Role',
                'Action Event',
                'Entity',
                'Resource ID',
                'IP Address',
                'Reason / Notes',
            ]);

            foreach ($data as $row) {
                fputcsv($output, [
                    $row['id'] ?? '',
                    $row['created_at'] ?? '',
                    $row['actor_id'] ?? '',
                    $row['actor_username'] ?? '',
                    $row['actor_name'] ?? '',
                    $row['actor_role'] ?? '',
                    $row['action_event'] ?? '',
                    $row['resource_entity'] ?? '',
                    $row['resource_id'] ?? '',
                    $row['ip_address'] ?? '',
                    $row['reason'] ?? '',
                ]);
            }

            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);

            return $this->response
                ->setContentType('text/csv; charset=UTF-8')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($csvContent);
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('Access denied: You are not authorized to export audit logs for the requested scope.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }
}
