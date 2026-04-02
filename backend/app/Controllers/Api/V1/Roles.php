<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\RoleModel;
use CodeIgniter\HTTP\ResponseInterface;

class Roles extends BaseController
{
    private RoleModel $roleModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
    }

    public function index(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data' => $this->roleModel->getAll(),
            ]);
    }
}
