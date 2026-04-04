<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\StockTransactionDetailModel;
use App\Models\StockTransactionModel;
use App\Services\StockTransactionService;
use CodeIgniter\HTTP\ResponseInterface;

class StockTransactions extends BaseController
{
    protected StockTransactionService $transactionService;
    protected StockTransactionModel $transactionModel;
    protected StockTransactionDetailModel $detailModel;

    public function __construct()
    {
        $this->transactionService = new StockTransactionService();
        $this->transactionModel   = new StockTransactionModel();
        $this->detailModel        = new StockTransactionDetailModel();
    }

    public function index(): ResponseInterface
    {
        $queryParams = $this->request->getGet();

        $allowedParams = ['page', 'perPage'];
        $unknownParams = array_diff(array_keys($queryParams), $allowedParams);

        if ($unknownParams !== []) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'message' => 'Validation failed.',
                    'errors'  => [
                        'query' => 'Unsupported query parameter(s): ' . implode(', ', $unknownParams),
                    ],
                ]);
        }

        $validationErrors = [];

        if (isset($queryParams['page']) && (! ctype_digit((string) $queryParams['page']) || (int) $queryParams['page'] < 1)) {
            $validationErrors['page'] = 'The page field must be a positive integer.';
        }

        if (isset($queryParams['perPage']) && (! ctype_digit((string) $queryParams['perPage']) || (int) $queryParams['perPage'] < 1 || (int) $queryParams['perPage'] > 100)) {
            $validationErrors['perPage'] = 'The perPage field must be an integer between 1 and 100.';
        }

        if ($validationErrors !== []) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'message' => 'Validation failed.',
                    'errors'  => $validationErrors,
                ]);
        }

        $page    = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($queryParams['perPage'] ?? 10)));

        $result = $this->transactionModel->getAllPaginated($page, $perPage);

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data'  => $result['transactions'],
                'meta'  => [
                    'page'       => $result['page'],
                    'perPage'    => $result['perPage'],
                    'total'      => $result['total'],
                    'totalPages' => $result['totalPages'],
                ],
                'links' => $this->buildPaginationLinks($result),
            ]);
    }

    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];
        $user = auth()->user();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'message' => 'Unauthorized.',
                ]);
        }

        $userId    = $user->id;
        $ipAddress = $this->request->getIPAddress();

        $result = $this->transactionService->createTransaction($data, $userId, $ipAddress);

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
                'message' => $result['message'],
                'data'    => $result['data'],
            ]);
    }

    public function show(int $id): ResponseInterface
    {
        $transaction = $this->transactionModel->findById($id);

        if ($transaction === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'message' => 'Stock transaction not found.',
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data' => $transaction,
            ]);
    }

    public function details(int $id): ResponseInterface
    {
        $transaction = $this->transactionModel->findById($id);

        if ($transaction === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'message' => 'Stock transaction not found.',
                ]);
        }

        $details = $this->detailModel->getDetailsByTransactionId($id);

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data' => $details,
            ]);
    }

    public function submitRevision(int $id): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];
        $user = auth()->user();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'message' => 'Unauthorized.',
                ]);
        }

        $userId    = $user->id;
        $ipAddress = $this->request->getIPAddress();

        $result = $this->transactionService->submitRevision($id, $data, $userId, $ipAddress);

        if (! $result['success']) {
            $statusCode = isset($result['errors']) && $result['errors'] === [] && $result['message'] === 'Parent transaction not found.'
                ? 404
                : 400;

            return $this->response
                ->setStatusCode($statusCode)
                ->setJSON([
                    'message' => $result['message'],
                    'errors'  => $result['errors'] ?? [],
                ]);
        }

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'message' => $result['message'],
                'data'    => $result['data'],
            ]);
    }

    public function approve(int $id): ResponseInterface
    {
        $user = auth()->user();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'message' => 'Unauthorized.',
                ]);
        }

        $userId    = $user->id;
        $ipAddress = $this->request->getIPAddress();

        $result = $this->transactionService->approveRevision($id, $userId, $ipAddress);

        if (! $result['success']) {
            $statusCode = isset($result['errors']) && $result['errors'] === [] && $result['message'] === 'Revision not found.'
                ? 404
                : 400;

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
                'message' => $result['message'],
                'data'    => $result['data'],
            ]);
    }

    public function reject(int $id): ResponseInterface
    {
        $user = auth()->user();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'message' => 'Unauthorized.',
                ]);
        }

        $userId    = $user->id;
        $ipAddress = $this->request->getIPAddress();

        $result = $this->transactionService->rejectRevision($id, $userId, $ipAddress);

        if (! $result['success']) {
            $statusCode = isset($result['errors']) && $result['errors'] === [] && $result['message'] === 'Revision not found.'
                ? 404
                : 400;

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
                'message' => $result['message'],
                'data'    => $result['data'],
            ]);
    }

    private function buildPaginationLinks(array $result): array
    {
        $queryParams = $this->request->getGet();
        $path        = current_url();

        $buildLink = function (int $page) use ($path, $queryParams, $result): string {
            return $path . '?' . http_build_query([
                ...$queryParams,
                'page'    => $page,
                'perPage' => $result['perPage'],
            ]);
        };

        return [
            'self'     => $buildLink($result['page']),
            'first'    => $buildLink(1),
            'last'     => $buildLink(max(1, $result['totalPages'])),
            'next'     => $result['page'] < $result['totalPages'] ? $buildLink($result['page'] + 1) : null,
            'previous' => $result['page'] > 1 ? $buildLink($result['page'] - 1) : null,
        ];
    }
}
