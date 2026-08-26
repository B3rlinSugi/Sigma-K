<?php

namespace App\Models;

use App\Entities\SubmissionEntity;
use CodeIgniter\Model;

/**
 * SubmissionModel
 *
 * Data Access Layer for submissions table.
 */
class SubmissionModel extends Model
{
    protected $table            = 'submissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = SubmissionEntity::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'institution_id',
        'author_id',
        'title',
        'submission_year',
        'current_state',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get submission details with joined institution and author data.
     *
     * @param int $id
     * @return array|null
     */
    public function getSubmissionWithDetails(int $id): ?array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('submissions s')
            ->select('s.*, i.name as institution_name, i.institution_code, u.username as author_username, u.full_name as author_name')
            ->join('institutions i', 's.institution_id = i.id')
            ->join('users u', 's.author_id = u.id')
            ->where('s.id', $id);

        return $builder->get()->getRowArray();
    }
}
