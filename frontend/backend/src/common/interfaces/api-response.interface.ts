export interface ApiResponse<T = any> {
  success: boolean;
  statusCode: number;
  message?: string;
  data: T;
  meta?: {
    timestamp: string;
    requestId?: string;
    [key: string]: any;
  };
}

export interface ApiErrorResponse {
  success: false;
  statusCode: number;
  error: {
    code: string;
    message: string;
    details?: Array<{
      field?: string;
      issue: string;
    }>;
  };
  meta: {
    timestamp: string;
    requestId?: string;
    path?: string;
  };
}
