import { Injectable, OnModuleInit, OnModuleDestroy } from '@nestjs/common';
import { PrismaClient } from '@prisma/client';

@Injectable()
export class PrismaService extends PrismaClient implements OnModuleInit, OnModuleDestroy {
  constructor() {
    super({
      log: process.env.NODE_ENV === 'development' ? ['warn', 'error'] : ['error'],
    });
  }

  async onModuleInit() {
    // Only connect if DATABASE_URL is configured and not running in pure mock test mode
    if (process.env.DATABASE_URL) {
      try {
        await this.$connect();
      } catch (err) {
        console.warn('Prisma could not connect to PostgreSQL (Development mode running with simulated state):', (err as Error).message);
      }
    }
  }

  async onModuleDestroy() {
    await this.$disconnect();
  }
}
