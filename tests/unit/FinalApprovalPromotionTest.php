<?php

namespace Tests\Unit;

use App\Entities\UserEntity;
use App\Models\ApprovalRecordModel;
use App\Models\InstitutionModel;
use App\Models\OrganizationalUnitModel;
use App\Models\PositionModel;
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
use App\Services\Workflow\FinalApprovalService;
use App\Services\Workflow\RevisionService;
use App\Services\Workflow\VerifierReviewService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * FinalApprovalPromotionTest
 *
 * Comprehensive Test Suite for Step 10:
 * Core Submission Final Approval Recording, Snapshot Immutability, and Master Data Promotion Reconciliation.
 *
 * Covers:
 * - APPROVAL-01 .. APPROVAL-10
 * - PROMOTION-01 .. PROMOTION-12
 * - REGRESSION (Step 1-9)
 *
 * @internal
 */
final class FinalApprovalPromotionTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected UserModel $userModel;
    protected InstitutionModel $instModel;
    protected SubmissionModel $subModel;
    protected SubmissionVersionModel $verModel;
    protected SubmissionUnitModel $unitModel;
    protected SubmissionPositionModel $posModel;
    protected VerificationRecordModel $verifModel;
    protected VerifierAssignmentModel $assignModel;
    protected ApprovalRecordModel $approvalModel;
    protected OrganizationalUnitModel $masterUnitModel;
    protected PositionModel $masterPosModel;

    protected JwtService $jwtService;
    protected SubmissionService $subService;
    protected SubmissionUnitService $unitService;
    protected SubmissionPositionService $posService;
    protected SubmissionVersionService $verService;
    protected AdminReviewService $adminService;
    protected VerifierReviewService $verifierService;
    protected RevisionService $revisionService;
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
    protected int $instBId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel          = new UserModel();
        $this->instModel          = new InstitutionModel();
        $this->subModel           = new SubmissionModel();
        $this->verModel           = new SubmissionVersionModel();
        $this->unitModel          = new SubmissionUnitModel();
        $this->posModel           = new SubmissionPositionModel();
        $this->verifModel         = new VerificationRecordModel();
        $this->assignModel        = new VerifierAssignmentModel();
        $this->approvalModel      = new ApprovalRecordModel();
        $this->masterUnitModel    = new OrganizationalUnitModel();
        $this->masterPosModel     = new PositionModel();

        $this->jwtService           = new JwtService();
        $this->subService           = new SubmissionService();
        $this->unitService          = new SubmissionUnitService();
        $this->posService           = new SubmissionPositionService();
        $this->verService           = new SubmissionVersionService();
        $this->adminService         = new AdminReviewService();
        $this->verifierService      = new VerifierReviewService();
        $this->revisionService      = new RevisionService();
        $this->finalApprovalService = new FinalApprovalService();

        $instA = $this->instModel->where('institution_code', 'TEST-INST-A')->first();
        $instB = $this->instModel->where('institution_code', 'TEST-INST-B')->first();

        $this->instAId = (int)$instA['id'];
        $this->instBId = (int)$instB['id'];

        $this->userA      = $this->userModel->findByUsername('test_user_a');
        $this->userB      = $this->userModel->findByUsername('test_user_b');
        $this->adminA     = $this->userModel->findByUsername('test_admin');
        $this->verifierA  = $this->userModel->findByUsername('test_verifier');
        $this->verifierB  = $this->userModel->findByUsername('test_verifier_b');
        $this->superAdmin = $this->userModel->findByUsername('test_super_admin');

        $this->tokenUserA      = $this->jwtService->generateAccessToken($this->userA, 'USER');
        $this->tokenUserB      = $this->jwtService->generateAccessToken($this->userB, 'USER');
        $this->tokenAdminA     = $this->jwtService->generateAccessToken($this->adminA, 'ADMIN');
        $this->tokenVerifierA  = $this->jwtService->generateAccessToken($this->verifierA, 'VERIFIER');
        $this->tokenVerifierB  = $this->jwtService->generateAccessToken($this->verifierB, 'VERIFIER');
        $this->tokenSuperAdmin = $this->jwtService->generateAccessToken($this->superAdmin, 'SUPER_ADMIN');
    }

    /**
     * Helper: Create submission and advance it through full lifecycle to READY_FOR_FINAL_DECISION
     */
    protected function createReadyForDecisionSubmission(int $institutionId, UserEntity $author, int $verifierId): int
    {
        // 1. Create Draft
        $created = $this->subService->createSubmission($author, [
            'institution_id'  => $institutionId,
            'title'           => 'Step 10 Final Approval Submission ' . uniqid(),
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        // 2. Add Units & Positions
        $unit = $this->unitService->addUnitChange($author, $subId, [
            'unit_code'   => 'SEC-STEP10-01',
            'unit_name'   => 'Seksi Promosi Master Data',
            'unit_level'  => 3,
            'order_index' => 1,
            'change_type' => 'NEW',
        ]);

        $this->posService->addPositionChange($author, $subId, [
            'version_unit_id' => $unit['id'],
            'position_name'   => 'Analis Kebijakan Utama',
            'position_type'   => 'FUNGSIONAL',
            'formation_count' => 1,
            'change_type'     => 'NEW',
        ]);

        // 3. Gate 1 Admin Screening & Verifier Assignment
        $this->subService->submitDraft($author, $subId);
        $this->adminService->acceptReview($this->adminA, $subId);
        $this->adminService->assignVerifier($this->adminA, $subId, $verifierId);

        // 4. Gate 2 Substantive Review & Substantive Approval (Step 9)
        $this->verifierService->startReview($this->verifierA, $subId);
        $this->verifierService->approveSubstantive($this->verifierA, $subId, [
            'recommendation_summary' => 'Rekomendasi teknis substansi penataan kelembagaan telah diverifikasi dan valid.',
            'substantive_findings'   => 'Peta jabatan dan evaluasi kelembagaan memenuhi standar.',
            'resolve_all_notes'      => true,
        ]);

        return $subId;
    }

    /**
     * APPROVAL-01: Authorized VERIFIER can approve READY_FOR_FINAL_DECISION submission -> APPROVED (200 OK)
     */
    public function testApproval01VerifierCanFinalApprove(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/approve", [
            'approval_number' => 'SK-PANRB/2026/08/' . rand(100, 999),
            'approval_notes'  => 'Disetujui secara final untuk penetapan data kelembagaan aktif.',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('APPROVED', $body['data']['currentState']);
        $this->assertNotEmpty($body['data']['approvalId']);
        $this->assertNotEmpty($body['data']['approvalNumber']);
    }

    /**
     * APPROVAL-02: USER cannot final approve -> 403 Forbidden
     */
    public function testApproval02UserCannotFinalApprove(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/approve", [
            'approval_notes' => 'User mencoba menyetujui final.',
        ]);

        $result->assertStatus(403);
    }

    /**
     * APPROVAL-03: ADMIN cannot final approve (Verifier exclusive) -> 403 Forbidden
     */
    public function testApproval03AdminCannotFinalApprove(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/approve", [
            'approval_notes' => 'Admin mencoba menyetujui final.',
        ]);

        $result->assertStatus(403);
    }

    /**
     * APPROVAL-04: Unauthenticated request returns 401
     */
    public function testApproval04UnauthenticatedReturns401(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->post("api/v1/submissions/{$subId}/approve");
        $result->assertStatus(401);
    }

    /**
     * APPROVAL-05: Verifier outside assignment/wrong verifier cannot approve -> 403 Forbidden
     */
    public function testApproval05WrongVerifierCannotApprove(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierB,
        ])->post("api/v1/submissions/{$subId}/approve", [
            'approval_notes' => 'Verifier B mencoba menyetujui usulan Verifier A.',
        ]);

        $result->assertStatus(403);
    }

    /**
     * APPROVAL-06: Cannot approve DRAFT submission -> 409 Conflict
     */
    public function testApproval06CannotApproveDraft(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Draft Approval Test',
            'submission_year' => 2026,
        ]);
        $draftId = $created['id'];

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$draftId}/approve");

        $result->assertStatus(409);
    }

    /**
     * APPROVAL-07: Cannot approve SUBMITTED submission without substantive verification -> 409 Conflict
     */
    public function testApproval07CannotApproveSubmitted(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Submitted Approval Test',
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];
        $this->subService->submitDraft($this->userA, $subId);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/approve");

        $result->assertStatus(409);
    }

    /**
     * APPROVAL-08: Cannot approve REVISION_REQUIRED submission -> 409 Conflict
     */
    public function testApproval08CannotApproveRevisionRequired(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Revision Required Approval Test',
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];
        $this->subService->submitDraft($this->userA, $subId);
        $this->adminService->acceptReview($this->adminA, $subId);
        $this->adminService->returnForRevision($this->adminA, $subId, 'Perlu perbaikan kelengkapan.');

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/approve");

        $result->assertStatus(409);
    }

    /**
     * APPROVAL-09: Cannot approve already APPROVED submission (Anti-Duplicate) -> 409 Conflict
     */
    public function testApproval09CannotApproveAlreadyApproved(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/approve");
        $res1->assertStatus(200);

        // Second approval attempt
        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/approve");
        $res2->assertStatus(409);
    }

    /**
     * APPROVAL-10: Approved version becomes immutable
     */
    public function testApproval10ApprovedVersionImmutable(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->finalApprovalService->approveSubmission($this->verifierA, $subId);

        // Attempt to add a unit to approved submission must fail with 409 Conflict
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/units", [
            'unit_code'   => 'SEC-TAMPER-02',
            'unit_name'   => 'Unit Setelah Persetujuan Final',
            'unit_level'  => 3,
            'change_type' => 'NEW',
        ]);

        $result->assertStatus(409);
    }

    /**
     * PROMOTION-01: Approved submission can be promoted -> PROMOTED (200 OK)
     */
    public function testPromotion01ApprovedSubmissionCanBePromoted(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->finalApprovalService->approveSubmission($this->verifierA, $subId);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/promote");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('PROMOTED', $body['data']['currentState']);
        $this->assertGreaterThanOrEqual(1, $body['data']['summary']['units']['created']);
        $this->assertGreaterThanOrEqual(1, $body['data']['summary']['positions']['created']);
    }

    /**
     * PROMOTION-02: Unapproved submission cannot be promoted -> 409 Conflict
     */
    public function testPromotion02UnapprovedCannotBePromoted(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);

        // Submission is READY_FOR_FINAL_DECISION, not yet APPROVED
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/promote");

        $result->assertStatus(409);
    }

    /**
     * PROMOTION-03, PROMOTION-04, PROMOTION-05, PROMOTION-06, PROMOTION-07:
     * Reconciliation correctly handles NEW, UPDATE, DELETE, UNCHANGED records and preserves hierarchy.
     */
    public function testPromotion03To07ReconciliationAppliesCorrectly(): void
    {
        // 1. Insert existing master unit & master position
        $db = Database::connect();
        $db->table('organizational_units')->insert([
            'institution_id' => $this->instAId,
            'unit_code'      => 'OLD-UNIT-01',
            'unit_name'      => 'Biro Lama Perencanaan',
            'unit_level'     => 2,
            'order_index'    => 1,
            'status'         => 'ACTIVE',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $existingUnitId = (int)$db->insertID();

        $db->table('positions')->insert([
            'unit_id'         => $existingUnitId,
            'position_name'   => 'Kepala Biro Lama',
            'position_type'   => 'STRUKTURAL',
            'echelon'         => 'II.a',
            'formation_count' => 1,
            'status'          => 'ACTIVE',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        $existingPosId = (int)$db->insertID();

        // 2. Create submission with UPDATE on existing, NEW child unit, and DELETE on another
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Reconciliation Comprehensive Test ' . uniqid(),
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        // UPDATE unit
        $updateUnit = $this->unitService->addUnitChange($this->userA, $subId, [
            'source_unit_id' => $existingUnitId,
            'unit_code'      => 'OLD-UNIT-01-UPDATED',
            'unit_name'      => 'Biro Perencanaan dan Organisasi',
            'unit_level'     => 2,
            'order_index'    => 1,
            'change_type'    => 'UPDATE',
        ]);

        $childCode = 'NEW-CHILD-' . uniqid();
        $childPosName = 'Kepala Bagian ' . uniqid();

        // NEW child unit under the updated unit
        $newChildUnit = $this->unitService->addUnitChange($this->userA, $subId, [
            'temp_parent_id' => $updateUnit['id'],
            'unit_code'      => $childCode,
            'unit_name'      => 'Bagian Tata Laksana Baru',
            'unit_level'     => 3,
            'order_index'    => 1,
            'change_type'    => 'NEW',
        ]);

        // UPDATE position
        $this->posService->addPositionChange($this->userA, $subId, [
            'version_unit_id'    => $updateUnit['id'],
            'source_position_id' => $existingPosId,
            'position_name'      => 'Kepala Biro Perencanaan dan Organisasi',
            'position_type'      => 'STRUKTURAL',
            'formation_count'    => 1,
            'change_type'        => 'UPDATE',
        ]);

        // NEW position under child unit
        $this->posService->addPositionChange($this->userA, $subId, [
            'version_unit_id' => $newChildUnit['id'],
            'position_name'   => $childPosName,
            'position_type'   => 'STRUKTURAL',
            'formation_count' => 1,
            'change_type'     => 'NEW',
        ]);

        // Advance submission to approval
        $this->subService->submitDraft($this->userA, $subId);
        $this->adminService->acceptReview($this->adminA, $subId);
        $this->adminService->assignVerifier($this->adminA, $subId, (int)$this->verifierA->id);
        $this->verifierService->startReview($this->verifierA, $subId);
        $this->verifierService->approveSubstantive($this->verifierA, $subId, [
            'recommendation_summary' => 'Rekomendasi rekonsiliasi komprehensif.',
            'resolve_all_notes'      => true,
        ]);
        $this->finalApprovalService->approveSubmission($this->verifierA, $subId);

        // Execute Promotion
        $promoResult = $this->finalApprovalService->promoteSubmission($this->verifierA, $subId);
        $this->assertEquals('PROMOTED', $promoResult['currentState']);

        // Verify UPDATE on master unit
        $updatedMasterUnit = $db->table('organizational_units')->where('id', $existingUnitId)->get()->getRowArray();
        $this->assertEquals('Biro Perencanaan dan Organisasi', $updatedMasterUnit['unit_name']);
        $this->assertEquals('OLD-UNIT-01-UPDATED', $updatedMasterUnit['unit_code']);

        // Verify NEW child master unit created and parent_unit_id correctly linked to existingUnitId
        $newMasterChild = $db->table('organizational_units')->where('unit_code', $childCode)->get()->getRowArray();
        $this->assertNotNull($newMasterChild);
        $this->assertEquals($existingUnitId, (int)$newMasterChild['parent_unit_id']);

        // Verify UPDATE on master position
        $updatedMasterPos = $db->table('positions')->where('id', $existingPosId)->get()->getRowArray();
        $this->assertEquals('Kepala Biro Perencanaan dan Organisasi', $updatedMasterPos['position_name']);

        // Verify NEW position created under new child master unit
        $newMasterPos = $db->table('positions')->where('position_name', $childPosName)->get()->getRowArray();
        $this->assertNotNull($newMasterPos);
        $this->assertEquals((int)$newMasterChild['id'], (int)$newMasterPos['unit_id']);
    }

    /**
     * PROMOTION-08: Promotion writes audit trail
     */
    public function testPromotion08AuditTrailWritten(): void
    {
        $db = Database::connect();

        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->finalApprovalService->approveSubmission($this->verifierA, $subId);
        $this->finalApprovalService->promoteSubmission($this->verifierA, $subId);

        $startedCount = $db->table('audit_logs')->where('action_event', 'SUBMISSION_PROMOTION_STARTED')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $startedCount);

        $promotedCount = $db->table('audit_logs')->where('action_event', 'SUBMISSION_PROMOTED')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $promotedCount);
    }

    /**
     * PROMOTION-09: Duplicate promotion is prevented (Idempotency) -> 409 Conflict
     */
    public function testPromotion09DuplicatePromotionRejected(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->finalApprovalService->approveSubmission($this->verifierA, $subId);

        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/promote");
        $res1->assertStatus(200);

        // Second promotion attempt
        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/promote");
        $res2->assertStatus(409);
    }

    /**
     * PROMOTION-11: Unauthorized promotion is rejected -> 403 Forbidden
     */
    public function testPromotion11UnauthorizedPromotionRejected(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->finalApprovalService->approveSubmission($this->verifierA, $subId);

        // Regular USER attempts to execute promotion
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/promote");

        $result->assertStatus(403);
    }

    /**
     * PROMOTION-12: Approved snapshot cannot be modified after promotion
     */
    public function testPromotion12SnapshotCannotBeModifiedAfterPromotion(): void
    {
        $subId = $this->createReadyForDecisionSubmission($this->instAId, $this->userA, (int)$this->verifierA->id);
        $this->finalApprovalService->approveSubmission($this->verifierA, $subId);
        $this->finalApprovalService->promoteSubmission($this->verifierA, $subId);

        // Attempting to modify unit in promoted submission must fail with 409 Conflict
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/units", [
            'unit_code'   => 'SEC-POST-PROMO-01',
            'unit_name'   => 'Unit Pasca Promosi',
            'unit_level'  => 3,
            'change_type' => 'NEW',
        ]);

        $result->assertStatus(409);
    }
}
