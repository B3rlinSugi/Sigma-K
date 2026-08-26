<?php

namespace App\Services\Reporting;

use App\Entities\UserEntity;
use App\Models\InstitutionModel;
use App\Models\OrganizationalUnitModel;
use App\Models\PositionModel;
use App\Models\SubmissionModel;
use App\Services\Authorization\AuthorizationService;
use App\Services\Authorization\ScopeResolver;
use Exception;

/**
 * ExecutiveReportService
 *
 * Domain service delivering executive KPI cards, lifecycle breakdown, institutional metrics,
 * and export data with Zero-Trust multi-tenant scoping.
 */
class ExecutiveReportService
{
    protected InstitutionModel $instModel;
    protected OrganizationalUnitModel $unitModel;
    protected PositionModel $posModel;
    protected SubmissionModel $subModel;
    protected AuthorizationService $authzService;
    protected ScopeResolver $scopeResolver;

    public function __construct(
        ?InstitutionModel $instModel = null,
        ?OrganizationalUnitModel $unitModel = null,
        ?PositionModel $posModel = null,
        ?SubmissionModel $subModel = null,
        ?AuthorizationService $authzService = null,
        ?ScopeResolver $scopeResolver = null
    ) {
        $this->instModel     = $instModel ?? new InstitutionModel();
        $this->unitModel     = $unitModel ?? new OrganizationalUnitModel();
        $this->posModel      = $posModel ?? new PositionModel();
        $this->subModel      = $subModel ?? new SubmissionModel();
        $this->authzService  = $authzService ?? new AuthorizationService();
        $this->scopeResolver = $scopeResolver ?? new ScopeResolver();
    }

    /**
     * Get Executive Overview Dashboard KPI Cards and Funnel Breakdown.
     *
     * @param UserEntity $actor
     * @return array
     * @throws Exception
     */
    public function getSummary(UserEntity $actor): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        $authorizedInstIds = $this->scopeResolver->getAuthorizedInstitutionIds($actor, $roleCode);

        $db = \Config\Database::connect();

        // 1. Total Institutions in Scope
        $instBuilder = $db->table('institutions')->where('status', 'ACTIVE');
        if ($authorizedInstIds !== null) {
            if (empty($authorizedInstIds)) {
                $totalInstitutions = 0;
            } else {
                $instBuilder->whereIn('id', $authorizedInstIds);
                $totalInstitutions = $instBuilder->countAllResults();
            }
        } else {
            $totalInstitutions = $instBuilder->countAllResults();
        }

        // 2. Units & Formations in Scope
        $unitBuilder = $db->table('organizational_units');
        $posBuilder  = $db->table('positions p')
            ->join('organizational_units ou', 'p.unit_id = ou.id');

        if ($authorizedInstIds !== null) {
            if (!empty($authorizedInstIds)) {
                $unitBuilder->whereIn('institution_id', $authorizedInstIds);
                $posBuilder->whereIn('ou.institution_id', $authorizedInstIds);
            } else {
                $unitBuilder->where('1=0');
                $posBuilder->where('1=0');
            }
        }

        $totalActiveUnits   = (clone $unitBuilder)->where('status', 'ACTIVE')->countAllResults();
        $totalInactiveUnits = (clone $unitBuilder)->where('status', 'INACTIVE')->countAllResults();

        $posSummary = (clone $posBuilder)
            ->select('COUNT(p.id) as total_positions, COALESCE(SUM(p.formation_count), 0) as total_formations')
            ->where('p.status', 'ACTIVE')
            ->get()
            ->getRowArray();

        $totalPositions  = (int)($posSummary['total_positions'] ?? 0);
        $totalFormations = (int)($posSummary['total_formations'] ?? 0);

        // 3. Submissions Funnel in Scope
        $subBuilder = $db->table('submissions');
        if ($authorizedInstIds !== null) {
            if (!empty($authorizedInstIds)) {
                $subBuilder->whereIn('institution_id', $authorizedInstIds);
            } else {
                $subBuilder->where('1=0');
            }
        }

        $totalSubmissions = (clone $subBuilder)->countAllResults();

        $stateCountsRaw = (clone $subBuilder)
            ->select('current_state, COUNT(id) as count')
            ->groupBy('current_state')
            ->get()
            ->getResultArray();

