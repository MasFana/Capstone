<?php

namespace App\Models;

use CodeIgniter\Model;

class ApprovalStatusModel extends Model
{
    public const NAME_APPROVED = 'APPROVED';
    public const NAME_PENDING = 'PENDING';
    public const NAME_REJECTED = 'REJECTED';

    protected $table         = 'approval_statuses';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name'];
    protected $useTimestamps = true;
    protected $returnType    = 'array';

    public function findByName(string $name): ?array
    {
        $status = $this->where('name', $name)->first();

        return $status ?: null;
    }

    public function getIdByName(string $name): ?int
    {
        $status = $this->findByName($name);

        return $status !== null ? (int) $status['id'] : null;
    }
}
