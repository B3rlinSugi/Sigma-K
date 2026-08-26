<?php

namespace Tests\Unit;

use App\Entities\UserEntity;
use App\Models\InstitutionModel;
use App\Models\OrganizationalUnitModel;
use App\Models\PositionModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionPositionModel;
use App\Models\SubmissionUnitModel;
use App\Models\SubmissionVersionModel;
use App\Models\UserModel;
use App\Services\Auth\JwtService;
use App\Services\Submission\SubmissionPositionService;
use App\Services\Submission\SubmissionService;
use App\Services\Submission\SubmissionUnitService;
use App\Services\Submission\SubmissionVersionService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * SubmissionDraftingTest
 *
 * Comprehensive Test Suite for Step 5:
 * Core Submission Drafting, Unit Changes, Position Changes, Versioning & Submit Gate.
 *
 * Covers:
 * - SUB-01 .. SUB-26
 *
 * @internal
 */
final class SubmissionDraftingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected UserModel $userModel;
    protected InstitutionModel $instModel;
    protected SubmissionModel $subModel;
    protected SubmissionVersionModel $verModel;
    protected SubmissionUnitModel $subUnitModel;
    protected SubmissionPositionModel $subPosModel;
    protected OrganizationalUnitModel $masterUnitModel;
    protected PositionModel $masterPosModel;

    protected JwtService $jwtService;
    protected SubmissionService $subService;
    protected SubmissionUnitService $unitService;
    protected SubmissionPositionService $posService;
    protected SubmissionVersionService $versionService;

    protected UserEntity $userA;
    protected UserEntity $admin;
    protected UserEntity $superAdmin;

    protected string $tokenUserA;
    protected string $tokenAdmin;
    protected string $tokenSuperAdmin;

    protected int $instAId;
    protected int $instBId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel        = new UserModel();
        $this->instModel        = new InstitutionModel();
        $this->subModel         = new SubmissionModel();
        $this->verModel         = new SubmissionVersionModel();
        $this->subUnitModel     = new SubmissionUnitModel();
        $this->subPosModel      = new SubmissionPositionModel();
        $this->masterUnitModel  = new OrganizationalUnitModel();
        $this->masterPosModel   = new PositionModel();

        $this->jwtService       = new JwtService();
        $this->subService       = new SubmissionService();
        $this->unitService      = new SubmissionUnitService();
        $this->posService       = new SubmissionPositionService();
        $this->versionService   = new SubmissionVersionService();

        // Load test users from eskld_db
        $this->userA        = $this->userModel->findByUsername('test_user_a');
        $this->admin        = $this->userModel->findByUsername('test_admin');
        $this->superAdmin   = $this->userModel->findByUsername('test_super_admin');

        $this->tokenUserA      = $this->jwtService->generateAccessToken($this->userA, 'USER');
        $this->tokenAdmin      = $this->jwtService->generateAccessToken($this->admin, 'ADMIN');
        $this->tokenSuperAdmin = $this->jwtService->generateAccessToken($this->superAdmin, 'SUPER_ADMIN');

        $instA = $this->instModel->where('institution_code', 'TEST-INST-A')->first();
        $instB = $this->instModel->where('institution_code', 'TEST-INST-B')->first();

        $this->instAId = (int)$instA['id'];
        $this->instBId = (int)$instB['id'];
    }

    /**
     * SUB-01: Authorized user creates submission -> 201 Created
     */
    public function testSub01AuthorizedUserCreatesSubmission(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post('api/v1/submissions', [
            'institution_id'  => $this->instAId,
            'title'           => 'Usulan Penataan Organisasi Instansi A 2026',
            'submission_year' => 2026,
        ]);

        $result->assertStatus(201);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('DRAFT', $body['data']['currentState']);
        $this->assertEquals($this->instAId, $body['data']['institutionId']);
        $this->assertEquals(1, $body['data']['currentVersion']);
    }

    /**
     * SUB-02: Unauthenticated user creates submission -> 401 Unauthorized
     */
    public function testSub02UnauthenticatedCreatesSubmission(): void
    {
        $result = $this->post('api/v1/submissions', [
            'institution_id'  => $this->instAId,
            'title'           => 'Unauthenticated submission',
            'submission_year' => 2026,
        ]);

        $result->assertStatus(401);
    }

    /**
     * SUB-03: User creates submission for unauthorized institution -> 403 Forbidden
     */
    public function testSub03UserCreatesSubmissionUnauthorizedInstitution(): void
    {
        // User A (Home: A) attempts to create submission on Inst B without grant
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post('api/v1/submissions', [
            'institution_id'  => $this->instBId,
            'title'           => 'Unauthorized submission on Inst B',
            'submission_year' => 2026,
        ]);

        $result->assertStatus(403);
        $body = json_decode($result->getJSON(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('FORBIDDEN', $body['error']['code']);
    }

    /**
     * SUB-04: User retrieves own authorized submission -> 200 OK
     */
    public function testSub04UserRetrievesOwnSubmission(): void
    {
        // Create draft first
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Draft for SUB-04 Test',
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/submissions/{$subId}");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals($subId, $body['data']['id']);
        $this->assertEquals('DRAFT', $body['data']['currentState']);
        $this->assertEquals($this->instAId, $body['data']['institutionId']);
    }

    /**
     * SUB-05: User attempts to retrieve unauthorized submission -> 403 Forbidden
     */
    public function testSub05UserAttemptsUnauthorizedSubmission(): void
    {
        // Super Admin creates submission on Inst B
        $created = $this->subService->createSubmission($this->superAdmin, [
            'institution_id'  => $this->instBId,
            'title'           => 'Draft on Inst B for SUB-05',
            'submission_year' => 2026,
        ]);
        $subBId = $created['id'];

        // User A (Home: A) tries to view submission B
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/submissions/{$subBId}");

        $result->assertStatus(403);
    }

    /**
     * SUB-06, SUB-07, SUB-08: Unit Changes in DRAFT (Add, Update, Delete)
     */
    public function testSub06To08UnitChangesInDraft(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Submission for Unit Changes Test',
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        // SUB-06: Add unit change
        $resultAdd = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/units", [
            'unit_code'   => 'DRAFT-UNIT-01',
            'unit_name'   => 'Biro Perencanaan dan Informasi',
            'unit_level'  => 2,
            'order_index' => 1,
            'change_type' => 'NEW',
        ]);

        $resultAdd->assertStatus(201);
        $bodyAdd = json_decode($resultAdd->getJSON(), true);
        $this->assertTrue($bodyAdd['success']);
        $unitId = $bodyAdd['data']['id'];

        // SUB-07: Update unit change
        $resultUpdate = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->withBodyFormat('json')->put("api/v1/submissions/{$subId}/units/{$unitId}", [
            'unit_name' => 'Biro Perencanaan dan Transformasi Digital',
        ]);

        $resultUpdate->assertStatus(200);
        $bodyUpdate = json_decode($resultUpdate->getJSON(), true);
        $this->assertEquals('Biro Perencanaan dan Transformasi Digital', $bodyUpdate['data']['unit_name']);

        // SUB-08: Delete unit change
        $resultDelete = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->delete("api/v1/submissions/{$subId}/units/{$unitId}");

        $resultDelete->assertStatus(200);
    }

    /**
     * SUB-09, SUB-10, SUB-11: Position Changes in DRAFT (Add, Update, Delete)
     */
    public function testSub09To11PositionChangesInDraft(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Submission for Position Changes Test',
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        // Add unit first
        $unit = $this->unitService->addUnitChange($this->userA, $subId, [
            'unit_code'   => 'DRAFT-UNIT-POS',
            'unit_name'   => 'Biro Kepegawaian',
            'change_type' => 'NEW',
        ]);
        $unitId = $unit['id'];

        // SUB-09: Add position change
        $resultAdd = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/positions", [
            'version_unit_id' => $unitId,
            'position_name'   => 'Kepala Bagian Mutasi',
            'position_type'   => 'STRUKTURAL',
            'echelon'         => 'III.a',
            'formation_count' => 1,
            'change_type'     => 'NEW',
        ]);

        $resultAdd->assertStatus(201);
        $bodyAdd = json_decode($resultAdd->getJSON(), true);
        $this->assertTrue($bodyAdd['success']);
        $posId = $bodyAdd['data']['id'];

        // SUB-10: Update position change
        $resultUpdate = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->withBodyFormat('json')->put("api/v1/submissions/{$subId}/positions/{$posId}", [
            'position_name' => 'Kepala Bagian Pengembangan Karir',
        ]);

        $resultUpdate->assertStatus(200);
        $bodyUpdate = json_decode($resultUpdate->getJSON(), true);
        $this->assertEquals('Kepala Bagian Pengembangan Karir', $bodyUpdate['data']['position_name']);

        // SUB-11: Delete position change
        $resultDelete = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->delete("api/v1/submissions/{$subId}/positions/{$posId}");

        $resultDelete->assertStatus(200);
    }

    /**
     * SUB-12 & SUB-13: Version Snapshots & Incremental Numbering
     */
    public function testSub12And13VersionSnapshots(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Submission for Snapshot Test',
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        // Add 1 unit and 1 position to Version 1
        $unit = $this->unitService->addUnitChange($this->userA, $subId, [
            'unit_code'   => 'SNAP-UNIT-1',
            'unit_name'   => 'Unit Snapshot 1',
            'change_type' => 'NEW',
        ]);
        $this->posService->addPositionChange($this->userA, $subId, [
            'version_unit_id' => $unit['id'],
            'position_name'   => 'Jabatan Snapshot 1',
            'position_type'   => 'JFT',
            'formation_count' => 2,
            'change_type'     => 'NEW',
        ]);

        // SUB-12: Create snapshot version 2
        $resultSnap = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/versions", [
            'notes' => 'Draft Version 2 Snapshot before final polish',
        ]);

        $resultSnap->assertStatus(201);
        $bodySnap = json_decode($resultSnap->getJSON(), true);
        $this->assertTrue($bodySnap['success']);
        $this->assertEquals(2, $bodySnap['data']['versionNumber']);

        // SUB-13: Verify cloned units exist in version 2
        $v2Id = $bodySnap['data']['versionId'];
        $v2Units = $this->subUnitModel->getByVersionId($v2Id);
        $this->assertCount(1, $v2Units);
        $this->assertEquals('SNAP-UNIT-1', $v2Units[0]->unit_code);

        $v2Positions = $this->subPosModel->getByVersionId($v2Id);
        $this->assertCount(1, $v2Positions);
        $this->assertEquals('Jabatan Snapshot 1', $v2Positions[0]['position_name']);
    }

    /**
     * SUB-14, SUB-15, SUB-16, SUB-17, SUB-18, SUB-19: Submit Draft & Draft Locking
     */
    public function testSub14To19SubmitDraftAndDraftLocking(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Submission for Final Submit Test',
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        $unit = $this->unitService->addUnitChange($this->userA, $subId, [
            'unit_code'   => 'SUBMIT-UNIT',
            'unit_name'   => 'Unit Submit Test',
            'change_type' => 'NEW',
        ]);
        $unitId = $unit['id'];

        // SUB-14 & SUB-15: Submit to Admin Gate
        $resultSubmit = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/submit", [
            'notes' => 'Siap diverifikasi Gate 1',
        ]);

        $resultSubmit->assertStatus(200);
        $bodySubmit = json_decode($resultSubmit->getJSON(), true);
        $this->assertTrue($bodySubmit['success']);
        $this->assertEquals('SUBMITTED_TO_ADMIN', $bodySubmit['data']['currentState']);
        $this->assertNotNull($bodySubmit['data']['submittedAt']);

        // SUB-16: Submitted submission cannot create version
        $resultVer = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/versions", [
            'notes' => 'Attempt to version locked submission',
        ]);
        $resultVer->assertStatus(409);

        // SUB-17: Submitted submission cannot add unit change
        $resultUnit = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/units", [
            'unit_code' => 'ILLEGAL-UNIT',
            'unit_name' => 'Illegal Unit',
        ]);
        $resultUnit->assertStatus(409);

        // SUB-18: Submitted submission cannot add position change
        $resultPos = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/positions", [
            'version_unit_id' => $unitId,
            'position_name'   => 'Illegal Position',
            'position_type'   => 'JFT',
        ]);
        $resultPos->assertStatus(409);

        // SUB-19: Submitted submission cannot delete unit change
        $resultDel = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->delete("api/v1/submissions/{$subId}/units/{$unitId}");
        $resultDel->assertStatus(409);
    }

    /**
     * SUB-20: User submits another user's submission on unauthorized institution -> 403 Forbidden
     */
    public function testSub20UserSubmitsUnauthorizedSubmission(): void
    {
        $created = $this->subService->createSubmission($this->superAdmin, [
            'institution_id'  => $this->instBId,
            'title'           => 'Draft on Inst B for SUB-20',
            'submission_year' => 2026,
        ]);
        $subBId = $created['id'];

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subBId}/submit");

        $result->assertStatus(403);
    }

    /**
     * SUB-21: Cross-institution unit reference rejected
     */
    public function testSub21CrossInstitutionUnitReference(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Draft for SUB-21',
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        // Create a unit on Inst B
        $db = Database::connect();
        $db->table('organizational_units')->insert([
            'institution_id' => $this->instBId,
            'parent_unit_id' => null,
            'unit_code'      => 'UNIT-B-SUB21',
            'unit_name'      => 'Unit B For SUB-21',
            'unit_level'     => 1,
            'order_index'    => 1,
            'status'         => 'ACTIVE',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $unitBId = (int)$db->insertID();

        // Attempt to reference unit from Inst B as source_unit_id in Submission A
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/units", [
            'unit_code'      => 'CROSS-UNIT',
            'unit_name'      => 'Cross Unit Test',
            'source_unit_id' => $unitBId,
            'change_type'    => 'UPDATE',
        ]);

        $result->assertStatus(422);

        // Cleanup
        $db->table('organizational_units')->where('id', $unitBId)->delete();
    }

    /**
     * SUB-22: Cross-institution position reference rejected
     */
    public function testSub22CrossInstitutionPositionReference(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Draft for SUB-22',
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        $unit = $this->unitService->addUnitChange($this->userA, $subId, [
            'unit_code'   => 'UNIT-FOR-SUB22',
            'unit_name'   => 'Unit for SUB-22',
            'change_type' => 'NEW',
        ]);
        $unitId = $unit['id'];

        // Create unit and pos on Inst B
        $db = Database::connect();
        $db->table('organizational_units')->insert([
            'institution_id' => $this->instBId,
            'parent_unit_id' => null,
            'unit_code'      => 'UNIT-B-SUB22',
            'unit_name'      => 'Unit B For SUB-22',
            'unit_level'     => 1,
            'order_index'    => 1,
            'status'         => 'ACTIVE',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $unitBId = (int)$db->insertID();

        $db->table('positions')->insert([
            'unit_id'         => $unitBId,
            'position_name'   => 'Pos B For SUB-22',
            'position_type'   => 'STRUKTURAL',
            'formation_count' => 1,
            'status'          => 'ACTIVE',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        $posBId = (int)$db->insertID();

        // Attempt to reference position from Inst B as source_position_id in Submission A
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/positions", [
            'version_unit_id'    => $unitId,
            'position_name'      => 'Cross Pos Test',
            'position_type'      => 'STRUKTURAL',
            'source_position_id' => $posBId,
            'change_type'        => 'UPDATE',
        ]);

        $result->assertStatus(422);

        // Cleanup
        $db->table('positions')->where('id', $posBId)->delete();
        $db->table('organizational_units')->where('id', $unitBId)->delete();
    }

    /**
     * SUB-23: Double submit protection -> First succeeds, second rejected with 409
     */
    public function testSub23DoubleSubmitProtection(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Draft for SUB-23 Double Submit',
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        // First submit
        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/submit");
        $res1->assertStatus(200);

        // Second submit attempt
        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/submissions/{$subId}/submit");
        $res2->assertStatus(409);
    }

    /**
     * SUB-24: Transaction failure on invalid data -> Rollback
     */
    public function testSub24TransactionRollbackOnFailure(): void
    {
        $created = $this->subService->createSubmission($this->userA, [
            'institution_id'  => $this->instAId,
            'title'           => 'Draft for SUB-24',
            'submission_year' => 2026,
        ]);
        $subId = $created['id'];

        // Attempting to add unit with invalid parent triggers failure and does not leave dirty records
        try {
            $this->unitService->addUnitChange($this->userA, $subId, [
                'unit_code'      => 'INVALID-PARENT-UNIT',
                'unit_name'      => 'Unit with bad parent',
                'temp_parent_id' => 999999,
            ]);
            $this->fail('Expected exception for invalid parent');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('Parent unit does not exist', $e->getMessage());
        }

        $units = $this->subUnitModel->where('unit_code', 'INVALID-PARENT-UNIT')->findAll();
        $this->assertEmpty($units, 'No dirty unit record should exist after transaction rollback.');
    }

    /**
     * SUB-25 & SUB-26: Audit event verification for submission create and submit
     */
    public function testSub25And26AuditEventsRecorded(): void
    {
        $db = Database::connect();

        $createCount = $db->table('audit_logs')->where('action_event', 'CREATE_SUBMISSION')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $createCount, 'SUB-25: CREATE_SUBMISSION audit event must exist.');

        $submitCount = $db->table('audit_logs')->where('action_event', 'SUBMIT_SUBMISSION')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $submitCount, 'SUB-26: SUBMIT_SUBMISSION audit event must exist.');
    }
}
