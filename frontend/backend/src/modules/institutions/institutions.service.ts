import { Injectable, NotFoundException, ConflictException } from '@nestjs/common';
import { PrismaService } from '../../infrastructure/database/prisma.service';
import { CreateInstitutionDto, UpdateInstitutionDto } from './dto/institution.dto';

const DEMO_INSTITUTIONS = [
  {
    id: 'inst-kemenko-pangan',
    code: 'KL-042',
    name: 'Kementerian Koordinator Bidang Pangan',
    shortName: 'Kemenko Pangan',
    category: 'KEMENKO',
    type: { id: 1, code: 'KEMENKO', name: 'Kementerian Koordinator' },
    status: 'ACTIVE',
    profile: {
      legalBasisNumber: 'Perpres No. 147 Tahun 2024',
      address: 'Jl. Merdeka Barat No. 8, Jakarta Pusat',
      email: 'humas@pangan.go.id',
      phone: '(021) 345-6789',
      website: 'https://pangan.go.id',
    },
    totalUnits: 14,
    totalStaff: 320,
    totalTupoksi: 48,
    createdAt: new Date().toISOString(),
  },
  {
    id: 'inst-kemendikdasmen',
    code: 'KL-043',
    name: 'Kementerian Pendidikan Dasar dan Menengah',
    shortName: 'Kemendikdasmen',
    category: 'TEKNIS',
    type: { id: 2, code: 'KEMENTERIAN', name: 'Kementerian' },
    status: 'ACTIVE',
    profile: {
      legalBasisNumber: 'Perpres No. 188 Tahun 2024',
      address: 'Jl. Jenderal Sudirman, Senayan, Jakarta Pusat',
      email: 'pengaduan@kemendikdasmen.go.id',
      phone: '(021) 571-1144',
      website: 'https://kemendikdasmen.go.id',
    },
    totalUnits: 28,
    totalStaff: 1420,
    totalTupoksi: 92,
    createdAt: new Date().toISOString(),
  },
  {
    id: 'inst-kemenpanrb',
    code: 'KL-007',
    name: 'Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi',
    shortName: 'KemenPANRB',
    category: 'TEKNIS',
    type: { id: 2, code: 'KEMENTERIAN', name: 'Kementerian' },
    status: 'ACTIVE',
    profile: {
      legalBasisNumber: 'Perpres No. 47 Tahun 2021',
      address: 'Jl. Jend. Sudirman Kav. 69, Jakarta Selatan',
      email: 'persuratan@menpan.go.id',
      phone: '(021) 739-8381',
      website: 'https://menpan.go.id',
    },
    totalUnits: 22,
    totalStaff: 890,
    totalTupoksi: 74,
    createdAt: new Date().toISOString(),
  },
  {
    id: 'inst-kemendiktisaintek',
    code: 'KL-044',
    name: 'Kementerian Pendidikan Tinggi, Sains, dan Teknologi',
    shortName: 'Kemendiktisaintek',
    category: 'TEKNIS',
    type: { id: 2, code: 'KEMENTERIAN', name: 'Kementerian' },
    status: 'ACTIVE',
    profile: {
      legalBasisNumber: 'Perpres No. 189 Tahun 2024',
      address: 'Jl. Pintu Satu Senayan, Jakarta Pusat',
      email: 'humas@kemendiktisaintek.go.id',
      phone: '(021) 5794-6100',
      website: 'https://kemendiktisaintek.go.id',
    },
    totalUnits: 24,
    totalStaff: 1100,
    totalTupoksi: 86,
    createdAt: new Date().toISOString(),
  },
  {
    id: 'inst-kemenbud',
    code: 'KL-045',
    name: 'Kementerian Kebudayaan',
    shortName: 'Kemenbud',
    category: 'TEKNIS',
    type: { id: 2, code: 'KEMENTERIAN', name: 'Kementerian' },
    status: 'ACTIVE',
    profile: {
      legalBasisNumber: 'Perpres No. 190 Tahun 2024',
      address: 'Kompleks Kemendikbud Gedung E, Senayan, Jakarta',
      email: 'info@kemenbud.go.id',
      phone: '(021) 572-5035',
      website: 'https://kebudayaan.go.id',
    },
    totalUnits: 16,
    totalStaff: 640,
    totalTupoksi: 52,
    createdAt: new Date().toISOString(),
  },
  {
    id: 'inst-pemprov-dki',
    code: 'PEMDA-31',
    name: 'Pemerintah Provinsi Daerah Khusus Ibukota Jakarta',
    shortName: 'Pemprov DKI Jakarta',
    category: 'PEMDA',
    type: { id: 3, code: 'PEMPROV', name: 'Pemerintah Daerah Provinsi' },
    status: 'ACTIVE',
    profile: {
      legalBasisNumber: 'UU No. 2 Tahun 2024 (UU DKJ)',
      address: 'Jl. Medan Merdeka Selatan No. 8-9, Jakarta Pusat',
      email: 'dki@jakarta.go.id',
      phone: '(021) 382-2000',
      website: 'https://jakarta.go.id',
    },
    totalUnits: 45,
    totalStaff: 12400,
    totalTupoksi: 160,
    createdAt: new Date().toISOString(),
  },
];

