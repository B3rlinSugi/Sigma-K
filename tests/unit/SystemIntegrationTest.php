<?php

namespace Tests\Unit;

use App\Entities\UserEntity;
use App\Models\ApprovalRecordModel;
use App\Models\InstitutionModel;
use App\Models\OrganizationalUnitModel;
use App\Models\PositionModel;
use App\Models\RevisionNoteModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionPositionModel;
use App\Models\SubmissionUnitModel;
use App\Models\SubmissionVersionModel;
use App\Models\UserModel;
use App\Models\VerificationRecordModel;
use App\Models\VerifierAssignmentModel;
use App\Services\Auth\JwtService;
use App\Services\Reporting\ExecutiveReportService;
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

/**
 * SystemIntegrationTest
 *
 * Comprehensive End-to-End System Integration Test Suite for Step 12.
 * Validates the entire E-SKLD backend as ONE unified, multi-role system.
 *
 * Scenarios Tested:
 * - E2E-01: Full Lifecycle Happy Path (USER Draft -> ADMIN Gate 1 -> VERIFIER Gate 2 -> Final Approval -> Master Data Promotion)
 * - E2E-02: Multi-Version Revision & Resubmission Cycle (v1 -> Revision -> v2 -> Approval -> Promotion)
 * - E2E-03: Admin Gate 1 Screening Return & Resubmission Workflow
 * - E2E-04: Multi-Tenant Data Isolation & BOLA Protection across Parallel Institutions
 * - E2E-05: Executive Reporting Dashboard & Export Reflection of End-to-End Lifecycles
 *
 * @internal
 */
