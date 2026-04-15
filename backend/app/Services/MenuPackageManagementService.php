<?php

namespace App\Services;

use App\Models\DishModel;
use App\Models\MealTimeModel;
use App\Models\MenuModel;

class MenuPackageManagementService
{
    protected MenuModel $menuModel;
    protected MealTimeModel $mealTimeModel;
    protected DishModel $dishModel;
    protected $menuDishModel;

    public function __construct()
    {
        $this->menuModel     = new MenuModel();
        $this->mealTimeModel = new MealTimeModel();
        $this->dishModel     = new DishModel();
        $modelClass = 'App\\Models\\MenuDishModel';
        $this->menuDishModel = new $modelClass();
    }

    public function getAllMenus(): array
    {
        $menus = $this->menuModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return [
            'success' => true,
            'data'    => array_map(fn (array $menu): array => [
                'id'   => (int) $menu['id'],
                'name' => $menu['name'],
            ], $menus),
            'meta'    => [
                'page'       => 1,
                'perPage'    => max(1, count($menus)),
                'total'      => count($menus),
                'totalPages' => count($menus) > 0 ? 1 : 0,
                'paginated'  => false,
            ],
        ];
    }

    public function getAllSlots(): array
    {
        $rows = $this->menuDishModel->getAllWithRelations();

        return [
            'success' => true,
            'data'    => array_map(fn (array $row): array => $this->formatSlot($row), $rows),
            'meta'    => [
                'page'       => 1,
                'perPage'    => max(1, count($rows)),
                'total'      => count($rows),
                'totalPages' => count($rows) > 0 ? 1 : 0,
                'paginated'  => false,
            ],
        ];
    }

    public function assignDishToSlot(array $data): array
    {
        $validation = service('validation');
        if (! $validation->setRules([
            'menu_id'      => 'required|is_natural_no_zero',
            'meal_time_id' => 'required|is_natural_no_zero',
            'dish_id'      => 'required|is_natural_no_zero',
        ])->run($data)) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validation->getErrors(),
            ];
        }

        $menuId     = (int) $data['menu_id'];
        $mealTimeId = (int) $data['meal_time_id'];
        $dishId     = (int) $data['dish_id'];

        $errors = [];

        $menu = $this->menuModel->find($menuId);
        if ($menu === null || $menuId < 1 || $menuId > 11) {
            $errors['menu_id'] = 'The selected menu is invalid.';
        }

        $mealTime = $this->mealTimeModel->find($mealTimeId);
        if ($mealTime === null) {
            $errors['meal_time_id'] = 'The selected meal time is invalid.';
        }

        $dish = $this->dishModel->findById($dishId);
        if ($dish === null) {
            $errors['dish_id'] = 'The selected dish is invalid.';
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ];
        }

        $existing = $this->menuDishModel->findBySlot($menuId, $mealTimeId);
        if ($existing !== null) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'menu_id,meal_time_id' => 'The menu_id and meal_time_id combination has already been taken.',
                ],
            ];
        }

        $created = $this->menuDishModel->insert([
            'menu_id'      => $menuId,
            'meal_time_id' => $mealTimeId,
            'dish_id'      => $dishId,
        ], true);

        if ($created === false) {
            return [
                'success' => false,
                'message' => 'Failed to assign menu slot.',
                'errors'  => $this->menuDishModel->errors(),
            ];
        }

        $row = $this->menuDishModel
            ->builder()
            ->select('menu_dishes.*, menus.name AS menu_name, meal_times.name AS meal_time_name, dishes.name AS dish_name')
            ->join('menus', 'menus.id = menu_dishes.menu_id')
            ->join('meal_times', 'meal_times.id = menu_dishes.meal_time_id')
            ->join('dishes', 'dishes.id = menu_dishes.dish_id')
            ->where('menu_dishes.id', (int) $created)
            ->get()
            ->getRowArray();

        return [
            'success' => true,
            'slot'    => $this->formatSlot($row),
        ];
    }

    private function formatSlot(array $row): array
    {
        return [
            'id'           => (int) $row['id'],
            'menu_id'      => (int) $row['menu_id'],
            'meal_time_id' => (int) $row['meal_time_id'],
            'dish_id'      => (int) $row['dish_id'],
            'created_at'   => $row['created_at'],
            'updated_at'   => $row['updated_at'],
            'menu'         => [
                'id'   => (int) $row['menu_id'],
                'name' => $row['menu_name'] ?? null,
            ],
            'meal_time'    => [
                'id'   => (int) $row['meal_time_id'],
                'name' => $row['meal_time_name'] ?? null,
            ],
            'dish'         => [
                'id'   => (int) $row['dish_id'],
                'name' => $row['dish_name'] ?? null,
            ],
        ];
    }
}