let inMemoryInstitutions: any[] = [...DEMO_INSTITUTIONS];

@Injectable()
export class InstitutionsService {
  constructor(private prisma: PrismaService) {}

  async findAll(query?: {
    search?: string;
    type?: string;
    status?: string;
    page?: number;
    pageSize?: number;
  }) {
    const page = Number(query?.page) || 1;
    const pageSize = Number(query?.pageSize) || 20;

    try {
      const where: any = {};
      if (query?.status) where.status = query.status;
      if (query?.type) where.type = { code: query.type };
      if (query?.search) {
        where.OR = [
          { name: { contains: query.search, mode: 'insensitive' } },
          { shortName: { contains: query.search, mode: 'insensitive' } },
          { code: { contains: query.search, mode: 'insensitive' } },
        ];
      }

      const [total, items] = await Promise.all([
        this.prisma.institution.count({ where }),
        this.prisma.institution.findMany({
          where,
          include: { type: true, profile: true },
          skip: (page - 1) * pageSize,
          take: pageSize,
          orderBy: { name: 'asc' },
        }),
      ]);

      const totalPages = Math.ceil(total / pageSize) || 1;

      return {
        data: items,
        meta: {
          page,
          pageSize,
          total,
          totalPages,
          hasNextPage: page < totalPages,
          hasPreviousPage: page > 1,
        },
      };
    } catch {
      // In-memory fallback
      let result = [...inMemoryInstitutions];
      if (query?.search) {
        const s = query.search.toLowerCase();
        result = result.filter(
          (i) =>
            i.name.toLowerCase().includes(s) ||
            i.shortName.toLowerCase().includes(s) ||
            i.code.toLowerCase().includes(s),
        );
      }
      if (query?.type) {
        result = result.filter((i) => i.category === query.type || i.type?.code === query.type);
      }

      const total = result.length;
      const totalPages = Math.ceil(total / pageSize) || 1;
      const paginated = result.slice((page - 1) * pageSize, page * pageSize);

      return {
        data: paginated,
        meta: {
          page,
          pageSize,
          total,
          totalPages,
          hasNextPage: page < totalPages,
          hasPreviousPage: page > 1,
        },
      };
    }
  }

  async findById(id: string) {
    try {
      const inst = await this.prisma.institution.findUnique({
        where: { id },
        include: {
          type: true,
          profile: true,
          organizationUnits: { take: 10 },
          tupoksiItems: { take: 10 },
        },
      });
      if (inst) return inst;
    } catch {}

    const found = inMemoryInstitutions.find((i) => i.id === id || i.code === id);
    if (!found) {
      throw new NotFoundException(`Instansi dengan ID '${id}' tidak ditemukan.`);
    }
    return found;
  }

  async create(dto: CreateInstitutionDto) {
    try {
      const existing = await this.prisma.institution.findUnique({
        where: { code: dto.code },
      });
      if (existing) {
        throw new ConflictException(`Kode instansi '${dto.code}' sudah terdaftar.`);
      }

      return await this.prisma.institution.create({
        data: {
          code: dto.code,
          name: dto.name,
          shortName: dto.shortName,
          typeId: dto.typeId,
          regionId: dto.regionId,
          status: dto.status || 'ACTIVE',
          profile: {
            create: {
              legalBasisNumber: dto.legalBasisNumber,
              phone: dto.phone,
              email: dto.email,
              website: dto.website,
            },
          },
        },
        include: { type: true, profile: true },
      });
    } catch (err: any) {
      if (err instanceof ConflictException) throw err;
      const newInst = {
        id: `inst-${Date.now()}`,
        code: dto.code,
        name: dto.name,
        shortName: dto.shortName,
        category: 'TEKNIS',
        type: { id: dto.typeId, code: 'KEMENTERIAN', name: 'Kementerian' },
        status: dto.status || 'ACTIVE',
        profile: {
          legalBasisNumber: dto.legalBasisNumber || '-',
          email: dto.email || '-',
          phone: dto.phone || '-',
          website: dto.website || '-',
        },
        totalUnits: 0,
        totalStaff: 0,
        totalTupoksi: 0,
        createdAt: new Date().toISOString(),
      };
      inMemoryInstitutions.push(newInst);
      return newInst;
    }
  }

  async update(id: string, dto: UpdateInstitutionDto) {
    const existing = await this.findById(id);
    try {
      return await this.prisma.institution.update({
        where: { id },
        data: {
          name: dto.name,
          shortName: dto.shortName,
          status: dto.status,
          profile: {
            upsert: {
              create: {
                address: dto.address,
                phone: dto.phone,
                email: dto.email,
                website: dto.website,
              },
              update: {
                address: dto.address,
                phone: dto.phone,
                email: dto.email,
                website: dto.website,
              },
            },
          },
        },
        include: { type: true, profile: true },
      });
    } catch {
      Object.assign(existing, dto);
      return existing;
    }
  }
}
