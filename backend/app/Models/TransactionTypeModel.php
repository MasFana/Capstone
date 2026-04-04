<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionTypeModel extends Model
{
    public const NAME_IN = 'IN';
    public const NAME_OUT = 'OUT';
    public const NAME_RETURN_IN = 'RETURN_IN';

    protected $table         = 'transaction_types';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name'];
    protected $useTimestamps = true;
    protected $returnType    = 'array';
}
