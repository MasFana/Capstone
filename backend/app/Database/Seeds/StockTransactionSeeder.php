<?php

namespace App\Database\Seeds;

use App\Models\ApprovalStatusModel;
use App\Models\ItemModel;
use App\Models\RoleModel;
use App\Models\TransactionTypeModel;
use App\Models\UserModel;
use CodeIgniter\Database\Seeder;
use RuntimeException;

class StockTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $typeModel = new TransactionTypeModel();
        $statusModel = new ApprovalStatusModel();
        $itemModel = new ItemModel();
        $userModel = new UserModel();
        $roleModel = new RoleModel();

        $outTypeId = $typeModel->getIdByName(TransactionTypeModel::NAME_OUT);
        $approvedStatusId = $statusModel->getIdByName(ApprovalStatusModel::NAME_APPROVED);

        if ($outTypeId === null || $approvedStatusId === null) {
            throw new RuntimeException('StockTransactionSeeder requires OUT transaction type and APPROVED approval status.');
        }

        $gudangRole = $roleModel->findByName('gudang');
        if ($gudangRole === null) {
            throw new RuntimeException('StockTransactionSeeder requires gudang role to be seeded.');
        }

        $gudangUser = $userModel
            ->where('role_id', $gudangRole['id'])
            ->where('deleted_at', null)
            ->first();

        if ($gudangUser === null) {
            throw new RuntimeException('StockTransactionSeeder requires an active gudang user.');
        }

        $rows = $itemModel
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->findAll();

        if ($rows === []) {
            throw new RuntimeException('StockTransactionSeeder requires seeded items.');
        }

        $inserted = $this->db->table('stock_transactions')->insert([
            'type_id' => $outTypeId,
            'transaction_date' => '2026-03-20',
            'is_revision' => false,
            'parent_transaction_id' => null,
            'approval_status_id' => $approvedStatusId,
            'approved_by' => null,
            'user_id' => (int) $gudangUser['id'],
            'spk_id' => null,
            'reason' => 'Baseline OUT usage for monthly SPK kering/pengemas reference.',
        ]);

        if ($inserted === false) {
            throw new RuntimeException('StockTransactionSeeder failed to insert stock transaction header.');
        }

        $transactionId = (int) $this->db->insertID();
        if ($transactionId <= 0) {
            throw new RuntimeException('StockTransactionSeeder failed to resolve inserted transaction ID.');
        }

        $details = [];
        foreach ($rows as $row) {
            $itemId = (int) $row['id'];
            $qty = match ((string) $row['name']) {
                'Beras' => 6000.00,
                'Ayam' => 4500.00,
                'Minyak Goreng' => 3000.00,
                'Telur' => 90.00,
                default => 250.00,
            };

            $details[] = [
                'transaction_id' => (int) $transactionId,
                'item_id' => $itemId,
                'qty' => $qty,
                'input_qty' => $qty,
                'input_unit' => 'base',
            ];
        }

        $this->db->table('stock_transaction_details')->insertBatch($details);
    }
}
