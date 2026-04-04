<?php

namespace App\Services;

use App\Models\ApprovalStatusModel;
use App\Models\ItemModel;
use App\Models\StockTransactionDetailModel;
use App\Models\StockTransactionModel;
use App\Models\TransactionTypeModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class StockTransactionService
{
    public const FORBIDDEN_FIELDS = [
        'user_id',
        'approved_by',
        'approval_status_id',
        'is_revision',
        'parent_transaction_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    private const ALLOWED_TOP_LEVEL_FIELDS = [
        'type_id',
        'transaction_date',
        'spk_id',
        'details',
    ];

    private const ALLOWED_DETAIL_FIELDS = [
        'item_id',
        'qty',
    ];

    private const ALLOWED_REVISION_TOP_LEVEL_FIELDS = [
        'transaction_date',
        'spk_id',
        'details',
    ];

    private const SUPPORTED_TRANSACTION_TYPES = [
        TransactionTypeModel::NAME_IN,
        TransactionTypeModel::NAME_OUT,
        TransactionTypeModel::NAME_RETURN_IN,
    ];

    protected StockTransactionModel $transactionModel;
    protected StockTransactionDetailModel $detailModel;
    protected TransactionTypeModel $typeModel;
    protected ItemModel $itemModel;
    protected ApprovalStatusModel $approvalStatusModel;
    protected AuditService $auditService;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->transactionModel    = new StockTransactionModel();
        $this->detailModel         = new StockTransactionDetailModel();
        $this->typeModel           = new TransactionTypeModel();
        $this->itemModel           = new ItemModel();
        $this->approvalStatusModel = new ApprovalStatusModel();
        $this->auditService        = new AuditService();
        $this->db                  = Database::connect();
    }

    public function createTransaction(array $data, int $userId, ?string $ipAddress = null): array
    {
        $approvedStatusId = $this->approvalStatusModel->getIdByName(ApprovalStatusModel::NAME_APPROVED);
        if ($approvedStatusId === null) {
            return [
                'success' => false,
                'message' => 'System error: APPROVED approval status not found.',
                'errors'  => [],
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

        $unknownTopLevelFields = array_diff(array_keys($data), self::ALLOWED_TOP_LEVEL_FIELDS);
        if ($unknownTopLevelFields !== []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'fields' => 'Unknown field(s): ' . implode(', ', $unknownTopLevelFields),
                ],
            ];
        }

        if (! isset($data['type_id']) || ! is_numeric($data['type_id'])) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['type_id' => 'The type_id field is required and must be numeric.'],
            ];
        }

        if (! isset($data['transaction_date'])) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['transaction_date' => 'The transaction_date field is required.'],
            ];
        }

        if (strtotime((string) $data['transaction_date']) === false) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['transaction_date' => 'The transaction_date field must be a valid date.'],
            ];
        }

        if (array_key_exists('spk_id', $data) && $data['spk_id'] !== null && (! is_numeric($data['spk_id']) || (int) $data['spk_id'] <= 0)) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['spk_id' => 'The spk_id field must be a positive integer when provided.'],
            ];
        }

        $type = $this->typeModel->find((int) $data['type_id']);
        if ($type === null) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['type_id' => 'The selected transaction type is invalid.'],
            ];
        }

        if (! in_array($type['name'], self::SUPPORTED_TRANSACTION_TYPES, true)) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['type_id' => sprintf('Unsupported transaction type: %s', $type['name'])],
            ];
        }

        if (! isset($data['details']) || ! is_array($data['details'])) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['details' => 'The details field is required and must be an array.'],
            ];
        }

        if ($data['details'] === []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['details' => 'The details field cannot be empty.'],
            ];
        }

        $itemIds = [];
        foreach ($data['details'] as $index => $detail) {
            if (! is_array($detail)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}" => 'Each detail entry must be an object.'],
                ];
            }

            $unknownDetailFields = array_diff(array_keys($detail), self::ALLOWED_DETAIL_FIELDS);
            if ($unknownDetailFields !== []) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}" => 'Unknown field(s): ' . implode(', ', $unknownDetailFields)],
                ];
            }

            if (! isset($detail['item_id']) || ! is_numeric($detail['item_id'])) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}.item_id" => 'The item_id field is required and must be numeric.'],
                ];
            }

            if (! isset($detail['qty']) || ! is_numeric($detail['qty']) || (float) $detail['qty'] <= 0) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}.qty" => 'The qty field is required and must be a positive number.'],
                ];
            }

            $itemId = (int) $detail['item_id'];
            if (in_array($itemId, $itemIds, true)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}.item_id" => 'Duplicate item_id found in details.'],
                ];
            }

            $itemIds[] = $itemId;

            $item = $this->itemModel->find($itemId);
            if ($item === null) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}.item_id" => 'The selected item is invalid.'],
                ];
            }
        }

        if ($type['name'] === TransactionTypeModel::NAME_OUT) {
            foreach ($data['details'] as $index => $detail) {
                $item         = $this->itemModel->find((int) $detail['item_id']);
                $currentQty   = (float) $item['qty'];
                $requestedQty = (float) $detail['qty'];

                if ($currentQty < $requestedQty) {
                    return [
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors'  => [
                            "details.{$index}.qty" => sprintf(
                                'Insufficient stock. Available: %s, Requested: %s',
                                number_format($currentQty, 2, '.', ''),
                                number_format($requestedQty, 2, '.', '')
                            ),
                        ],
                    ];
                }
            }
        }

        $this->db->transStart();

        $transactionData = [
            'type_id'            => (int) $data['type_id'],
            'transaction_date'   => $data['transaction_date'],
            'is_revision'        => false,
            'parent_transaction_id' => null,
            'approval_status_id' => $approvedStatusId,
            'approved_by'        => null,
            'user_id'            => $userId,
            'spk_id'             => isset($data['spk_id']) && is_numeric($data['spk_id']) ? (int) $data['spk_id'] : null,
        ];

        $transactionId = $this->transactionModel->insert($transactionData, true);

        if ($transactionId === false) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Failed to create stock transaction.',
                'errors'  => $this->transactionModel->errors(),
            ];
        }

        foreach ($data['details'] as $detail) {
            $detailData = [
                'transaction_id' => $transactionId,
                'item_id'        => (int) $detail['item_id'],
                'qty'            => (float) $detail['qty'],
            ];

            if ($this->detailModel->insert($detailData) === false) {
                $this->db->transRollback();

                return [
                    'success' => false,
                    'message' => 'Failed to create transaction details.',
                    'errors'  => $this->detailModel->errors(),
                ];
            }

            $changeQty = (float) $detail['qty'];
            $itemId    = (int) $detail['item_id'];
            $escapedQty = $this->db->escape(number_format($changeQty, 2, '.', ''));

            // Atomic qty update using DB-side arithmetic with conditional update for OUT
            if (in_array($type['name'], [TransactionTypeModel::NAME_IN, TransactionTypeModel::NAME_RETURN_IN], true)) {
                // IN/RETURN_IN: unconditional increment
                $builder = $this->db->table('items');
                $builder->where('id', $itemId);
                $builder->set('qty', "qty + {$escapedQty}", false);
                $builder->set('updated_at', date('Y-m-d H:i:s'));

                if (! $builder->update()) {
                    $this->db->transRollback();

                    return [
                        'success' => false,
                        'message' => 'Failed to update item quantity.',
                        'errors'  => [],
                    ];
                }
            } else {
                // OUT: conditional decrement (qty >= changeQty)
                $builder = $this->db->table('items');
                $builder->where('id', $itemId);
                $builder->where("qty >= {$escapedQty}", null, false);
                $builder->set('qty', "qty - {$escapedQty}", false);
                $builder->set('updated_at', date('Y-m-d H:i:s'));

                if (! $builder->update()) {
                    $this->db->transRollback();

                    return [
                        'success' => false,
                        'message' => 'Failed to update item quantity.',
                        'errors'  => [],
                    ];
                }

                $affectedRows = $this->db->affectedRows();

                if ($affectedRows === 0) {
                    $this->db->transRollback();

                    return [
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors'  => [
                            'details' => 'Insufficient stock. Stock may have changed since validation.',
                        ],
                    ];
                }
            }
        }

        $auditLogged = $this->auditService->log(
            $userId,
            'stock_transaction_create',
            'stock_transactions',
            (int) $transactionId,
            'Stock transaction created.',
            null,
            $transactionData,
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
                'message' => 'Stock transaction created successfully.',
                'data'    => [
                    'id'                 => (int) $transactionId,
                    'approval_status_id' => $approvedStatusId,
                    'is_revision'        => false,
                ],
            ];
    }

    private function collectForbiddenFieldErrors(array $data): array
    {
        $errors = [];

        foreach (self::FORBIDDEN_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $errors[$field] = sprintf('The %s field cannot be set directly.', $field);
            }
        }

        return $errors;
    }

    public function submitRevision(int $parentTransactionId, array $data, int $userId, ?string $ipAddress = null): array
    {
        $parent = $this->transactionModel->findById($parentTransactionId);
        if ($parent === null) {
            return [
                'success' => false,
                'message' => 'Parent transaction not found.',
                'errors'  => [],
            ];
        }

        if ((bool) $parent['is_revision']) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['id' => 'Revision transactions cannot be revised again.'],
            ];
        }

        // Check forbidden fields
        $forbiddenErrors = $this->collectForbiddenFieldErrors($data);
        if ($forbiddenErrors !== []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $forbiddenErrors,
            ];
        }

        // Check unknown top-level fields
        $unknownTopLevelFields = array_diff(array_keys($data), self::ALLOWED_REVISION_TOP_LEVEL_FIELDS);
        if ($unknownTopLevelFields !== []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'fields' => 'Unknown field(s): ' . implode(', ', $unknownTopLevelFields),
                ],
            ];
        }

        // Validate transaction_date
        if (! isset($data['transaction_date'])) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['transaction_date' => 'The transaction_date field is required.'],
            ];
        }

        if (strtotime((string) $data['transaction_date']) === false) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['transaction_date' => 'The transaction_date field must be a valid date.'],
            ];
        }

        // Validate spk_id
        if (array_key_exists('spk_id', $data) && $data['spk_id'] !== null && (! is_numeric($data['spk_id']) || (int) $data['spk_id'] <= 0)) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['spk_id' => 'The spk_id field must be a positive integer when provided.'],
            ];
        }

        // Validate details
        if (! isset($data['details']) || ! is_array($data['details'])) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['details' => 'The details field is required and must be an array.'],
            ];
        }

        if ($data['details'] === []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['details' => 'The details field cannot be empty.'],
            ];
        }

        // Validate each detail entry
        $itemIds = [];
        foreach ($data['details'] as $index => $detail) {
            if (! is_array($detail)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}" => 'Each detail entry must be an object.'],
                ];
            }

            $unknownDetailFields = array_diff(array_keys($detail), self::ALLOWED_DETAIL_FIELDS);
            if ($unknownDetailFields !== []) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}" => 'Unknown field(s): ' . implode(', ', $unknownDetailFields)],
                ];
            }

            if (! isset($detail['item_id']) || ! is_numeric($detail['item_id'])) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}.item_id" => 'The item_id field is required and must be numeric.'],
                ];
            }

            if (! isset($detail['qty']) || ! is_numeric($detail['qty']) || (float) $detail['qty'] <= 0) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}.qty" => 'The qty field is required and must be a positive number.'],
                ];
            }

            $itemId = (int) $detail['item_id'];
            if (in_array($itemId, $itemIds, true)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}.item_id" => 'Duplicate item_id found in details.'],
                ];
            }

            $itemIds[] = $itemId;

            $item = $this->itemModel->find($itemId);
            if ($item === null) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ["details.{$index}.item_id" => 'The selected item is invalid.'],
                ];
            }
        }

        // Get PENDING status ID
        $pendingStatusId = $this->approvalStatusModel->getIdByName(ApprovalStatusModel::NAME_PENDING);
        if ($pendingStatusId === null) {
            return [
                'success' => false,
                'message' => 'System error: PENDING approval status not found.',
                'errors'  => [],
            ];
        }

        // Start transaction
        $this->db->transStart();

        $revisionData = [
            'type_id'               => $parent['type_id'],
            'transaction_date'      => $data['transaction_date'],
            'is_revision'           => true,
            'parent_transaction_id' => $parentTransactionId,
            'approval_status_id'    => $pendingStatusId,
            'approved_by'           => null,
            'user_id'               => $userId,
            'spk_id'                => isset($data['spk_id']) && is_numeric($data['spk_id']) ? (int) $data['spk_id'] : null,
        ];

        $revisionId = $this->transactionModel->insert($revisionData, true);

        if ($revisionId === false) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Failed to create revision transaction.',
                'errors'  => $this->transactionModel->errors(),
            ];
        }

        // Insert detail rows
        foreach ($data['details'] as $detail) {
            $detailData = [
                'transaction_id' => $revisionId,
                'item_id'        => (int) $detail['item_id'],
                'qty'            => (float) $detail['qty'],
            ];

            if ($this->detailModel->insert($detailData) === false) {
                $this->db->transRollback();

                return [
                    'success' => false,
                    'message' => 'Failed to create revision details.',
                    'errors'  => $this->detailModel->errors(),
                ];
            }
        }

        // Write audit log
        $auditLogged = $this->auditService->log(
            $userId,
            'stock_transaction_revision_submit',
            'stock_transactions',
            (int) $revisionId,
            'Stock transaction revision submitted.',
            null,
            $revisionData,
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
            'message' => 'Revision submitted successfully.',
            'data'    => [
                'id'                    => (int) $revisionId,
                'approval_status_id'    => $pendingStatusId,
                'is_revision'           => true,
                'parent_transaction_id' => $parentTransactionId,
            ],
        ];
    }

    public function approveRevision(int $revisionId, int $approverId, ?string $ipAddress = null): array
    {
        $revision = $this->transactionModel->findRevisionById($revisionId);
        if ($revision === null) {
            $transaction = $this->transactionModel->findById($revisionId);
            if ($transaction === null) {
                return [
                    'success' => false,
                    'message' => 'Revision not found.',
                    'errors'  => [],
                ];
            }

            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['id' => 'Transaction is not a revision.'],
            ];
        }

        $pendingStatusId  = $this->approvalStatusModel->getIdByName(ApprovalStatusModel::NAME_PENDING);
        $approvedStatusId = $this->approvalStatusModel->getIdByName(ApprovalStatusModel::NAME_APPROVED);
        $rejectedStatusId = $this->approvalStatusModel->getIdByName(ApprovalStatusModel::NAME_REJECTED);

        if ($pendingStatusId === null || $approvedStatusId === null || $rejectedStatusId === null) {
            return [
                'success' => false,
                'message' => 'System error: approval statuses not found.',
                'errors'  => [],
            ];
        }

        if ((int) $revision['approval_status_id'] !== $pendingStatusId) {
            if ((int) $revision['approval_status_id'] === $approvedStatusId) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['id' => 'Revision already approved.'],
                ];
            }

            if ((int) $revision['approval_status_id'] === $rejectedStatusId) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['id' => 'Revision already rejected.'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['id' => 'Revision has an invalid approval state.'],
            ];
        }

        // Load detail rows
        $details = $this->detailModel->getDetailsByTransactionId($revisionId);
        if ($details === []) {
            return [
                'success' => false,
                'message' => 'System error: revision has no details.',
                'errors'  => [],
            ];
        }

        // Get transaction type to determine qty mutation direction
        $type = $this->typeModel->find((int) $revision['type_id']);
        if ($type === null) {
            return [
                'success' => false,
                'message' => 'System error: transaction type not found.',
                'errors'  => [],
            ];
        }

        // Start transaction
        $this->db->transStart();

        // Apply stock mutations per detail using existing atomic logic
        foreach ($details as $detail) {
            $changeQty  = (float) $detail['qty'];
            $itemId     = (int) $detail['item_id'];
            $escapedQty = $this->db->escape(number_format($changeQty, 2, '.', ''));

            if (in_array($type['name'], [TransactionTypeModel::NAME_IN, TransactionTypeModel::NAME_RETURN_IN], true)) {
                // IN/RETURN_IN: unconditional increment
                $builder = $this->db->table('items');
                $builder->where('id', $itemId);
                $builder->set('qty', "qty + {$escapedQty}", false);
                $builder->set('updated_at', date('Y-m-d H:i:s'));

                if (! $builder->update()) {
                    $this->db->transRollback();

                    return [
                        'success' => false,
                        'message' => 'Failed to update item quantity.',
                        'errors'  => [],
                    ];
                }
            } else {
                // OUT: conditional decrement
                $builder = $this->db->table('items');
                $builder->where('id', $itemId);
                $builder->where("qty >= {$escapedQty}", null, false);
                $builder->set('qty', "qty - {$escapedQty}", false);
                $builder->set('updated_at', date('Y-m-d H:i:s'));

                if (! $builder->update()) {
                    $this->db->transRollback();

                    return [
                        'success' => false,
                        'message' => 'Failed to update item quantity.',
                        'errors'  => [],
                    ];
                }

                $affectedRows = $this->db->affectedRows();

                if ($affectedRows === 0) {
                    $this->db->transRollback();

                    return [
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors'  => [
                            'details' => 'Insufficient stock. Stock may have changed since revision submission.',
                        ],
                    ];
                }
            }
        }

        // Update revision row
        $oldValues = $revision;
        $updated   = $this->transactionModel->update($revisionId, [
            'approval_status_id' => $approvedStatusId,
            'approved_by'        => $approverId,
        ]);

        if (! $updated) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Failed to update revision status.',
                'errors'  => [],
            ];
        }

        // Write audit log
        $newValues = $this->transactionModel->find($revisionId);

        $auditLogged = $this->auditService->log(
            $approverId,
            'stock_transaction_revision_approve',
            'stock_transactions',
            $revisionId,
            'Stock transaction revision approved.',
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
            'message' => 'Revision approved successfully.',
            'data'    => [
                'id'                 => $revisionId,
                'approval_status_id' => $approvedStatusId,
                'approved_by'        => $approverId,
            ],
        ];
    }

    public function rejectRevision(int $revisionId, int $approverId, ?string $ipAddress = null): array
    {
        // Ensure revision exists
        $revision = $this->transactionModel->findRevisionById($revisionId);
        if ($revision === null) {
            $transaction = $this->transactionModel->findById($revisionId);
            if ($transaction === null) {
                return [
                    'success' => false,
                    'message' => 'Revision not found.',
                    'errors'  => [],
                ];
            }

            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['id' => 'Transaction is not a revision.'],
            ];
        }

        // Get status IDs
        $pendingStatusId  = $this->approvalStatusModel->getIdByName(ApprovalStatusModel::NAME_PENDING);
        $approvedStatusId = $this->approvalStatusModel->getIdByName(ApprovalStatusModel::NAME_APPROVED);
        $rejectedStatusId = $this->approvalStatusModel->getIdByName(ApprovalStatusModel::NAME_REJECTED);

        if ($pendingStatusId === null || $approvedStatusId === null || $rejectedStatusId === null) {
            return [
                'success' => false,
                'message' => 'System error: approval statuses not found.',
                'errors'  => [],
            ];
        }

        if ((int) $revision['approval_status_id'] !== $pendingStatusId) {
            if ((int) $revision['approval_status_id'] === $approvedStatusId) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['id' => 'Revision already approved.'],
                ];
            }

            if ((int) $revision['approval_status_id'] === $rejectedStatusId) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['id' => 'Revision already rejected.'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['id' => 'Revision has an invalid approval state.'],
            ];
        }

        // Start transaction
        $this->db->transStart();

        // Update revision row (no qty mutation)
        $oldValues = $revision;
        $updated   = $this->transactionModel->update($revisionId, [
            'approval_status_id' => $rejectedStatusId,
            'approved_by'        => $approverId,
        ]);

        if (! $updated) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Failed to update revision status.',
                'errors'  => [],
            ];
        }

        // Write audit log
        $newValues = $this->transactionModel->find($revisionId);

        $auditLogged = $this->auditService->log(
            $approverId,
            'stock_transaction_revision_reject',
            'stock_transactions',
            $revisionId,
            'Stock transaction revision rejected.',
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
            'message' => 'Revision rejected successfully.',
            'data'    => [
                'id'                 => $revisionId,
                'approval_status_id' => $rejectedStatusId,
                'approved_by'        => $approverId,
            ],
        ];
    }
}
