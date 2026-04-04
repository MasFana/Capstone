<?php

namespace App\Models;

use CodeIgniter\Model;

class ItemCategoryModel extends Model
{
    public const NAME_BASAH = 'BASAH';
    public const NAME_KERING = 'KERING';

    protected $table         = 'item_categories';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name'];
    protected $useTimestamps = true;
    protected $returnType    = 'array';

    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Get item category ID by name with case-insensitive and trimmed matching.
     *
     * @param string $name Category name to search for
     * @return int|null Category ID if found, null otherwise
     */
    public function getIdByName(string $name): ?int
    {
        $trimmedName = trim($name);
        $result = $this->where('LOWER(name)', strtolower($trimmedName))->first();

        return $result !== null ? (int) $result['id'] : null;
    }
}
