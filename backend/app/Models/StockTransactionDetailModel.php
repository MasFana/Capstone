<?php

namespace App\Models;

use CodeIgniter\Model;

class StockTransactionDetailModel extends Model
{
    protected $table         = 'stock_transaction_details';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'transaction_id',
        'item_id',
        'qty',
        'input_qty',
        'input_unit',
    ];
    protected $useTimestamps = false;
    protected $returnType    = 'array';

    public function getDetailsByTransactionId(int $transactionId): array
    {
        return $this->where('transaction_id', $transactionId)->findAll();
    }
}
