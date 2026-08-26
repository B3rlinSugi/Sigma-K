import {
  ExceptionFilter,
  Catch,
  ArgumentsHost,
  HttpException,
  HttpStatus,
} from '@nestjs/common';
import { Request, Response } from 'express';
import { ApiErrorResponse } from '../interfaces/api-response.interface';

@Catch()
export class AllExceptionsFilter implements ExceptionFilter {
  catch(exception: unknown, host: ArgumentsHost) {
    const ctx = host.switchToHttp();
    const response = ctx.getResponse<Response>();
    const request = ctx.getRequest<Request>();

    let status = HttpStatus.INTERNAL_SERVER_ERROR;
    let errorCode = 'INTERNAL_SERVER_ERROR';
    let message = 'Terjadi kesalahan internal server.';
    let details: Array<{ field?: string; issue: string }> | undefined = undefined;

    if (exception instanceof HttpException) {
      status = exception.getStatus();
      const exceptionResponse = exception.getResponse();

      if (typeof exceptionResponse === 'string') {
        message = exceptionResponse;
      } else if (typeof exceptionResponse === 'object' && exceptionResponse !== null) {
        const resObj = exceptionResponse as Record<string, any>;
        message = resObj.message || message;
        errorCode = resObj.error || resObj.code || this.mapStatusToErrorCode(status);

        if (Array.isArray(resObj.message)) {
          errorCode = 'VALIDATION_ERROR';
          message = 'Validasi input data gagal diproses.';
          details = resObj.message.map((msg: string) => ({
            issue: msg,
          }));
        } else if (resObj.details) {
          details = resObj.details;
        }
      }
    } else if (exception instanceof Error) {
      // Don't leak stack trace in production
      if (process.env.NODE_ENV !== 'production') {
        message = exception.message;
      }
    }

    const errorResponse: ApiErrorResponse = {
      success: false,
      statusCode: status,
      error: {
        code: errorCode,
        message: Array.isArray(message) ? message[0] : message,
        details,
      },
      meta: {
        timestamp: new Date().toISOString(),
        requestId: (request.headers['x-request-id'] as string) || `req-${Date.now()}`,
        path: request.url,
      },
    };

    response.status(status).json(errorResponse);
  }

  private mapStatusToErrorCode(status: number): string {
    switch (status) {
      case HttpStatus.BAD_REQUEST:
        return 'BAD_REQUEST';
      case HttpStatus.UNAUTHORIZED:
        return 'UNAUTHORIZED';
      case HttpStatus.FORBIDDEN:
        return 'FORBIDDEN';
      case HttpStatus.NOT_FOUND:
        return 'RESOURCE_NOT_FOUND';
      case HttpStatus.CONFLICT:
        return 'CONFLICT';
      case HttpStatus.UNPROCESSABLE_ENTITY:
        return 'UNPROCESSABLE_ENTITY';
      default:
        return 'INTERNAL_SERVER_ERROR';
    }
  }
}
