<?php

namespace App\Services;

use App\Models\ItemCategoryModel;
use App\Models\ItemModel;

class ItemManagementService
{
    private const ALLOWED_QUERY_PARAMS = ['page', 'perPage', 'item_category_id', 'is_active', 'q'];
    public const FORBIDDEN_FIELDS      = ['qty', 'id', 'created_at', 'updated_at', 'deleted_at'];

    protected ItemModel $itemModel;
    protected ItemCategoryModel $itemCategoryModel;

    public function __construct()
    {
        $this->itemModel         = new ItemModel();
        $this->itemCategoryModel = new ItemCategoryModel();
    }

    public function getAllItems(array $queryParams): array
    {
        $unknownParams = array_diff(array_keys($queryParams), self::ALLOWED_QUERY_PARAMS);

        if ($unknownParams !== []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'query' => 'Unsupported query parameter(s): ' . implode(', ', $unknownParams),
                ],
            ];
        }

        $queryErrors = $this->validateListQueryValues($queryParams);
        if ($queryErrors !== []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $queryErrors,
            ];
        }

        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($queryParams['perPage'] ?? 10)));
        $categoryId = isset($queryParams['item_category_id']) ? (int) $queryParams['item_category_id'] : null;
        $isActive = isset($queryParams['is_active']) ? filter_var($queryParams['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
        $search = trim((string) ($queryParams['q'] ?? ''));

        $result = $this->itemModel->getAllWithCategories($page, $perPage, $categoryId, $isActive, $search);

        return [
            'success' => true,
            'data'    => array_map(fn (array $item): array => $this->formatItemResponse($item), $result['items']),
            'meta'    => [
                'page'       => $result['page'],
                'perPage'    => $result['perPage'],
                'total'      => $result['total'],
                'totalPages' => $result['totalPages'],
            ],
        ];
    }

    public function getItemById(int $id): ?array
    {
        $item = $this->itemModel->findWithCategory($id);

        if ($item === null) {
            return null;
        }

        return $this->formatItemResponse($item);
    }

    public function createItem(array $data): array
    {
        $forbiddenErrors = $this->collectForbiddenFieldErrors($data);
        if ($forbiddenErrors !== []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $forbiddenErrors,
            ];
        }

        // Resolve item_category_name to item_category_id if provided
        if (isset($data['item_category_name']) && !isset($data['item_category_id'])) {
            $categoryId = $this->itemCategoryModel->getIdByName($data['item_category_name']);
            if ($categoryId === null) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['item_category_name' => 'The selected item category is invalid.'],
                ];
            }
            $data['item_category_id'] = $categoryId;
        }

        if (! $this->itemCategoryModel->exists((int) $data['item_category_id'])) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['item_category_id' => 'The selected item category is invalid.'],
            ];
        }

        if ($this->itemModel->nameExists($data['name'])) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['name' => 'The name has already been taken.'],
            ];
        }

        if (array_key_exists('is_active', $data) && ! $this->isSupportedBooleanValue($data['is_active'])) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['is_active' => 'The is_active field must be a valid boolean.'],
            ];
        }

        $insertData = [
            'item_category_id' => (int) $data['item_category_id'],
            'name'             => trim((string) $data['name']),
            'unit_base'        => trim((string) $data['unit_base']),
            'unit_convert'     => trim((string) $data['unit_convert']),
            'conversion_base'  => (int) $data['conversion_base'],
            'is_active'        => array_key_exists('is_active', $data)
                ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)
                : true,
        ];

        $created = $this->itemModel->insert($insertData, true);

        if ($created === false) {
            return [
                'success' => false,
                'message' => 'Failed to create item.',
                'errors'  => $this->itemModel->errors(),
            ];
        }

        $item = $this->getItemById((int) $created);

        return [
            'success' => true,
            'item'    => $item,
        ];
    }

    public function updateItem(int $id, array $data): array
    {
        $existing = $this->itemModel->findWithCategory($id);

        if ($existing === null) {
            return [
                'success' => false,
                'message' => 'Item not found.',
            ];
        }

        $forbiddenErrors = $this->collectForbiddenFieldErrors($data);
        if ($forbiddenErrors !== []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $forbiddenErrors,
            ];
        }

        // Resolve item_category_name to item_category_id if provided
        if (isset($data['item_category_name']) && !isset($data['item_category_id'])) {
            $categoryId = $this->itemCategoryModel->getIdByName($data['item_category_name']);
            if ($categoryId === null) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['item_category_name' => 'The selected item category is invalid.'],
                ];
            }
            $data['item_category_id'] = $categoryId;
        }

        if (isset($data['item_category_id']) && ! $this->itemCategoryModel->exists((int) $data['item_category_id'])) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['item_category_id' => 'The selected item category is invalid.'],
            ];
        }

        if (isset($data['name']) && $this->itemModel->nameExists($data['name'], $id)) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['name' => 'The name has already been taken.'],
            ];
        }

        if (array_key_exists('is_active', $data) && ! $this->isSupportedBooleanValue($data['is_active'])) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['is_active' => 'The is_active field must be a valid boolean.'],
            ];
        }

        $updateData = [];

        if (isset($data['item_category_id'])) {
            $updateData['item_category_id'] = (int) $data['item_category_id'];
        }
        if (isset($data['name'])) {
            $updateData['name'] = trim((string) $data['name']);
        }
        if (isset($data['unit_base'])) {
            $updateData['unit_base'] = trim((string) $data['unit_base']);
        }
        if (isset($data['unit_convert'])) {
            $updateData['unit_convert'] = trim((string) $data['unit_convert']);
        }
        if (isset($data['conversion_base'])) {
            $updateData['conversion_base'] = (int) $data['conversion_base'];
        }
        if (array_key_exists('is_active', $data)) {
            $updateData['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        if ($updateData === []) {
            return [
                'success' => true,
                'item'    => $this->formatItemResponse($existing),
            ];
        }

        $updated = $this->itemModel->update($id, $updateData);

        if (! $updated) {
            return [
                'success' => false,
                'message' => 'Failed to update item.',
                'errors'  => $this->itemModel->errors(),
            ];
        }

        $item = $this->getItemById($id);

        return [
            'success' => true,
            'item'    => $item,
        ];
    }

    public function deleteItem(int $id): array
    {
        $existing = $this->itemModel->find($id);

        if ($existing === null) {
            return [
                'success' => false,
                'message' => 'Item not found.',
            ];
        }

        if (! $this->itemModel->delete($id)) {
            return [
                'success' => false,
                'message' => 'Failed to delete item.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Item deleted successfully.',
        ];
    }

    private function collectForbiddenFieldErrors(array $data): array
    {
        $errors = [];

        foreach (self::FORBIDDEN_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $errors[$field] = sprintf('The %s field cannot be modified directly.', $field);
            }
        }

        return $errors;
    }

    private function formatItemResponse(array $item): array
    {
        return [
            'id'               => (int) $item['id'],
            'item_category_id' => (int) $item['item_category_id'],
            'name'             => $item['name'],
            'unit_base'        => $item['unit_base'],
            'unit_convert'     => $item['unit_convert'],
            'conversion_base'  => (int) $item['conversion_base'],
            'qty'              => number_format((float) $item['qty'], 2, '.', ''),
            'is_active'        => (bool) $item['is_active'],
            'created_at'       => $item['created_at'],
            'updated_at'       => $item['updated_at'],
            'category'         => [
                'id'   => (int) $item['item_category_id'],
                'name' => $item['category_name'] ?? null,
            ],
        ];
    }

    private function isSupportedBooleanValue(mixed $value): bool
    {
        return in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true);
    }

    private function validateListQueryValues(array $queryParams): array
    {
        $errors = [];

        if (isset($queryParams['page']) && (! ctype_digit((string) $queryParams['page']) || (int) $queryParams['page'] < 1)) {
            $errors['page'] = 'The page field must be a positive integer.';
        }

        if (isset($queryParams['perPage']) && (! ctype_digit((string) $queryParams['perPage']) || (int) $queryParams['perPage'] < 1 || (int) $queryParams['perPage'] > 100)) {
            $errors['perPage'] = 'The perPage field must be an integer between 1 and 100.';
        }

        if (isset($queryParams['item_category_id']) && (! ctype_digit((string) $queryParams['item_category_id']) || (int) $queryParams['item_category_id'] < 1)) {
            $errors['item_category_id'] = 'The item_category_id field must be a positive integer.';
        }

        if (isset($queryParams['is_active']) && ! in_array((string) $queryParams['is_active'], ['0', '1', 'true', 'false'], true)) {
            $errors['is_active'] = 'The is_active field must be a boolean value.';
        }

        return $errors;
    }
}
