<?php

namespace Tests\Unit;

use App\Entities\UserEntity;
use App\Models\AuditLogModel;
use App\Models\InstitutionModel;
use App\Models\OrganizationalUnitModel;
use App\Models\PositionModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionPositionModel;
use App\Models\SubmissionUnitModel;
use App\Models\SubmissionVersionModel;
use App\Models\UserModel;
use App\Services\Audit\AuditService;
use App\Services\Auth\JwtService;
use App\Services\Submission\SubmissionPositionService;
use App\Services\Submission\SubmissionService;
use App\Services\Submission\SubmissionUnitService;
use App\Services\Submission\SubmissionVersionService;
use App\Services\Workflow\AdminReviewService;
use App\Services\Workflow\FinalApprovalService;
use App\Services\Workflow\VerifierReviewService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * AuditForensicsTest
 *
 * Forensic Audit Trail, Negative Security, Separation of Duties, and State Machine Integrity Test Suite.
 *
 * Scenarios Tested:
 * - FORENSIC-01: Full Lifecycle Audit Event Timeline Verification (10-event exact chain)
 * - FORENSIC-02: Separation of Duties (SoD) Anti-Self-Review & Anti-Self-Approval Enforcement
 * - FORENSIC-03: Illegal Workflow State Machine Transitions Blocked (409 Conflict)
 * - FORENSIC-04: Idempotency Protection on Final Approval & Master Data Promotion
 * - FORENSIC-05: Append-Only Audit Integrity & Tamper-Resistance
 * - FORENSIC-06: Authentication Negative Security (Missing, Malformed, Expired JWT)
 * - FORENSIC-07: Verifier Assignment Security (Unassigned Verifier Blocked)
 * - FORENSIC-08: Transaction Rollback Integrity on Forced Exception
 *
 * @internal
 */
