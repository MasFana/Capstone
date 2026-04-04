<?php

namespace App\Models;

use CodeIgniter\Model;

class ApprovalStatusModel extends Model
{
    protected $table         = 'approval_statuses';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name'];
    protected $useTimestamps = true;
    protected $returnType    = 'array';
}
