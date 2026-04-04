<?php

namespace App\Models;

use CodeIgniter\Model;

class StockTransactionModel extends Model
{
    protected $table          = 'stock_transactions';
    protected $primaryKey     = 'id';
    protected $allowedFields  = [
        'type_id',
        'transaction_date',
        'is_revision',
        'parent_transaction_id',
        'approval_status_id',
        'approved_by',
        'user_id',
        'spk_id',
    ];
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';
    protected $returnType     = 'array';

    public function getAllPaginated(int $page, int $perPage): array
    {
        $builder = $this->builder();
        $builder->where('deleted_at', null);
        $builder->orderBy('transaction_date', 'DESC');
        $builder->orderBy('id', 'DESC');

        $countBuilder = clone $builder;
        $total        = $countBuilder->countAllResults();

        $transactions = $builder
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        return [
            'transactions' => $transactions,
            'total'        => $total,
            'page'         => $page,
            'perPage'      => $perPage,
            'totalPages'   => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    public function findById(int $id): ?array
    {
        $builder = $this->builder();
        $builder->where('id', $id);
        $builder->where('deleted_at', null);

        $transaction = $builder->get()->getRowArray();

        return $transaction ?: null;
    }

    public function findRevisionById(int $id): ?array
    {
        $builder = $this->builder();
        $builder->where('id', $id);
        $builder->where('is_revision', true);
        $builder->where('deleted_at', null);

        $transaction = $builder->get()->getRowArray();

        return $transaction ?: null;
    }
}
