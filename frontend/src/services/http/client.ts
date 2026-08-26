import { envConfig } from '@/config/env.config';
import { AppError } from './errors';
import { authTokenProvider, AuthTokenProvider } from './token-provider';
import { ApiSuccessResponse, HttpRequestConfig } from './types';

/**
 * Centralized Production HTTP Client
 * Uses native fetch with automatic JSON handling, authorization injection, and error normalization.
 */
export class HttpClient {
  private baseUrl: string;
  private tokenProvider: AuthTokenProvider;
  private defaultTimeout: number;

  constructor(options?: {
    baseUrl?: string;
    tokenProvider?: AuthTokenProvider;
    timeoutMs?: number;
  }) {
    this.baseUrl = options?.baseUrl || envConfig.apiBaseUrl;
    this.tokenProvider = options?.tokenProvider || authTokenProvider;
    this.defaultTimeout = options?.timeoutMs || envConfig.requestTimeoutMs;
  }

  /**
   * Core request dispatcher
   */
  public async request<T>(endpoint: string, method: string, config?: HttpRequestConfig): Promise<T> {
    const url = this.buildUrl(endpoint, config?.params);
    const headers = new Headers(config?.headers || {});

    // Set standard headers
    if (!headers.has('Accept')) {
      headers.set('Accept', 'application/json');
    }
    if (!headers.has('Content-Type') && config?.body !== undefined && !(config.body instanceof FormData)) {
      headers.set('Content-Type', 'application/json');
    }

    // Attach Authorization Bearer token if present and not skipped
    if (!config?.skipAuth) {
      const token = this.tokenProvider.getAccessToken();
      if (token && !headers.has('Authorization')) {
        headers.set('Authorization', `Bearer ${token}`);
      }
    }

    // Setup Timeout Signal
    const timeoutMs = config?.timeout || this.defaultTimeout;
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

    // Combine user signal with timeout signal
    if (config?.signal) {
      config.signal.addEventListener('abort', () => controller.abort());
    }

    try {
      let bodyData: BodyInit | null | undefined = undefined;
      if (config?.body !== undefined) {
        bodyData = config.body instanceof FormData ? config.body : JSON.stringify(config.body);
      }

      const response = await fetch(url, {
        method,
        headers,
        body: bodyData,
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      // Parse JSON payload
      let parsedBody: unknown = null;
      const contentType = response.headers.get('content-type') || '';
      if (contentType.includes('application/json')) {
        try {
          parsedBody = await response.json();
        } catch {
          parsedBody = null;
        }
      } else {
        parsedBody = await response.text();
      }

      // Handle non-2xx HTTP errors
      if (!response.ok) {
        throw AppError.fromApiResponse(response.status, parsedBody);
      }

      // Handle standard CodeIgniter success envelope unwrapping
      if (parsedBody && typeof parsedBody === 'object' && 'success' in parsedBody) {
        const envelope = parsedBody as ApiSuccessResponse<T>;
        if (envelope.success === true) {
          return envelope.data;
        }
      }

      return parsedBody as T;
    } catch (error) {
      clearTimeout(timeoutId);

      if (error instanceof AppError) {
        throw error;
      }

      throw AppError.fromNetwork(error);
    }
  }

  public get<T>(endpoint: string, config?: HttpRequestConfig): Promise<T> {
    return this.request<T>(endpoint, 'GET', config);
  }

  public post<T>(endpoint: string, body?: unknown, config?: HttpRequestConfig): Promise<T> {
    return this.request<T>(endpoint, 'POST', { ...config, body });
  }

  public put<T>(endpoint: string, body?: unknown, config?: HttpRequestConfig): Promise<T> {
    return this.request<T>(endpoint, 'PUT', { ...config, body });
  }

  public patch<T>(endpoint: string, body?: unknown, config?: HttpRequestConfig): Promise<T> {
    return this.request<T>(endpoint, 'PATCH', { ...config, body });
  }

  public delete<T>(endpoint: string, config?: HttpRequestConfig): Promise<T> {
    return this.request<T>(endpoint, 'DELETE', config);
  }

  /**
   * Fetch raw Blob for file downloads (e.g. CSV / PDF exports)
   */
  public async getBlob(endpoint: string, config?: HttpRequestConfig): Promise<Blob> {
    const url = this.buildUrl(endpoint, config?.params);
    const headers = new Headers(config?.headers || {});

    if (!config?.skipAuth) {
      const token = this.tokenProvider.getAccessToken();
      if (token && !headers.has('Authorization')) {
        headers.set('Authorization', `Bearer ${token}`);
      }
    }

    const timeoutMs = config?.timeout || this.defaultTimeout;
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

    try {
      const response = await fetch(url, {
        method: 'GET',
        headers,
        signal: controller.signal,
      });
      clearTimeout(timeoutId);

      if (!response.ok) {
        let errBody: unknown = null;
        try {
          errBody = await response.json();
        } catch {
          errBody = await response.text();
        }
        throw AppError.fromApiResponse(response.status, errBody);
      }

      return await response.blob();
    } catch (error) {
      clearTimeout(timeoutId);
      if (error instanceof AppError) throw error;
      throw AppError.fromNetwork(error);
    }
  }

  /**
   * Helper: construct full URL with query parameters
   */
  private buildUrl(endpoint: string, params?: Record<string, string | number | boolean | undefined | null>): string {
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    const fullUrl = `${this.baseUrl.replace(/\/+$/, '')}${cleanEndpoint}`;

    if (!params) {
      return fullUrl;
    }

    const url = new URL(fullUrl);
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        url.searchParams.append(key, String(value));
      }
    });

    return url.toString();
  }
}

export const httpClient = new HttpClient();
