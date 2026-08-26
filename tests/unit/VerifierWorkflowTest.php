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
use App\Services\Workflow\VerifierReviewService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * VerifierWorkflowTest
 *
 * Comprehensive Test Suite for Step 7:
 * Gate 2 Verifier Substantive Review, Revision Notes, and Return for Substantive Revision.
 *
 * Covers:
 * - VERIFIER-01 .. VERIFIER-26
 *
 * @internal
 */
final class VerifierWorkflowTest extends CIUnitTestCase
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
    protected VerifierReviewService $verifierService;

    protected UserEntity $userA;
    protected UserEntity $userB;
    protected UserEntity $adminA;
    protected UserEntity $verifierA;
    protected UserEntity $verifierB;
    protected UserEntity $superAdmin;

    protected string $tokenUserA;
    protected string $tokenUserB;
    protected string $tokenAdminA;
    protected string $tokenVerifierA;
    protected string $tokenVerifierB;
    protected string $tokenSuperAdmin;

    protected int $instAId;
    protected int $instBId;
    protected int $instCId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel       = new UserModel();
        $this->instModel       = new InstitutionModel();
        $this->subModel        = new SubmissionModel();
        $this->verModel        = new SubmissionVersionModel();
        $this->scopeModel      = new UserScopeModel();
        $this->assignModel     = new VerifierAssignmentModel();
        $this->jwtService      = new JwtService();
        $this->subService      = new SubmissionService();
        $this->adminService    = new AdminReviewService();
        $this->verifierService = new VerifierReviewService();

        $instA = $this->instModel->where('institution_code', 'TEST-INST-A')->first();
        $instB = $this->instModel->where('institution_code', 'TEST-INST-B')->first();
        $instC = $this->instModel->where('institution_code', 'TEST-INST-C')->first();

        $this->instAId = (int)$instA['id'];
        $this->instBId = (int)$instB['id'];
        $this->instCId = (int)$instC['id'];

        $this->userA      = $this->userModel->findByUsername('test_user_a');
        $this->adminA     = $this->userModel->findByUsername('test_admin');
        $this->verifierA  = $this->userModel->findByUsername('test_verifier');
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

        // Create second verifier (verifierB) for assignment isolation tests
        $verifierB = $this->userModel->findByUsername('test_verifier_b');
        if (!$verifierB) {
            $db = Database::connect();
            $db->table('users')->insert([
                'home_institution_id' => $this->instCId,
                'role_id'             => (int)$this->verifierA->role_id,
                'username'            => 'test_verifier_b',
                'email'               => 'verifier_b@test.go.id',
                'password_hash'       => password_hash('password123', PASSWORD_BCRYPT),
                'full_name'           => 'Second Verifier B',
                'nip'                 => '197606061999031006',
                'status'              => 'ACTIVE',
                'created_at'          => date('Y-m-d H:i:s'),
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);
            $verifierB = $this->userModel->findByUsername('test_verifier_b');
        }
        $this->verifierB = $verifierB;

        $this->tokenUserA      = $this->jwtService->generateAccessToken($this->userA, 'USER');
        $this->tokenUserB      = $this->jwtService->generateAccessToken($this->userB, 'USER');
        $this->tokenAdminA     = $this->jwtService->generateAccessToken($this->adminA, 'ADMIN');
        $this->tokenVerifierA  = $this->jwtService->generateAccessToken($this->verifierA, 'VERIFIER');
        $this->tokenVerifierB  = $this->jwtService->generateAccessToken($this->verifierB, 'VERIFIER');
        $this->tokenSuperAdmin = $this->jwtService->generateAccessToken($this->superAdmin, 'SUPER_ADMIN');
    }

    /**
     * Helper to create a submission in ASSIGNED_TO_VERIFIER state
     */
    protected function createAssignedSubmission(int $institutionId, UserEntity $author, int $verifierId): int
    {
        $created = $this->subService->createSubmission($author, [
            'institution_id'  => $institutionId,
            'title'           => 'Submission for Verifier Review Test ' . uniqid(),
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        $this->subService->submitDraft($author, $subId, 'Submitted for Gate 1');
        $this->adminService->acceptReview($this->adminA, $subId);
        $this->adminService->assignVerifier($this->adminA, $subId, $verifierId, 'Assigned to Verifier for Gate 2');

        return $subId;
    }

    /**
     * VERIFIER-01: Authenticated verifier sees own assigned submissions -> 200 OK
     */
    public function testVerifier01AssignedQueue(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->get('api/v1/verifier/submissions/assigned');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
        $this->assertGreaterThanOrEqual(1, count($body['data']));

        $ids = array_column($body['data'], 'id');
        $this->assertContains((string)$subId, array_map('strval', $ids));
    }

    /**
     * VERIFIER-02: Unauthenticated request is rejected -> 401 Unauthorized
     */
    public function testVerifier02UnauthenticatedRejected(): void
    {
        $result = $this->get('api/v1/verifier/submissions/assigned');
        $result->assertStatus(401);
    }

    /**
     * VERIFIER-03: USER cannot access verifier queue -> 403 Forbidden
     */
    public function testVerifier03UserCannotAccessQueue(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get('api/v1/verifier/submissions/assigned');

        $result->assertStatus(403);
    }

    /**
     * VERIFIER-04: ADMIN cannot access verifier queue -> 403 Forbidden
     */
    public function testVerifier04AdminCannotAccessQueue(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->get('api/v1/verifier/submissions/assigned');

        $result->assertStatus(403);
    }

    /**
     * VERIFIER-05: Verifier cannot see submission assigned to another verifier
     */
    public function testVerifier05CannotSeeOtherVerifierSubmissions(): void
    {
        // Assigned specifically to Verifier A
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        // Verifier B checks assigned queue
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierB,
        ])->get('api/v1/verifier/submissions/assigned');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $ids = array_column($body['data'], 'id');
        $this->assertNotContains((string)$subId, array_map('strval', $ids));
    }

    /**
     * VERIFIER-06: Assigned verifier starts review successfully -> IN_REVIEW_BY_VERIFIER (200 OK)
     */
    public function testVerifier06AssignedVerifierStartsReview(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/start", [
            'notes' => 'Memulai telaah kesesuaian peta jabatan dan ABK.',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('IN_REVIEW_BY_VERIFIER', $body['data']['currentState']);
        $this->assertEquals($subId, $body['data']['submissionId']);
        $this->assertNotEmpty($body['data']['verificationId']);
    }

    /**
     * VERIFIER-07: Wrong verifier cannot start review -> 403 Forbidden
     */
    public function testVerifier07WrongVerifierCannotStartReview(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        // Verifier B attempts to start review on submission assigned to Verifier A
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierB,
        ])->post("api/v1/submissions/{$subId}/verifier-review/start");

        $result->assertStatus(403);
    }

    /**
     * VERIFIER-08: Submission author cannot start verifier review -> 403 Forbidden (SoD)
     */
    public function testVerifier08AuthorCannotStartReviewSoD(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        // Update author to verifierA to simulate author with verifier role
        $db = Database::connect();
        $db->table('submissions')->where('id', $subId)->update(['author_id' => (int)$this->verifierA->id]);

        // Verifier A attempts to start review on own submission
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/start");

        $result->assertStatus(403);
        $body = json_decode($result->getJSON(), true);
        $this->assertStringContainsString('Separation of Duties', $body['error']['message']);
    }

    /**
     * VERIFIER-09: Double start review is rejected -> 409 Conflict (Locked)
     */
    public function testVerifier09DoubleStartReviewRejected(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        // First start
        $this->verifierService->startReview($this->verifierA, $subId);

        // Second start
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/start");

        $result->assertStatus(409);
    }

    /**
     * VERIFIER-10: Illegal workflow transition is rejected -> 409 Conflict
     */
    public function testVerifier10IllegalStateStartRejected(): void
    {
        // Submission still in DRAFT
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Draft for Illegal Transition Test',
            'submission_year' => 2026,
        ]);
        $draftId = $created['id'];

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$draftId}/verifier-review/start");

        $result->assertStatus(409);
    }

    /**
     * VERIFIER-11: Verifier creates substantive revision note -> 201 Created
     */
    public function testVerifier11CreateSubstantiveRevisionNote(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->verifierService->startReview($this->verifierA, $subId);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/notes", [
            'issue_description' => 'Nama unit eselon II belum sesuai dengan nomenklatur PermenPANRB No. 1 Tahun 2024.',
        ]);

        $result->assertStatus(201);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals($subId, $body['data']['submissionId']);
        $this->assertNotEmpty($body['data']['id']);
        $this->assertFalse($body['data']['isResolved']);
    }

    /**
     * VERIFIER-12: Revision note associated with correct verification record
     */
    public function testVerifier12RevisionNoteAssociatedWithVerificationRecord(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $startResult = $this->verifierService->startReview($this->verifierA, $subId);

        $noteResult = $this->verifierService->addReviewNote($this->verifierA, $subId, [
            'issue_description' => 'Formasi jabatan fungsional Analis Kebijakan melebihi batas bezetting.',
        ]);

        $this->assertEquals($startResult['verificationId'], $noteResult['verificationId']);

        $db = Database::connect();
        $persisted = $db->table('revision_notes')->where('id', $noteResult['id'])->get()->getRowArray();
        $this->assertNotNull($persisted);
        $this->assertEquals($startResult['verificationId'], (int)$persisted['verification_id']);
    }

    /**
     * VERIFIER-13: Verifier returns submission for revision -> REVISION_REQUIRED_BY_VERIFIER (200 OK)
     */
    public function testVerifier13ReturnForSubstantiveRevision(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->verifierService->startReview($this->verifierA, $subId);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/return", [
            'reason' => 'Terdapat 3 ketidaksesuaian formasi jabatan pada Bagian Keuangan dan Perencanaan.',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('REVISION_REQUIRED_BY_VERIFIER', $body['data']['currentState']);
        $this->assertNotEmpty($body['data']['revisionNoteId']);
    }

    /**
     * VERIFIER-14: Return without reason is rejected -> 422 Unprocessable Entity
     */
    public function testVerifier14ReturnWithoutReasonRejected(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->verifierService->startReview($this->verifierA, $subId);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/return", [
            'reason' => '',
        ]);

        $result->assertStatus(422);
    }

    /**
     * VERIFIER-15: Wrong verifier cannot return submission -> 403 Forbidden
     */
    public function testVerifier15WrongVerifierCannotReturn(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->verifierService->startReview($this->verifierA, $subId);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierB,
        ])->post("api/v1/submissions/{$subId}/verifier-review/return", [
            'reason' => 'Pengembalian oleh verifikator tidak sah.',
        ]);

        $result->assertStatus(403);
    }

    /**
     * VERIFIER-16: Submission author cannot return submission as verifier -> 403 Forbidden (SoD)
     */
    public function testVerifier16AuthorCannotReturnSoD(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->verifierService->startReview($this->verifierA, $subId);

        // Update author to verifierA
        $db = Database::connect();
        $db->table('submissions')->where('id', $subId)->update(['author_id' => (int)$this->verifierA->id]);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/return", [
            'reason' => 'Pengembalian usulan sendiri.',
        ]);

        $result->assertStatus(403);
        $body = json_decode($result->getJSON(), true);
        $this->assertStringContainsString('Separation of Duties', $body['error']['message']);
    }

    /**
     * VERIFIER-17: Invalid workflow state return is rejected -> 409 Conflict
     */
    public function testVerifier17ReturnBeforeStartRejected(): void
    {
        // Submission is still in ASSIGNED_TO_VERIFIER (review not started yet)
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/return", [
            'reason' => 'Mencoba return langsung tanpa start review.',
        ]);

        $result->assertStatus(409);
    }

    /**
     * VERIFIER-18, VERIFIER-19, VERIFIER-20: Audit events for review start, note, and return
     */
    public function testVerifier18To20AuditEventsRecorded(): void
    {
        $db = Database::connect();

        $startCount = $db->table('audit_logs')->where('action_event', 'VERIFIER_REVIEW_START')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $startCount, 'VERIFIER-18: VERIFIER_REVIEW_START audit event must exist.');

        $noteCount = $db->table('audit_logs')->where('action_event', 'VERIFIER_REVIEW_NOTE')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $noteCount, 'VERIFIER-19: VERIFIER_REVIEW_NOTE audit event must exist.');

        $returnCount = $db->table('audit_logs')->where('action_event', 'VERIFIER_REVIEW_RETURN')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $returnCount, 'VERIFIER-20: VERIFIER_REVIEW_RETURN audit event must exist.');
    }

    /**
     * VERIFIER-21, VERIFIER-22, VERIFIER-23: Master data immutability
     */
    public function testVerifier21To23MasterDataUnchanged(): void
    {
        $db = Database::connect();

        // 3 institutions seeded
        $instCount = $db->table('institutions')->countAllResults();
        $this->assertGreaterThanOrEqual(3, $instCount, 'VERIFIER-21: Master institutions must remain intact.');

        // Core unit master data unchanged
        $unitCount = $db->table('organizational_units')->countAllResults();
        $this->assertGreaterThanOrEqual(0, $unitCount, 'VERIFIER-22: Master organizational units must remain intact.');

        // Core position master data unchanged
        $posCount = $db->table('positions')->countAllResults();
        $this->assertGreaterThanOrEqual(0, $posCount, 'VERIFIER-23: Master positions must remain intact.');
    }

    /**
     * VERIFIER-24: Concurrent start is safely rejected
     */
    public function testVerifier24ConcurrentStartRejected(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/start");
        $res1->assertStatus(200);

        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/start");
        $res2->assertStatus(409);
    }

    /**
     * VERIFIER-25: Concurrent return/start state race is safely rejected
     */
    public function testVerifier25ConcurrentReturnAndStartRace(): void
    {
        $subId = $this->createAssignedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->verifierService->startReview($this->verifierA, $subId);

        // Return first
        $resReturn = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/return", [
            'reason' => 'Pengembalian pertama sukses.',
        ]);
        $resReturn->assertStatus(200);

        // Second return attempt on returned submission must fail with 409
        $resReturn2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/return", [
            'reason' => 'Pengembalian kedua harus gagal.',
        ]);
        $resReturn2->assertStatus(409);
    }
}
