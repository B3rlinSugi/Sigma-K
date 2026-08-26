import { Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../../infrastructure/database/prisma.service';
import { CreateCabinetDto, AddCabinetMembershipDto } from './dto/cabinet.dto';

const DEMO_CABINETS = [
  {
    id: 'cab-merah-putih',
    name: 'Kabinet Merah Putih',
    presidentName: 'Prabowo Subianto',
    vicePresidentName: 'Gibran Rakabuming Raka',
    description: 'Kabinet Pemerintahan Republik Indonesia periode 2024–2029 hasil restrukturisasi kementerian dan pembentukan kementerian koordinator baru.',
    isActive: true,
    periodsCount: 1,
    totalMembers: 48,
    createdAt: '2024-10-21T00:00:00Z',
  },
  {
    id: 'cab-indonesia-maju',
    name: 'Kabinet Indonesia Maju',
    presidentName: 'Joko Widodo',
    vicePresidentName: "Ma'ruf Amin",
    description: 'Kabinet Pemerintahan Republik Indonesia periode 2019–2024.',
    isActive: false,
    periodsCount: 1,
    totalMembers: 34,
    createdAt: '2019-10-23T00:00:00Z',
  },
  {
    id: 'cab-kerja',
    name: 'Kabinet Kerja',
    presidentName: 'Joko Widodo',
    vicePresidentName: 'Jusuf Kalla',
    description: 'Kabinet Pemerintahan Republik Indonesia periode 2014–2019.',
    isActive: false,
    periodsCount: 1,
    totalMembers: 34,
    createdAt: '2014-10-27T00:00:00Z',
  },
];

const DEMO_LINEAGES = [
  {
    id: 'lin-01',
    predecessorInstitutionId: 'inst-kemendikbudristek',
    predecessorName: 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi',
    successorInstitutionId: 'inst-kemendikdasmen',
    successorName: 'Kementerian Pendidikan Dasar dan Menengah',
    cabinetPeriodId: 'per-merah-putih-01',
    transitionType: 'SPLIT',
    notes: 'Pemisahan urusan pendidikan dasar & menengah berdasar Perpres No. 188/2024',
    effectiveDate: '2024-10-21',
  },
  {
    id: 'lin-02',
    predecessorInstitutionId: 'inst-kemendikbudristek',
    predecessorName: 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi',
    successorInstitutionId: 'inst-kemendiktisaintek',
    successorName: 'Kementerian Pendidikan Tinggi, Sains, dan Teknologi',
    cabinetPeriodId: 'per-merah-putih-01',
    transitionType: 'SPLIT',
    notes: 'Pemisahan urusan pendidikan tinggi, riset & teknologi berdasar Perpres No. 189/2024',
    effectiveDate: '2024-10-21',
  },
  {
    id: 'lin-03',
    predecessorInstitutionId: 'inst-kemendikbudristek',
    predecessorName: 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi',
    successorInstitutionId: 'inst-kemenbud',
    successorName: 'Kementerian Kebudayaan',
    cabinetPeriodId: 'per-merah-putih-01',
    transitionType: 'SPLIT',
    notes: 'Pemisahan urusan kebudayaan menjadi kementerian tersendiri berdasar Perpres No. 190/2024',
    effectiveDate: '2024-10-21',
  },
  {
    id: 'lin-04',
    predecessorInstitutionId: undefined,
    predecessorName: undefined,
    successorInstitutionId: 'inst-kemenko-pangan',
    successorName: 'Kementerian Koordinator Bidang Pangan',
    cabinetPeriodId: 'per-merah-putih-01',
    transitionType: 'NEW',
    notes: 'Pembentukan kementerian koordinator baru berdasar Perpres No. 147/2024',
    effectiveDate: '2024-10-21',
  },
];

@Injectable()
export class CabinetsService {
  constructor(private prisma: PrismaService) {}

  async findAll() {
    try {
      const cabinets = await this.prisma.cabinet.findMany({
        include: { periods: true },
        orderBy: { createdAt: 'desc' },
      });
      if (cabinets.length > 0) return cabinets;
    } catch {}
    return DEMO_CABINETS;
  }

  async findById(id: string) {
    try {
      const cabinet = await this.prisma.cabinet.findUnique({
        where: { id },
        include: {
          periods: {
            include: {
              memberships: { include: { institution: true } },
            },
          },
        },
      });
      if (cabinet) return cabinet;
    } catch {}

    const found = DEMO_CABINETS.find((c) => c.id === id);
    if (!found) throw new NotFoundException(`Kabinet dengan ID '${id}' tidak ditemukan.`);
    return found;
  }

  async getMemberships(cabinetId: string, category?: string) {
    const cabinet = await this.findById(cabinetId);
    return [
      {
        id: 'mem-01',
        cabinetPeriodId: 'per-merah-putih-01',
        institutionId: 'inst-kemenko-pangan',
        institutionCode: 'KL-042',
        institutionName: 'Kementerian Koordinator Bidang Pangan',
        institutionShortName: 'Kemenko Pangan',
        category: 'KEMENKO',
        joinedDate: '2024-10-21',
        isActiveInCabinet: true,
      },
      {
        id: 'mem-02',
        cabinetPeriodId: 'per-merah-putih-01',
        institutionId: 'inst-kemendikdasmen',
        institutionCode: 'KL-043',
        institutionName: 'Kementerian Pendidikan Dasar dan Menengah',
        institutionShortName: 'Kemendikdasmen',
        category: 'TEKNIS',
        joinedDate: '2024-10-21',
        isActiveInCabinet: true,
      },
      {
        id: 'mem-03',
        cabinetPeriodId: 'per-merah-putih-01',
        institutionId: 'inst-kemenpanrb',
        institutionCode: 'KL-007',
        institutionName: 'Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi',
        institutionShortName: 'KemenPANRB',
        category: 'TEKNIS',
        joinedDate: '2024-10-21',
        isActiveInCabinet: true,
      },
    ];
  }

  async compareCabinets(baseCabinetId: string, targetCabinetId: string) {
    return {
      baseCabinet: {
        id: 'cab-indonesia-maju',
        name: 'Kabinet Indonesia Maju',
        period: '2019–2024',
      },
      targetCabinet: {
        id: 'cab-merah-putih',
        name: 'Kabinet Merah Putih',
        period: '2024–2029',
      },
      totalBase: 34,
      totalTarget: 48,
      addedCount: 14,
      removedCount: 0,
      splitCount: 3,
      mergedCount: 0,
      renamedCount: 2,
      unchangedCount: 29,
      items: [
        {
          institutionId: 'inst-kemenko-pangan',
          code: 'KL-042',
          name: 'Kementerian Koordinator Bidang Pangan',
          shortName: 'Kemenko Pangan',
          category: 'KEMENKO',
          statusA: 'ABSENT',
          statusB: 'PRESENT',
          transitionType: 'NEW',
          details: 'Kementerian Koordinator baru dibentuk berdasar Perpres No. 147/2024.',
        },
        {
          institutionId: 'inst-kemendikdasmen',
          code: 'KL-043',
          name: 'Kementerian Pendidikan Dasar dan Menengah',
          shortName: 'Kemendikdasmen',
          category: 'TEKNIS',
          statusA: 'ABSENT',
          statusB: 'PRESENT',
          transitionType: 'SPLIT',
          details: 'Hasil pemisahan dari Kemendikbudristek (Perpres 188/2024).',
          predecessors: ['Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi'],
        },
        {
          institutionId: 'inst-kemendiktisaintek',
          code: 'KL-044',
          name: 'Kementerian Pendidikan Tinggi, Sains, dan Teknologi',
          shortName: 'Kemendiktisaintek',
          category: 'TEKNIS',
          statusA: 'ABSENT',
          statusB: 'PRESENT',
          transitionType: 'SPLIT',
          details: 'Hasil pemisahan dari Kemendikbudristek (Perpres 189/2024).',
          predecessors: ['Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi'],
        },
        {
          institutionId: 'inst-kemenbud',
          code: 'KL-045',
          name: 'Kementerian Kebudayaan',
          shortName: 'Kemenbud',
          category: 'TEKNIS',
          statusA: 'ABSENT',
          statusB: 'PRESENT',
          transitionType: 'SPLIT',
          details: 'Hasil pemisahan urusan kebudayaan dari Kemendikbudristek (Perpres 190/2024).',
          predecessors: ['Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi'],
        },
      ],
    };
  }

  async getLineages(cabinetId: string) {
    return DEMO_LINEAGES;
  }
}
