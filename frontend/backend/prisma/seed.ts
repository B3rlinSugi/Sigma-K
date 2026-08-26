import { PrismaClient } from '@prisma/client';
import * as bcrypt from 'bcryptjs';

const prisma = new PrismaClient();

async function main() {
  console.log('🌱 Starting SIGMA-K Database Seeding (DEMO / DEVELOPMENT DATA ONLY)...');

  const passwordHash = await bcrypt.hash('Password123!', 10);

  // 1. Roles
  const roles = [
    { id: 1, name: 'USER', description: 'Operator Instansi Kementerian/Lembaga/Pemda', permissions: ['submission.create', 'submission.submit', 'submission.resubmit'] },
    { id: 2, name: 'VERIFIKATOR', description: 'Analis Kelembagaan KemenPANRB', permissions: ['submission.review', 'submission.verify', 'submission.request_revision', 'submission.reject'] },
    { id: 3, name: 'ADMIN', description: 'Administrator Pusat KemenPANRB', permissions: ['*'] },
    { id: 4, name: 'SESDEP', description: 'Pimpinan / Executive Perspective (Prototype Persona)', permissions: ['analytics.view', 'audit.view', 'supervisory.read_all'] },
  ];

  for (const r of roles) {
    await prisma.role.upsert({
      where: { id: r.id },
      update: { name: r.name, description: r.description, permissions: r.permissions },
      create: r,
    });
  }

  // 2. Institution Types
  const types = [
    { id: 1, code: 'KEMENKO', name: 'Kementerian Koordinator', level: 'PUSAT' },
    { id: 2, code: 'KEMENTERIAN', name: 'Kementerian Teknis / Portofolio', level: 'PUSAT' },
    { id: 3, code: 'LPNK', name: 'Lembaga Pemerintah Non-Kementerian', level: 'PUSAT' },
    { id: 4, code: 'LNS', name: 'Lembaga Non-Struktural', level: 'PUSAT' },
    { id: 5, code: 'PEMPROV', name: 'Pemerintah Daerah Provinsi', level: 'DAERAH' },
    { id: 6, code: 'PEMKAB', name: 'Pemerintah Daerah Kabupaten', level: 'DAERAH' },
    { id: 7, code: 'PEMKOT', name: 'Pemerintah Daerah Kota', level: 'DAERAH' },
  ];

  for (const t of types) {
    await prisma.institutionType.upsert({
      where: { id: t.id },
      update: t,
      create: t,
    });
  }

  // 3. Echelon Levels
  const echelons = [
    { id: 1, code: 'I.a', name: 'Pimpinan Tinggi Madya (Eselon I.a)', rankOrder: 1 },
    { id: 2, code: 'I.b', name: 'Pimpinan Tinggi Madya (Eselon I.b)', rankOrder: 2 },
    { id: 3, code: 'II.a', name: 'Pimpinan Tinggi Pratama (Eselon II.a)', rankOrder: 3 },
    { id: 4, code: 'II.b', name: 'Pimpinan Tinggi Pratama (Eselon II.b)', rankOrder: 4 },
    { id: 5, code: 'NON_ESELON', name: 'Jabatan Fungsional / Non-Eselon', rankOrder: 5 },
  ];

  for (const e of echelons) {
    await prisma.echelonLevel.upsert({
      where: { id: e.id },
      update: e,
      create: e,
    });
  }

  console.log('✅ Master data roles, types, and echelons seeded.');
}

main()
  .catch((e) => {
    console.error('Seed error:', e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
