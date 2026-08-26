import { ApiErrorResponse } from './types';

/**
 * Normalized Application & API Error Class
 * Standardizes backend HTTP response errors across the UI layer.
 */
export class AppError extends Error {
  public readonly statusCode: number;
  public readonly code: string;
  public readonly details?: Record<string, string | string[]>;
  public readonly timestamp?: string;
  public readonly isNetworkError: boolean;

  constructor(params: {
    message: string;
    statusCode: number;
    code: string;
    details?: Record<string, string | string[]>;
    timestamp?: string;
    isNetworkError?: boolean;
  }) {
    super(params.message);
    this.name = 'AppError';
    this.statusCode = params.statusCode;
    this.code = params.code;
    this.details = params.details;
    this.timestamp = params.timestamp;
    this.isNetworkError = params.isNetworkError ?? false;

    // Maintain proper prototype chain
    Object.setPrototypeOf(this, AppError.prototype);
  }

  /**
   * Helper predicates for UI error handling
   */
  public isUnauthorized(): boolean {
    return this.statusCode === 401 || this.code === 'UNAUTHORIZED';
  }

  public isForbidden(): boolean {
    return this.statusCode === 403 || this.code === 'FORBIDDEN';
  }

  public isNotFound(): boolean {
    return this.statusCode === 404 || this.code === 'NOT_FOUND';
  }

  public isConflict(): boolean {
    return this.statusCode === 409 || this.code === 'CONFLICT';
  }

  public isValidationError(): boolean {
    return this.statusCode === 422 || this.code === 'VALIDATION_FAILED';
  }

  public isRateLimited(): boolean {
    return this.statusCode === 429 || this.code === 'RATE_LIMITED';
  }

  public isServerError(): boolean {
    return this.statusCode >= 500;
  }

  /**
   * Factory: Create from HTTP error response payload
   */
  public static fromApiResponse(status: number, body: unknown): AppError {
    const errObj = body as Partial<ApiErrorResponse>;

    if (errObj && errObj.error && typeof errObj.error === 'object') {
      return new AppError({
        statusCode: status,
        code: errObj.error.code || `HTTP_${status}`,
        message: errObj.error.message || `Request failed with status ${status}`,
        details: errObj.error.details,
        timestamp: errObj.meta?.timestamp,
      });
    }

    // Fallback standard HTTP status mapping
    const defaultCodes: Record<number, string> = {
      400: 'BAD_REQUEST',
      401: 'UNAUTHORIZED',
      403: 'FORBIDDEN',
      404: 'NOT_FOUND',
      409: 'CONFLICT',
      422: 'VALIDATION_FAILED',
      429: 'RATE_LIMITED',
      500: 'INTERNAL_SERVER_ERROR',
    };

    return new AppError({
      statusCode: status,
      code: defaultCodes[status] || `HTTP_${status}`,
      message: typeof body === 'string' ? body : `Request failed with status ${status}`,
    });
  }

  /**
   * Factory: Create from Network or Fetch Failure
   */
  public static fromNetwork(err: unknown): AppError {
    const msg = err instanceof Error ? err.message : 'Network connection failed';
    return new AppError({
      statusCode: 0,
      code: 'NETWORK_ERROR',
      message: msg.includes('aborted') ? 'Request timed out.' : 'Failed to connect to the backend server.',
      isNetworkError: true,
    });
  }
}
