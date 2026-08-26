<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\Reporting\ExecutiveReportService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * ReportController
 *
 * REST API Controller for Step 11: Executive Reporting Dashboard & Data Exports.
 */
class ReportController extends BaseApiController
{
    protected ExecutiveReportService $reportService;

    public function __construct(?ExecutiveReportService $reportService = null)
    {
        $this->reportService = $reportService ?? new ExecutiveReportService();
    }

    /**
     * GET /api/v1/reports/summary
     * High-level executive overview KPI cards and lifecycle funnel breakdown.
     */
    public function summary(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        try {
            $data = $this->reportService->getSummary($user);
            return $this->respondSuccess($data, 'Executive report summary retrieved successfully.');
        } catch (Throwable $e) {
            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/reports/submissions
     * Submissions breakdown by state, year, and institution.
     */
    public function submissions(): ResponseInterface
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
            $data = $this->reportService->getSubmissionsReport($user, $params);
            return $this->respondSuccess($data, 'Submissions report retrieved successfully.');
        } catch (Throwable $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->respondForbidden('Access denied: You are not authorized to view reports for the requested institution.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/reports/institutions
     * Aggregate organizational units and formations count per institution.
     */
    public function institutions(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $params = $this->request->getGet();
        if (empty($params)) {
            parse_str((string)$this->request->getUri()->getQuery(), $parsedParams);
            $params = array_merge($params, $parsedParams);
        }

        try {
            $data = $this->reportService->getInstitutionsReport($user, $params);
            return $this->respondSuccess($data, 'Institutions report retrieved successfully.');
        } catch (Throwable $e) {
            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/reports/approvals
     * Formal approval records and decree numbers summary.
     */
    public function approvals(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $params = $this->request->getGet();
        if (empty($params)) {
            parse_str((string)$this->request->getUri()->getQuery(), $parsedParams);
            $params = array_merge($params, $parsedParams);
        }

        try {
            $data = $this->reportService->getApprovalsReport($user, $params);
            return $this->respondSuccess($data, 'Approvals report retrieved successfully.');
        } catch (Throwable $e) {
            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/reports/promotions
     * Master data promotion activity and history.
     */
    public function promotions(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $params = $this->request->getGet();
        if (empty($params)) {
            parse_str((string)$this->request->getUri()->getQuery(), $parsedParams);
            $params = array_merge($params, $parsedParams);
        }

        try {
            $data = $this->reportService->getPromotionsReport($user, $params);
            return $this->respondSuccess($data, 'Promotions report retrieved successfully.');
        } catch (Throwable $e) {
            return $this->respondServerError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/reports/export
     * Export report dataset in CSV or JSON format.
     */
    public function export(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized();
        }

        $params = $this->request->getGet();
        if (empty($params)) {
            parse_str((string)$this->request->getUri()->getQuery(), $parsedParams);
            $params = array_merge($params, $parsedParams);
        }

        $type   = strtolower((string)($params['type'] ?? 'submissions'));
        $format = strtolower((string)($params['format'] ?? 'csv'));

        try {
            switch ($type) {
                case 'institutions':
                    $dataset = $this->reportService->getInstitutionsReport($user, $params);
                    $headers = ['ID', 'Code', 'Institution Name', 'Short Name', 'Category', 'Status', 'Active Units', 'Active Positions', 'Total Formations', 'Total Submissions'];
                    break;
                case 'approvals':
                    $dataset = $this->reportService->getApprovalsReport($user, $params);
                    $headers = ['Approval ID', 'Approval Number', 'Approved At', 'Approval Notes', 'Approver Name', 'Approver NIP', 'Submission ID', 'Submission Title', 'Submission Year', 'Institution ID', 'Institution Name', 'Version Number'];
                    break;
                case 'promotions':
                    $dataset = $this->reportService->getPromotionsReport($user, $params);
                    $headers = ['Submission ID', 'Submission Title', 'Submission Year', 'Promoted At', 'Institution ID', 'Institution Name', 'Approval Number', 'Author Name'];
                    break;
                case 'submissions':
                default:
                    $dataset = $this->reportService->getSubmissionsReport($user, $params);
                    $headers = ['ID', 'Institution ID', 'Institution Name', 'Title', 'Submission Year', 'Current State', 'Created At', 'Updated At', 'Author Name'];
                    break;
            }

            if ($format === 'json') {
                return $this->respondSuccess([
                    'reportType' => $type,
                    'exportedAt' => date('Y-m-d H:i:s'),
                    'count'      => count($dataset),
                    'data'       => $dataset,
                ], 'Report exported successfully.');
            }

            // CSV format
            $filename = 'report_' . $type . '_' . date('Ymd_His') . '.csv';
            $output = fopen('php://temp', 'r+');

            fputcsv($output, $headers);

            foreach ($dataset as $row) {
                fputcsv($output, array_values($row));
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
                return $this->respondForbidden('Access denied: You are not authorized to export reports for the requested scope.');
            }

            return $this->respondServerError($e->getMessage());
        }
    }
}
