<?php

namespace Tests\Unit;

use App\Entities\UserEntity;
use App\Models\InstitutionModel;
use App\Models\OrganizationalUnitModel;
use App\Models\PositionModel;
use App\Models\UserModel;
use App\Services\Auth\JwtService;
use App\Services\OrgStructure\OrgHierarchyService;
use App\Services\OrgStructure\PositionService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * OrganizationalStructureTest
 *
 * Comprehensive Test Suite for Step 4:
 * Master Organizational Structure & Positions.
 *
 * Covers:
 * - UNIT-01 .. UNIT-08
 * - POS-01 .. POS-05
 *
 * @internal
 */
final class OrganizationalStructureTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected UserModel $userModel;
    protected InstitutionModel $instModel;
    protected OrganizationalUnitModel $unitModel;
    protected PositionModel $posModel;

    protected JwtService $jwtService;
    protected OrgHierarchyService $hierarchyService;
    protected PositionService $positionService;

    protected UserEntity $userA;
    protected UserEntity $admin;
    protected UserEntity $verifier;
    protected UserEntity $superAdmin;

    protected string $tokenUserA;
    protected string $tokenAdmin;
    protected string $tokenSuperAdmin;

    protected int $instAId;
    protected int $instBId;
    protected int $instCId;

    protected int $testUnitAId;
    protected int $testPosAId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel        = new UserModel();
        $this->instModel        = new InstitutionModel();
        $this->unitModel        = new OrganizationalUnitModel();
        $this->posModel         = new PositionModel();
        $this->jwtService       = new JwtService();
        $this->hierarchyService = new OrgHierarchyService();
        $this->positionService  = new PositionService();

        // Load test users from eskld_db
        $this->userA        = $this->userModel->findByUsername('test_user_a');
        $this->admin        = $this->userModel->findByUsername('test_admin');
        $this->verifier     = $this->userModel->findByUsername('test_verifier');
        $this->superAdmin   = $this->userModel->findByUsername('test_super_admin');

        $this->tokenUserA      = $this->jwtService->generateAccessToken($this->userA, 'USER');
        $this->tokenAdmin      = $this->jwtService->generateAccessToken($this->admin, 'ADMIN');
        $this->tokenSuperAdmin = $this->jwtService->generateAccessToken($this->superAdmin, 'SUPER_ADMIN');

        $instA = $this->instModel->where('institution_code', 'TEST-INST-A')->first();
        $instB = $this->instModel->where('institution_code', 'TEST-INST-B')->first();
        $instC = $this->instModel->where('institution_code', 'TEST-INST-C')->first();

        $this->instAId = (int)$instA['id'];
        $this->instBId = (int)$instB['id'];
        $this->instCId = (int)$instC['id'];

        // Ensure baseline unit and position for Inst A exist for testing
        $db = Database::connect();
        $unit = $this->unitModel->where('institution_id', $this->instAId)->where('unit_code', 'TEST-UNIT-A1')->first();
        if (!$unit) {
            $db->table('organizational_units')->insert([
                'institution_id' => $this->instAId,
                'parent_unit_id' => null,
                'unit_code'      => 'TEST-UNIT-A1',
                'unit_name'      => 'Sekretariat Utama Instansi A',
                'unit_level'     => 1,
                'order_index'    => 1,
                'status'         => 'ACTIVE',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
            $this->testUnitAId = (int)$db->insertID();
        } else {
            $this->testUnitAId = (int)$unit->id;
        }

        $pos = $this->posModel->where('unit_id', $this->testUnitAId)->where('position_name', 'Sekretaris Utama A')->first();
        if (!$pos) {
            $db->table('positions')->insert([
                'unit_id'         => $this->testUnitAId,
                'position_name'   => 'Sekretaris Utama A',
                'position_type'   => 'STRUKTURAL',
                'echelon'         => 'I.a',
                'formation_count' => 1,
                'status'          => 'ACTIVE',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
            $this->testPosAId = (int)$db->insertID();
        } else {
            $this->testPosAId = (int)$pos->id;
        }
    }

    /**
     * UNIT-01: Authenticated user retrieves units for their home institution -> 200 OK
     */
    public function testUnit01UserHomeInstitutionUnits(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/institutions/{$this->instAId}/units");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals($this->instAId, $body['data']['institutionId']);
        $this->assertIsArray($body['data']['tree']);
        $this->assertGreaterThanOrEqual(1, count($body['data']['tree']));
    }

    /**
     * UNIT-02: Unauthenticated request attempts to retrieve units -> 401 Unauthorized
     */
    public function testUnit02UnauthenticatedUnits(): void
    {
        $result = $this->get("api/v1/institutions/{$this->instAId}/units");
        $result->assertStatus(401);

        $resultDetail = $this->get("api/v1/units/{$this->testUnitAId}");
        $resultDetail->assertStatus(401);
    }

    /**
     * UNIT-03: User attempts to retrieve units belonging to another institution without access -> 403 Forbidden
     */
    public function testUnit03UserOtherInstitutionWithoutAccess(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/institutions/{$this->instBId}/units");

        $result->assertStatus(403);
        $body = json_decode($result->getJSON(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('FORBIDDEN', $body['error']['code']);
    }

    /**
     * UNIT-04: User has active VIEW access/grant to another institution -> 200 OK
     */
    public function testUnit04UserOtherInstitutionWithGrant(): void
    {
        $db = Database::connect();
        $db->table('access_grants')->insert([
            'user_id'               => (int)$this->userA->id,
            'target_institution_id' => $this->instBId,
            'start_date'            => date('Y-m-d'),
            'end_date'              => date('Y-m-d', strtotime('+30 days')),
            'status'                => 'ACTIVE',
            'granted_by'            => (int)$this->admin->id,
            'grant_reason'          => 'UNIT-04 Active VIEW Grant',
            'created_at'            => date('Y-m-d H:i:s'),
        ]);
        $grantId = $db->insertID();

        $permView = $db->table('permissions')->where('permission_code', 'VIEW')->get()->getRow();
        $db->table('access_grant_permissions')->insert([
            'grant_id'      => $grantId,
            'permission_id' => (int)$permView->id,
        ]);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/institutions/{$this->instBId}/units");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals($this->instBId, $body['data']['institutionId']);

        // Cleanup
        $db->table('access_grant_permissions')->where('grant_id', $grantId)->delete();
        $db->table('access_grants')->where('id', $grantId)->delete();
    }

    /**
     * UNIT-05: Request targets nonexistent institution -> 404 Not Found
     */
    public function testUnit05NonexistentInstitution(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/institutions/999999/units');

        $result->assertStatus(404);
        $body = json_decode($result->getJSON(), true);
        $this->assertFalse($body['success']);
    }

    /**
     * UNIT-06: Organizational unit hierarchy correctly builds parent-child tree
     */
    public function testUnit06HierarchyTreeConstruction(): void
    {
        $mockUnits = [
            ['id' => 10, 'parent_unit_id' => null, 'unit_code' => 'ROOT', 'unit_name' => 'Root Unit', 'unit_level' => 1, 'order_index' => 1, 'status' => 'ACTIVE'],
            ['id' => 11, 'parent_unit_id' => 10, 'unit_code' => 'CHILD-1', 'unit_name' => 'Child 1', 'unit_level' => 2, 'order_index' => 1, 'status' => 'ACTIVE'],
            ['id' => 12, 'parent_unit_id' => 10, 'unit_code' => 'CHILD-2', 'unit_name' => 'Child 2', 'unit_level' => 2, 'order_index' => 2, 'status' => 'ACTIVE'],
            ['id' => 13, 'parent_unit_id' => 11, 'unit_code' => 'GRANDCHILD', 'unit_name' => 'Grandchild', 'unit_level' => 3, 'order_index' => 1, 'status' => 'ACTIVE'],
        ];

        $tree = $this->hierarchyService->buildHierarchyTree($mockUnits);

        $this->assertCount(1, $tree);
        $this->assertEquals(10, $tree[0]['id']);
        $this->assertCount(2, $tree[0]['children']);
        $this->assertEquals(11, $tree[0]['children'][0]['id']);
        $this->assertCount(1, $tree[0]['children'][0]['children']);
        $this->assertEquals(13, $tree[0]['children'][0]['children'][0]['id']);
    }

    /**
     * UNIT-07: Self-parenting / malformed hierarchy does not cause infinite recursion
     */
    public function testUnit07SelfParentingSafeHandling(): void
    {
        // Malformed node pointing to itself (id: 20, parent_unit_id: 20)
        $mockUnits = [
            ['id' => 20, 'parent_unit_id' => 20, 'unit_code' => 'SELF-LOOP', 'unit_name' => 'Self Looped Unit', 'unit_level' => 1, 'order_index' => 1, 'status' => 'ACTIVE'],
            ['id' => 21, 'parent_unit_id' => 20, 'unit_code' => 'CHILD', 'unit_name' => 'Child of Self Loop', 'unit_level' => 2, 'order_index' => 1, 'status' => 'ACTIVE'],
        ];

        $tree = $this->hierarchyService->buildHierarchyTree($mockUnits);

        $this->assertCount(1, $tree);
        $this->assertEquals(20, $tree[0]['id']);
        $this->assertCount(1, $tree[0]['children']);
        $this->assertEquals(21, $tree[0]['children'][0]['id']);
    }

    /**
     * UNIT-08: Circular hierarchy does not cause infinite recursion (A -> B -> C -> A)
     */
    public function testUnit08CircularHierarchySafeHandling(): void
    {
        // Closed loop: 31 -> 32 -> 33 -> 31
        $mockUnits = [
            ['id' => 31, 'parent_unit_id' => 33, 'unit_code' => 'LOOP-A', 'unit_name' => 'Loop Unit A', 'unit_level' => 1, 'order_index' => 1, 'status' => 'ACTIVE'],
            ['id' => 32, 'parent_unit_id' => 31, 'unit_code' => 'LOOP-B', 'unit_name' => 'Loop Unit B', 'unit_level' => 2, 'order_index' => 1, 'status' => 'ACTIVE'],
            ['id' => 33, 'parent_unit_id' => 32, 'unit_code' => 'LOOP-C', 'unit_name' => 'Loop Unit C', 'unit_level' => 3, 'order_index' => 1, 'status' => 'ACTIVE'],
        ];

        $tree = $this->hierarchyService->buildHierarchyTree($mockUnits);

        // Verification: Loop is detected and safely broken without exhausting stack memory
        $this->assertNotEmpty($tree);
        $this->assertIsArray($tree[0]['children']);
    }

    /**
     * POS-01: Authorized user retrieves positions for an accessible unit -> 200 OK
     */
    public function testPos01AuthorizedUserRetrievesPositions(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/units/{$this->testUnitAId}/positions");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals($this->testUnitAId, $body['data']['unitId']);
        $this->assertIsArray($body['data']['positions']);
        $this->assertGreaterThanOrEqual(1, count($body['data']['positions']));
    }

    /**
     * POS-02: Unauthorized user attempts to retrieve positions belonging to another institution -> 403 Forbidden
     */
    public function testPos02UnauthorizedUserPositions(): void
    {
        // Create unit on Inst B
        $db = Database::connect();
        $db->table('organizational_units')->insert([
            'institution_id' => $this->instBId,
            'parent_unit_id' => null,
            'unit_code'      => 'UNIT-B-TEST',
            'unit_name'      => 'Unit B For POS-02',
            'unit_level'     => 1,
            'order_index'    => 1,
            'status'         => 'ACTIVE',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $unitBId = (int)$db->insertID();

        // User A (Home: A) attempts to access positions in Unit B without grant
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/units/{$unitBId}/positions");

        $result->assertStatus(403);
        $body = json_decode($result->getJSON(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('FORBIDDEN', $body['error']['code']);

        // Cleanup
        $db->table('organizational_units')->where('id', $unitBId)->delete();
    }

    /**
     * POS-03: Retrieve position detail -> 200 OK
     */
    public function testPos03RetrievePositionDetail(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/positions/{$this->testPosAId}");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals($this->testPosAId, $body['data']['id']);
        $this->assertEquals('Sekretaris Utama A', $body['data']['positionName']);
        $this->assertEquals($this->instAId, $body['data']['institutionId']);
    }

    /**
     * POS-04: Position ID does not exist -> 404 Not Found
     */
    public function testPos04PositionNotFound(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/positions/999999');

        $result->assertStatus(404);
        $body = json_decode($result->getJSON(), true);
        $this->assertFalse($body['success']);
    }

    /**
     * POS-05: Position belongs to institution B but user only has access to institution A -> 403 Forbidden
     */
    public function testPos05CrossInstitutionPositionForbidden(): void
    {
        $db = Database::connect();
        // Insert unit and position in Inst B
        $db->table('organizational_units')->insert([
            'institution_id' => $this->instBId,
            'parent_unit_id' => null,
            'unit_code'      => 'UNIT-B-POS05',
            'unit_name'      => 'Unit B For POS-05',
            'unit_level'     => 1,
            'order_index'    => 1,
            'status'         => 'ACTIVE',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $unitBId = (int)$db->insertID();

        $db->table('positions')->insert([
            'unit_id'         => $unitBId,
            'position_name'   => 'Kepala Bagian B',
            'position_type'   => 'STRUKTURAL',
            'echelon'         => 'III.a',
            'formation_count' => 1,
            'status'          => 'ACTIVE',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        $posBId = (int)$db->insertID();

        // User A attempts to view Position in Inst B
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get("api/v1/positions/{$posBId}");

        $result->assertStatus(403);
        $body = json_decode($result->getJSON(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('FORBIDDEN', $body['error']['code']);

        // Cleanup
        $db->table('positions')->where('id', $posBId)->delete();
        $db->table('organizational_units')->where('id', $unitBId)->delete();
    }
}
