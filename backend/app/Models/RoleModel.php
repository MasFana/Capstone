<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table         = 'roles';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name'];
    protected $useTimestamps = true;
    protected $returnType    = 'array';

    public function findByName(string $name): ?array
    {
        return $this->where('name', $name)->first();
    }

    public function getAll(): array
    {
        return $this->orderBy('name', 'ASC')->findAll();
    }

    public function getAllRoles(): array
    {
        return $this->getAll();
    }
}
