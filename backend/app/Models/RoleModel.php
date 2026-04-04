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

    /**
     * Get role ID by name with case-insensitive and trimmed matching.
     *
     * @param string $name Role name to search for
     * @return int|null Role ID if found, null otherwise
     */
    public function getIdByName(string $name): ?int
    {
        $trimmedName = trim($name);
        $result = $this->where('LOWER(name)', strtolower($trimmedName))->first();

        return $result !== null ? (int) $result['id'] : null;
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
