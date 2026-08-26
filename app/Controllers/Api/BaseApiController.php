<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * BaseApiController
 * 
 * Foundational API Controller providing standardized JSON response formatting
 * conforming to E-SKLD REST API Contract Specification V1.
 */
abstract class BaseApiController extends BaseController
{
    /**
     * Standard Success Response (200 OK / 201 Created)
     *
     * @param mixed       $data
     * @param string      $message
     * @param int         $statusCode
     * @param array       $meta
     * @return ResponseInterface
     */
    protected function respondSuccess($data = null, string $message = 'Resource retrieved successfully.', int $statusCode = 200, array $meta = []): ResponseInterface
    {
        $payload = [
            'success'    => true,
            'statusCode' => $statusCode,
            'message'    => $message,
            'data'       => $data,
            'meta'       => array_merge([
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            ], $meta),
        ];

        return $this->response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setJSON($payload);
    }

    /**
     * Standard Resource Created Response (201 Created)
     *
     * @param mixed  $data
     * @param string $message
     * @param array  $meta
     * @return ResponseInterface
     */
    protected function respondCreated($data = null, string $message = 'Resource created successfully.', array $meta = []): ResponseInterface
    {
        return $this->respondSuccess($data, $message, 201, $meta);
    }

    /**
     * Standard Error Response
     *
     * @param string $code
     * @param string $message
     * @param int    $statusCode
     * @param array  $details
     * @return ResponseInterface
     */
    protected function respondError(string $code, string $message, int $statusCode = 400, array $details = []): ResponseInterface
    {
        $payload = [
            'success'    => false,
            'statusCode' => $statusCode,
            'error'      => [
                'code'    => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta'       => [
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            ],
        ];

        return $this->response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setJSON($payload);
    }

    /**
     * 422 Validation Error
     *
     * @param array  $details
     * @param string $message
     * @return ResponseInterface
     */
    protected function respondValidationError(array $details, string $message = 'The given input data was invalid.'): ResponseInterface
    {
        return $this->respondError('VALIDATION_FAILED', $message, 422, $details);
    }

    /**
     * 401 Unauthorized Error
     *
     * @param string $message
     * @return ResponseInterface
     */
    protected function respondUnauthorized(string $message = 'Authentication required or token expired.'): ResponseInterface
    {
        return $this->respondError('UNAUTHORIZED', $message, 401);
    }

    /**
     * 403 Forbidden Error
     *
     * @param string $message
     * @return ResponseInterface
     */
    protected function respondForbidden(string $message = 'You do not have permission to access this resource.'): ResponseInterface
    {
        return $this->respondError('FORBIDDEN', $message, 403);
    }

    /**
     * 404 Not Found Error
     *
     * @param string $message
     * @return ResponseInterface
     */
    protected function respondNotFound(string $message = 'The requested resource was not found.'): ResponseInterface
    {
        return $this->respondError('NOT_FOUND', $message, 404);
    }

    /**
     * 409 Conflict Error (State Machine or Constraint Collision)
     *
     * @param string $message
     * @return ResponseInterface
     */
    protected function respondConflict(string $message = 'Action conflicts with current resource state.'): ResponseInterface
    {
        return $this->respondError('CONFLICT', $message, 409);
    }

    /**
     * 500 Internal Server Error
     *
     * @param string $message
     * @return ResponseInterface
     */
    protected function respondServerError(string $message = 'An unexpected internal server error occurred.'): ResponseInterface
    {
        return $this->respondError('INTERNAL_SERVER_ERROR', $message, 500);
    }
}
