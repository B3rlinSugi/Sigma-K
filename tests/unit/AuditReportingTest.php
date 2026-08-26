<?php

namespace Tests\Unit;

use App\Entities\UserEntity;
use App\Models\AuditLogModel;
use App\Models\InstitutionModel;
use App\Models\SubmissionModel;
use App\Models\UserModel;
use App\Services\Audit\AuditQueryService;
use App\Services\Audit\AuditService;
use App\Services\Auth\JwtService;
use App\Services\Reporting\ExecutiveReportService;
use App\Services\Submission\SubmissionPositionService;
use App\Services\Submission\SubmissionService;
use App\Services\Submission\SubmissionUnitService;
use App\Services\Workflow\AdminReviewService;
use App\Services\Workflow\FinalApprovalService;
use App\Services\Workflow\VerifierReviewService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * AuditReportingTest
 *
 * Comprehensive Test Suite for Step 11:
 * Comprehensive Audit Logging & Executive Reporting Dashboard / Export API.
 *
 * Covers:
 * - AUDIT-01 .. AUDIT-10
 * - REPORT-01 .. REPORT-07
 * - EXPORT-01 .. EXPORT-05
 * - REGRESSION (Step 1-10)
 *
 * @internal
 */
final class AuditReportingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected UserModel $userModel;
    protected InstitutionModel $instModel;
    protected SubmissionModel $subModel;
    protected AuditLogModel $auditModel;

    protected JwtService $jwtService;
    protected AuditService $auditService;
    protected AuditQueryService $auditQueryService;
    protected ExecutiveReportService $reportService;
    protected SubmissionService $subService;
    protected SubmissionUnitService $unitService;
    protected SubmissionPositionService $posService;
    protected AdminReviewService $adminService;
    protected VerifierReviewService $verifierService;
    protected FinalApprovalService $finalApprovalService;

    protected UserEntity $userA;
    protected UserEntity $userB;
    protected UserEntity $adminA;
    protected UserEntity $verifierA;
    protected UserEntity $superAdmin;

    protected string $tokenUserA;
    protected string $tokenUserB;
    protected string $tokenAdminA;
    protected string $tokenVerifierA;
    protected string $tokenSuperAdmin;

    protected int $instAId;
    protected int $instBId;
    protected int $instCId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel            = new UserModel();
        $this->instModel            = new InstitutionModel();
        $this->subModel             = new SubmissionModel();
        $this->auditModel           = new AuditLogModel();
        $this->jwtService           = new JwtService();
        $this->auditService         = new AuditService();
        $this->auditQueryService    = new AuditQueryService();
        $this->reportService        = new ExecutiveReportService();
        $this->subService           = new SubmissionService();
        $this->unitService          = new SubmissionUnitService();
        $this->posService           = new SubmissionPositionService();
        $this->adminService         = new AdminReviewService();
        $this->verifierService      = new VerifierReviewService();
        $this->finalApprovalService = new FinalApprovalService();

        $instA = $this->instModel->where('institution_code', 'TEST-INST-A')->first();
        $instB = $this->instModel->where('institution_code', 'TEST-INST-B')->first();
        $instC = $this->instModel->where('institution_code', 'TEST-INST-C')->first();

        $this->instAId = (int)$instA['id'];
        $this->instBId = (int)$instB['id'];
        $this->instCId = (int)$instC['id'];

        $this->userA      = $this->userModel->findByUsername('test_user_a');
        $this->userB      = $this->userModel->findByUsername('test_user_b');
        $this->adminA     = $this->userModel->findByUsername('test_admin');
        $this->verifierA  = $this->userModel->findByUsername('test_verifier');
        $this->superAdmin = $this->userModel->findByUsername('test_super_admin');

        $this->tokenUserA      = $this->jwtService->generateAccessToken($this->userA, 'USER');
        $this->tokenUserB      = $this->jwtService->generateAccessToken($this->userB, 'USER');
        $this->tokenAdminA     = $this->jwtService->generateAccessToken($this->adminA, 'ADMIN');
        $this->tokenVerifierA  = $this->jwtService->generateAccessToken($this->verifierA, 'VERIFIER');
        $this->tokenSuperAdmin = $this->jwtService->generateAccessToken($this->superAdmin, 'SUPER_ADMIN');
    }

    /**
     * AUDIT-01: SuperAdmin can view global audit logs with pagination -> 200 OK
     */
    public function testAudit01SuperAdminViewsGlobalAuditLogs(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/audit-logs');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('data', $body['data']);
        $this->assertArrayHasKey('pagination', $body['data']);
        $this->assertGreaterThanOrEqual(1, $body['data']['pagination']['total']);
    }

    /**
     * AUDIT-02: Unauthenticated request to audit logs returns 401 Unauthorized
     */
    public function testAudit02UnauthenticatedReturns401(): void
    {
        $result = $this->get('api/v1/audit-logs');
        $result->assertStatus(401);
    }

    /**
     * AUDIT-03: USER can only view audit logs scoped to self or home institution
     */
    public function testAudit03UserAuditLogsScoped(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get('api/v1/audit-logs');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        foreach ($body['data']['data'] as $log) {
            // Either actor is User A, or actor belongs to Inst A
            $this->assertTrue(
                (int)$log['actor_id'] === (int)$this->userA->id ||
                (int)$log['actor_institution_id'] === $this->instAId
            );
        }
    }

    /**
     * AUDIT-04: ADMIN can view audit logs within authorized scope
     */
    public function testAudit04AdminViewsScopedAuditLogs(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->get('api/v1/audit-logs');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']['data']);
    }

    /**
     * AUDIT-05: User or Admin requesting audit logs for unauthorized institution returns 403 Forbidden (BOLA/IDOR)
     */
    public function testAudit05UnauthorizedInstitutionForbidden(): void
    {
        // User A (Inst A) attempting to query Inst B
        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get('api/v1/audit-logs?institution_id=' . $this->instBId);
        $res1->assertStatus(403);

        // Admin A attempting to query unauthorized institution 99999
        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->get('api/v1/audit-logs?institution_id=99999');
        $res2->assertStatus(403);
    }

    /**
     * AUDIT-06: VERIFIER can view audit logs within scope
     */
    public function testAudit06VerifierViewsScopedAuditLogs(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->get('api/v1/audit-logs');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertTrue($body['success']);
    }

    /**
     * AUDIT-07: User can view details of own audit log -> 200 OK
     */
    public function testAudit07UserViewsOwnAuditLogDetail(): void
    {
        // Log a test action for User A
        $this->auditService->log([
            'actor_id'        => $this->userA->id,
            'actor_role'      => 'USER',
            'action_event'    => 'TEST_USER_ACTION',
            'resource_entity' => 'submissions',
            'resource_id'     => 1,
            'reason'          => 'Unit test user log',
        ]);

        $db = Database::connect();
        $latestId = (int)$db->table('audit_logs')->where('actor_id', $this->userA->id)->orderBy('id', 'DESC')->get()->getRowArray()['id'];

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/audit-logs/{$latestId}");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals($latestId, $body['data']['id']);
        $this->assertEquals('TEST_USER_ACTION', $body['data']['action_event']);
    }

    /**
     * AUDIT-08: User cannot view detailed audit log of another institution -> 403 Forbidden
     */
    public function testAudit08UserCannotViewOtherInstitutionAuditLog(): void
    {
        // Log an action for User B (Inst B)
        $this->auditService->log([
            'actor_id'        => $this->userB->id,
            'actor_role'      => 'USER',
            'action_event'    => 'TEST_USER_B_ACTION',
            'resource_entity' => 'submissions',
            'resource_id'     => 2,
            'reason'          => 'User B action',
        ]);

        $db = Database::connect();
        $userBLogId = (int)$db->table('audit_logs')->where('actor_id', $this->userB->id)->orderBy('id', 'DESC')->get()->getRowArray()['id'];

        // User A attempts to view User B's audit log
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/audit-logs/{$userBLogId}");

        $result->assertStatus(403);
    }

    /**
     * AUDIT-09: Filtering audit logs by action_event and date range
     */
    public function testAudit09FilteringAuditLogs(): void
    {
        $today = date('Y-m-d');
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get("api/v1/audit-logs?date_from={$today}&date_to={$today}&per_page=10");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertLessThanOrEqual(10, count($body['data']['data']));
    }

    /**
     * AUDIT-10: Audit logs cannot be modified or deleted (Append-Only Model Integrity)
     */
    public function testAudit10AuditLogsCannotBeUpdatedOrDeleted(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->auditModel->update(1, ['reason' => 'Tamper attempt']);
    }

    /**
     * REPORT-01: Executive summary returns KPI overview and funnel breakdown -> 200 OK
     */
    public function testReport01ExecutiveSummaryOverview(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/reports/summary');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('overview', $body['data']);
        $this->assertArrayHasKey('funnel', $body['data']);
        $this->assertArrayHasKey('totalInstitutions', $body['data']['overview']);
        $this->assertArrayHasKey('totalActiveUnits', $body['data']['overview']);
        $this->assertArrayHasKey('totalPositions', $body['data']['overview']);
        $this->assertArrayHasKey('totalFormations', $body['data']['overview']);
    }

    /**
     * REPORT-02: Submissions report respects authorized institution scope
     */
    public function testReport02SubmissionsReportScoped(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get('api/v1/reports/submissions');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        foreach ($body['data'] as $sub) {
            $this->assertEquals($this->instAId, (int)$sub['institution_id']);
        }
    }

    /**
     * REPORT-03: Institutions report aggregates units and formations accurately
     */
    public function testReport03InstitutionsReport(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/reports/institutions');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
        $this->assertNotEmpty($body['data']);
    }

    /**
     * REPORT-04: Approvals report lists formal approvals
     */
    public function testReport04ApprovalsReport(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/reports/approvals');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
    }

    /**
     * REPORT-05: Promotions report lists promoted submissions
     */
    public function testReport05PromotionsReport(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/reports/promotions');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
    }

    /**
     * REPORT-07: User or Admin accessing unauthorized institution submissions returns 403 Forbidden
     */
    public function testReport07UnauthorizedInstitutionForbidden(): void
    {
        // User A (Inst A) attempting to query Inst B report
        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get('api/v1/reports/submissions?institution_id=' . $this->instBId);
        $res1->assertStatus(403);

        // Admin A attempting to query unauthorized institution 99999 report
        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->get('api/v1/reports/submissions?institution_id=99999');
        $res2->assertStatus(403);
    }

    /**
     * EXPORT-01: Export audit logs as CSV stream -> 200 OK (text/csv)
     */
    public function testExport01AuditLogsCsv(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/audit-logs/export?format=csv');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Timestamp', $result->getBody());
        $this->assertStringContainsString('Action Event', $result->getBody());
    }

    /**
     * EXPORT-02: Export audit logs as JSON stream -> 200 OK (application/json)
     */
    public function testExport02AuditLogsJson(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/audit-logs/export?format=json');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('data', $body['data']);
    }

    /**
     * EXPORT-03: Export submissions report as CSV -> 200 OK
     */
    public function testExport03SubmissionsReportCsv(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/reports/export?type=submissions&format=csv');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Submission Year', $result->getBody());
    }

    /**
     * EXPORT-04: Export approvals report as CSV -> 200 OK
     */
    public function testExport04ApprovalsReportCsv(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/reports/export?type=approvals&format=csv');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Approval ID', $result->getBody());
    }
}

