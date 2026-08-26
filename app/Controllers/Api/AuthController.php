<?php

namespace App\Controllers\Api;

use App\Services\Auth\AuthContext;
use App\Services\Auth\AuthService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * AuthController
 *
 * REST API Controller for User Authentication, Profile Loading, and Logout.
 */
class AuthController extends BaseApiController
{
    protected AuthService $authService;

    public function __construct(?AuthService $authService = null)
    {
        $this->authService = $authService ?? new AuthService();
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost() ?: [];
        if (empty($input)) {
            $input = [
                'username' => $this->request->getVar('username'),
                'password' => $this->request->getVar('password'),
            ];
        }

        $rules = [
            'username' => 'required|min_length[3]|max_length[100]',
            'password' => 'required|min_length[4]|max_length[255]',
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $username = (string)($input['username'] ?? '');
        $password = (string)($input['password'] ?? '');
        $ip = $this->request->getIPAddress();
        $userAgent = (string)$this->request->getUserAgent();

        try {
            $result = $this->authService->authenticate($username, $password, $ip, $userAgent);
            return $this->respondSuccess($result, 'Login successful.');
        } catch (Throwable $e) {
            return $this->respondUnauthorized('Invalid credentials or inactive account.');
        }
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized('Unauthenticated.');
        }

        try {
            $profile = $this->authService->getCurrentUserProfile((int)$user->id);
            return $this->respondSuccess($profile, 'User profile retrieved successfully.');
        } catch (Throwable $e) {
            return $this->respondUnauthorized('Unable to load user profile.');
        }
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(): ResponseInterface
    {
        $user = AuthContext::getUser();
        $this->authService->logout($user);

        return $this->respondSuccess(
            [
                'loggedOut' => true,
                'note'      => 'Token invalidated on client side.',
            ],
            'Logged out successfully.'
        );
    }

    /**
     * GET /api/v1/auth/test
     */
    public function testAuth(): ResponseInterface
    {
        $user = AuthContext::getUser();
        if (!$user) {
            return $this->respondUnauthorized('Unauthenticated.');
        }

        return $this->respondSuccess([
            'userId'   => (int)$user->id,
            'username' => $user->username,
            'status'   => $user->status,
        ], 'Authenticated test successful.');
    }
}