        $stateCounts = [];
        foreach ($stateCountsRaw as $row) {
            $stateCounts[$row['current_state']] = (int)$row['count'];
        }

        $funnel = [
            'draft'           => $stateCounts['DRAFT'] ?? 0,
            'screening'       => ($stateCounts['SUBMITTED_TO_ADMIN'] ?? 0) + ($stateCounts['ADMIN_REVIEW'] ?? 0) + ($stateCounts['IN_REVIEW_BY_ADMIN'] ?? 0),
            'revision'        => ($stateCounts['REVISION_REQUIRED'] ?? 0) + ($stateCounts['REVISION_REQUIRED_BY_VERIFIER'] ?? 0) + ($stateCounts['RESUBMITTED'] ?? 0),
            'verification'    => ($stateCounts['ASSIGNED_TO_VERIFIER'] ?? 0) + ($stateCounts['IN_REVIEW_BY_VERIFIER'] ?? 0) + ($stateCounts['READY_FOR_FINAL_DECISION'] ?? 0),
            'approved'        => $stateCounts['APPROVED'] ?? 0,
            'promoted'        => $stateCounts['PROMOTED'] ?? 0,
        ];

        // 4. Recent Approvals
        $recentApprovalsBuilder = $db->table('approval_records ar')
            ->select('ar.id, ar.approval_number, ar.approved_at, u.full_name as approver_name, s.id as submission_id, s.title as submission_title, i.name as institution_name')
            ->join('submission_versions sv', 'ar.version_id = sv.id')
            ->join('submissions s', 'sv.submission_id = s.id')
            ->join('institutions i', 's.institution_id = i.id')
            ->join('users u', 'ar.approver_id = u.id')
            ->orderBy('ar.id', 'DESC')
            ->limit(5);

        if ($authorizedInstIds !== null) {
            if (!empty($authorizedInstIds)) {
                $recentApprovalsBuilder->whereIn('s.institution_id', $authorizedInstIds);
            } else {
                $recentApprovalsBuilder->where('1=0');
            }
        }
        $recentApprovals = $recentApprovalsBuilder->get()->getResultArray();

