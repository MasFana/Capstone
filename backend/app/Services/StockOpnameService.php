<?php

namespace App\Services;

use App\Models\ItemModel;
use App\Models\StockTransactionDetailModel;
use App\Models\StockTransactionModel;
use App\Models\StockOpnameDetailModel;
use App\Models\StockOpnameModel;
use App\Models\TransactionTypeModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class StockOpnameService
{
    private const ALLOWED_CREATE_FIELDS = [
        'opname_date',
        'notes',
        'details',
    ];

    private const ALLOWED_DETAIL_FIELDS = [
        'item_id',
        'counted_qty',
    ];

    protected StockOpnameModel $stockOpnameModel;
    protected StockOpnameDetailModel $stockOpnameDetailModel;
    protected StockTransactionModel $stockTransactionModel;
    protected StockTransactionDetailModel $stockTransactionDetailModel;
    protected ItemModel $itemModel;
    protected TransactionTypeModel $transactionTypeModel;
    protected AuditService $auditService;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->stockOpnameModel       = new StockOpnameModel();
        $this->stockOpnameDetailModel = new StockOpnameDetailModel();
        $this->stockTransactionModel  = new StockTransactionModel();
        $this->stockTransactionDetailModel = new StockTransactionDetailModel();
        $this->itemModel              = new ItemModel();
        $this->transactionTypeModel   = new TransactionTypeModel();
        $this->auditService           = new AuditService();
        $this->db                     = Database::connect();
    }

    public function createDraft(array $data, int $userId, ?string $ipAddress = null): array
    {
        $unknownTopLevelFields = array_diff(array_keys($data), self::ALLOWED_CREATE_FIELDS);
        if ($unknownTopLevelFields !== []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'fields' => 'Unknown field(s): ' . implode(', ', $unknownTopLevelFields),
                ],
            ];
        }

        if (! isset($data['opname_date']) || strtotime((string) $data['opname_date']) === false) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'opname_date' => 'The opname_date field is required and must be a valid date.',
                ],
            ];
        }

        $notes = null;
        if (array_key_exists('notes', $data) && $data['notes'] !== null) {
            $notes = trim((string) $data['notes']);
            if ($notes !== '' && mb_strlen($notes) > 1000) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => [
                        'notes' => 'The notes field must not exceed 1000 characters.',
                    ],
                ];
            }

            if ($notes === '') {
                $notes = null;
            }
        }

        if (! isset($data['details']) || ! is_array($data['details']) || $data['details'] === []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'details' => 'The details field is required and must be a non-empty array.',
                ],
            ];
        }

        $normalizedDetails = [];
        $itemIds           = [];

        foreach ($data['details'] as $index => $detail) {
            if (! is_array($detail)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => [
                        "details.{$index}" => 'Each detail entry must be an object.',
                    ],
                ];
            }

            $unknownDetailFields = array_diff(array_keys($detail), self::ALLOWED_DETAIL_FIELDS);
            if ($unknownDetailFields !== []) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => [
                        "details.{$index}" => 'Unknown field(s): ' . implode(', ', $unknownDetailFields),
                    ],
                ];
            }

            if (! isset($detail['item_id']) || ! is_numeric($detail['item_id'])) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => [
                        "details.{$index}.item_id" => 'The item_id field is required and must be numeric.',
                    ],
                ];
            }

            if (! array_key_exists('counted_qty', $detail) || ! is_numeric($detail['counted_qty']) || (float) $detail['counted_qty'] < 0) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => [
                        "details.{$index}.counted_qty" => 'The counted_qty field is required and must be a non-negative number.',
                    ],
                ];
            }

            $itemId = (int) $detail['item_id'];
            if (in_array($itemId, $itemIds, true)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => [
                        "details.{$index}.item_id" => 'Duplicate item_id found in details.',
                    ],
                ];
            }

            $item = $this->itemModel->find($itemId);
            if ($item === null) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => [
                        "details.{$index}.item_id" => 'The selected item is invalid.',
                    ],
                ];
            }

            $itemIds[] = $itemId;

            $systemQty   = round((float) $item['qty'], 2);
            $countedQty  = round((float) $detail['counted_qty'], 2);
            $varianceQty = round($countedQty - $systemQty, 2);

            $normalizedDetails[] = [
                'item_id'      => $itemId,
                'system_qty'   => $systemQty,
                'counted_qty'  => $countedQty,
                'variance_qty' => $varianceQty,
            ];
        }

        $this->db->transStart();

        $stockOpnameData = [
            'opname_date' => $data['opname_date'],
            'state'       => StockOpnameModel::STATE_DRAFT,
            'notes'       => $notes,
            'created_by'  => $userId,
        ];

        $stockOpnameId = $this->stockOpnameModel->insert($stockOpnameData, true);
        if ($stockOpnameId === false) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Failed to create stock opname draft.',
                'errors'  => $this->stockOpnameModel->errors(),
            ];
        }

        foreach ($normalizedDetails as $detail) {
            $detailData = [
                'stock_opname_id' => (int) $stockOpnameId,
                'item_id'         => $detail['item_id'],
                'system_qty'      => number_format((float) $detail['system_qty'], 2, '.', ''),
                'counted_qty'     => number_format((float) $detail['counted_qty'], 2, '.', ''),
                'variance_qty'    => number_format((float) $detail['variance_qty'], 2, '.', ''),
            ];

            if ($this->stockOpnameDetailModel->insert($detailData) === false) {
                $this->db->transRollback();

                return [
                    'success' => false,
                    'message' => 'Failed to create stock opname details.',
                    'errors'  => $this->stockOpnameDetailModel->errors(),
                ];
            }
        }

        $auditLogged = $this->auditService->log(
            $userId,
            'stock_opname_create_draft',
            'stock_opnames',
            (int) $stockOpnameId,
            'Stock opname draft created.',
            null,
            [
                'id'          => (int) $stockOpnameId,
                'opname_date' => $data['opname_date'],
                'state'       => StockOpnameModel::STATE_DRAFT,
                'notes'       => $notes,
                'details'     => $normalizedDetails,
            ],
            $ipAddress
        );

        if (! $auditLogged) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Failed to write audit log.',
                'errors'  => [],
            ];
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return [
                'success' => false,
                'message' => 'Transaction failed.',
                'errors'  => [],
            ];
        }

        return [
            'success' => true,
            'message' => 'Stock opname draft created successfully.',
            'data'    => [
                'id'    => (int) $stockOpnameId,
                'state' => StockOpnameModel::STATE_DRAFT,
            ],
        ];
    }

    public function submit(int $id, int $userId, ?string $ipAddress = null): array
    {
        $stockOpname = $this->stockOpnameModel->findById($id);
        if ($stockOpname === null) {
            return [
                'success' => false,
                'message' => 'Stock opname not found.',
                'errors'  => [],
                'status'  => 404,
            ];
        }

        if ($stockOpname['state'] !== StockOpnameModel::STATE_DRAFT) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'state' => sprintf('Invalid state transition from %s to SUBMITTED.', $stockOpname['state']),
                ],
                'status'  => 400,
            ];
        }

        $oldValues = $stockOpname;

        $updated = $this->stockOpnameModel->update($id, [
            'state'        => StockOpnameModel::STATE_SUBMITTED,
            'submitted_by' => $userId,
            'submitted_at' => date('Y-m-d H:i:s'),
            'approved_by'  => null,
            'approved_at'  => null,
            'rejected_by'  => null,
            'rejected_at'  => null,
            'rejection_reason' => null,
            'posted_by'    => null,
            'posted_at'    => null,
        ]);

        if (! $updated) {
            return [
                'success' => false,
                'message' => 'Failed to submit stock opname.',
                'errors'  => [],
                'status'  => 400,
            ];
        }

        $newValues = $this->stockOpnameModel->find($id);
        $this->auditService->log(
            $userId,
            'stock_opname_submit',
            'stock_opnames',
            $id,
            'Stock opname submitted.',
            $oldValues,
            $newValues,
            $ipAddress
        );

        return [
            'success' => true,
            'message' => 'Stock opname submitted successfully.',
            'data'    => [
                'id'    => (int) $id,
                'state' => StockOpnameModel::STATE_SUBMITTED,
            ],
        ];
    }

    public function approve(int $id, int $userId, ?string $ipAddress = null): array
    {
        $stockOpname = $this->stockOpnameModel->findById($id);
        if ($stockOpname === null) {
            return [
                'success' => false,
                'message' => 'Stock opname not found.',
                'errors'  => [],
                'status'  => 404,
            ];
        }

        if ($stockOpname['state'] !== StockOpnameModel::STATE_SUBMITTED) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'state' => sprintf('Invalid state transition from %s to APPROVED.', $stockOpname['state']),
                ],
                'status'  => 400,
            ];
        }

        $oldValues = $stockOpname;

        $updated = $this->stockOpnameModel->update($id, [
            'state'           => StockOpnameModel::STATE_APPROVED,
            'approved_by'     => $userId,
            'approved_at'     => date('Y-m-d H:i:s'),
            'rejected_by'     => null,
            'rejected_at'     => null,
            'rejection_reason' => null,
        ]);

        if (! $updated) {
            return [
                'success' => false,
                'message' => 'Failed to approve stock opname.',
                'errors'  => [],
                'status'  => 400,
            ];
        }

        $newValues = $this->stockOpnameModel->find($id);
        $this->auditService->log(
            $userId,
            'stock_opname_approve',
            'stock_opnames',
            $id,
            'Stock opname approved.',
            $oldValues,
            $newValues,
            $ipAddress
        );

        return [
            'success' => true,
            'message' => 'Stock opname approved successfully.',
            'data'    => [
                'id'    => (int) $id,
                'state' => StockOpnameModel::STATE_APPROVED,
            ],
        ];
    }

    public function reject(int $id, array $data, int $userId, ?string $ipAddress = null): array
    {
        $stockOpname = $this->stockOpnameModel->findById($id);
        if ($stockOpname === null) {
            return [
                'success' => false,
                'message' => 'Stock opname not found.',
                'errors'  => [],
                'status'  => 404,
            ];
        }

        if ($stockOpname['state'] !== StockOpnameModel::STATE_SUBMITTED) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'state' => sprintf('Invalid state transition from %s to REJECTED.', $stockOpname['state']),
                ],
                'status'  => 400,
            ];
        }

        if (! isset($data['reason']) || trim((string) $data['reason']) === '') {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'reason' => 'The reason field is required.',
                ],
                'status'  => 400,
            ];
        }

        $reason = trim((string) $data['reason']);
        if (mb_strlen($reason) > 255) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'reason' => 'The reason field must not exceed 255 characters.',
                ],
                'status'  => 400,
            ];
        }

        $oldValues = $stockOpname;

        $updated = $this->stockOpnameModel->update($id, [
            'state'            => StockOpnameModel::STATE_REJECTED,
            'rejected_by'      => $userId,
            'rejected_at'      => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
            'approved_by'      => null,
            'approved_at'      => null,
        ]);

        if (! $updated) {
            return [
                'success' => false,
                'message' => 'Failed to reject stock opname.',
                'errors'  => [],
                'status'  => 400,
            ];
        }

        $newValues = $this->stockOpnameModel->find($id);
        $this->auditService->log(
            $userId,
            'stock_opname_reject',
            'stock_opnames',
            $id,
            'Stock opname rejected.',
            $oldValues,
            $newValues,
            $ipAddress
        );

        return [
            'success' => true,
            'message' => 'Stock opname rejected successfully.',
            'data'    => [
                'id'    => (int) $id,
                'state' => StockOpnameModel::STATE_REJECTED,
            ],
        ];
    }

    public function post(int $id, int $userId, ?string $ipAddress = null): array
    {
        $stockOpname = $this->stockOpnameModel->findById($id);
        if ($stockOpname === null) {
            return [
                'success' => false,
                'message' => 'Stock opname not found.',
                'errors'  => [],
                'status'  => 404,
            ];
        }

        if ($stockOpname['state'] !== StockOpnameModel::STATE_APPROVED) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'state' => sprintf('Invalid state transition from %s to POSTED.', $stockOpname['state']),
                ],
                'status'  => 400,
            ];
        }

        $details = $this->stockOpnameDetailModel->getDetailsByStockOpnameId($id);
        if ($details === []) {
            return [
                'success' => false,
                'message' => 'System error: stock opname has no details.',
                'errors'  => [],
                'status'  => 400,
            ];
        }

        $inTypeId  = $this->transactionTypeModel->getIdByName(TransactionTypeModel::NAME_IN);
        $outTypeId = $this->transactionTypeModel->getIdByName(TransactionTypeModel::NAME_OUT);
        if ($inTypeId === null || $outTypeId === null) {
            return [
                'success' => false,
                'message' => 'System error: transaction type not found.',
                'errors'  => [],
                'status'  => 400,
            ];
        }

        $approvedStatusId = (new \App\Models\ApprovalStatusModel())->getIdByName(\App\Models\ApprovalStatusModel::NAME_APPROVED);
        if ($approvedStatusId === null) {
            return [
                'success' => false,
                'message' => 'System error: APPROVED approval status not found.',
                'errors'  => [],
                'status'  => 400,
            ];
        }

        $this->db->transStart();

        foreach ($details as $detail) {
            $itemId    = (int) $detail['item_id'];
            $variance  = round((float) $detail['variance_qty'], 2);

            if (abs($variance) < 0.005) {
                continue;
            }

            $typeId = $variance > 0 ? $inTypeId : $outTypeId;
            $qty    = abs($variance);

            $transactionId = $this->stockTransactionModel->insert([
                'type_id'               => $typeId,
                'transaction_date'      => $stockOpname['opname_date'],
                'is_revision'           => false,
                'parent_transaction_id' => null,
                'approval_status_id'    => $approvedStatusId,
                'approved_by'           => null,
                'user_id'               => $userId,
                'spk_id'                => null,
                'reason'                => sprintf('Stock opname #%d posting for item #%d', $id, $itemId),
            ], true);

            if ($transactionId === false) {
                $this->db->transRollback();

                return [
                    'success' => false,
                    'message' => 'Failed to create posting transaction.',
                    'errors'  => $this->stockTransactionModel->errors(),
                    'status'  => 400,
                ];
            }

            if ($this->stockTransactionDetailModel->insert([
                'transaction_id' => (int) $transactionId,
                'item_id'        => $itemId,
                'qty'            => number_format($qty, 2, '.', ''),
                'input_qty'      => number_format($qty, 2, '.', ''),
                'input_unit'     => 'base',
            ]) === false) {
                $this->db->transRollback();

                return [
                    'success' => false,
                    'message' => 'Failed to create posting transaction detail.',
                    'errors'  => $this->stockTransactionDetailModel->errors(),
                    'status'  => 400,
                ];
            }

            $escapedQty = $this->db->escape(number_format($qty, 2, '.', ''));
            $itemBuilder = $this->db->table('items');
            $itemBuilder->where('id', $itemId);
            $itemBuilder->set('updated_at', date('Y-m-d H:i:s'));

            if ($variance > 0) {
                $itemBuilder->set('qty', "qty + {$escapedQty}", false);
                if (! $itemBuilder->update()) {
                    $this->db->transRollback();

                    return [
                        'success' => false,
                        'message' => 'Failed to update item quantity.',
                        'errors'  => [],
                        'status'  => 400,
                    ];
                }
            } else {
                $itemBuilder->where("qty >= {$escapedQty}", null, false);
                $itemBuilder->set('qty', "qty - {$escapedQty}", false);
                if (! $itemBuilder->update() || $this->db->affectedRows() === 0) {
                    $this->db->transRollback();

                    return [
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors'  => [
                            'details' => 'Insufficient stock to post stock opname variance.',
                        ],
                        'status'  => 400,
                    ];
                }
            }
        }

        $oldValues = $stockOpname;
        $updated   = $this->stockOpnameModel->update($id, [
            'state'     => StockOpnameModel::STATE_POSTED,
            'posted_by' => $userId,
            'posted_at' => date('Y-m-d H:i:s'),
        ]);

        if (! $updated) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Failed to finalize stock opname.',
                'errors'  => [],
                'status'  => 400,
            ];
        }

        $newValues = $this->stockOpnameModel->find($id);
        $auditLogged = $this->auditService->log(
            $userId,
            'stock_opname_post',
            'stock_opnames',
            $id,
            'Stock opname posted.',
            $oldValues,
            $newValues,
            $ipAddress
        );

        if (! $auditLogged) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Failed to write audit log.',
                'errors'  => [],
                'status'  => 400,
            ];
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return [
                'success' => false,
                'message' => 'Transaction failed.',
                'errors'  => [],
                'status'  => 400,
            ];
        }

        return [
            'success' => true,
            'message' => 'Stock opname posted successfully.',
            'data'    => [
                'id'    => (int) $id,
                'state' => StockOpnameModel::STATE_POSTED,
            ],
        ];
    }

    public function findByIdWithDetails(int $id): ?array
    {
        $header = $this->stockOpnameModel->findById($id);
        if ($header === null) {
            return null;
        }

        $details = $this->stockOpnameDetailModel->getDetailsByStockOpnameId($id);

        return [
            'header'  => $header,
            'details' => $details,
        ];
    }
}
