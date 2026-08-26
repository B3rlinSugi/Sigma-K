import {
  Injectable,
  NestInterceptor,
  ExecutionContext,
  CallHandler,
} from '@nestjs/common';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { ApiResponse } from '../interfaces/api-response.interface';

@Injectable()
export class ResponseTransformInterceptor<T>
  implements NestInterceptor<T, ApiResponse<T>>
{
  intercept(
    context: ExecutionContext,
    next: CallHandler,
  ): Observable<ApiResponse<T>> {
    const request = context.switchToHttp().getRequest();
    const statusCode = context.switchToHttp().getResponse().statusCode;

    return next.handle().pipe(
      map((res) => {
        // If response is already an ApiResponse envelope, return directly
        if (res && typeof res === 'object' && 'success' in res && 'data' in res) {
          return res;
        }

        // If response is paginated ({ data, meta })
        if (res && typeof res === 'object' && 'data' in res && 'meta' in res) {
          return {
            success: true,
            statusCode,
            data: res.data,
            meta: {
              ...res.meta,
              timestamp: new Date().toISOString(),
              requestId: (request.headers['x-request-id'] as string) || `req-${Date.now()}`,
            },
          };
        }

        return {
          success: true,
          statusCode,
          message: res?.message || 'Operasi berhasil dieksekusi',
          data: res?.data !== undefined ? res.data : res,
          meta: {
            timestamp: new Date().toISOString(),
            requestId: (request.headers['x-request-id'] as string) || `req-${Date.now()}`,
          },
        };
      }),
    );
  }
}
