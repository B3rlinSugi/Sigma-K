import { Injectable, NotFoundException, ConflictException } from '@nestjs/common';
import { PrismaService } from '../../infrastructure/database/prisma.service';
import { CreateOrganizationUnitDto, UpdateOrganizationUnitDto } from './dto/organization.dto';
import { AntiCircularOrgGuard } from './domain/anti-circular.guard';

const DEMO_ORG_UNITS = [
  // Kemenko Pangan Structure
  {
    id: 'unit-pangan-01',
    institutionId: 'inst-kemenko-pangan',
    unitCode: 'MENKO-PGN',
    unitName: 'Menteri Koordinator Bidang Pangan',
    hierarchyLevel: 1,
    echelonName: 'Menteri / Setingkat',
    leaderTitle: 'Menteri Koordinator',
    leaderName: 'Dr. (H.C.) Zulkifli Hasan, S.E., M.M.',
    staffCount: 12,
    tupoksiCount: 6,
    parentId: null,
    isActive: true,
  },
  {
    id: 'unit-pangan-02',
    institutionId: 'inst-kemenko-pangan',
    unitCode: 'SESMENKO-PGN',
    unitName: 'Sekretariat Kementerian Koordinator',
    hierarchyLevel: 2,
    echelonName: 'Eselon I.a',
    leaderTitle: 'Sekretaris Kementerian Koordinator',
    leaderName: 'Ir. H. Budi Hermanto, M.Si.',
    staffCount: 84,
    tupoksiCount: 14,
    parentId: 'unit-pangan-01',
    isActive: true,
  },
  {
    id: 'unit-pangan-03',
    institutionId: 'inst-kemenko-pangan',
    unitCode: 'DEP-1-PGN',
    unitName: 'Deputi Bidang Kedaulatan & Ketersediaan Pangan (Deputi I)',
    hierarchyLevel: 2,
    echelonName: 'Eselon I.a',
    leaderTitle: 'Deputi Bidang Kedaulatan Pangan',
    leaderName: 'Prof. Dr. Ir. Dwi Setyawan, M.Sc.',
    staffCount: 45,
    tupoksiCount: 12,
    parentId: 'unit-pangan-01',
    isActive: true,
  },
  {
    id: 'unit-pangan-04',
    institutionId: 'inst-kemenko-pangan',
    unitCode: 'DEP-2-PGN',
    unitName: 'Deputi Bidang Distribusi, Cadangan & Akses Pangan (Deputi II)',
    hierarchyLevel: 2,
    echelonName: 'Eselon I.a',
    leaderTitle: 'Deputi Bidang Distribusi Pangan',
    leaderName: 'Dr. Hendra Wijaya, S.P., M.Si.',
    staffCount: 40,
    tupoksiCount: 10,
    parentId: 'unit-pangan-01',
    isActive: true,
  },
  {
    id: 'unit-pangan-05',
    institutionId: 'inst-kemenko-pangan',
    unitCode: 'RO-ORTALA-PGN',
    unitName: 'Biro Manajemen Kinerja, Organisasi, dan Tata Kelola',
    hierarchyLevel: 3,
    echelonName: 'Eselon II.a',
    leaderTitle: 'Kepala Biro Ortala',
    leaderName: 'Dra. Endang Sulistyowati, M.M.',
    staffCount: 28,
    tupoksiCount: 8,
    parentId: 'unit-pangan-02',
    isActive: true,
  },
];

let inMemoryOrgUnits: any[] = [...DEMO_ORG_UNITS];

@Injectable()
export class OrganizationsService {
  constructor(private prisma: PrismaService) {}

  async findByInstitution(institutionId: string) {
    try {
      const units = await this.prisma.organizationUnit.findMany({
        where: { institutionId, isActive: true },
        include: { echelon: true },
        orderBy: [{ hierarchyLevel: 'asc' }, { sortOrder: 'asc' }],
      });
      if (units.length > 0) return units;
    } catch {}

    return inMemoryOrgUnits.filter((u) => u.institutionId === institutionId);
  }

