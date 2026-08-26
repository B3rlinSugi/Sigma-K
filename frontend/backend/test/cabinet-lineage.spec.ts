import { CabinetsService } from '../src/modules/cabinets/cabinets.service';
import { PrismaService } from '../src/infrastructure/database/prisma.service';

describe('CabinetsService & Lineage Matrix Calculation', () => {
  let service: CabinetsService;

  beforeEach(() => {
    service = new CabinetsService({} as PrismaService);
  });

  it('should compute cabinet comparison with transition types (NEW, SPLIT, MERGE, RENAME, DISSOLVED, UNCHANGED)', async () => {
    const comparison = await service.compareCabinets('cab-indonesia-maju', 'cab-merah-putih');

    expect(comparison).toBeDefined();
    expect(comparison.totalBase).toBe(34);
    expect(comparison.totalTarget).toBe(48);
    expect(comparison.addedCount).toBe(14);
    expect(comparison.splitCount).toBe(3);

    // Verify item transition types
    const splitItem = comparison.items.find((i) => i.transitionType === 'SPLIT');
    expect(splitItem).toBeDefined();
    expect(splitItem?.name).toContain('Kementerian Pendidikan Dasar dan Menengah');

    const newItem = comparison.items.find((i) => i.transitionType === 'NEW');
    expect(newItem).toBeDefined();
    expect(newItem?.name).toContain('Kementerian Koordinator Bidang Pangan');
  });

  it('should retrieve institutional lineages with predecessor and successor references', async () => {
    const lineages = await service.getLineages('cab-merah-putih');
    expect(lineages.length).toBeGreaterThan(0);

    const splitLineage = lineages.find((l) => l.transitionType === 'SPLIT');
    expect(splitLineage).toBeDefined();
    expect(splitLineage?.predecessorName).toContain('Pendidikan');
  });
});
