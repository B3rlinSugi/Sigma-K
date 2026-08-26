/**
 * Environment Configuration for E-SKLD / SIGMA-K Frontend
 * Supports both API Integration Mode and Isolated Demo Mock Mode.
 */

export type DataTransportMode = 'mock' | 'api';

export interface EnvConfig {
  apiBaseUrl: string;
  dataMode: DataTransportMode;
  requestTimeoutMs: number;
  isApiMode: boolean;
  isMockMode: boolean;
}

const rawMode = (process.env.NEXT_PUBLIC_DATA_MODE || 'mock').toLowerCase();
const dataMode: DataTransportMode = rawMode === 'api' ? 'api' : 'mock';

export const envConfig: EnvConfig = {
  apiBaseUrl: process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8080/api/v1',
  dataMode,
  requestTimeoutMs: parseInt(process.env.NEXT_PUBLIC_REQUEST_TIMEOUT_MS || '15000', 10),
  isApiMode: dataMode === 'api',
  isMockMode: dataMode === 'mock',
};