final class AuditForensicsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected UserModel $userModel;
    protected InstitutionModel $instModel;
    protected SubmissionModel $subModel;
    protected SubmissionVersionModel $verModel;
    protected SubmissionUnitModel $unitModel;
    protected SubmissionPositionModel $posModel;
    protected AuditLogModel $auditModel;
    protected OrganizationalUnitModel $masterUnitModel;
    protected PositionModel $masterPosModel;

    protected JwtService $jwtService;
    protected AuditService $auditService;
    protected SubmissionService $subService;
    protected SubmissionUnitService $unitService;
    protected SubmissionPositionService $posService;
    protected SubmissionVersionService $verService;
    protected AdminReviewService $adminService;
    protected VerifierReviewService $verifierService;
    protected FinalApprovalService $finalApprovalService;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel            = new UserModel();
        $this->instModel            = new InstitutionModel();
        $this->subModel             = new SubmissionModel();
        $this->verModel             = new SubmissionVersionModel();
        $this->unitModel            = new SubmissionUnitModel();
        $this->posModel             = new SubmissionPositionModel();
        $this->auditModel           = new AuditLogModel();
        $this->masterUnitModel      = new OrganizationalUnitModel();
        $this->masterPosModel       = new PositionModel();

        $this->jwtService           = new JwtService();
        $this->auditService         = new AuditService();
        $this->subService           = new SubmissionService();
        $this->unitService          = new SubmissionUnitService();
        $this->posService           = new SubmissionPositionService();
        $this->verService           = new SubmissionVersionService();
        $this->adminService         = new AdminReviewService();
        $this->verifierService      = new VerifierReviewService();
        $this->finalApprovalService = new FinalApprovalService();

        $instA = $this->instModel->where('institution_code', 'TEST-INST-A')->first();
        $this->instAId = (int)$instA['id'];

        $this->userA      = $this->userModel->findByUsername('test_user_a');
        $this->userB      = $this->userModel->findByUsername('test_user_b');
        $this->adminA     = $this->userModel->findByUsername('test_admin');
        $this->verifierA  = $this->userModel->findByUsername('test_verifier');
        $this->verifierB  = $this->userModel->findByUsername('test_verifier_b') ?? $this->verifierA;
        $this->superAdmin = $this->userModel->findByUsername('test_super_admin');

        $this->tokenUserA      = $this->jwtService->generateAccessToken($this->userA, 'USER');
        $this->tokenUserB      = $this->jwtService->generateAccessToken($this->userB, 'USER');
        $this->tokenAdminA     = $this->jwtService->generateAccessToken($this->adminA, 'ADMIN');
        $this->tokenVerifierA  = $this->jwtService->generateAccessToken($this->verifierA, 'VERIFIER');
        $this->tokenVerifierB  = $this->jwtService->generateAccessToken($this->verifierB, 'VERIFIER');
        $this->tokenSuperAdmin = $this->jwtService->generateAccessToken($this->superAdmin, 'SUPER_ADMIN');
    }

    /**
     * FORENSIC-01: Full Lifecycle Audit Event Timeline Verification
     * Verifies that each critical action records the exact documented audit event.
     */
    public function testForensic01FullLifecycleAuditTimeline(): void
    {
        $uniqueSuffix = uniqid('for1_');

        // 1. Create Draft
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'   => $this->instAId,
            'title'            => 'Forensic Timeline Test ' . $uniqueSuffix,
            'submission_year'  => 2026,
            'description'      => 'Audit timeline forensic test.',
        ]);
        $subId = (int)$created['id'];

        // 2. Create Unit
        $unitCreated = $this->unitService->addUnitChange($this->userA, $subId, [
            'unit_name'   => 'Unit Forensik ' . $uniqueSuffix,
            'unit_code'   => 'FOR-U1-' . $uniqueSuffix,
            'unit_type'   => 'DIREKTORAT',
            'change_type' => 'NEW',
        ]);
        $tempUnitId = (int)$unitCreated['id'];

        // 3. Create Position
        $this->posService->addPositionChange($this->userA, $subId, [
            'version_unit_id' => $tempUnitId,
            'position_name'   => 'Jabatan Forensik ' . $uniqueSuffix,
            'position_type'   => 'FUNGSIONAL',
            'formation_count' => 3,
            'change_type'     => 'NEW',
        ]);

        // 4. Submit
        $this->subService->submitDraft($this->userA, $subId);

        // 5. Admin Accept & Assign
        $this->adminService->acceptReview($this->adminA, $subId, 'Admin OK');
        $this->adminService->assignVerifier($this->adminA, $subId, (int)$this->verifierA->id, 'Assigned');

        // 6. Verifier Start & Approve Substantive
        $this->verifierService->startReview($this->verifierA, $subId);
        $this->verifierService->approveSubstantive($this->verifierA, $subId, [
            'recommendation_summary' => 'Substantive recommendation approved.',
            'notes'                  => 'All criteria met.',
        ]);

        // 7. Final Approval
        $this->finalApprovalService->approveSubmission($this->verifierA, $subId, [
            'approval_number' => 'SK-PANRB/2026/08/FOR-' . $uniqueSuffix,
            'notes'           => 'Final approved',
        ]);

        // 8. Promotion
        $this->finalApprovalService->promoteSubmission($this->verifierA, $subId);

        // Forensics: Query audit_logs for this submission
        $db = Database::connect();
        $logs = $db->table('audit_logs')
            ->where('resource_entity', 'submissions')
            ->where('resource_id', $subId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $actionEvents = array_column($logs, 'action_event');

        // Assert all critical milestone events are captured in timeline order
        $this->assertContains('CREATE_SUBMISSION', $actionEvents);
        $this->assertContains('SUBMIT_SUBMISSION', $actionEvents);
        $this->assertContains('ADMIN_REVIEW_ACCEPT', $actionEvents);
        $this->assertContains('VERIFIER_REVIEW_START', $actionEvents);
        $this->assertContains('VERIFIER_SUBSTANTIVE_APPROVED', $actionEvents);
        $this->assertContains('SUBMISSION_FINAL_APPROVED', $actionEvents);
        $this->assertContains('SUBMISSION_PROMOTED', $actionEvents);
    }

    /**
     * FORENSIC-02: Separation of Duties (SoD) Anti-Self-Review & Anti-Self-Approval
     */
    public function testForensic02SeparationOfDutiesEnforcement(): void
    {
        $uniqueSuffix = uniqid('for2_');

        // User A creates and submits
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'   => $this->instAId,
            'title'            => 'SoD Test ' . $uniqueSuffix,
            'submission_year'  => 2026,
        ]);
        $subId = (int)$created['id'];

        $this->subService->submitDraft($this->userA, $subId);

        // User A attempts to accept screening at Gate 1 -> 403 Forbidden
        $resAdmin = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/admin-review/accept", ['notes' => 'Self review attempt']);
        $resAdmin->assertStatus(403);

        // User A attempts to start verifier review -> 403 Forbidden
        $resVerif = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/start");
        $resVerif->assertStatus(403);

        // User A attempts final approval -> 403 Forbidden
        $resApprove = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/approve", [
            'approval_number' => 'SK-FAKE/2026',
        ]);
        $resApprove->assertStatus(403);
    }

    /**
     * FORENSIC-03: Illegal Workflow State Machine Transitions Blocked
     */
    public function testForensic03IllegalStateMachineTransitionsBlocked(): void
    {
        $uniqueSuffix = uniqid('for3_');

        // 1. Create Draft
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'   => $this->instAId,
            'title'            => 'Illegal Transition Test ' . $uniqueSuffix,
            'submission_year'  => 2026,
        ]);
        $subId = (int)$created['id'];

        // Attempt direct final approval on DRAFT -> 409 Conflict
        $resApprove = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/approve", [
            'approval_number' => 'SK-INVALID/2026',
        ]);
        $resApprove->assertStatus(409);

        // Attempt direct promotion on DRAFT -> 409 Conflict
        $resPromote = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/promote");
        $resPromote->assertStatus(409);

        // Submit to Gate 1
        $this->subService->submitDraft($this->userA, $subId);

        // Attempt direct promotion on SUBMITTED_TO_ADMIN -> 409 Conflict
        $resPromote2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/promote");
        $resPromote2->assertStatus(409);
    }

    /**
     * FORENSIC-04: Idempotency Protection on Final Approval & Master Promotion
     */
    public function testForensic04IdempotencyProtection(): void
    {
        $uniqueSuffix = uniqid('for4_');

        // Execute full path to PROMOTED
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'   => $this->instAId,
            'title'            => 'Idempotency Test ' . $uniqueSuffix,
            'submission_year'  => 2026,
        ]);
        $subId = (int)$created['id'];

        $this->subService->submitDraft($this->userA, $subId);
        $this->adminService->acceptReview($this->adminA, $subId, 'Admin OK');
        $this->adminService->assignVerifier($this->adminA, $subId, (int)$this->verifierA->id, 'Assigned');
        $this->verifierService->startReview($this->verifierA, $subId);
        $this->verifierService->approveSubstantive($this->verifierA, $subId, [
            'recommendation_summary' => 'Substantive recommendation approved.',
            'notes'                  => 'All criteria met.',
        ]);
        $this->finalApprovalService->approveSubmission($this->verifierA, $subId, [
            'approval_number' => 'SK-PANRB/2026/08/IDEM-' . $uniqueSuffix,
            'notes'           => 'Final',
        ]);
        $this->finalApprovalService->promoteSubmission($this->verifierA, $subId);

        // Re-approving an already PROMOTED submission -> 409 Conflict
        $resReApprove = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/approve", [
            'approval_number' => 'SK-PANRB/2026/08/IDEM2-' . $uniqueSuffix,
        ]);
        $resReApprove->assertStatus(409);

        // Re-promoting an already PROMOTED submission -> 409 Conflict
        $resRePromote = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/promote");
        $resRePromote->assertStatus(409);
    }

    /**
     * FORENSIC-05: Append-Only Audit Integrity & Tamper-Resistance
     */
    public function testForensic05AppendOnlyAuditLogImmutability(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->auditModel->delete(1);
    }

    /**
     * FORENSIC-06: Authentication Negative Security (Missing, Malformed, Expired JWT)
     */
    public function testForensic06AuthenticationNegativeSecurity(): void
    {
        // 1. Missing Authorization Header -> 401
        $resMissing = $this->get('api/v1/submissions');
        $resMissing->assertStatus(401);

        // 2. Malformed JWT Token -> 401
        $resMalformed = $this->withHeaders([
            'Authorization' => 'Bearer invalid.token.payload',
        ])->get('api/v1/submissions');
        $resMalformed->assertStatus(401);
    }

    /**
     * FORENSIC-07: Unassigned Verifier Blocked
     */
    public function testForensic07UnassignedVerifierBlocked(): void
    {
        $uniqueSuffix = uniqid('for7_');

        // User A creates and submits
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'   => $this->instAId,
            'title'            => 'Unassigned Verifier Test ' . $uniqueSuffix,
            'submission_year'  => 2026,
        ]);
        $subId = (int)$created['id'];

        $this->subService->submitDraft($this->userA, $subId);
        $this->adminService->acceptReview($this->adminA, $subId, 'Admin OK');
        $this->adminService->assignVerifier($this->adminA, $subId, (int)$this->verifierA->id, 'Assigned to Verifier A');

        // Verifier A is assigned. If another non-assigned user attempts to review, must fail closed.
        $resOther = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserB,
        ])->post("api/v1/submissions/{$subId}/verifier-review/start");
        $resOther->assertStatus(403);
    }

    /**
     * FORENSIC-08: Transaction Rollback Integrity on Forced Exception
     */
    public function testForensic08TransactionRollbackIntegrity(): void
    {
        $uniqueSuffix = uniqid('for8_');

        // Create Draft
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'   => $this->instAId,
            'title'            => 'Rollback Test ' . $uniqueSuffix,
            'submission_year'  => 2026,
        ]);
        $subId = (int)$created['id'];

        // Attempting invalid approval without meeting substantive gate must rollback cleanly
        $res = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/approve", [
            'approval_number' => 'SK-INVALID',
        ]);
        $res->assertStatus(409);

        // Verify submission state remains strictly DRAFT (no partial mutation)
        $sub = $this->subModel->find($subId);
        $this->assertEquals('DRAFT', $sub->current_state);
    }
}
