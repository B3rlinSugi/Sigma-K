<?php

namespace App\Filters;

use App\Models\UserModel;
use App\Services\Auth\AuthContext;
use App\Services\Auth\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * AuthFilter
 *
 * Intercepts protected API endpoints, verifies JWT Bearer token,
 * checks user account active status, and binds user to AuthContext.
 */
class AuthFilter implements FilterInterface
{
    protected JwtService $jwtService;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->jwtService = new JwtService();
        $this->userModel  = new UserModel();
    }

    /**
     * Inspect request before controller execution.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        AuthContext::clear();

        $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader) || !preg_match('/^Bearer\s+(.*?)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Authentication is required.');
        }

        $token = $matches[1];

        try {
            $decoded = $this->jwtService->validateToken($token);
            $userId = (int)($decoded->sub ?? 0);

            if ($userId <= 0) {
                return $this->unauthorizedResponse('Invalid token claims.');
            }

            $user = $this->userModel->find($userId);
            if (!$user || !$user->isActive()) {
                return $this->unauthorizedResponse('User account is invalid or inactive.');
            }

            // Bind user to request context
            AuthContext::setUser($user);
            return $request;
        } catch (Throwable $e) {
            log_message('debug', '[AuthFilter] Token validation failed: ' . $e->getMessage());
            return $this->unauthorizedResponse('Invalid or expired authentication token.');
        }
    }

    /**
     * Perform cleanup after controller execution.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        AuthContext::clear();
    }

    /**
     * Generate standard 401 Unauthorized JSON response.
     */
    protected function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = service('response');
        return $response
            ->setStatusCode(401)
            ->setContentType('application/json')
            ->setJSON([
                'success'    => false,
                'statusCode' => 401,
                'error'      => [
                    'code'    => 'UNAUTHENTICATED',
                    'message' => $message,
                    'details' => [],
                ],
                'meta'       => [
                    'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
                ],
            ]);
    }
}
