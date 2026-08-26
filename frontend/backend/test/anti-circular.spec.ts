import { AntiCircularOrgGuard } from '../src/modules/organizations/domain/anti-circular.guard';

describe('AntiCircularOrgGuard (Organization Hierarchy Adjacency List)', () => {
  const sampleHierarchy = [
    { id: 'unit-1', parentId: null }, // Root: Menteri
    { id: 'unit-2', parentId: 'unit-1' }, // Child: Sekretariat
    { id: 'unit-3', parentId: 'unit-1' }, // Child: Deputi I
    { id: 'unit-4', parentId: 'unit-3' }, // Grandchild: Asisten Deputi 1.1
    { id: 'unit-5', parentId: 'unit-4' }, // Great-grandchild: Tim Kerja 1.1.1
  ];

  it('should allow setting a unit under a valid non-descendant parent', () => {
    // Moving unit-2 under unit-3 (valid)
    const result = AntiCircularOrgGuard.isCircular('unit-2', 'unit-3', sampleHierarchy);
    expect(result).toBe(false);
  });

  it('should allow setting a unit to root (parentId = null)', () => {
    const result = AntiCircularOrgGuard.isCircular('unit-5', null, sampleHierarchy);
    expect(result).toBe(false);
  });

  it('should prevent setting a unit as its own parent (A -> A)', () => {
    const result = AntiCircularOrgGuard.isCircular('unit-3', 'unit-3', sampleHierarchy);
    expect(result).toBe(true);
  });

  it('should detect and prevent direct circular relationship (A -> B -> A)', () => {
    // unit-3 is parent of unit-4. Trying to make unit-4 the parent of unit-3!
    const result = AntiCircularOrgGuard.isCircular('unit-3', 'unit-4', sampleHierarchy);
    expect(result).toBe(true);
  });

  it('should detect and prevent deep circular relationship (A -> B -> C -> D -> A)', () => {
    // unit-3 -> unit-4 -> unit-5. Trying to make unit-5 the parent of unit-3!
    const result = AntiCircularOrgGuard.isCircular('unit-3', 'unit-5', sampleHierarchy);
    expect(result).toBe(true);
  });
});
