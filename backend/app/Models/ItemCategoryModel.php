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
}
