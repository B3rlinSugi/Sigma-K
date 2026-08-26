<?php

namespace App\Services\OrgStructure;

use App\Entities\UserEntity;
use App\Models\OrganizationalUnitModel;
use App\Models\PositionModel;
use App\Services\Authorization\AuthorizationService;
use Exception;

/**
 * PositionService
 *
 * Handles domain logic for master positions (jabatan) and institution ownership validation.
 */
class PositionService
{
    protected PositionModel $positionModel;
    protected OrganizationalUnitModel $unitModel;
    protected AuthorizationService $authzService;

    public function __construct(
        ?PositionModel $positionModel = null,
        ?OrganizationalUnitModel $unitModel = null,
        ?AuthorizationService $authzService = null
    ) {
        $this->positionModel = $positionModel ?? new PositionModel();
        $this->unitModel     = $unitModel ?? new OrganizationalUnitModel();
        $this->authzService  = $authzService ?? new AuthorizationService();
    }

    /**
     * Get positions belonging to a specific organizational unit.
     *
     * @param UserEntity $user
     * @param int        $unitId
     * @return array
     * @throws Exception
     */
    public function getPositionsByUnit(UserEntity $user, int $unitId): array
    {
        $unit = $this->unitModel->getUnitWithDetails($unitId);
        if (!$unit) {
            throw new Exception('NOT_FOUND');
        }

        $institutionId = (int)$unit['institution_id'];
        if (!$this->authzService->can($user, 'VIEW', $institutionId)) {
            throw new Exception('FORBIDDEN');
        }

        $positions = $this->positionModel->getByUnitId($unitId, true);
        $positionsArray = [];
        foreach ($positions as $p) {
            $positionsArray[] = is_array($p) ? $p : $p->toArray();
        }

        return [
            'unitId'          => (int)$unit['id'],
            'unitCode'        => $unit['unit_code'],
            'unitName'        => $unit['unit_name'],
            'institutionId'   => $institutionId,
            'institutionCode' => $unit['institution_code'],
            'institutionName' => $unit['institution_name'],
            'totalPositions'  => count($positionsArray),
            'positions'       => $positionsArray,
        ];
    }

    /**
     * Get details of a single position with institution ownership authorization.
     *
     * @param UserEntity $user
     * @param int        $positionId
     * @return array|null
     * @throws Exception
     */
    public function getPositionDetail(UserEntity $user, int $positionId): ?array
    {
        $position = $this->positionModel->getPositionWithUnitAndInstitution($positionId);
        if (!$position) {
            return null;
        }

        $institutionId = (int)$position['institution_id'];
        if (!$this->authzService->can($user, 'VIEW', $institutionId)) {
            throw new Exception('FORBIDDEN');
        }

        return [
            'id'              => (int)$position['id'],
            'unitId'          => (int)$position['unit_id'],
            'unitCode'        => $position['unit_code'],
            'unitName'        => $position['unit_name'],
            'unitLevel'       => (int)$position['unit_level'],
            'institutionId'   => $institutionId,
            'institutionCode' => $position['institution_code'],
            'institutionName' => $position['institution_name'],
            'positionName'    => $position['position_name'],
            'positionType'    => $position['position_type'],
            'echelon'         => $position['echelon'],
            'formationCount'  => (int)$position['formation_count'],
            'status'          => $position['status'],
            'createdAt'       => $position['created_at'],
            'updatedAt'       => $position['updated_at'],
        ];
    }
}