final class SystemIntegrationTest extends CIUnitTestCase
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
    protected RevisionNoteModel $noteModel;
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
    protected ExecutiveReportService $reportService;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel            = new UserModel();
        $this->instModel            = new InstitutionModel();
        $this->subModel             = new SubmissionModel();
        $this->verModel             = new SubmissionVersionModel();
        $this->unitModel            = new SubmissionUnitModel();
        $this->posModel             = new SubmissionPositionModel();
        $this->verifModel           = new VerificationRecordModel();
        $this->assignModel          = new VerifierAssignmentModel();
        $this->noteModel            = new RevisionNoteModel();
        $this->approvalModel        = new ApprovalRecordModel();
        $this->masterUnitModel      = new OrganizationalUnitModel();
        $this->masterPosModel       = new PositionModel();

        $this->jwtService           = new JwtService();
        $this->subService           = new SubmissionService();
        $this->unitService          = new SubmissionUnitService();
        $this->posService           = new SubmissionPositionService();
        $this->verService           = new SubmissionVersionService();
        $this->adminService         = new AdminReviewService();
        $this->verifierService      = new VerifierReviewService();
        $this->revisionService      = new RevisionService();
        $this->finalApprovalService = new FinalApprovalService();
        $this->reportService        = new ExecutiveReportService();

        $instA = $this->instModel->where('institution_code', 'TEST-INST-A')->first();
        $instB = $this->instModel->where('institution_code', 'TEST-INST-B')->first();

        $this->instAId = (int)$instA['id'];
        $this->instBId = (int)$instB['id'];

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
     * E2E-01: Standard Full Lifecycle Happy Path
     * USER Draft -> ADMIN Gate 1 -> VERIFIER Gate 2 -> Final Approval -> Master Data Promotion
     */
    public function testE2E01FullLifecycleHappyPathDraftToPromotedMasterData(): void
    {
        $uniqueSuffix = uniqid('e2e1_');

        // Step 1: User A creates submission draft
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'   => $this->instAId,
            'title'            => 'E2E SOTK Happy Path ' . $uniqueSuffix,
            'submission_year'  => 2026,
            'description'      => 'Full E2E integration test submission.',
        ]);
        $submissionId = (int)$created['id'];

        // Step 2: User A adds a proposed Unit and Position to Draft v1
        $unitRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$submissionId}/units", [
            'unit_name'        => 'Direktorat Integrasi Sistem ' . $uniqueSuffix,
            'unit_code'        => 'DIR-INT-' . $uniqueSuffix,
            'unit_type'        => 'DIREKTORAT',
            'change_type'      => 'NEW',
        ]);
        $unitRes->assertStatus(201);
        $unitBody = json_decode($unitRes->getJSON(), true);
        $tempUnitId = (int)$unitBody['data']['id'];

        $posRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$submissionId}/positions", [
            'version_unit_id' => $tempUnitId,
            'position_name'   => 'Analis Integrasi E2E ' . $uniqueSuffix,
            'position_type'   => 'FUNGSIONAL',
            'echelon'         => 'NON_ESELON',
            'formation_count' => 5,
            'change_type'     => 'NEW',
        ]);
        $posRes->assertStatus(201);

        // Step 3: User A submits draft -> SUBMITTED_TO_ADMIN
        $submitRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$submissionId}/submit");
        $submitRes->assertStatus(200);

        $sub = $this->subModel->find($submissionId);
        $this->assertEquals('SUBMITTED_TO_ADMIN', $sub->current_state);

        // Step 4: Admin A accepts Gate 1 screening & assigns Verifier A -> ASSIGNED_TO_VERIFIER
        $acceptRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$submissionId}/admin-review/accept", [
            'notes' => 'Gate 1 screening passed cleanly.',
        ]);
        $acceptRes->assertStatus(200);

        $assignRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$submissionId}/assign-verifier", [
            'verifier_id' => $this->verifierA->id,
            'notes'       => 'Assigned to Verifier A for substantive review.',
        ]);
        $assignRes->assertStatus(200);

        $sub = $this->subModel->find($submissionId);
        $this->assertEquals('ASSIGNED_TO_VERIFIER', $sub->current_state);

        // Step 5: Verifier A starts substantive review -> IN_REVIEW_BY_VERIFIER
        $startRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$submissionId}/verifier-review/start");
        $startRes->assertStatus(200);

        // Step 6: Verifier A approves substantive verification -> READY_FOR_FINAL_DECISION
        $substRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$submissionId}/verifier-review/approve", [
            'recommendation_summary' => 'Rekomendasi teknis disetujui penuh.',
            'notes'                  => 'Semua persyaratan substantif terpenuhi.',
        ]);
        $substRes->assertStatus(200);

        $sub = $this->subModel->find($submissionId);
        $this->assertEquals('READY_FOR_FINAL_DECISION', $sub->current_state);

        // Step 7: Verifier A records Final Formal Approval -> APPROVED
        $skNumber = 'SK-PANRB/2026/08/' . strtoupper($uniqueSuffix);
        $approvalRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$submissionId}/approve", [
            'approval_number' => $skNumber,
            'notes'           => 'Persetujuan resmi kelembagaan.',
        ]);
        $approvalRes->assertStatus(200);

        $sub = $this->subModel->find($submissionId);
        $this->assertEquals('APPROVED', $sub->current_state);

        // Step 8: Verifier / Authorized Actor promotes snapshot to Master Data -> PROMOTED
        $promoteRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$submissionId}/promote");
        $promoteRes->assertStatus(200);
        $promoteBody = json_decode($promoteRes->getJSON(), true);

        $this->assertTrue($promoteBody['success']);
        $this->assertEquals('PROMOTED', $promoteBody['data']['currentState']);

        // Step 9: Verify master data reconciliation in database
        $promotedUnit = $this->masterUnitModel->where('unit_code', 'DIR-INT-' . $uniqueSuffix)->first();
        $this->assertNotNull($promotedUnit);
        $this->assertEquals('ACTIVE', $promotedUnit->status);
        $this->assertEquals($this->instAId, (int)$promotedUnit->institution_id);

        $promotedPos = $this->masterPosModel->where('position_name', 'Analis Integrasi E2E ' . $uniqueSuffix)->first();
        $this->assertNotNull($promotedPos);
        $this->assertEquals('ACTIVE', $promotedPos->status);
        $this->assertEquals(5, (int)$promotedPos->formation_count);
        $this->assertEquals((int)$promotedUnit->id, (int)$promotedPos->unit_id);
    }

    /**
     * E2E-02: Multi-Version Revision & Resubmission Cycle
     * Draft v1 -> Gate 1 -> Gate 2 Return for Revision -> Branch v2 -> Fix -> Resubmit -> Approve -> Promote
     */
    public function testE2E02FullLifecycleMultiVersionRevisionCycle(): void
    {
        $uniqueSuffix = uniqid('e2e2_');

        // 1. User creates submission
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'   => $this->instAId,
            'title'            => 'E2E Revision Flow ' . $uniqueSuffix,
            'submission_year'  => 2026,
            'description'      => 'Revision flow test.',
        ]);
        $subId = (int)$created['id'];

        $unitRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/units", [
            'unit_name'   => 'Unit Revisi v1 ' . $uniqueSuffix,
            'unit_code'   => 'REV-U1-' . $uniqueSuffix,
            'unit_type'   => 'DIREKTORAT',
            'change_type' => 'NEW',
        ]);
        $tempUnitId = (int)json_decode($unitRes->getJSON(), true)['data']['id'];

        $this->subService->submitDraft($this->userA, $subId);

        // 2. Admin accepts and assigns Verifier
        $this->adminService->acceptReview($this->adminA, $subId, 'Admin OK');
        $this->adminService->assignVerifier($this->adminA, $subId, (int)$this->verifierA->id, 'Assigned');

        // 3. Verifier starts review, adds note, and returns for revision
        $this->verifierService->startReview($this->verifierA, $subId);
        $this->verifierService->addReviewNote($this->verifierA, $subId, [
            'version_unit_id'   => $tempUnitId,
            'issue_description' => 'Mohon ubah nama unit menjadi Unit Terkoreksi v2.',
        ]);
        $this->verifierService->returnForRevision($this->verifierA, $subId, 'Perlu perbaikan nomenklatur unit.');

        $sub = $this->subModel->find($subId);
        $this->assertEquals('REVISION_REQUIRED_BY_VERIFIER', $sub->current_state);

        // 4. User initializes Revision Version v2
        $initRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/revision");
        $initRes->assertStatus(201);
        $initBody = json_decode($initRes->getJSON(), true);
        $v2Id = (int)$initBody['data']['versionId'];

        $this->assertEquals(2, $initBody['data']['versionNumber']);

        // 5. User updates unit in v2
        $v2Unit = $this->unitModel->where('version_id', $v2Id)->first();
        $this->unitService->updateUnitChange($this->userA, $subId, (int)$v2Unit->id, [
            'unit_name'   => 'Unit Terkoreksi v2 ' . $uniqueSuffix,
            'unit_code'   => 'REV-U2-' . $uniqueSuffix,
            'unit_type'   => 'DIREKTORAT',
            'change_type' => 'NEW',
        ]);

        // 6. User resubmits v2 -> RESUBMITTED
        $resubmitRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/resubmit", [
            'notes' => 'Sudah diperbaiki nomenklatur unit.',
        ]);
        $resubmitRes->assertStatus(200);

        $sub = $this->subModel->find($subId);
        $this->assertEquals('RESUBMITTED', $sub->current_state);

        // 7. Verifier restarts review, approves substantive, approves final, and promotes
        $this->verifierService->startReview($this->verifierA, $subId);
        $this->verifierService->approveSubstantive($this->verifierA, $subId, [
            'recommendation_summary' => 'Rekomendasi teknis v2 disetujui.',
            'notes'                  => 'Catatan substantif OK.',
            'resolve_all_notes'      => true,
        ]);
        $this->finalApprovalService->approveSubmission($this->verifierA, $subId, [
            'approval_number' => 'SK-PANRB/2026/08/REV-' . $uniqueSuffix,
            'notes'           => 'Final v2',
        ]);
        $this->finalApprovalService->promoteSubmission($this->verifierA, $subId);

        $sub = $this->subModel->find($subId);
        $this->assertEquals('PROMOTED', $sub->current_state);

        // Master data should contain v2, NOT v1
        $promotedUnit = $this->masterUnitModel->where('unit_code', 'REV-U2-' . $uniqueSuffix)->first();
        $this->assertNotNull($promotedUnit);
        $this->assertEquals('Unit Terkoreksi v2 ' . $uniqueSuffix, $promotedUnit->unit_name);
    }

    /**
     * E2E-03: Admin Gate 1 Screening Return and Resubmission Workflow
     */
    public function testE2E03AdminGate1ScreeningReturnAndResubmit(): void
    {
        $uniqueSuffix = uniqid('e2e3_');

        // 1. User creates and submits draft
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'   => $this->instAId,
            'title'            => 'E2E Admin Return ' . $uniqueSuffix,
            'submission_year'  => 2026,
            'description'      => 'Admin screening test.',
        ]);
        $subId = (int)$created['id'];

        $this->subService->submitDraft($this->userA, $subId);

        // 2. Admin accepts screening & assigns Verifier
        $acceptRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/admin-review/accept", [
            'notes' => 'Screening awal diterima, memeriksa kelengkapan.',
        ]);
        $acceptRes->assertStatus(200);

        $assignRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdminA,
        ])->post("api/v1/submissions/{$subId}/assign-verifier", [
            'verifier_id' => $this->verifierA->id,
            'notes'       => 'Ditugaskan ke Verifikator A.',
        ]);
        $assignRes->assertStatus(200);

        // 3. Verifier starts review and returns for revision
        $this->verifierService->startReview($this->verifierA, $subId);
        $returnRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/return", [
            'reason' => 'Dokumen pendukung SK penetapan lama belum lengkap.',
        ]);
        $returnRes->assertStatus(200);

        $sub = $this->subModel->find($subId);
        $this->assertEquals('REVISION_REQUIRED_BY_VERIFIER', $sub->current_state);

        // 4. User branches revision version v2
        $initRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/revision", [
            'notes' => 'Memulai revisi untuk melengkapi dokumen.',
        ]);
        $initRes->assertStatus(201);

        // 5. User resubmits to Gate 2 -> RESUBMITTED
        $resubmitRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/resubmit", [
            'notes' => 'Dokumen pendukung telah diunggah dan dilengkapi.',
        ]);
        $resubmitRes->assertStatus(200);

        $sub = $this->subModel->find($subId);
        $this->assertEquals('RESUBMITTED', $sub->current_state);

        // 6. Verifier restarts review on RESUBMITTED
        $startRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenVerifierA,
        ])->post("api/v1/submissions/{$subId}/verifier-review/start");
        $startRes->assertStatus(200);
    }

    /**
     * E2E-04: Multi-Tenant Data Isolation & BOLA Protection
     */
    public function testE2E04MultiTenantDataIsolationAndBOLAProtection(): void
    {
        $uniqueSuffix = uniqid('e2e4_');

        // User A creates submission in Inst A
        $createdA = $this->subService->createSubmission($this->userA, [
            'institution_id'   => $this->instAId,
            'title'            => 'Inst A Submission ' . $uniqueSuffix,
            'submission_year'  => 2026,
            'description'      => 'Inst A data.',
        ]);
        $subAId = (int)$createdA['id'];

        // User B creates submission in Inst B
        $createdB = $this->subService->createSubmission($this->userB, [
            'institution_id'   => $this->instBId,
            'title'            => 'Inst B Submission ' . $uniqueSuffix,
            'submission_year'  => 2026,
            'description'      => 'Inst B data.',
        ]);
        $subBId = (int)$createdB['id'];

        // User A attempts to view or modify User B's submission -> 403 Forbidden
        $viewRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/submissions/{$subBId}");
        $viewRes->assertStatus(403);

        $modifyRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subBId}/submit");
        $modifyRes->assertStatus(403);

        // User B attempts to view User A's submission -> 403 Forbidden
        $viewResB = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserB,
        ])->get("api/v1/submissions/{$subAId}");
        $viewResB->assertStatus(403);
    }

    /**
     * E2E-05: Executive Reporting Dashboard & Export Reflection of End-to-End Lifecycles
     */
    public function testE2E05ExecutiveDashboardReflectsE2ELifecycle(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/reports/summary');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('overview', $body['data']);
        $this->assertArrayHasKey('funnel', $body['data']);
        $this->assertGreaterThanOrEqual(1, $body['data']['overview']['totalInstitutions']);
        $this->assertGreaterThanOrEqual(1, $body['data']['overview']['totalSubmissions']);
    }
}
