<?php

namespace App\Services\OrgStructure;

use App\Entities\UserEntity;
use App\Models\InstitutionModel;
use App\Models\OrganizationalUnitModel;
use App\Models\PositionModel;
use App\Services\Authorization\AuthorizationService;
use Exception;

/**
 * OrgHierarchyService
 *
 * Handles domain logic for organizational unit trees, hierarchy resolution,
 * anti-cycle DFS traversal, and unit detail retrieval.
 */
class OrgHierarchyService
{
    protected OrganizationalUnitModel $unitModel;
    protected PositionModel $positionModel;
    protected InstitutionModel $institutionModel;
    protected AuthorizationService $authzService;

    public function __construct(
        ?OrganizationalUnitModel $unitModel = null,
        ?PositionModel $positionModel = null,
        ?InstitutionModel $institutionModel = null,
        ?AuthorizationService $authzService = null
    ) {
        $this->unitModel        = $unitModel ?? new OrganizationalUnitModel();
        $this->positionModel    = $positionModel ?? new PositionModel();
        $this->institutionModel = $institutionModel ?? new InstitutionModel();
        $this->authzService     = $authzService ?? new AuthorizationService();
    }

    /**
     * Retrieve the organizational hierarchy tree for an institution.
     *
     * @param UserEntity $user
     * @param int        $institutionId
     * @return array
     * @throws Exception
     */
    public function getInstitutionHierarchy(UserEntity $user, int $institutionId): array
    {
        // 1. Verify institution exists
        $institution = $this->institutionModel->find($institutionId);
        if (!$institution) {
            throw new Exception('NOT_FOUND');
        }

        // 2. Zero-Trust Authorization check
        if (!$this->authzService->can($user, 'VIEW', $institutionId)) {
            throw new Exception('FORBIDDEN');
        }

        // 3. Fetch all active units for the institution
        $units = $this->unitModel->getByInstitutionId($institutionId, true);

        // Convert Entity objects to arrays for tree processing
        $unitsArray = [];
        foreach ($units as $unit) {
            $unitsArray[] = is_array($unit) ? $unit : $unit->toArray();
        }

        // 4. Build cycle-safe hierarchical tree
        $tree = $this->buildHierarchyTree($unitsArray);

        return [
            'institutionId'   => (int)$institution['id'],
            'institutionCode' => $institution['institution_code'],
            'institutionName' => $institution['name'],
            'totalUnits'      => count($unitsArray),
            'tree'            => $tree,
        ];
    }

    /**
     * Get detail of a specific organizational unit.
     *
     * @param UserEntity $user
     * @param int        $unitId
     * @return array|null
     * @throws Exception
     */
    public function getUnitDetail(UserEntity $user, int $unitId): ?array
    {
        $unit = $this->unitModel->getUnitWithDetails($unitId);
        if (!$unit) {
            return null;
        }

        // Zero-Trust Authorization check on institution ownership
        $institutionId = (int)$unit['institution_id'];
        if (!$this->authzService->can($user, 'VIEW', $institutionId)) {
            throw new Exception('FORBIDDEN');
        }

        // Fetch direct children units
        $children = $this->unitModel
            ->where('parent_unit_id', $unitId)
            ->where('status', 'ACTIVE')
            ->orderBy('order_index', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $childrenArray = [];
        foreach ($children as $c) {
            $childrenArray[] = is_array($c) ? $c : $c->toArray();
        }

        // Fetch positions belonging to this unit
        $positions = $this->positionModel->getByUnitId($unitId, true);
        $positionsArray = [];
        foreach ($positions as $p) {
            $positionsArray[] = is_array($p) ? $p : $p->toArray();
        }

        return [
            'id'              => (int)$unit['id'],
            'institutionId'   => $institutionId,
            'institutionCode' => $unit['institution_code'],
            'institutionName' => $unit['institution_name'],
            'parentUnitId'    => $unit['parent_unit_id'] ? (int)$unit['parent_unit_id'] : null,
            'parentUnitName'  => $unit['parent_unit_name'] ?? null,
            'parentUnitCode'  => $unit['parent_unit_code'] ?? null,
            'unitCode'        => $unit['unit_code'],
            'unitName'        => $unit['unit_name'],
            'unitLevel'       => (int)$unit['unit_level'],
            'orderIndex'      => (int)$unit['order_index'],
            'status'          => $unit['status'],
            'createdAt'       => $unit['created_at'],
            'updatedAt'       => $unit['updated_at'],
            'children'        => $childrenArray,
            'positions'       => $positionsArray,
        ];
    }

    /**
     * Build hierarchical tree with Cycle Detection and Anti-Loop DFS algorithm.
     *
     * @param array $units List of unit arrays
     * @return array Hierarchical tree with nested children
     */
    public function buildHierarchyTree(array $units): array
    {
        if (empty($units)) {
            return [];
        }

        // 1. Index units by id and group children by parent_unit_id
        $unitMap = [];
        $childrenMap = [];

        foreach ($units as $u) {
            $id = (int)$u['id'];
            $parentId = !empty($u['parent_unit_id']) ? (int)$u['parent_unit_id'] : null;

            $unitMap[$id] = [
                'id'           => $id,
                'parentId'     => $parentId,
                'unitCode'     => $u['unit_code'],
                'unitName'     => $u['unit_name'],
                'unitLevel'    => (int)($u['unit_level'] ?? 1),
                'orderIndex'   => (int)($u['order_index'] ?? 0),
                'status'       => $u['status'] ?? 'ACTIVE',
                'children'     => [],
            ];

            // Anti-self loop check: if parent == id, treat as root
            if ($parentId !== null && $parentId !== $id) {
                if (!isset($childrenMap[$parentId])) {
                    $childrenMap[$parentId] = [];
                }
                $childrenMap[$parentId][] = $id;
            }
        }

        // 2. Identify roots:
        // A node is root if parent is null, parent == id (self-parent), or parent not found in unitMap
        $roots = [];
        foreach ($unitMap as $id => $node) {
            $parentId = $node['parentId'];
            if ($parentId === null || $parentId === $id || !isset($unitMap[$parentId])) {
                $roots[] = $id;
            }
        }

        // If no roots detected due to a complete closed cycle (e.g. A->B->C->A), break cycle by picking smallest ID
        if (empty($roots) && !empty($unitMap)) {
            $firstId = array_key_first($unitMap);
            $roots[] = $firstId;
        }

        // 3. Build tree recursively using DFS with ancestor tracking
        $tree = [];
        foreach ($roots as $rootId) {
            $tree[] = $this->buildSubtreeDfs($rootId, $unitMap, $childrenMap, []);
        }

        return $tree;
    }

    /**
     * Recursive DFS tree builder with cycle guard.
     */
    protected function buildSubtreeDfs(int $currentId, array $unitMap, array $childrenMap, array $ancestors): array
    {
        $node = $unitMap[$currentId];

        // Add current node to ancestor path for this branch
        $newAncestors = $ancestors;
        $newAncestors[$currentId] = true;

        $childIds = $childrenMap[$currentId] ?? [];
        $children = [];

        foreach ($childIds as $childId) {
            // Cycle Guard: if child is already in ancestor path, break circular reference safely
            if (isset($newAncestors[$childId])) {
                log_message('warning', "[OrgHierarchyService] Circular reference detected between unit {$currentId} and {$childId}. Breaking loop safely.");
                continue;
            }

            if (isset($unitMap[$childId])) {
                $children[] = $this->buildSubtreeDfs($childId, $unitMap, $childrenMap, $newAncestors);
            }
        }

        $node['children'] = $children;
        return $node;
    }
}