        return [
            'overview' => [
                'totalInstitutions'    => $totalInstitutions,
                'totalActiveUnits'     => $totalActiveUnits,
                'totalInactiveUnits'   => $totalInactiveUnits,
                'totalPositions'       => $totalPositions,
                'totalFormations'      => $totalFormations,
                'totalSubmissions'     => $totalSubmissions,
            ],
            'funnel'          => $funnel,
            'stateBreakdown'  => $stateCounts,
            'recentApprovals' => $recentApprovals,
        ];
    }

    /**
     * Get detailed submissions report grouped by state and year.
     *
     * @param UserEntity $actor
     * @param array      $params
     * @return array
     * @throws Exception
     */
    public function getSubmissionsReport(UserEntity $actor, array $params = []): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        $authorizedInstIds = $this->scopeResolver->getAuthorizedInstitutionIds($actor, $roleCode);

        // Security check for custom institution_id filter
        if (!empty($params['institution_id']) && $authorizedInstIds !== null) {
            $reqInstId = (int)$params['institution_id'];
            if (!in_array($reqInstId, $authorizedInstIds, true)) {
                throw new Exception('FORBIDDEN');
            }
        }

        $db = \Config\Database::connect();
        $builder = $db->table('submissions s')
            ->select('s.id, s.institution_id, i.name as institution_name, s.title, s.submission_year, s.current_state, s.created_at, s.updated_at, u.full_name as author_name')
            ->join('institutions i', 's.institution_id = i.id')
            ->join('users u', 's.author_id = u.id')
            ->orderBy('s.id', 'DESC');

        if ($authorizedInstIds !== null) {
            if (!empty($authorizedInstIds)) {
                $builder->whereIn('s.institution_id', $authorizedInstIds);
            } else {
                $builder->where('1=0');
            }
        }

        if (!empty($params['institution_id'])) {
            $builder->where('s.institution_id', (int)$params['institution_id']);
        }
        if (!empty($params['status'])) {
            $builder->where('s.current_state', trim((string)$params['status']));
        }
        if (!empty($params['year'])) {
            $builder->where('s.submission_year', (int)$params['year']);
        }

        $limit = min(500, max(1, (int)($params['limit'] ?? 100)));
        $builder->limit($limit);

        return $builder->get()->getResultArray();
    }

    /**
     * Get aggregate institutional report (units, formations, submission statistics per K/L/D).
     *
     * @param UserEntity $actor
     * @param array      $params
     * @return array
     * @throws Exception
     */
    public function getInstitutionsReport(UserEntity $actor, array $params = []): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        $authorizedInstIds = $this->scopeResolver->getAuthorizedInstitutionIds($actor, $roleCode);

        $db = \Config\Database::connect();
        $builder = $db->table('institutions i')
            ->select('i.id, i.institution_code, i.name, i.short_name, i.category, i.status,
                COUNT(DISTINCT ou.id) as total_units,
                COUNT(DISTINCT p.id) as total_positions,
                COALESCE(SUM(p.formation_count), 0) as total_formations,
                COUNT(DISTINCT s.id) as total_submissions')
            ->join('organizational_units ou', "i.id = ou.institution_id AND ou.status = 'ACTIVE'", 'left')
            ->join('positions p', "ou.id = p.unit_id AND p.status = 'ACTIVE'", 'left')
            ->join('submissions s', 'i.id = s.institution_id', 'left')
            ->groupBy('i.id, i.institution_code, i.name, i.short_name, i.category, i.status')
            ->orderBy('i.name', 'ASC');

        if ($authorizedInstIds !== null) {
            if (!empty($authorizedInstIds)) {
                $builder->whereIn('i.id', $authorizedInstIds);
            } else {
                $builder->where('1=0');
            }
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get formal approvals report.
     *
     * @param UserEntity $actor
     * @param array      $params
     * @return array
     * @throws Exception
     */
    public function getApprovalsReport(UserEntity $actor, array $params = []): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        $authorizedInstIds = $this->scopeResolver->getAuthorizedInstitutionIds($actor, $roleCode);

        $db = \Config\Database::connect();
        $builder = $db->table('approval_records ar')
            ->select('ar.id as approval_id, ar.approval_number, ar.approved_at, ar.approval_notes, u.full_name as approver_name, u.nip as approver_nip, s.id as submission_id, s.title as submission_title, s.submission_year, i.id as institution_id, i.name as institution_name, sv.version_number')
            ->join('submission_versions sv', 'ar.version_id = sv.id')
            ->join('submissions s', 'sv.submission_id = s.id')
            ->join('institutions i', 's.institution_id = i.id')
            ->join('users u', 'ar.approver_id = u.id')
            ->orderBy('ar.approved_at', 'DESC');

        if ($authorizedInstIds !== null) {
            if (!empty($authorizedInstIds)) {
                $builder->whereIn('s.institution_id', $authorizedInstIds);
            } else {
                $builder->where('1=0');
            }
        }

        $limit = min(500, max(1, (int)($params['limit'] ?? 100)));
        $builder->limit($limit);

        return $builder->get()->getResultArray();
    }

    /**
     * Get master data promotion report.
     *
     * @param UserEntity $actor
     * @param array      $params
     * @return array
     * @throws Exception
     */
    public function getPromotionsReport(UserEntity $actor, array $params = []): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        $authorizedInstIds = $this->scopeResolver->getAuthorizedInstitutionIds($actor, $roleCode);

        $db = \Config\Database::connect();
        $builder = $db->table('submissions s')
            ->select('s.id as submission_id, s.title as submission_title, s.submission_year, s.updated_at as promoted_at, i.id as institution_id, i.name as institution_name, ar.approval_number, u.full_name as author_name')
            ->join('institutions i', 's.institution_id = i.id')
            ->join('users u', 's.author_id = u.id')
            ->join('submission_versions sv', 's.id = sv.submission_id', 'left')
            ->join('approval_records ar', 'sv.id = ar.version_id', 'left')
            ->where('s.current_state', 'PROMOTED')
            ->orderBy('s.updated_at', 'DESC');

        if ($authorizedInstIds !== null) {
            if (!empty($authorizedInstIds)) {
                $builder->whereIn('s.institution_id', $authorizedInstIds);
            } else {
                $builder->where('1=0');
            }
        }

        $limit = min(500, max(1, (int)($params['limit'] ?? 100)));
        $builder->limit($limit);

        return $builder->get()->getResultArray();
    }
}
