<?php

namespace App\Models;

use CodeIgniter\Model;

class StockOpnameModel extends Model
{
    public const STATE_DRAFT = 'DRAFT';
    public const STATE_SUBMITTED = 'SUBMITTED';
    public const STATE_APPROVED = 'APPROVED';
    public const STATE_REJECTED = 'REJECTED';
    public const STATE_POSTED = 'POSTED';

    protected $table          = 'stock_opnames';
    protected $primaryKey     = 'id';
    protected $allowedFields  = [
        'opname_date',
        'state',
        'notes',
        'created_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'posted_by',
        'posted_at',
    ];
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';
    protected $returnType     = 'array';

    public function findById(int $id): ?array
    {
        $builder = $this->builder();
        $builder->where('id', $id);
        $builder->where('deleted_at', null);

        $row = $builder->get()->getRowArray();

        return $row ?: null;
    }
}
