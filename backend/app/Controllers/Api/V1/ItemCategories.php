<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\ItemCategoryModel;
use CodeIgniter\HTTP\ResponseInterface;

class ItemCategories extends BaseController
{
    private ItemCategoryModel $itemCategoryModel;

    public function __construct()
    {
        $this->itemCategoryModel = new ItemCategoryModel();
    }

    public function index(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data' => $this->itemCategoryModel->orderBy('name', 'ASC')->findAll(),
            ]);
    }
}
