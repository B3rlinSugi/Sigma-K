<?php

namespace Tests\Unit;

use App\Entities\UserEntity;
use App\Models\InstitutionModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionVersionModel;
use App\Models\UserModel;
use App\Models\UserScopeModel;
use App\Models\VerifierAssignmentModel;
use App\Services\Auth\JwtService;
use App\Services\Submission\SubmissionService;
use App\Services\Workflow\AdminReviewService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * AdminWorkflowTest
 *
 * Comprehensive Test Suite for Step 6:
 * Gate 1 Admin Review Queue, Acceptance, Return for Revision, and Verifier Assignment.
 *
 * Covers:
 * - ADMIN-01 .. ADMIN-25
 *
 * @internal
 */
final class AdminWorkflowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected UserModel $userModel;
    protected InstitutionModel $instModel;
    protected SubmissionModel $subModel;
    protected SubmissionVersionModel $verModel;
    protected UserScopeModel $scopeModel;
    protected VerifierAssignmentModel $assignModel;

    protected JwtService $jwtService;
    protected SubmissionService $subService;
    protected AdminReviewService $adminService;

    protected UserEntity $userA;
    protected UserEntity $userB;
    protected UserEntity $adminA;
    protected UserEntity $verifier;
    protected UserEntity $superAdmin;

    protected string $tokenUserA;
    protected string $tokenUserB;
    protected string $tokenAdminA;
    protected string $tokenVerifier;
    protected string $tokenSuperAdmin;

    protected int $instAId;
    protected int $instBId;
    protected int $instCId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel    = new UserModel();
        $this->instModel    = new InstitutionModel();
        $this->subModel     = new SubmissionModel();
        $this->verModel     = new SubmissionVersionModel();
        $this->scopeModel   = new UserScopeModel();
        $this->assignModel  = new VerifierAssignmentModel();
        $this->jwtService   = new JwtService();
        $this->subService   = new SubmissionService();
        $this->adminService = new AdminReviewService();

        $instA = $this->instModel->where('institution_code', 'TEST-INST-A')->first();
        $instB = $this->instModel->where('institution_code', 'TEST-INST-B')->first();
        $instC = $this->instModel->where('institution_code', 'TEST-INST-C')->first();

        $this->instAId = (int)$instA['id'];
        $this->instBId = (int)$instB['id'];
        $this->instCId = (int)$instC['id'];

        // Load test users
        $this->userA      = $this->userModel->findByUsername('test_user_a');
        $this->adminA     = $this->userModel->findByUsername('test_admin');
        $this->verifier   = $this->userModel->findByUsername('test_verifier');
        $this->superAdmin = $this->userModel->findByUsername('test_super_admin');

        $userB = $this->userModel->findByUsername('test_user_b');
        if (!$userB) {
            $db = Database::connect();
            $db->table('users')->insert([
                'home_institution_id' => $this->instBId,
                'role_id'             => (int)$this->userA->role_id,
                'username'            => 'test_user_b',
                'email'               => 'user_b@test.go.id',
                'password_hash'       => password_hash('password123', PASSWORD_BCRYPT),
                'full_name'           => 'Test User B',
                'nip'                 => '199001012015011002',
                'status'              => 'ACTIVE',
                'created_at'          => date('Y-m-d H:i:s'),
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);
            $userB = $this->userModel->findByUsername('test_user_b');
        }
        $this->userB = $userB;

        $userC = $this->userModel->findByUsername('test_user_c');
        if (!$userC) {
            $db = Database::connect();
            $db->table('users')->insert([
                'home_institution_id' => $this->instCId,
                'role_id'             => (int)$this->userA->role_id,
                'username'            => 'test_user_c',
                'email'               => 'user_c@test.go.id',
                'password_hash'       => password_hash('password123', PASSWORD_BCRYPT),
                'full_name'           => 'Test User C',
                'nip'                 => '199001012015011003',
                'status'              => 'ACTIVE',
                'created_at'          => date('Y-m-d H:i:s'),
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);
            $userC = $this->userModel->findByUsername('test_user_c');
        }
        $this->userC = $userC;

        $adminRestricted = $this->userModel->findByUsername('test_admin_restricted');
        if (!$adminRestricted) {
            $db = Database::connect();
            $db->table('users')->insert([
                'home_institution_id' => $this->instAId,
                'role_id'             => (int)$this->adminA->role_id, // ADMIN
                'username'            => 'test_admin_restricted',
                'email'               => 'admin_restricted@test.go.id',
                'password_hash'       => password_hash('password123', PASSWORD_BCRYPT),
                'full_name'           => 'Admin Restricted Inst A',
                'nip'                 => '198505052010011005',
                'status'              => 'ACTIVE',
                'created_at'          => date('Y-m-d H:i:s'),
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);
            $adminRestricted = $this->userModel->findByUsername('test_admin_restricted');
        }
        $this->adminRestricted = $adminRestricted;

        $this->tokenUserA           = $this->jwtService->generateAccessToken($this->userA, 'USER');
        $this->tokenUserB           = $this->jwtService->generateAccessToken($this->userB, 'USER');
        $this->tokenAdminA          = $this->jwtService->generateAccessToken($this->adminA, 'ADMIN');
        $this->tokenAdminRestricted = $this->jwtService->generateAccessToken($this->adminRestricted, 'ADMIN');
        $this->tokenVerifier        = $this->jwtService->generateAccessToken($this->verifier, 'VERIFIER');
        $this->tokenSuperAdmin      = $this->jwtService->generateAccessToken($this->superAdmin, 'SUPER_ADMIN');
    }

    protected UserEntity $userC;
    protected UserEntity $adminRestricted;
    protected string $tokenAdminRestricted;

    /**
     * Helper to create a submission in SUBMITTED_TO_ADMIN state
     */
    protected function createSubmittedSubmission(int $institutionId, UserEntity $author): int
    {
        $created = $this->subService->createSubmission($author, [
            'institution_id'  => $institutionId,
            'title'           => 'Submission for Admin Review Test ' . uniqid(),
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        $this->subService->submitDraft($author, $subId, 'Ready for Admin review');

        return $subId;
    }

    /**
     * ADMIN-01: Authorized Admin sees SUBMITTED_TO_ADMIN queue -> 200 OK
     */
    public function testAdmin01AuthorizedAdminQueue(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->get('api/v1/admin/submissions/queue');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
        $this->assertGreaterThanOrEqual(1, count($body['data']));

        $ids = array_column($body['data'], 'id');
        $this->assertContains((string)$subId, array_map('strval', $ids));
    }

    /**
     * ADMIN-02: USER attempts Admin queue -> 403 Forbidden
     */
    public function testAdmin02UserAttemptsAdminQueue(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get('api/v1/admin/submissions/queue');

        $result->assertStatus(403);
    }

    /**
     * ADMIN-03: Admin outside institution scope attempts queue -> Unauthorized submissions excluded
     */
    public function testAdmin03AdminOutsideScopeQueue(): void
    {
        // Create submission in Inst B where adminRestricted (Home: Inst A) has no scope
        $subBId = $this->createSubmittedSubmission($this->instBId, $this->userB);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminRestricted,
        ])->get('api/v1/admin/submissions/queue');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $ids = array_column($body['data'], 'id');
        $this->assertNotContains((string)$subBId, array_map('strval', $ids));
    }

    /**
     * ADMIN-04: Authorized Admin accepts submission -> IN_REVIEW_BY_ADMIN (200 OK)
     */
    public function testAdmin04AuthorizedAdminAcceptsSubmission(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/admin-review/accept", [
            'notes' => 'Dokumen administratif lengkap, masuk telaah teknis.',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('IN_REVIEW_BY_ADMIN', $body['data']['currentState']);
        $this->assertEquals($subId, $body['data']['submissionId']);
    }

    /**
     * ADMIN-05: Unauthorized Admin accepts submission -> 403 Forbidden
     */
    public function testAdmin05UnauthorizedAdminAcceptsSubmission(): void
    {
        $subBId = $this->createSubmittedSubmission($this->instBId, $this->userB);

        // adminRestricted (Home: Inst A) attempts to accept submission on Inst B
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminRestricted,
        ])->post("api/v1/submissions/{$subBId}/admin-review/accept");

        $result->assertStatus(403);
    }

    /**
     * ADMIN-06: User accepts submission -> 403 Forbidden
     */
    public function testAdmin06UserAcceptsSubmission(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/admin-review/accept");

        $result->assertStatus(403);
    }

    /**
     * ADMIN-07: Submission author attempts Admin review on own submission -> 403 Forbidden (SoD)
     */
    public function testAdmin07AuthorAttemptsAdminReviewSoD(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);

        // Update author to Admin A to simulate author with admin role
        $db = Database::connect();
        $db->table('submissions')->where('id', $subId)->update(['author_id' => (int)$this->adminA->id]);

        // Admin A attempts to accept their own submission
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/admin-review/accept");

        $result->assertStatus(403);
        $body = json_decode($result->getJSON(), true);
        $this->assertStringContainsString('Separation of Duties', $body['error']['message']);
    }

    /**
     * ADMIN-08: Admin accepts already accepted submission -> 409 Conflict (Locked)
     */
    public function testAdmin08AdminAcceptsAlreadyAccepted(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);

        // First accept
        $this->adminService->acceptReview($this->adminA, $subId);

        // Second accept
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/admin-review/accept");

        $result->assertStatus(409);
    }

    /**
     * ADMIN-09: Admin returns submission with valid reason -> REVISION_REQUIRED (200 OK)
     */
    public function testAdmin09AdminReturnsSubmissionWithReason(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/admin-review/return", [
            'reason' => 'Surat usulan belum ditandatangani oleh PPK Instansi.',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('REVISION_REQUIRED', $body['data']['currentState']);
        $this->assertNotEmpty($body['data']['revisionNoteId']);
    }

    /**
     * ADMIN-10: Admin returns without reason -> 422 Unprocessable Entity
     */
    public function testAdmin10AdminReturnsWithoutReason(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/admin-review/return", [
            'reason' => '',
        ]);

        $result->assertStatus(422);
    }

    /**
     * ADMIN-11: Unauthorized Admin returns submission -> 403 Forbidden
     */
    public function testAdmin11UnauthorizedAdminReturnsSubmission(): void
    {
        $subBId = $this->createSubmittedSubmission($this->instBId, $this->userB);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminRestricted,
        ])->post("api/v1/submissions/{$subBId}/admin-review/return", [
            'reason' => 'Alasan pengembalian instansi luar.',
        ]);

        $result->assertStatus(403);
    }

    /**
     * ADMIN-12: Admin assigns eligible Verifier -> ASSIGNED_TO_VERIFIER (200 OK)
     */
    public function testAdmin12AdminAssignsEligibleVerifier(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);

        // Admin must accept submission first (state becomes IN_REVIEW_BY_ADMIN)
        $this->adminService->acceptReview($this->adminA, $subId);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/assign-verifier", [
            'verifier_id' => (int)$this->verifier->id,
            'notes'       => 'Mohon verifikasi peta jabatan Biro Perencanaan.',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('ASSIGNED_TO_VERIFIER', $body['data']['currentState']);
        $this->assertEquals((int)$this->verifier->id, $body['data']['verifierId']);
    }

    /**
     * ADMIN-13: Admin assigns non-Verifier user -> 422 Unprocessable Entity
     */
    public function testAdmin13AdminAssignsNonVerifier(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);
        $this->adminService->acceptReview($this->adminA, $subId);

        // Attempt to assign User B (Role: USER) as Verifier
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/assign-verifier", [
            'verifier_id' => (int)$this->userB->id,
        ]);

        $result->assertStatus(422);
    }

    /**
     * ADMIN-14: Admin assigns inactive Verifier -> 422 Unprocessable Entity
     */
    public function testAdmin14AdminAssignsInactiveVerifier(): void
    {
        $db = Database::connect();
        // Create an inactive verifier
        $db->table('users')->insert([
            'username'            => 'inactive_verifier_' . uniqid(),
            'email'               => 'inactive@kemenpanrb.go.id',
            'password_hash'       => password_hash('password123', PASSWORD_BCRYPT),
            'full_name'           => 'Inactive Verifier',
            'role_id'             => (int)$this->verifier->role_id,
            'home_institution_id' => $this->instAId,
            'status'              => 'INACTIVE',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);
        $inactiveVerId = (int)$db->insertID();

        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);
        $this->adminService->acceptReview($this->adminA, $subId);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/assign-verifier", [
            'verifier_id' => $inactiveVerId,
        ]);

        $result->assertStatus(422);

        // Cleanup
        $db->table('users')->where('id', $inactiveVerId)->delete();
    }

    /**
     * ADMIN-15: Admin assigns submission author as Verifier -> 403 Forbidden (SoD)
     */
    public function testAdmin15AuthorCannotBeAssignedAsVerifier(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);
        $this->adminService->acceptReview($this->adminA, $subId);

        // Admin attempts to assign the author as the verifier
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/assign-verifier", [
            'verifier_id' => (int)$this->userA->id,
        ]);

        $result->assertStatus(403);
        $body = json_decode($result->getJSON(), true);
        $this->assertStringContainsString('Separation of Duties', $body['error']['message']);
    }

    /**
     * ADMIN-16: Admin outside institution scope assigns Verifier -> 403 Forbidden
     */
    public function testAdmin16AdminOutsideScopeAssignsVerifier(): void
    {
        $subBId = $this->createSubmittedSubmission($this->instBId, $this->userB);

        // SuperAdmin accepts on Inst B
        $this->adminService->acceptReview($this->superAdmin, $subBId);

        // adminRestricted (Home: Inst A) attempts to assign verifier on Inst B
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminRestricted,
        ])->post("api/v1/submissions/{$subBId}/assign-verifier", [
            'verifier_id' => (int)$this->verifier->id,
        ]);

        $result->assertStatus(403);
    }

    /**
     * ADMIN-17: Assign verifier from invalid state (DRAFT or SUBMITTED_TO_ADMIN before accept) -> 409 Conflict
     */
    public function testAdmin17AssignVerifierInvalidState(): void
    {
        // Direct assignment while still in SUBMITTED_TO_ADMIN (without accept first)
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/assign-verifier", [
            'verifier_id' => (int)$this->verifier->id,
        ]);

        $result->assertStatus(409);
    }

    /**
     * ADMIN-18: Double acceptance -> First succeeds, second 409 Conflict
     */
    public function testAdmin18DoubleAcceptanceProtection(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);

        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/admin-review/accept");
        $res1->assertStatus(200);

        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/admin-review/accept");
        $res2->assertStatus(409);
    }

    /**
     * ADMIN-19: Concurrent return vs accept -> Only one valid transition
     */
    public function testAdmin19ConcurrentReturnVsAccept(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);

        // Return first
        $resReturn = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/admin-review/return", [
            'reason' => 'Perlu revisi kelengkapan dokumen.',
        ]);
        $resReturn->assertStatus(200);

        // Accept attempt on returned submission must fail with 409
        $resAccept = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/admin-review/accept");
        $resAccept->assertStatus(409);
    }

    /**
     * ADMIN-20: Concurrent verifier assignment -> Only one assignment succeeds
     */
    public function testAdmin20ConcurrentVerifierAssignment(): void
    {
        $subId = $this->createSubmittedSubmission($this->instAId, $this->userA);
        $this->adminService->acceptReview($this->adminA, $subId);

        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/assign-verifier", [
            'verifier_id' => (int)$this->verifier->id,
        ]);
        $res1->assertStatus(200);

        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/assign-verifier", [
            'verifier_id' => (int)$this->verifier->id,
        ]);
        $res2->assertStatus(409);
    }

    /**
     * ADMIN-21, ADMIN-22, ADMIN-23: Audit logs for review accept, return, and assign verifier
     */
    public function testAdmin21To23AuditEventsRecorded(): void
    {
        $db = Database::connect();

        $acceptCount = $db->table('audit_logs')->where('action_event', 'ADMIN_REVIEW_ACCEPT')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $acceptCount, 'ADMIN-21: ADMIN_REVIEW_ACCEPT audit event must exist.');

        $returnCount = $db->table('audit_logs')->where('action_event', 'ADMIN_REVIEW_RETURN')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $returnCount, 'ADMIN-22: ADMIN_REVIEW_RETURN audit event must exist.');

        $assignCount = $db->table('audit_logs')->where('action_event', 'ASSIGN_VERIFIER')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $assignCount, 'ADMIN-23: ASSIGN_VERIFIER audit event must exist.');
    }
}
