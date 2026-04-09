<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\TransactionTypeModel;
use CodeIgniter\HTTP\ResponseInterface;

class TransactionTypes extends BaseController
{
    private TransactionTypeModel $transactionTypeModel;

    public function __construct()
    {
        $this->transactionTypeModel = new TransactionTypeModel();
    }

    public function index(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data' => $this->transactionTypeModel->orderBy('name', 'ASC')->findAll(),
            ]);
    }
}
