<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\ApprovalStatusModel;
use CodeIgniter\HTTP\ResponseInterface;

class ApprovalStatuses extends BaseController
{
    private ApprovalStatusModel $approvalStatusModel;

    public function __construct()
    {
        $this->approvalStatusModel = new ApprovalStatusModel();
    }

    public function index(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data' => $this->approvalStatusModel->orderBy('name', 'ASC')->findAll(),
            ]);
    }
}
