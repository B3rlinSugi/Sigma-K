import { NestFactory } from '@nestjs/core';
import { ValidationPipe, Logger } from '@nestjs/common';
import { SwaggerModule, DocumentBuilder } from '@nestjs/swagger';
import { AppModule } from './app.module';
import { AllExceptionsFilter } from './common/filters/http-exception.filter';
import { ResponseTransformInterceptor } from './common/interceptors/response-transform.interceptor';

async function bootstrap() {
  const logger = new Logger('SIGMA-K-Bootstrap');
  const app = await NestFactory.create(AppModule);

  // 1. API Global Prefix
  const apiPrefix = process.env.API_PREFIX || '/api/v1';
  app.setGlobalPrefix(apiPrefix.replace(/^\//, ''));

  // 2. CORS
  app.enableCors({
    origin: process.env.CORS_ORIGIN || 'http://localhost:3000',
    credentials: true,
  });

  // 3. Global Filters, Interceptors, and Pipes
  app.useGlobalFilters(new AllExceptionsFilter());
  app.useGlobalInterceptors(new ResponseTransformInterceptor());
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      forbidNonWhitelisted: true,
      transform: true,
      transformOptions: {
        enableImplicitConversion: true,
      },
    }),
  );

  // 4. OpenAPI / Swagger Documentation
  const swaggerConfig = new DocumentBuilder()
    .setTitle('SIGMA-K REST API v1')
    .setDescription(
      'Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan — KemenPANRB',
    )
    .setVersion('1.0.0')
    .addBearerAuth()
    .build();

  const document = SwaggerModule.createDocument(app, swaggerConfig);
  SwaggerModule.setup('api/docs', app, document);

  // 5. Start Server
  const port = process.env.PORT || 3001;
  await app.listen(port);
  logger.log(`=======================================================`);
  logger.log(`🚀 SIGMA-K Backend running on: http://localhost:${port}/${apiPrefix.replace(/^\//, '')}`);
  logger.log(`📚 Swagger API Docs available at: http://localhost:${port}/api/docs`);
  logger.log(`=======================================================`);
}

bootstrap();
