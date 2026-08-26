import { Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../../infrastructure/database/prisma.service';
import { CreateTupoksiDto } from './dto/tupoksi.dto';

const DEMO_TUPOKSI = [
  {
    id: 'tup-01',
    institutionId: 'inst-kemenko-pangan',
    type: 'DUTY',
    contentText: 'Kementerian Koordinator Bidang Pangan mempunyai tugas menyelenggarakan koordinasi, sinkronisasi, dan pengendalian urusan kementerian dalam penyelenggaraan pemerintahan di bidang pangan.',
    legalArticleReference: 'Perpres No. 147 Tahun 2024 Pasal 4',
    sequenceNumber: 1,
    version: '1.0',
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'tup-02',
    institutionId: 'inst-kemenko-pangan',
    type: 'FUNCTION',
    contentText: 'Koordinasi dan sinkronisasi perumusan, penetapan, dan pelaksanaan kebijakan kementerian/lembaga yang terkait dengan isu di bidang kedaulatan, ketersediaan, dan stabilitas pasokan pangan nasional.',
    legalArticleReference: 'Perpres No. 147 Tahun 2024 Pasal 5 huruf a',
    sequenceNumber: 1,
    version: '1.0',
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'tup-03',
    institutionId: 'inst-kemenko-pangan',
    type: 'FUNCTION',
    contentText: 'Pengendalian pelaksanaan kebijakan kementerian/lembaga yang terkait dengan isu di bidang pangan.',
    legalArticleReference: 'Perpres No. 147 Tahun 2024 Pasal 5 huruf b',
    sequenceNumber: 2,
    version: '1.0',
    updatedAt: new Date().toISOString(),
  },
];

let inMemoryTupoksi: any[] = [...DEMO_TUPOKSI];

@Injectable()
export class TupoksiService {
  constructor(private prisma: PrismaService) {}

  async findAll(institutionId?: string, type?: string, search?: string) {
    try {
      const where: any = {};
      if (institutionId) where.institutionId = institutionId;
      if (type) where.type = type;
      if (search) {
        where.contentText = { contains: search, mode: 'insensitive' };
      }

      const items = await this.prisma.tupoksiItem.findMany({
        where,
        include: { organizationUnit: true, institution: true },
        orderBy: { sequenceNumber: 'asc' },
      });
      if (items.length > 0) return items;
    } catch {}

    let result = [...inMemoryTupoksi];
    if (institutionId) result = result.filter((t) => t.institutionId === institutionId);
    if (type) result = result.filter((t) => t.type === type);
    if (search) {
      const s = search.toLowerCase();
      result = result.filter((t) => t.contentText.toLowerCase().includes(s));
    }
    return result;
  }

  async findById(id: string) {
    try {
      const item = await this.prisma.tupoksiItem.findUnique({
        where: { id },
        include: { organizationUnit: true, institution: true },
      });
      if (item) return item;
    } catch {}

    const found = inMemoryTupoksi.find((t) => t.id === id);
    if (!found) throw new NotFoundException(`Butir tupoksi dengan ID '${id}' tidak ditemukan.`);
    return found;
  }

  async create(dto: CreateTupoksiDto) {
    try {
      return await this.prisma.tupoksiItem.create({
        data: {
          institutionId: dto.institutionId,
          organizationUnitId: dto.organizationUnitId,
          type: dto.type,
          contentText: dto.contentText,
          legalArticleReference: dto.legalArticleReference,
          sequenceNumber: dto.sequenceNumber || 1,
        },
      });
    } catch {
      const newItem = {
        id: `tup-${Date.now()}`,
        institutionId: dto.institutionId,
        organizationUnitId: dto.organizationUnitId,
        type: dto.type,
        contentText: dto.contentText,
        legalArticleReference: dto.legalArticleReference,
        sequenceNumber: dto.sequenceNumber || 1,
        version: '1.0',
        updatedAt: new Date().toISOString(),
      };
      inMemoryTupoksi.push(newItem);
      return newItem;
    }
  }
}
