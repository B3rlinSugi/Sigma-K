import { Cabinet, CabinetMembership, CabinetComparisonSummary } from '@/types/cabinet';
import { MOCK_CABINETS, MOCK_CABINET_MEMBERSHIPS, MOCK_CABINET_COMPARISON } from '@/data/mock/cabinets';

const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

export class CabinetService {
  static async getCabinets(): Promise<Cabinet[]> {
    await delay(120);
    return [...MOCK_CABINETS];
  }

  static async getCabinetById(id: string): Promise<Cabinet | null> {
    await delay(100);
    return MOCK_CABINETS.find((c) => c.id === id) || null;
  }

  static async getCabinetMemberships(periodId?: string): Promise<CabinetMembership[]> {
    await delay(150);
    return [...MOCK_CABINET_MEMBERSHIPS];
  }

  static async getCabinetComparison(baseId: string, targetId: string): Promise<CabinetComparisonSummary> {
    await delay(180);
    return MOCK_CABINET_COMPARISON;
  }
}
