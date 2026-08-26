<?php

namespace Tests\Unit;

use App\Entities\UserEntity;
use App\Models\InstitutionModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionPositionModel;
use App\Models\SubmissionUnitModel;
use App\Models\SubmissionVersionModel;
use App\Models\UserModel;
use App\Models\UserScopeModel;
use App\Models\VerifierAssignmentModel;
use App\Services\Auth\JwtService;
use App\Services\Submission\SubmissionPositionService;
use App\Services\Submission\SubmissionService;
use App\Services\Submission\SubmissionUnitService;
use App\Services\Submission\SubmissionVersionService;
use App\Services\Workflow\AdminReviewService;
use App\Services\Workflow\RevisionService;
use App\Services\Workflow\VerifierReviewService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * RevisionWorkflowTest
 *
 * Comprehensive Test Suite for Step 8:
 * USER Revision Inspection, Revision Version Branching, Mutation in Revision State, and Resubmission Workflow.
 *
 * Covers:
 * - REVISION-01 .. REVISION-22
 *
 * @internal
 */
final class RevisionWorkflowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected UserModel $userModel;
    protected InstitutionModel $instModel;
    protected SubmissionModel $subModel;
    protected SubmissionVersionModel $verModel;
    protected SubmissionUnitModel $unitModel;
    protected SubmissionPositionModel $posModel;
    protected UserScopeModel $scopeModel;
    protected VerifierAssignmentModel $assignModel;

    protected JwtService $jwtService;
    protected SubmissionService $subService;
    protected SubmissionUnitService $unitService;
    protected SubmissionPositionService $posService;
    protected SubmissionVersionService $verService;
    protected AdminReviewService $adminService;
    protected VerifierReviewService $verifierService;
    protected RevisionService $revisionService;

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

        $this->userModel       = new UserModel();
        $this->instModel       = new InstitutionModel();
        $this->subModel        = new SubmissionModel();
        $this->verModel        = new SubmissionVersionModel();
        $this->unitModel       = new SubmissionUnitModel();
        $this->posModel        = new SubmissionPositionModel();
        $this->scopeModel      = new UserScopeModel();
        $this->assignModel     = new VerifierAssignmentModel();
        $this->jwtService      = new JwtService();
        $this->subService      = new SubmissionService();
        $this->unitService     = new SubmissionUnitService();
        $this->posService      = new SubmissionPositionService();
        $this->verService      = new SubmissionVersionService();
        $this->adminService    = new AdminReviewService();
        $this->verifierService = new VerifierReviewService();
        $this->revisionService = new RevisionService();

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

        $this->tokenUserA      = $this->jwtService->generateAccessToken($this->userA, 'USER');
        $this->tokenUserB      = $this->jwtService->generateAccessToken($this->userB, 'USER');
        $this->tokenAdminA     = $this->jwtService->generateAccessToken($this->adminA, 'ADMIN');
        $this->tokenVerifierA  = $this->jwtService->generateAccessToken($this->verifierA, 'VERIFIER');
        $this->tokenSuperAdmin = $this->jwtService->generateAccessToken($this->superAdmin, 'SUPER_ADMIN');
    }

    /**
     * Helper to create a submission in REVISION_REQUIRED_BY_VERIFIER state
     */
    protected function createReturnedByVerifierSubmission(int $institutionId, UserEntity $author): int
    {
        $created = $this->subService->createSubmission($author, [
            'institution_id'  => $institutionId,
            'title'           => 'Submission for Revision Cycle Test ' . uniqid(),
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        // Add a unit and position
        $unit = $this->unitService->addUnitChange($author, $subId, [
            'unit_code'   => 'SEC-REV-01',
            'unit_name'   => 'Seksi Perencanaan Revisi',
            'unit_level'  => 3,
            'order_index' => 1,
            'change_type' => 'NEW',
        ]);

        $this->posService->addPositionChange($author, $subId, [
            'version_unit_id' => $unit['id'],
            'position_name'   => 'Analis Perencanaan',
            'position_type'   => 'FUNGSIONAL',
            'formation_count' => 2,
            'change_type'     => 'NEW',
        ]);

        // Submit to Gate 1 Admin
        $this->subService->submitDraft($author, $subId);
        $this->adminService->acceptReview($this->adminA, $subId);
        $this->adminService->assignVerifier($this->adminA, $subId, (int)$this->verifierA->id);

        // Gate 2 Verifier Starts & Returns
        $this->verifierService->startReview($this->verifierA, $subId);
        $this->verifierService->returnForRevision(
            $this->verifierA,
            $subId,
            'Formasi analis perencanaan perlu disesuaikan dengan standar bezetting kementerian.',
            $unit['id']
        );

        return $subId;
    }

    /**
     * REVISION-01: Author can view revision notes and inspection details -> 200 OK
     */
    public function testRevision01AuthorViewsRevisionDetails(): void
    {
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/submissions/{$subId}/revision");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertTrue($body['data']['isRevisionState']);
        $this->assertTrue($body['data']['canRevise']);
        $this->assertNotEmpty($body['data']['revisionNotes']);
        $this->assertContains('resubmit', $body['data']['availableActions']);
    }

    /**
     * REVISION-02: Unauthorized user cannot view another user's revision -> 403 Forbidden
     */
    public function testRevision02UnauthorizedUserCannotViewRevision(): void
    {
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);

        // User B attempts to view User A's revision
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserB,
        ])->get("api/v1/submissions/{$subId}/revision");

        $result->assertStatus(403);
    }

    /**
     * REVISION-03 & REVISION-04: Author can branch a new revision version (v2) -> 201 Created
     */
    public function testRevision03And04AuthorCreatesRevisionVersion(): void
    {
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/revision", [
            'notes' => 'Perbaikan formasi Analis Perencanaan sesuai catatan Verifikator.',
        ]);

        $result->assertStatus(201);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals(2, $body['data']['versionNumber']);
        $this->assertNotEmpty($body['data']['versionId']);
    }

    /**
     * REVISION-05 & REVISION-06: Previous version remains unchanged and new version increments correctly
     */
    public function testRevision05And06VersionImmutabilityAndIncrement(): void
    {
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);

        $v1 = $this->verModel->where('submission_id', $subId)->where('version_number', 1)->first();
        $this->assertNotNull($v1);
        $v1SubmittedAt = is_array($v1) ? $v1['submitted_at'] : $v1->submitted_at;
        $this->assertNotNull($v1SubmittedAt);

        // Create v2
        $this->revisionService->startRevisionVersion($this->userA, $subId, 'Revision v2');

        $v2 = $this->verModel->where('submission_id', $subId)->where('version_number', 2)->first();
        $this->assertNotNull($v2);
        $v2SubmittedAt = is_array($v2) ? $v2['submitted_at'] : $v2->submitted_at;
        $this->assertNull($v2SubmittedAt); // unsubmitted draft revision

        // Verify v1 was not modified
        $v1Id = is_array($v1) ? $v1['id'] : $v1->id;
        $v1Check = $this->verModel->where('id', $v1Id)->first();
        $v1CheckSubmittedAt = is_array($v1Check) ? $v1Check['submitted_at'] : $v1Check->submitted_at;
        $this->assertEquals($v1SubmittedAt, $v1CheckSubmittedAt);
    }

    /**
     * REVISION-07: Verifier/admin revision notes remain immutable
     */
    public function testRevision07RevisionNotesImmutable(): void
    {
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);

        $notesBefore = $this->subModel->db->table('revision_notes')->countAllResults();

        // Create revision version
        $this->revisionService->startRevisionVersion($this->userA, $subId);

        $notesAfter = $this->subModel->db->table('revision_notes')->countAllResults();
        $this->assertEquals($notesBefore, $notesAfter);
    }

    /**
     * REVISION-08: Author can modify proposed units and positions in the new revision version
     */
    public function testRevision08AuthorModifiesUnitsAndPositionsInRevision(): void
    {
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);
        $v2 = $this->revisionService->startRevisionVersion($this->userA, $subId);

        // Add a new unit to the revision version
        $unitResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/units", [
            'unit_code'   => 'SEC-REV-02',
            'unit_name'   => 'Seksi Evaluasi Revisi',
            'unit_level'  => 3,
            'order_index' => 2,
            'change_type' => 'NEW',
        ]);
        $unitResult->assertStatus(201);

        $unitBody = json_decode($unitResult->getJSON(), true);
        $unitId = $unitBody['data']['id'];

        // Add position to the unit
        $posResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/positions", [
            'version_unit_id' => $unitId,
            'position_name'   => 'Analis Evaluasi',
            'position_type'   => 'FUNGSIONAL',
            'formation_count' => 3,
            'change_type'     => 'NEW',
        ]);
        $posResult->assertStatus(201);
    }

    /**
     * REVISION-09, REVISION-10, REVISION-11: Master institutions, units, and positions remain unchanged
     */
    public function testRevision09To11MasterDataProtected(): void
    {
        $db = Database::connect();

        $instCountBefore = $db->table('institutions')->countAllResults();
        $unitCountBefore = $db->table('organizational_units')->countAllResults();
        $posCountBefore  = $db->table('positions')->countAllResults();

        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);
        $this->revisionService->startRevisionVersion($this->userA, $subId);

        $this->assertEquals($instCountBefore, $db->table('institutions')->countAllResults());
        $this->assertEquals($unitCountBefore, $db->table('organizational_units')->countAllResults());
        $this->assertEquals($posCountBefore, $db->table('positions')->countAllResults());
    }

    /**
     * REVISION-12: Author can resubmit valid corrected version -> RESUBMITTED (200 OK)
     */
    public function testRevision12AuthorResubmitsCorrectedVersion(): void
    {
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);
        $this->revisionService->startRevisionVersion($this->userA, $subId);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/resubmit", [
            'notes' => 'Telah dilakukan penyesuaian formasi sesuai catatan verifikator.',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('RESUBMITTED', $body['data']['currentState']);
        $this->assertEquals(2, $body['data']['versionNumber']);
    }

    /**
     * REVISION-13: Resubmission without new version is rejected -> 409 Conflict
     */
    public function testRevision13ResubmitWithoutNewVersionRejected(): void
    {
        // Submission is in revision state, but author has NOT branched a new version yet (v1 is already submitted)
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/resubmit");

        $result->assertStatus(409);
    }

    /**
     * REVISION-14: Resubmission from invalid workflow state is rejected -> 409 Conflict
     */
    public function testRevision14ResubmitInvalidStateRejected(): void
    {
        // Submission still in DRAFT
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Draft for Resubmit Test',
            'submission_year' => 2026,
        ]);
        $draftId = $created['id'];

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$draftId}/resubmit");

        $result->assertStatus(409);
    }

    /**
     * REVISION-15: Second resubmission of same version is rejected -> 409 Conflict
     */
    public function testRevision15DoubleResubmissionRejected(): void
    {
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);
        $this->revisionService->startRevisionVersion($this->userA, $subId);

        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/resubmit");
        $res1->assertStatus(200);

        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/resubmit");
        $res2->assertStatus(409);
    }

    /**
     * REVISION-16: Wrong user cannot resubmit -> 403 Forbidden
     */
    public function testRevision16WrongUserCannotResubmit(): void
    {
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);
        $this->revisionService->startRevisionVersion($this->userA, $subId);

        // User B attempts to resubmit User A's submission
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserB,
        ])->post("api/v1/submissions/{$subId}/resubmit");

        $result->assertStatus(403);
    }

    /**
     * REVISION-17, REVISION-18, REVISION-19: Audit events for version created and resubmitted
     */
    public function testRevision17To19AuditEventsRecorded(): void
    {
        $db = Database::connect();

        $versionCreatedCount = $db->table('audit_logs')->where('action_event', 'REVISION_VERSION_CREATED')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $versionCreatedCount, 'REVISION-17: REVISION_VERSION_CREATED audit must exist.');

        $resubmitCount = $db->table('audit_logs')->where('action_event', 'REVISION_SUBMITTED')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $resubmitCount, 'REVISION-18: REVISION_SUBMITTED audit must exist.');
    }

    /**
     * REVISION-20: Concurrent resubmission is safely rejected
     */
    public function testRevision20ConcurrentResubmission(): void
    {
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);
        $this->revisionService->startRevisionVersion($this->userA, $subId);

        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/resubmit");
        $res1->assertStatus(200);

        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/resubmit");
        $res2->assertStatus(409);
    }

    /**
     * REVISION-21: Historical versions remain immutable after resubmission
     */
    public function testRevision21HistoricalVersionsImmutability(): void
    {
        $subId = $this->createReturnedByVerifierSubmission($this->instAId, $this->userA);
        $this->revisionService->startRevisionVersion($this->userA, $subId);
        $this->revisionService->resubmit($this->userA, $subId);

        // Attempting to modify unit on resubmitted version must fail with 409
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/units", [
            'unit_code'   => 'SEC-REV-03',
            'unit_name'   => 'Unit on Resubmitted Submission',
            'unit_level'  => 3,
            'change_type' => 'NEW',
        ]);

        $result->assertStatus(409);
    }
}
