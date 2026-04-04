<?php

namespace App\Models;

use CodeIgniter\Model;

class ItemModel extends Model
{
    protected $table          = 'items';
    protected $primaryKey     = 'id';
    protected $allowedFields  = [
        'item_category_id',
        'name',
        'unit_base',
        'unit_convert',
        'conversion_base',
        'is_active',
    ];
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';
    protected $returnType     = 'array';

    public function getAllWithCategories(
        int $page,
        int $perPage,
        ?int $categoryId,
        ?bool $isActive,
        string $search,
    ): array {
        $builder = $this->builder();
        $builder->select('items.*, item_categories.name AS category_name');
        $builder->join('item_categories', 'item_categories.id = items.item_category_id');
        $builder->where('items.deleted_at', null);

        if ($categoryId !== null) {
            $builder->where('items.item_category_id', $categoryId);
        }

        if ($isActive !== null) {
            $builder->where('items.is_active', $isActive);
        }

        if ($search !== '') {
            $builder->like('items.name', $search);
        }

        $builder->orderBy('item_categories.name', 'ASC');
        $builder->orderBy('items.name', 'ASC');
        $builder->orderBy('items.id', 'ASC');

        $countBuilder = clone $builder;
        $total        = $countBuilder->countAllResults();

        $items = $builder
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        return [
            'items'      => $items,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    public function findWithCategory(int $id): ?array
    {
        $builder = $this->builder();
        $builder->select('items.*, item_categories.name AS category_name');
        $builder->join('item_categories', 'item_categories.id = items.item_category_id');
        $builder->where('items.id', $id);
        $builder->where('items.deleted_at', null);

        $item = $builder->get()->getRowArray();

        return $item ?: null;
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        $builder = $this->builder();
        $builder->where('name', $name);
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->countAllResults() > 0;
    }
}
