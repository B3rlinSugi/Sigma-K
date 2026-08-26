<?php

namespace Tests\Unit;

use App\Entities\UserEntity;
use App\Models\AccessGrantModel;
use App\Models\InstitutionModel;
use App\Models\UserModel;
use App\Services\Auth\AuthService;
use App\Services\Auth\JwtService;
use App\Services\Authorization\AuthorizationService;
use App\Services\Authorization\ScopeResolver;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Firebase\JWT\JWT;
use Config\Database;

/**
 * AuthAndAuthorizationTest
 *
 * Comprehensive Test Suite for Step 2: Authentication & Authorization Foundation
 * Covers AUTH-01 through AUTH-17.
 *
 * @internal
 */
final class AuthAndAuthorizationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected UserModel $userModel;
    protected AuthService $authService;
    protected AuthorizationService $authzService;
    protected ScopeResolver $scopeResolver;
    protected JwtService $jwtService;

    protected UserEntity $userA;
    protected UserEntity $admin;
    protected UserEntity $verifier;
    protected UserEntity $superAdmin;
    protected UserEntity $inactiveUser;

    protected int $instAId;
    protected int $instBId;
    protected int $instCId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel    = new UserModel();
        $this->authService  = new AuthService();
        $this->authzService = new AuthorizationService();
        $this->scopeResolver= new ScopeResolver();
        $this->jwtService   = new JwtService();

        // Load test users from eskld_db
        $this->userA        = $this->userModel->findByUsername('test_user_a');
        $this->admin        = $this->userModel->findByUsername('test_admin');
        $this->verifier     = $this->userModel->findByUsername('test_verifier');
        $this->superAdmin   = $this->userModel->findByUsername('test_super_admin');
        $this->inactiveUser = $this->userModel->findByUsername('test_inactive_user');

        $instModel = new InstitutionModel();
        $instA = $instModel->where('institution_code', 'TEST-INST-A')->first();
        $instB = $instModel->where('institution_code', 'TEST-INST-B')->first();
        $instC = $instModel->where('institution_code', 'TEST-INST-C')->first();

        $this->instAId = (int)$instA['id'];
        $this->instBId = (int)$instB['id'];
        $this->instCId = (int)$instC['id'];
    }

    /**
     * AUTH-01: Valid Login -> 200 OK & JWT Returned
     */
    public function testAuth01ValidLogin(): void
    {
        $result = $this->post('api/v1/auth/login', [
            'username' => 'test_user_a',
            'password' => 'Password123!',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('accessToken', $body['data']);
        $this->assertEquals('Bearer', $body['data']['tokenType']);
        $this->assertEquals('test_user_a', $body['data']['user']['username']);
        $this->assertEquals('USER', $body['data']['user']['role']);
        $this->assertArrayNotHasKey('password_hash', $body['data']['user']);
    }

    /**
     * AUTH-02: Wrong Password -> 401 Unauthorized
     */
    public function testAuth02WrongPassword(): void
    {
        $result = $this->post('api/v1/auth/login', [
            'username' => 'test_user_a',
            'password' => 'CompletelyWrongPassword!',
        ]);

        $result->assertStatus(401);
        $body = json_decode($result->getJSON(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('UNAUTHORIZED', $body['error']['code']);
    }

    /**
     * AUTH-03: Unknown Username -> 401 Unauthorized
     */
    public function testAuth03UnknownUsername(): void
    {
        $result = $this->post('api/v1/auth/login', [
            'username' => 'nonexistent_user_xyz',
            'password' => 'Password123!',
        ]);

        $result->assertStatus(401);
        $body = json_decode($result->getJSON(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('UNAUTHORIZED', $body['error']['code']);
    }

    /**
     * AUTH-04: Inactive User -> 401 Unauthorized
     */
    public function testAuth04InactiveUser(): void
    {
        $result = $this->post('api/v1/auth/login', [
            'username' => 'test_inactive_user',
            'password' => 'Password123!',
        ]);

        $result->assertStatus(401);
        $body = json_decode($result->getJSON(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('UNAUTHORIZED', $body['error']['code']);
    }

    /**
     * AUTH-05: No Token Provided on Protected Endpoint -> 401 Unauthorized
     */
    public function testAuth05NoToken(): void
    {
        $result = $this->get('api/v1/auth/me');

        $result->assertStatus(401);
        $body = json_decode($result->getJSON(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('UNAUTHENTICATED', $body['error']['code']);
    }

    /**
     * AUTH-06: Invalid Token Structure / Signature -> 401 Unauthorized
     */
    public function testAuth06InvalidToken(): void
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer invalid.jwt.token.structure',
        ])->get('api/v1/auth/me');

        $result->assertStatus(401);
        $body = json_decode($result->getJSON(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('UNAUTHENTICATED', $body['error']['code']);
    }

    /**
     * AUTH-07: Expired Token -> 401 Unauthorized
     */
    public function testAuth07ExpiredToken(): void
    {
        // Forge an expired token (expired 1 hour ago)
        $secret = env('jwt.secret', 'default_secret');
        $expiredPayload = [
            'iss' => 'eskld-kemenpanrb',
            'sub' => (int)$this->userA->id,
            'iat' => time() - 7200,
            'exp' => time() - 3600,
            'jti' => bin2hex(random_bytes(16)),
        ];
        $expiredToken = JWT::encode($expiredPayload, $secret, 'HS256');

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $expiredToken,
        ])->get('api/v1/auth/me');

        $result->assertStatus(401);
        $body = json_decode($result->getJSON(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('UNAUTHENTICATED', $body['error']['code']);
    }

    /**
     * AUTH-08: Valid Token -> Authenticated 200 OK on /me and /test
     */
    public function testAuth08ValidToken(): void
    {
        $token = $this->jwtService->generateAccessToken($this->userA, 'USER');

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('api/v1/auth/me');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals('test_user_a', $body['data']['username']);
        $this->assertEquals('USER', $body['data']['role']);
        $this->assertIsArray($body['data']['permissions']);
        $this->assertContains('VIEW', $body['data']['permissions']);
        $this->assertContains('CREATE', $body['data']['permissions']);

        // Test /api/v1/auth/test endpoint
        $testResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('api/v1/auth/test');

        $testResult->assertStatus(200);
        $testBody = json_decode($testResult->getJSON(), true);
        $this->assertTrue($testBody['success']);
        $this->assertEquals('test_user_a', $testBody['data']['username']);
    }

    /**
     * AUTH-09: USER attempting APPROVE -> DENIED (SoD)
     */
    public function testAuth09UserAttemptingApprove(): void
    {
        $canApprove = $this->authzService->can($this->userA, 'APPROVE', $this->instAId, 'VERIFIER_REVIEW');
        $this->assertFalse($canApprove, 'USER must be denied APPROVE permission.');
    }

    /**
     * AUTH-10: ADMIN attempting APPROVE -> DENIED (SoD)
     */
    public function testAuth10AdminAttemptingApprove(): void
    {
        $canApprove = $this->authzService->can($this->admin, 'APPROVE', $this->instAId, 'VERIFIER_REVIEW');
        $this->assertFalse($canApprove, 'ADMIN must be denied APPROVE permission.');
    }

    /**
     * AUTH-11: SUPER_ADMIN attempting APPROVE -> DENIED (SoD)
     */
    public function testAuth11SuperAdminAttemptingApprove(): void
    {
        $canApprove = $this->authzService->can($this->superAdmin, 'APPROVE', $this->instAId, 'VERIFIER_REVIEW');
        $this->assertFalse($canApprove, 'SUPER_ADMIN must be denied APPROVE permission (SoD protection).');
    }

    /**
     * AUTH-12: VERIFIER attempting APPROVE -> ALLOWED
     */
    public function testAuth12VerifierAttemptingApprove(): void
    {
        $canApprove = $this->authzService->can($this->verifier, 'APPROVE', $this->instAId, 'VERIFIER_REVIEW');
        $this->assertTrue($canApprove, 'VERIFIER must be allowed APPROVE in VERIFIER_REVIEW state.');
    }

    /**
     * AUTH-13: VERIFIER attempting VERIFY -> ALLOWED
     */
    public function testAuth13VerifierAttemptingVerify(): void
    {
        $canVerify = $this->authzService->can($this->verifier, 'VERIFY', $this->instAId, 'VERIFIER_REVIEW');
        $this->assertTrue($canVerify, 'VERIFIER must be allowed VERIFY in VERIFIER_REVIEW state.');
    }

    /**
     * AUTH-14: USER own institution -> ALLOWED according to base permissions
     */
    public function testAuth14UserOwnInstitution(): void
    {
        $canView   = $this->authzService->can($this->userA, 'VIEW', $this->instAId);
        $canCreate = $this->authzService->can($this->userA, 'CREATE', $this->instAId);
        $canEdit   = $this->authzService->can($this->userA, 'EDIT', $this->instAId, 'DRAFT');

        $this->assertTrue($canView, 'USER must be allowed VIEW on own institution.');
        $this->assertTrue($canCreate, 'USER must be allowed CREATE on own institution.');
        $this->assertTrue($canEdit, 'USER must be allowed EDIT on own institution in DRAFT state.');
    }

    /**
     * AUTH-15: USER other institution without grant -> DENIED
     */
    public function testAuth15UserOtherInstitutionWithoutGrant(): void
    {
        // User A (Home: A) accessing Inst B without grant
        $canView = $this->authzService->can($this->userA, 'VIEW', $this->instBId);
        $canEdit = $this->authzService->can($this->userA, 'EDIT', $this->instBId);

        $this->assertFalse($canView, 'USER must be denied VIEW on other institution without grant.');
        $this->assertFalse($canEdit, 'USER must be denied EDIT on other institution without grant.');
    }

    /**
     * AUTH-16: USER other institution with active VIEW grant -> ALLOWED VIEW, DENIED EDIT
     */
    public function testAuth16UserOtherInstitutionWithActiveGrant(): void
    {
        // Insert active grant for User A on Inst B with VIEW permission only
        $db = Database::connect();
        $db->table('access_grants')->insert([
            'user_id'               => (int)$this->userA->id,
            'target_institution_id' => $this->instBId,
            'start_date'            => date('Y-m-d'),
            'end_date'              => date('Y-m-d', strtotime('+30 days')),
            'status'                => 'ACTIVE',
            'granted_by'            => (int)$this->admin->id,
            'grant_reason'          => 'Unit testing active grant',
            'created_at'            => date('Y-m-d H:i:s'),
        ]);
        $grantId = $db->insertID();

        $permView = $db->table('permissions')->where('permission_code', 'VIEW')->get()->getRow();
        $db->table('access_grant_permissions')->insert([
            'grant_id'      => $grantId,
            'permission_id' => (int)$permView->id,
        ]);

        $canView = $this->authzService->can($this->userA, 'VIEW', $this->instBId);
        $canEdit = $this->authzService->can($this->userA, 'EDIT', $this->instBId);

        $this->assertTrue($canView, 'USER with active VIEW grant must be allowed VIEW.');
        $this->assertFalse($canEdit, 'USER with VIEW-only grant must be strictly denied EDIT.');

        // Cleanup test grant
        $db->table('access_grant_permissions')->where('grant_id', $grantId)->delete();
        $db->table('access_grants')->where('id', $grantId)->delete();
    }

    /**
     * AUTH-17: USER other institution with expired grant -> DENIED
     */
    public function testAuth17UserOtherInstitutionWithExpiredGrant(): void
    {
        // Insert expired grant (past date)
        $db = Database::connect();
        $db->table('access_grants')->insert([
            'user_id'               => (int)$this->userA->id,
            'target_institution_id' => $this->instBId,
            'start_date'            => '2024-01-01',
            'end_date'              => '2024-01-31',
            'status'                => 'ACTIVE',
            'granted_by'            => (int)$this->admin->id,
            'grant_reason'          => 'Unit testing expired grant',
            'created_at'            => date('Y-m-d H:i:s'),
        ]);
        $grantId = $db->insertID();

        $permView = $db->table('permissions')->where('permission_code', 'VIEW')->get()->getRow();
        $db->table('access_grant_permissions')->insert([
            'grant_id'      => $grantId,
            'permission_id' => (int)$permView->id,
        ]);

        $canView = $this->authzService->can($this->userA, 'VIEW', $this->instBId);

        $this->assertFalse($canView, 'USER with expired grant must be denied access.');

        // Cleanup test grant
        $db->table('access_grant_permissions')->where('grant_id', $grantId)->delete();
        $db->table('access_grants')->where('id', $grantId)->delete();
    }
}
