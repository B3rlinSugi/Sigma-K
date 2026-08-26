export const appConfig = () => ({
  port: parseInt(process.env.PORT || '3001', 10),
  nodeEnv: process.env.NODE_ENV || 'development',
  apiPrefix: process.env.API_PREFIX || '/api/v1',
  corsOrigin: process.env.CORS_ORIGIN || 'http://localhost:3000',
  jwt: {
    secret: process.env.JWT_SECRET || 'sigmak-provisional-secret-key-2026',
    expiration: process.env.JWT_EXPIRATION || '15m',
    refreshSecret: process.env.JWT_REFRESH_SECRET || 'sigmak-provisional-refresh-secret-2026',
    refreshExpiration: process.env.JWT_REFRESH_EXPIRATION || '7d',
  },
  storage: {
    driver: process.env.STORAGE_DRIVER || 'local',
    localPath: process.env.LOCAL_STORAGE_PATH || './uploads',
    maxFileSizeMb: parseInt(process.env.MAX_FILE_SIZE_MB || '10', 10),
  },
  realtime: {
    transport: process.env.REALTIME_TRANSPORT || 'internal',
  },
});
