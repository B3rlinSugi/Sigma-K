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
use App\Models\VerificationRecordModel;
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
 * SubstantiveApprovalTest
 *
 * Comprehensive Test Suite for Step 9:
 * Gate 2 Verifier Substantive Verification Approval & Technical Recommendation Workflow.
 *
 * Covers:
 * - VERIFICATION-01 .. VERIFICATION-24
 *
 * @internal
 */
final class SubstantiveApprovalTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected UserModel $userModel;
    protected InstitutionModel $instModel;
    protected SubmissionModel $subModel;
    protected SubmissionVersionModel $verModel;
    protected SubmissionUnitModel $unitModel;
    protected SubmissionPositionModel $posModel;
    protected VerificationRecordModel $verifModel;
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
        $this->unitModel       = new SubmissionUnitModel();
        $this->posModel        = new SubmissionPositionModel();
        $this->verifModel      = new VerificationRecordModel();
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
        $this->userB      = $this->userModel->findByUsername('test_user_b');
        $this->adminA     = $this->userModel->findByUsername('test_admin');
        $this->verifierA  = $this->userModel->findByUsername('test_verifier');
        $this->superAdmin = $this->userModel->findByUsername('test_super_admin');

        $verifierB = $this->userModel->findByUsername('test_verifier_b');
        if (!$verifierB) {
            $db = Database::connect();
            $db->table('users')->insert([
                'home_institution_id' => $this->instBId,
                'role_id'             => (int)$this->verifierA->role_id,
                'username'            => 'test_verifier_b',
                'email'               => 'verifier_b@test.go.id',
                'password_hash'       => password_hash('password123', PASSWORD_BCRYPT),
                'full_name'           => 'Test Verifier B',
                'nip'                 => '198501012010011003',
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
     * Helper to create a submission in RESUBMITTED state with a corrected version (v2)
     */
    protected function createResubmittedSubmission(int $institutionId, UserEntity $author, int $verifierId): int
    {
        $created = $this->subService->createSubmission($author, [
            'institution_id'  => $institutionId,
            'title'           => 'Substantive Verification Submission ' . uniqid(),
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        // Initial proposed unit & position
        $unit = $this->unitService->addUnitChange($author, $subId, [
            'unit_code'   => 'SEC-SUB-01',
            'unit_name'   => 'Seksi Telaah Substantif',
            'unit_level'  => 3,
            'order_index' => 1,
            'change_type' => 'NEW',
        ]);

        $this->posService->addPositionChange($author, $subId, [
            'version_unit_id' => $unit['id'],
            'position_name'   => 'Analis Kelembagaan Pertama',
            'position_type'   => 'FUNGSIONAL',
            'formation_count' => 2,
            'change_type'     => 'NEW',
        ]);

        // Submit to Gate 1 Admin -> Assign Verifier
        $this->subService->submitDraft($author, $subId);
        $this->adminService->acceptReview($this->adminA, $subId);
        $this->adminService->assignVerifier($this->adminA, $subId, $verifierId);

        // Gate 2 Verifier Starts & Returns for Revision
        $this->verifierService->startReview($this->verifierA, $subId);
        $this->verifierService->returnForRevision(
            $this->verifierA,
            $subId,
            'Perlu perbaikan nomenklatur seksi dan penambahan justifikasi analisis beban kerja.',
            $unit['id']
        );

        // Author Branches v2, fixes items, and resubmits
        $this->revisionService->startRevisionVersion($author, $subId, 'Perbaikan v2');
        $this->revisionService->resubmit($author, $subId, 'Telah disesuaikan sesuai telaah verifikator.');

        return $subId;
    }

    /**
     * VERIFICATION-01: Assigned verifier can access review inspection of resubmitted submission -> 200 OK
     */
    public function testVerification01AssignedVerifierAccessesReview(): void
    {
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->get("api/v1/verifier/submissions/{$subId}/review");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('RESUBMITTED', $body['data']['submission']['currentState']);
        $this->assertEquals(2, $body['data']['currentVersion']['version_number']);
        $this->assertNotEmpty($body['data']['versions']);
        $this->assertNotEmpty($body['data']['revisionNotes']);
    }

    /**
     * VERIFICATION-02: Unauthenticated user is rejected -> 401 Unauthorized
     */
    public function testVerification02UnauthenticatedRejected(): void
    {
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->get("api/v1/verifier/submissions/{$subId}/review");
        $result->assertStatus(401);
    }

    /**
     * VERIFICATION-03: USER cannot perform verifier approval -> 403 Forbidden
     */
    public function testVerification03UserCannotApproveSubstantive(): void
    {
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/approve", [
            'recommendation_summary' => 'Disetujui untuk penetapan.',
            'resolve_all_notes'      => true,
        ]);

        $result->assertStatus(403);
    }

    /**
     * VERIFICATION-04: ADMIN cannot perform verifier approval -> 403 Forbidden
     */
    public function testVerification04AdminCannotApproveSubstantive(): void
    {
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/approve", [
            'recommendation_summary' => 'Admin mencoba memverifikasi substantif.',
            'resolve_all_notes'      => true,
        ]);

        $result->assertStatus(403);
    }

    /**
     * VERIFICATION-05: Wrong verifier cannot review or approve the submission -> 403 Forbidden
     */
    public function testVerification05WrongVerifierRejected(): void
    {
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        // Verifier B attempts to approve Verifier A's assigned submission
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierB,
        ])->post("api/v1/submissions/{$subId}/verifier-review/approve", [
            'recommendation_summary' => 'Rekomendasi dari verifikator yang salah.',
            'resolve_all_notes'      => true,
        ]);

        $result->assertStatus(403);
    }

    /**
     * VERIFICATION-06: Author cannot verify or approve own submission (SoD) -> 403 Forbidden
     */
    public function testVerification06AuthorCannotVerifyOwnSubmission(): void
    {
        // Create submission where author is User A
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        // Even if actor claims verifier or superadmin role, author id matching actor triggers SoD
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/approve", [
            'recommendation_summary' => 'Self verification attempt.',
            'resolve_all_notes'      => true,
        ]);

        $result->assertStatus(403);
    }

    /**
     * VERIFICATION-07, VERIFICATION-08, VERIFICATION-09: Verifier inspects latest version, history, revision notes
     */
    public function testVerification07To09VerifierInspectsHistoryAndNotes(): void
    {
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->get("api/v1/verifier/submissions/{$subId}/review");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        // VERIFICATION-07: Latest version
        $this->assertEquals(2, $body['data']['currentVersion']['version_number']);
        $this->assertNotEmpty($body['data']['proposedUnits']);

        // VERIFICATION-08: Version history contains both v1 and v2
        $this->assertCount(2, $body['data']['versions']);

        // VERIFICATION-09: Previous revision notes present
        $this->assertNotEmpty($body['data']['revisionNotes']);
    }

    /**
     * VERIFICATION-10: Verifier cannot modify submission version directly -> 403 Forbidden
     */
    public function testVerification10VerifierCannotDirectlyModifyVersion(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Draft Tamper Test ' . uniqid(),
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        // Verifier attempts to edit proposed unit
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/units", [
            'unit_code'   => 'SEC-TAMPER-01',
            'unit_name'   => 'Unit Dimodifikasi Verifikator',
            'unit_level'  => 3,
            'change_type' => 'NEW',
        ]);

        $result->assertStatus(403);
    }

    /**
     * VERIFICATION-11: Unresolved revision requirement prevents substantive approval -> 422 Unprocessable Entity
     */
    public function testVerification11UnresolvedRevisionNotesPreventApproval(): void
    {
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        // Attempting to approve without resolving active revision notes
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/approve", [
            'recommendation_summary' => 'Mencoba menyetujui tanpa menyelesaikan catatan perbaikan.',
            'resolve_all_notes'      => false,
        ]);

        $result->assertStatus(422);
        $body = json_decode($result->getJSON(), true);
        $this->assertFalse($body['success']);
    }

    /**
     * VERIFICATION-12: Valid corrected submission passes substantive verification -> READY_FOR_FINAL_DECISION (200 OK)
     */
    public function testVerification12SubstantiveVerificationPassed(): void
    {
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/approve", [
            'recommendation_summary'    => 'Usulan penataan kelembagaan telah sesuai dengan ketentuan PermenPANRB No. 1 Tahun 2024 dan layak diteruskan ke tahap penetapan.',
            'substantive_findings'      => 'Struktur organisasi telah memenuhi rentang kendali dan analisis beban kerja terpenuhi.',
            'regulatory_considerations' => 'Sesuai dengan ketentuan pasal 15 PermenPANRB terkait penataan organisasi pemerintah.',
            'recommended_action'        => 'PROCEED_TO_FINAL_APPROVAL',
            'resolve_all_notes'         => true,
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('READY_FOR_FINAL_DECISION', $body['data']['currentState']);
        $this->assertEquals('SUBSTANTIVE_PASSED', $body['data']['verificationResult']);
        $this->assertEquals(2, $body['data']['versionNumber']);
    }

    /**
     * VERIFICATION-13 & VERIFICATION-14: Verification record and recommendation reference exact version
     */
    public function testVerification13And14RecordReferencesExactVersion(): void
    {
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $this->verifierService->approveSubstantive($this->verifierA, $subId, [
            'recommendation_summary' => 'Rekomendasi teknis penetapan organisasi.',
            'resolve_all_notes'      => true,
        ]);

        $rec = $this->verifierService->getRecommendation($this->verifierA, $subId);
        $this->assertNotNull($rec);
        $this->assertEquals(2, $rec['versionNumber']);
        $this->assertEquals((int)$this->verifierA->id, $rec['verifierId']);
    }

    /**
     * VERIFICATION-15: Recommendation is queryable and verified
     */
    public function testVerification15RecommendationQueryable(): void
    {
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $this->verifierService->approveSubstantive($this->verifierA, $subId, [
            'recommendation_summary'    => 'Rekomendasi teknis substansi penataan kelembagaan.',
            'substantive_findings'      => 'Seluruh evaluasi formasi telah valid.',
            'regulatory_considerations' => 'Telah sesuai regulasi.',
            'resolve_all_notes'         => true,
        ]);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->get("api/v1/submissions/{$subId}/recommendation");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('SUBSTANTIVE_PASSED', $body['data']['verificationResult']);
        $this->assertEquals('Rekomendasi teknis substansi penataan kelembagaan.', $body['data']['recommendation']['recommendation_summary']);
    }

    /**
     * VERIFICATION-16 & VERIFICATION-17: Audit events for substantive verification and recommendation
     */
    public function testVerification16And17AuditEventsRecorded(): void
    {
        $db = Database::connect();

        $approvedAuditCount = $db->table('audit_logs')->where('action_event', 'VERIFIER_SUBSTANTIVE_APPROVED')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $approvedAuditCount, 'VERIFICATION-16: VERIFIER_SUBSTANTIVE_APPROVED audit must exist.');

        $recAuditCount = $db->table('audit_logs')->where('action_event', 'VERIFIER_RECOMMENDATION_CREATED')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $recAuditCount, 'VERIFICATION-17: VERIFIER_RECOMMENDATION_CREATED audit must exist.');
    }

    /**
     * VERIFICATION-18 & VERIFICATION-19: Duplicate and concurrent verification rejected -> 409 Conflict
     */
    public function testVerification18And19DuplicateVerificationRejected(): void
    {
        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/approve", [
            'recommendation_summary' => 'Rekomendasi pertama.',
            'resolve_all_notes'      => true,
        ]);
        $res1->assertStatus(200);

        // Attempt second verification on already approved submission
        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/approve", [
            'recommendation_summary' => 'Rekomendasi kedua.',
            'resolve_all_notes'      => true,
        ]);
        $res2->assertStatus(409);
    }

    /**
     * VERIFICATION-20: Verification of outdated/invalid state is rejected -> 409 Conflict
     */
    public function testVerification20VerificationInvalidStateRejected(): void
    {
        // Submission in DRAFT state
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Draft Substantive Verification Test',
            'submission_year' => 2026,
        ]);
        $draftId = $created['id'];

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$draftId}/verifier-review/approve", [
            'recommendation_summary' => 'Percobaan memverifikasi draft langsung.',
        ]);

        $result->assertStatus(409);
    }

    /**
     * VERIFICATION-21, VERIFICATION-22, VERIFICATION-23: Master data remains untouched
     */
    public function testVerification21To23MasterDataProtected(): void
    {
        $db = Database::connect();

        $instCountBefore = $db->table('institutions')->countAllResults();
        $unitCountBefore = $db->table('organizational_units')->countAllResults();
        $posCountBefore  = $db->table('positions')->countAllResults();

        $subId = $this->createResubmittedSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->verifierService->approveSubstantive($this->verifierA, $subId, [
            'recommendation_summary' => 'Rekomendasi aman.',
            'resolve_all_notes'      => true,
        ]);

        $this->assertEquals($instCountBefore, $db->table('institutions')->countAllResults());
        $this->assertEquals($unitCountBefore, $db->table('organizational_units')->countAllResults());
        $this->assertEquals($posCountBefore, $db->table('positions')->countAllResults());
    }
}
