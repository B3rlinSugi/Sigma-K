<?php

namespace Tests\Unit;

use App\Entities\UserEntity;
use App\Models\AccessGrantModel;
use App\Models\AccessRequestModel;
use App\Models\InstitutionModel;
use App\Models\UserModel;
use App\Models\UserScopeModel;
use App\Services\Access\AccessGrantService;
use App\Services\Access\AccessRequestService;
use App\Services\Auth\JwtService;
use App\Services\Authorization\AuthorizationService;
use App\Services\Authorization\ScopeResolver;
use App\Services\Institution\InstitutionService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * InstitutionAndAccessManagementTest
 *
 * Comprehensive Test Suite for Step 3:
 * Institution, User Scope, Access Request, and Access Grant Management.
 *
 * Covers:
 * - INST-01 .. INST-05
 * - SCOPE-01 .. SCOPE-03
 * - GRANT-01 .. GRANT-14
 * - AUDIT-01 .. AUDIT-03
 *
 * @internal
 */
final class InstitutionAndAccessManagementTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected UserModel $userModel;
    protected InstitutionModel $instModel;
    protected AccessRequestModel $reqModel;
    protected AccessGrantModel $grantModel;
    protected UserScopeModel $scopeModel;

    protected JwtService $jwtService;
    protected AuthorizationService $authzService;
    protected InstitutionService $instService;
    protected AccessRequestService $reqService;
    protected AccessGrantService $grantService;

    protected UserEntity $userA;
    protected UserEntity $admin;
    protected UserEntity $verifier;
    protected UserEntity $superAdmin;

    protected string $tokenUserA;
    protected string $tokenAdmin;
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
        $this->reqModel     = new AccessRequestModel();
        $this->grantModel   = new AccessGrantModel();
        $this->scopeModel   = new UserScopeModel();

        $this->jwtService   = new JwtService();
        $this->authzService = new AuthorizationService();
        $this->instService  = new InstitutionService();
        $this->reqService   = new AccessRequestService();
        $this->grantService = new AccessGrantService();

        // Load test users from eskld_db
        $this->userA        = $this->userModel->findByUsername('test_user_a');
        $this->admin        = $this->userModel->findByUsername('test_admin');
        $this->verifier     = $this->userModel->findByUsername('test_verifier');
        $this->superAdmin   = $this->userModel->findByUsername('test_super_admin');

        $this->tokenUserA      = $this->jwtService->generateAccessToken($this->userA, 'USER');
        $this->tokenAdmin      = $this->jwtService->generateAccessToken($this->admin, 'ADMIN');
        $this->tokenVerifier   = $this->jwtService->generateAccessToken($this->verifier, 'VERIFIER');
        $this->tokenSuperAdmin = $this->jwtService->generateAccessToken($this->superAdmin, 'SUPER_ADMIN');

        $instA = $this->instModel->where('institution_code', 'TEST-INST-A')->first();
        $instB = $this->instModel->where('institution_code', 'TEST-INST-B')->first();
        $instC = $this->instModel->where('institution_code', 'TEST-INST-C')->first();

        $this->instAId = (int)$instA['id'];
        $this->instBId = (int)$instB['id'];
        $this->instCId = (int)$instC['id'];
    }

    /**
     * INST-01: List institutions authenticated -> 200 OK & Paginated Array
     */
    public function testInst01ListInstitutionsAuthenticated(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSuperAdmin,
        ])->get('api/v1/institutions');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
        $this->assertGreaterThanOrEqual(3, count($body['data']));
        $this->assertArrayHasKey('meta', $body);
        $this->assertArrayHasKey('total', $body['meta']);
    }

    /**
     * INST-02: Unauthenticated institution access -> 401 Unauthorized
     */
    public function testInst02UnauthenticatedInstitutionAccess(): void
    {
        $result = $this->get('api/v1/institutions');
        $result->assertStatus(401);

        $resultDetail = $this->get('api/v1/institutions/' . $this->instAId);
        $resultDetail->assertStatus(401);
    }

    /**
     * INST-03: USER view own home institution -> 200 OK
     */
    public function testInst03UserViewOwnInstitution(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get('api/v1/institutions/' . $this->instAId);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals($this->instAId, $body['data']['id']);
        $this->assertEquals('TEST-INST-A', $body['data']['institution_code']);
    }

    /**
     * INST-04: USER view other institution without grant -> 403 Forbidden
     */
    public function testInst04UserOtherInstitutionWithoutGrant(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get('api/v1/institutions/' . $this->instBId);

        $result->assertStatus(403);
        $body = json_decode($result->getJSON(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('FORBIDDEN', $body['error']['code']);
    }

    /**
     * INST-05: USER view other institution with active VIEW grant -> 200 OK
     */
    public function testInst05UserOtherInstitutionWithViewGrant(): void
    {
        $db = Database::connect();
        $db->table('access_grants')->insert([
            'user_id'               => (int)$this->userA->id,
            'target_institution_id' => $this->instBId,
            'start_date'            => date('Y-m-d'),
            'end_date'              => date('Y-m-d', strtotime('+30 days')),
            'status'                => 'ACTIVE',
            'granted_by'            => (int)$this->admin->id,
            'grant_reason'          => 'INST-05 Active VIEW Grant',
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
        ])->get('api/v1/institutions/' . $this->instBId);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals($this->instBId, $body['data']['id']);

        // Cleanup
        $db->table('access_grant_permissions')->where('grant_id', $grantId)->delete();
        $db->table('access_grants')->where('id', $grantId)->delete();
    }

    /**
     * SCOPE-01: USER home institution scope -> /me/scopes
     */
    public function testScope01UserHomeInstitutionScope(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->get('api/v1/me/scopes');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('USER', $body['data']['role']);
        $this->assertFalse($body['data']['isGlobalScope']);
        $this->assertEquals($this->instAId, $body['data']['homeInstitution']['id']);
    }

    /**
     * SCOPE-02: ADMIN active scope -> /me/scopes
     */
    public function testScope02AdminActiveScope(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdmin,
        ])->get('api/v1/me/scopes');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('ADMIN', $body['data']['role']);
        $this->assertIsArray($body['data']['assignedScopes']);
        $this->assertGreaterThanOrEqual(1, count($body['data']['assignedScopes']));
    }

    /**
     * SCOPE-03: Expired scope denied
     */
    public function testScope03ExpiredScopeDenied(): void
    {
        // Insert expired scope for Admin on instC
        $db = Database::connect();
        $db->table('user_scopes')->insert([
            'user_id'        => (int)$this->admin->id,
            'institution_id' => $this->instCId,
            'scope_type'     => 'TEST_EXPIRED_SCOPE',
            'start_date'     => '2024-01-01',
            'end_date'       => '2024-01-31',
            'status'         => 'ACTIVE',
            'assigned_by'    => (int)$this->superAdmin->id,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        $scopeId = $db->insertID();

        $activeScopes = $this->scopeModel->getActiveInstitutionIds((int)$this->admin->id);
        $this->assertNotContains($this->instCId, $activeScopes, 'Expired scope must not be included in active scope IDs.');

        // Cleanup
        $db->table('user_scopes')->where('id', $scopeId)->delete();
    }

    /**
     * GRANT-01: Create access request -> 201 Created
     */
    public function testGrant01CreateAccessRequest(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post('api/v1/access-requests', [
            'target_institution_id' => $this->instBId,
            'requested_start_date'  => date('Y-m-d'),
            'requested_end_date'    => date('Y-m-d', strtotime('+30 days')),
            'reason'                => 'Penugasan audit bersama kelembagaan Instansi B',
            'permissions'           => ['VIEW', 'EDIT'],
        ]);

        $result->assertStatus(201);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('PENDING', $body['data']['status']);
        $this->assertEquals($this->instBId, $body['data']['target_institution_id']);
        $this->assertContains('VIEW', $body['data']['permission_codes']);
        $this->assertContains('EDIT', $body['data']['permission_codes']);

        // Cleanup created request
        $reqId = $body['data']['id'];
        $db = Database::connect();
        $db->table('access_request_permissions')->where('request_id', $reqId)->delete();
        $db->table('access_requests')->where('id', $reqId)->delete();
    }

    /**
     * GRANT-02: Invalid date range (start > end) -> 422 Unprocessable Entity
     */
    public function testGrant02InvalidDateRange(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post('api/v1/access-requests', [
            'target_institution_id' => $this->instBId,
            'requested_start_date'  => '2026-10-30',
            'requested_end_date'    => '2026-10-01',
            'reason'                => 'Invalid date range request',
            'permissions'           => ['VIEW'],
        ]);

        $result->assertStatus(422);
        $body = json_decode($result->getJSON(), true);
        $this->assertFalse($body['success']);
    }

    /**
     * GRANT-03: Invalid permission (Attempting to request APPROVE / VERIFY) -> 422
     */
    public function testGrant03InvalidPermission(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post('api/v1/access-requests', [
            'target_institution_id' => $this->instBId,
            'requested_start_date'  => date('Y-m-d'),
            'requested_end_date'    => date('Y-m-d', strtotime('+30 days')),
            'reason'                => 'Attempting to request illegal APPROVE permission',
            'permissions'           => ['APPROVE'],
        ]);

        $result->assertStatus(422);
        $body = json_decode($result->getJSON(), true);
        $this->assertFalse($body['success']);
    }

    /**
     * GRANT-04, GRANT-07, GRANT-08: Approve access request -> Issues VIEW-only grant
     */
    public function testGrant04ApproveAccessRequest(): void
    {
        // 1. Create request by User A for Inst B
        $created = $this->reqService->createRequest($this->userA, [
            'target_institution_id' => $this->instBId,
            'requested_start_date'  => date('Y-m-d'),
            'requested_end_date'    => date('Y-m-d', strtotime('+15 days')),
            'reason'                => 'GRANT-04 Workflow Test',
            'permissions'           => ['VIEW', 'EDIT'],
        ]);
        $reqId = $created['id'];

        // 2. Admin approves with VIEW only
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdmin,
        ])->post("api/v1/access-requests/{$reqId}/approve", [
            'granted_permissions' => ['VIEW'],
            'notes'               => 'Disetujui untuk izin VIEW saja',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('APPROVED', $body['data']['status']);

        $grantId = $body['data']['grantId'];

        // GRANT-07 & GRANT-08 Verification
        $canView = $this->authzService->can($this->userA, 'VIEW', $this->instBId);
        $canEdit = $this->authzService->can($this->userA, 'EDIT', $this->instBId);

        $this->assertTrue($canView, 'GRANT-07: User must be allowed VIEW under approved grant.');
        $this->assertFalse($canEdit, 'GRANT-08: User with VIEW-only grant must be denied EDIT.');

        // Cleanup
        $db = Database::connect();
        $db->table('access_grant_permissions')->where('grant_id', $grantId)->delete();
        $db->table('access_grants')->where('id', $grantId)->delete();
        $db->table('access_request_permissions')->where('request_id', $reqId)->delete();
        $db->table('access_requests')->where('id', $reqId)->delete();
    }

    /**
     * GRANT-05: Requester cannot self-approve -> 403 Forbidden
     */
    public function testGrant05RequesterCannotSelfApprove(): void
    {
        $created = $this->reqService->createRequest($this->userA, [
            'target_institution_id' => $this->instBId,
            'requested_start_date'  => date('Y-m-d'),
            'requested_end_date'    => date('Y-m-d', strtotime('+15 days')),
            'reason'                => 'GRANT-05 Self Approve Test',
            'permissions'           => ['VIEW'],
        ]);
        $reqId = $created['id'];

        // User A attempts to approve own request
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenUserA,
        ])->post("api/v1/access-requests/{$reqId}/approve", [
            'notes' => 'Self approve illegal attempt',
        ]);

        $result->assertStatus(403);

        // Cleanup
        $db = Database::connect();
        $db->table('access_request_permissions')->where('request_id', $reqId)->delete();
        $db->table('access_requests')->where('id', $reqId)->delete();
    }

    /**
     * GRANT-06: Admin outside scope cannot approve -> 403 Forbidden
     */
    public function testGrant06AdminOutsideScopeCannotApprove(): void
    {
        // Target Inst C is not in Admin's active user_scopes
        $created = $this->reqService->createRequest($this->userA, [
            'target_institution_id' => $this->instCId,
            'requested_start_date'  => date('Y-m-d'),
            'requested_end_date'    => date('Y-m-d', strtotime('+15 days')),
            'reason'                => 'GRANT-06 Admin outside scope test',
            'permissions'           => ['VIEW'],
        ]);
        $reqId = $created['id'];

        // Admin attempts to approve request on Inst C (outside Admin scope)
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdmin,
        ])->post("api/v1/access-requests/{$reqId}/approve", [
            'notes' => 'Admin outside scope attempt',
        ]);

        $result->assertStatus(403);

        // Cleanup
        $db = Database::connect();
        $db->table('access_request_permissions')->where('request_id', $reqId)->delete();
        $db->table('access_requests')->where('id', $reqId)->delete();
    }

    /**
     * GRANT-09: VIEW + EDIT grant -> ALLOWS both VIEW and EDIT
     */
    public function testGrant09ViewAndEditGrant(): void
    {
        $db = Database::connect();
        $db->table('access_grants')->insert([
            'user_id'               => (int)$this->userA->id,
            'target_institution_id' => $this->instBId,
            'start_date'            => date('Y-m-d'),
            'end_date'              => date('Y-m-d', strtotime('+30 days')),
            'status'                => 'ACTIVE',
            'granted_by'            => (int)$this->admin->id,
            'grant_reason'          => 'GRANT-09 VIEW+EDIT Test',
            'created_at'            => date('Y-m-d H:i:s'),
        ]);
        $grantId = $db->insertID();

        $permView = $db->table('permissions')->where('permission_code', 'VIEW')->get()->getRow();
        $permEdit = $db->table('permissions')->where('permission_code', 'EDIT')->get()->getRow();

        $db->table('access_grant_permissions')->insert(['grant_id' => $grantId, 'permission_id' => (int)$permView->id]);
        $db->table('access_grant_permissions')->insert(['grant_id' => $grantId, 'permission_id' => (int)$permEdit->id]);

        $canView = $this->authzService->can($this->userA, 'VIEW', $this->instBId);
        $canEdit = $this->authzService->can($this->userA, 'EDIT', $this->instBId, 'DRAFT');

        $this->assertTrue($canView, 'GRANT-09: User must be allowed VIEW.');
        $this->assertTrue($canEdit, 'GRANT-09: User must be allowed EDIT in DRAFT state.');

        // Cleanup
        $db->table('access_grant_permissions')->where('grant_id', $grantId)->delete();
        $db->table('access_grants')->where('id', $grantId)->delete();
    }

    /**
     * GRANT-10: Expired grant dynamically denied
     */
    public function testGrant10ExpiredGrantDenied(): void
    {
        $db = Database::connect();
        $db->table('access_grants')->insert([
            'user_id'               => (int)$this->userA->id,
            'target_institution_id' => $this->instBId,
            'start_date'            => '2024-01-01',
            'end_date'              => '2024-01-31',
            'status'                => 'ACTIVE',
            'granted_by'            => (int)$this->admin->id,
            'grant_reason'          => 'GRANT-10 Expired Grant',
            'created_at'            => date('Y-m-d H:i:s'),
        ]);
        $grantId = $db->insertID();

        $permView = $db->table('permissions')->where('permission_code', 'VIEW')->get()->getRow();
        $db->table('access_grant_permissions')->insert(['grant_id' => $grantId, 'permission_id' => (int)$permView->id]);

        $canView = $this->authzService->can($this->userA, 'VIEW', $this->instBId);
        $this->assertFalse($canView, 'GRANT-10: Expired grant must be dynamically denied.');

        // Cleanup
        $db->table('access_grant_permissions')->where('grant_id', $grantId)->delete();
        $db->table('access_grants')->where('id', $grantId)->delete();
    }

    /**
     * GRANT-11 & GRANT-12: Revoke grant -> 200 OK & Access Immediately Denied
     */
    public function testGrant11And12RevokeGrant(): void
    {
        $db = Database::connect();
        $db->table('access_grants')->insert([
            'user_id'               => (int)$this->userA->id,
            'target_institution_id' => $this->instBId,
            'start_date'            => date('Y-m-d'),
            'end_date'              => date('Y-m-d', strtotime('+30 days')),
            'status'                => 'ACTIVE',
            'granted_by'            => (int)$this->admin->id,
            'grant_reason'          => 'GRANT-11 Revoke Test',
            'created_at'            => date('Y-m-d H:i:s'),
        ]);
        $grantId = $db->insertID();

        $permView = $db->table('permissions')->where('permission_code', 'VIEW')->get()->getRow();
        $db->table('access_grant_permissions')->insert(['grant_id' => $grantId, 'permission_id' => (int)$permView->id]);

        // Prior to revoke: allowed
        $this->assertTrue($this->authzService->can($this->userA, 'VIEW', $this->instBId));

        // Admin revokes grant
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenAdmin,
        ])->post("api/v1/access-grants/{$grantId}/revoke", [
            'revoke_reason' => 'Tugas telah selesai lebih awal',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('REVOKED', $body['data']['status']);

        // GRANT-12: After revoke -> denied
        $this->assertFalse($this->authzService->can($this->userA, 'VIEW', $this->instBId), 'GRANT-12: Revoked grant must immediately deny access.');

        // Verify physical record retained
        $row = $this->grantModel->find($grantId);
        $this->assertNotNull($row, 'Physical record must remain in database.');
        $this->assertEquals('REVOKED', $row['status']);

        // Cleanup
        $db->table('access_grant_permissions')->where('grant_id', $grantId)->delete();
        $db->table('access_grants')->where('id', $grantId)->delete();
    }

    /**
     * GRANT-13: Access Grant cannot produce VERIFY
     */
    public function testGrant13GrantCannotProduceVerify(): void
    {
        $canVerify = $this->authzService->can($this->userA, 'VERIFY', $this->instBId, 'VERIFIER_REVIEW');
        $this->assertFalse($canVerify, 'Access grant cannot grant VERIFY to non-VERIFIER.');
    }

    /**
     * GRANT-14: Access Grant cannot produce APPROVE
     */
    public function testGrant14GrantCannotProduceApprove(): void
    {
        $canApprove = $this->authzService->can($this->userA, 'APPROVE', $this->instBId, 'VERIFIER_REVIEW');
        $this->assertFalse($canApprove, 'Access grant cannot grant APPROVE to non-VERIFIER.');
    }

    /**
     * AUDIT-01, AUDIT-02, AUDIT-03: Audit log recording verification
     */
    public function testAuditEventsRecorded(): void
    {
        $db = Database::connect();
        $reqCount = $db->table('audit_logs')->where('action_event', 'REQUEST_ACCESS')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $reqCount, 'AUDIT-01: REQUEST_ACCESS must be logged in audit_logs.');

        $grantCount = $db->table('audit_logs')->where('action_event', 'GRANT_ACCESS')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $grantCount, 'AUDIT-02: GRANT_ACCESS must be logged in audit_logs.');

        $revokeCount = $db->table('audit_logs')->where('action_event', 'REVOKE_ACCESS')->countAllResults();
        $this->assertGreaterThanOrEqual(1, $revokeCount, 'AUDIT-03: REVOKE_ACCESS must be logged in audit_logs.');
    }
}
