<?php

namespace Tests\Unit;

use App\Entities\UserEntity;
use App\Models\InstitutionModel;
use App\Models\SubmissionModel;
use App\Models\UserModel;
use App\Services\Auth\JwtService;
use App\Services\Submission\SubmissionService;
use App\Services\Workflow\AdminReviewService;
use App\Services\Workflow\VerifierReviewService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * BenchmarkTest
 *
 * Controlled Performance & Latency Benchmarking Suite for Step 12.
 * Measures end-to-end response times of core E-SKLD transactional and reporting endpoints.
 *
 * @internal
 */
final class BenchmarkTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected UserModel $userModel;
    protected InstitutionModel $instModel;
    protected SubmissionModel $subModel;
    protected JwtService $jwtService;
    protected SubmissionService $subService;
    protected AdminReviewService $adminService;
    protected VerifierReviewService $verifierService;

    protected UserEntity $userA;
    protected UserEntity $adminA;
    protected UserEntity $verifierA;
    protected UserEntity $superAdmin;

    protected string $tokenUserA;
    protected string $tokenAdminA;
    protected string $tokenVerifierA;
    protected string $tokenSuperAdmin;

    protected int $instAId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel       = new UserModel();
        $this->instModel       = new InstitutionModel();
        $this->subModel        = new SubmissionModel();
        $this->jwtService      = new JwtService();
        $this->subService      = new SubmissionService();
        $this->adminService    = new AdminReviewService();
        $this->verifierService = new VerifierReviewService();

        $instA = $this->instModel->where('institution_code', 'TEST-INST-A')->first();
        $this->instAId = (int)$instA['id'];

        $this->userA      = $this->userModel->findByUsername('test_user_a');
        $this->adminA     = $this->userModel->findByUsername('test_admin');
        $this->verifierA  = $this->userModel->findByUsername('test_verifier');
        $this->superAdmin = $this->userModel->findByUsername('test_super_admin');

        $this->tokenUserA      = $this->jwtService->generateAccessToken($this->userA, 'USER');
        $this->tokenAdminA     = $this->jwtService->generateAccessToken($this->adminA, 'ADMIN');
        $this->tokenVerifierA  = $this->jwtService->generateAccessToken($this->verifierA, 'VERIFIER');
        $this->tokenSuperAdmin = $this->jwtService->generateAccessToken($this->superAdmin, 'SUPER_ADMIN');
    }

    /**
     * BENCH-01: Submission Draft Creation Latency
     */
    public function testBenchmark01DraftCreationLatency(): void
    {
        $start = microtime(true);

        $res = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post('api/v1/submissions', [
            'institution_id'  => $this->instAId,
            'title'           => 'Benchmark Submission ' . uniqid(),
            'submission_year' => 2026,
        ]);

        $elapsedMs = (microtime(true) - $start) * 1000;

        $res->assertStatus(201);
        $this->assertLessThan(500.0, $elapsedMs, "Draft creation took {$elapsedMs}ms (expected < 500ms)");
    }

    /**
     * BENCH-02: Organization Hierarchy Retrieval Latency
     */
    public function testBenchmark02OrgHierarchyRetrievalLatency(): void
    {
        $start = microtime(true);

        $res = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/institutions/{$this->instAId}/units");

        $elapsedMs = (microtime(true) - $start) * 1000;

        $res->assertStatus(200);
        $this->assertLessThan(500.0, $elapsedMs, "Org hierarchy retrieval took {$elapsedMs}ms (expected < 500ms)");
    }

    /**
     * BENCH-03: Verifier Queue Query Latency
     */
    public function testBenchmark03VerifierQueueQueryLatency(): void
    {
        $start = microtime(true);

        $res = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->get('api/v1/verifier/submissions/assigned');

        $elapsedMs = (microtime(true) - $start) * 1000;

        $res->assertStatus(200);
        $this->assertLessThan(500.0, $elapsedMs, "Verifier queue query took {$elapsedMs}ms (expected < 500ms)");
    }

    /**
     * BENCH-04: Executive Summary Report Aggregation Latency
     */
    public function testBenchmark04ExecutiveReportSummaryLatency(): void
    {
        $start = microtime(true);

        $res = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/reports/summary');

        $elapsedMs = (microtime(true) - $start) * 1000;

        $res->assertStatus(200);
        $this->assertLessThan(500.0, $elapsedMs, "Executive report summary took {$elapsedMs}ms (expected < 500ms)");
    }

    /**
     * BENCH-05: Streaming CSV Export Latency
     */
    public function testBenchmark05StreamingExportCsvLatency(): void
    {
        $start = microtime(true);

        $res = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/reports/export?type=submissions&format=csv');

        $elapsedMs = (microtime(true) - $start) * 1000;

        $res->assertStatus(200);
        $this->assertLessThan(500.0, $elapsedMs, "Streaming CSV export took {$elapsedMs}ms (expected < 500ms)");
    }
}
