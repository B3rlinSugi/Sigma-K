/**
 * HTTP Client Types & Standard Envelope Interfaces
 * Compatible with CodeIgniter 4 BaseApiController JSON formatting.
 */

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

export interface HttpRequestConfig {
  headers?: Record<string, string>;
  params?: Record<string, string | number | boolean | undefined | null>;
  body?: unknown;
  timeout?: number;
  signal?: AbortSignal;
  skipAuth?: boolean;
}

/**
 * Standard Backend Success Envelope
 * Returned by BaseApiController::respondSuccess
 */
export interface ApiSuccessResponse<T> {
  success: true;
  statusCode: number;
  message: string;
  data: T;
  meta?: {
    timestamp?: string;
    total?: number;
    page?: number;
    perPage?: number;
    [key: string]: unknown;
  };
}

/**
 * Standard Backend Error Envelope
 * Returned by BaseApiController::respondError
 */
export interface ApiErrorPayload {
  code: string;
  message: string;
  details?: Record<string, string | string[]>;
}

export interface ApiErrorResponse {
  success: false;
  statusCode: number;
  error: ApiErrorPayload;
  meta?: {
    timestamp?: string;
  };
}
