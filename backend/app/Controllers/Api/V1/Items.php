<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Services\ItemManagementService;
use CodeIgniter\HTTP\ResponseInterface;

class Items extends BaseController
{
    protected ItemManagementService $itemService;

    public function __construct()
    {
        $this->itemService = new ItemManagementService();
    }

    public function index(): ResponseInterface
    {
        $result = $this->itemService->getAllItems($this->request->getGet());

        if (! $result['success']) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'message' => $result['message'],
                    'errors'  => $result['errors'] ?? [],
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data'  => $result['data'],
                'meta'  => $result['meta'],
                'links' => $this->buildPaginationLinks($result['meta']),
            ]);
    }

    public function show(int $id): ResponseInterface
    {
        $item = $this->itemService->getItemById($id);

        if ($item === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'message' => 'Item not found.',
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data' => $item,
            ]);
    }

    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];

        $forbiddenFieldErrors = $this->collectForbiddenFieldErrors($data);
        if ($forbiddenFieldErrors !== []) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'message' => 'Validation failed.',
                    'errors'  => $forbiddenFieldErrors,
                ]);
        }

        $rules = [
            'name'             => 'required|max_length[100]',
            'item_category_id' => 'required|is_natural_no_zero',
            'unit_base'        => 'required|max_length[20]',
            'unit_convert'     => 'required|max_length[20]',
            'conversion_base'  => 'required|is_natural_no_zero',
            'is_active'        => 'permit_empty',
        ];

        if (! $this->validateData($data, $rules)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'message' => 'Validation failed.',
                    'errors'  => $this->validator->getErrors(),
                ]);
        }

        $result = $this->itemService->createItem($data);

        if (! $result['success']) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'message' => $result['message'],
                    'errors'  => $result['errors'] ?? [],
                ]);
        }

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'message' => 'Item created successfully.',
                'data'    => $result['item'],
            ]);
    }

    public function update(int $id): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];

        $forbiddenFieldErrors = $this->collectForbiddenFieldErrors($data);
        if ($forbiddenFieldErrors !== []) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'message' => 'Validation failed.',
                    'errors'  => $forbiddenFieldErrors,
                ]);
        }

        $validationData = [
            ...$data,
            'id' => $id,
        ];

        $rules = [
            'id'               => 'required|is_natural_no_zero',
            'name'             => 'permit_empty|max_length[100]|is_unique[items.name,id,{id}]',
            'item_category_id' => 'permit_empty|is_natural_no_zero',
            'unit_base'        => 'permit_empty|max_length[20]',
            'unit_convert'     => 'permit_empty|max_length[20]',
            'conversion_base'  => 'permit_empty|is_natural_no_zero',
            'is_active'        => 'permit_empty',
        ];

        if (! $this->validateData($validationData, $rules)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'message' => 'Validation failed.',
                    'errors'  => $this->validator->getErrors(),
                ]);
        }

        $result = $this->itemService->updateItem($id, $data);

        if (! $result['success']) {
            $statusCode = $result['message'] === 'Item not found.' ? 404 : 400;

            return $this->response
                ->setStatusCode($statusCode)
                ->setJSON([
                    'message' => $result['message'],
                    'errors'  => $result['errors'] ?? [],
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'message' => 'Item updated successfully.',
                'data'    => $result['item'],
            ]);
    }

    public function delete(int $id): ResponseInterface
    {
        $result = $this->itemService->deleteItem($id);

        if (! $result['success']) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'message' => $result['message'],
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'message' => $result['message'],
            ]);
    }

    private function collectForbiddenFieldErrors(array $data): array
    {
        $forbiddenFields = ItemManagementService::FORBIDDEN_FIELDS;
        $errors          = [];

        foreach ($forbiddenFields as $field) {
            if (array_key_exists($field, $data)) {
                $errors[$field] = sprintf('The %s field cannot be modified directly.', $field);
            }
        }

        return $errors;
    }

    private function buildPaginationLinks(array $meta): array
    {
        $queryParams = $this->request->getGet();
        $path        = current_url();

        $buildLink = function (int $page) use ($path, $queryParams, $meta): string {
            return $path . '?' . http_build_query([
                ...$queryParams,
                'page'    => $page,
                'perPage' => $meta['perPage'],
            ]);
        };

        return [
            'self'     => $buildLink($meta['page']),
            'first'    => $buildLink(1),
            'last'     => $buildLink(max(1, $meta['totalPages'])),
            'next'     => $meta['page'] < $meta['totalPages'] ? $buildLink($meta['page'] + 1) : null,
            'previous' => $meta['page'] > 1 ? $buildLink($meta['page'] - 1) : null,
        ];
    }
}
