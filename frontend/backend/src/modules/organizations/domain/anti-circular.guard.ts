/**
 * Anti-Circular DFS Validator for Organization Hierarchy (Adjacency List)
 * Ensures that setting unit A's parent to unit B does not create a cycle (e.g. A -> B -> C -> A)
 */
export class AntiCircularOrgGuard {
  /**
   * Checks if moving a unit under targetParentId will cause a circular hierarchy loop.
   * @param unitId The unit being moved
   * @param targetParentId The proposed new parent unit ID
   * @param allUnits Current state of units (adjacency list)
   * @returns true if circular relationship detected (INVALID), false if safe (VALID)
   */
  static isCircular(
    unitId: string,
    targetParentId: string | null | undefined,
    allUnits: Array<{ id: string; parentId?: string | null }>,
  ): boolean {
    if (!targetParentId) return false; // Setting to root is always safe
    if (unitId === targetParentId) return true; // Cannot be parent of oneself

    // Build children lookup map: parentId -> [childIds]
    const childrenMap = new Map<string, string[]>();
    for (const u of allUnits) {
      if (u.parentId) {
        if (!childrenMap.has(u.parentId)) {
          childrenMap.set(u.parentId, []);
        }
        childrenMap.get(u.parentId)!.push(u.id);
      }
    }

    // Traverse all descendants of unitId using DFS
    const visited = new Set<string>();
    const stack = [unitId];

    while (stack.length > 0) {
      const current = stack.pop()!;
      if (current === targetParentId) {
        // targetParentId is a descendant of unitId! Circular loop detected!
        return true;
      }

      visited.add(current);
      const children = childrenMap.get(current) || [];
      for (const child of children) {
        if (!visited.has(child)) {
          stack.push(child);
        }
      }
    }

    return false;
  }
}