  async findAll(search?: string, echelon?: string) {
    try {
      const where: any = { isActive: true };
      if (search) {
        where.OR = [
          { unitName: { contains: search, mode: 'insensitive' } },
          { unitCode: { contains: search, mode: 'insensitive' } },
        ];
      }
      const units = await this.prisma.organizationUnit.findMany({
        where,
        include: { echelon: true },
        orderBy: { hierarchyLevel: 'asc' },
      });
      if (units.length > 0) return units;
    } catch {}

    let result = [...inMemoryOrgUnits];
    if (search) {
      const s = search.toLowerCase();
      result = result.filter(
        (u) => u.unitName.toLowerCase().includes(s) || u.unitCode.toLowerCase().includes(s),
      );
    }
    return result;
  }

  async findById(id: string) {
    try {
      const unit = await this.prisma.organizationUnit.findUnique({
        where: { id },
        include: { echelon: true, tupoksiItems: true, parent: true, children: true },
      });
      if (unit) return unit;
    } catch {}

    const found = inMemoryOrgUnits.find((u) => u.id === id);
    if (!found) throw new NotFoundException(`Unit organisasi dengan ID '${id}' tidak ditemukan.`);
    return found;
  }

  async create(dto: CreateOrganizationUnitDto) {
    // Check circular guard if parentId provided
    if (dto.parentId) {
      const existingUnits = await this.findByInstitution(dto.institutionId);
      const isCircular = AntiCircularOrgGuard.isCircular(
        'NEW_UNIT_ID',
        dto.parentId,
        existingUnits.map((u) => ({ id: u.id, parentId: u.parentId })),
      );
      if (isCircular) {
        throw new ConflictException(
          'Struktur hierarki tidak valid: Hubungan siklis terdeteksi (Circular dependency detected).',
        );
      }
    }

    try {
      return await this.prisma.organizationUnit.create({
        data: {
          institutionId: dto.institutionId,
          parentId: dto.parentId,
          unitCode: dto.unitCode,
          unitName: dto.unitName,
          echelonId: dto.echelonId,
          hierarchyLevel: dto.hierarchyLevel || 1,
          leaderTitle: dto.leaderTitle,
          leaderName: dto.leaderName,
          staffCount: dto.staffCount || 0,
        },
      });
    } catch {
      const newUnit = {
        id: `unit-${Date.now()}`,
        institutionId: dto.institutionId,
        unitCode: dto.unitCode,
        unitName: dto.unitName,
        hierarchyLevel: dto.hierarchyLevel || 1,
        echelonName: 'Unit Struktural',
        leaderTitle: dto.leaderTitle,
        leaderName: dto.leaderName,
        staffCount: dto.staffCount || 0,
        tupoksiCount: 0,
        parentId: dto.parentId || null,
        isActive: true,
      };
      inMemoryOrgUnits.push(newUnit);
      return newUnit;
    }
  }

  async update(id: string, dto: UpdateOrganizationUnitDto) {
    const existing = await this.findById(id);

    // CRITICAL: Anti-Circular Dependency Check when parentId is changing
    if (dto.parentId !== undefined && dto.parentId !== existing.parentId) {
      const allUnits = await this.findByInstitution(existing.institutionId);
      const isCircular = AntiCircularOrgGuard.isCircular(
        id,
        dto.parentId,
        allUnits.map((u) => ({ id: u.id, parentId: u.parentId })),
      );

      if (isCircular) {
        throw new ConflictException(
          'Struktur hierarki tidak valid: Unit kerja tidak dapat menjadi bawahan dari bawahannya sendiri (Circular dependency detected).',
        );
      }
    }

    try {
      return await this.prisma.organizationUnit.update({
        where: { id },
        data: {
          parentId: dto.parentId,
          unitName: dto.unitName,
          unitCode: dto.unitCode,
          echelonId: dto.echelonId,
          leaderTitle: dto.leaderTitle,
          leaderName: dto.leaderName,
          staffCount: dto.staffCount,
          isActive: dto.isActive,
        },
      });
    } catch {
      Object.assign(existing, dto);
      return existing;
    }
  }
}
