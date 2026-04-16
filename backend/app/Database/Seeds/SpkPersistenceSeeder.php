<?php

namespace App\Database\Seeds;

use App\Models\ItemCategoryModel;
use App\Models\ItemModel;
use App\Models\RoleModel;
use App\Models\UserModel;
use CodeIgniter\Database\Seeder;
use RuntimeException;

class SpkPersistenceSeeder extends Seeder
{
    public function run(): void
    {
        $roleModel = new RoleModel();
        $userModel = new UserModel();
        $categoryModel = new ItemCategoryModel();
        $itemModel = new ItemModel();

        $dapurRole = $roleModel->findByName('dapur');
        if ($dapurRole === null) {
            throw new RuntimeException('SpkPersistenceSeeder requires dapur role to be seeded.');
        }

        $spkUser = $userModel
            ->where('role_id', $dapurRole['id'])
            ->where('deleted_at', null)
            ->first();

        if ($spkUser === null) {
            throw new RuntimeException('SpkPersistenceSeeder requires an active dapur user.');
        }

        $basahCategoryId = $categoryModel->getIdByName(ItemCategoryModel::NAME_BASAH);
        $keringCategoryId = $categoryModel->getIdByName(ItemCategoryModel::NAME_KERING);

        if ($basahCategoryId === null || $keringCategoryId === null) {
            throw new RuntimeException('SpkPersistenceSeeder requires BASAH and KERING categories.');
        }

        $dailyPatient = $this->db->table('daily_patients')
            ->where('service_date', '2026-04-15')
            ->get()
            ->getRowArray();

        if ($dailyPatient === null) {
            throw new RuntimeException('SpkPersistenceSeeder requires baseline daily_patients row (2026-04-15).');
        }

        $allItems = $itemModel
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->findAll();

        if ($allItems === []) {
            throw new RuntimeException('SpkPersistenceSeeder requires seeded items.');
        }

        $basahItems = array_values(array_filter($allItems, static fn(array $item): bool => (int) $item['item_category_id'] === (int) $basahCategoryId));
        $keringItems = array_values(array_filter($allItems, static fn(array $item): bool => (int) $item['item_category_id'] === (int) $keringCategoryId));

        if ($basahItems === [] || $keringItems === []) {
            throw new RuntimeException('SpkPersistenceSeeder requires at least one BASAH item and one KERING item.');
        }

        $basahInserted = $this->db->table('spk_calculations')->insert([
            'spk_type' => 'basah',
            'calculation_scope' => 'combined_window',
            'scope_key' => 'basah|combined_window|2026-04-15|2026-04-16|' . $basahCategoryId,
            'version' => 1,
            'is_latest' => false,
            'calculation_date' => '2026-04-15',
            'target_date_start' => '2026-04-15',
            'target_date_end' => '2026-04-16',
            'target_month' => null,
            'daily_patient_id' => (int) $dailyPatient['id'],
            'user_id' => (int) $spkUser['id'],
            'category_id' => (int) $basahCategoryId,
            'estimated_patients' => 126,
            'is_finish' => true,
        ]);

        if ($basahInserted === false) {
            throw new RuntimeException('SpkPersistenceSeeder failed to insert basah SPK header.');
        }

        $basahSpkId = (int) $this->db->insertID();
        if ($basahSpkId <= 0) {
            throw new RuntimeException('SpkPersistenceSeeder failed to resolve basah SPK ID.');
        }

        $basahRecommendations = [];
        foreach ($basahItems as $idx => $item) {
            $required = 900.00 + ($idx * 125.00);
            $system = max($required - (float) $item['qty'], 0.0);

            $basahRecommendations[] = [
                'spk_id' => (int) $basahSpkId,
                'item_id' => (int) $item['id'],
                'target_date' => '2026-04-15',
                'current_stock_qty' => (float) $item['qty'],
                'required_qty' => $required,
                'system_recommended_qty' => $system,
                'recommended_qty' => $system,
                'is_overridden' => false,
                'override_reason' => null,
                'overridden_by' => null,
                'overridden_at' => null,
            ];
        }

        $this->db->table('spk_recommendations')->insertBatch($basahRecommendations);

        $basahV2Inserted = $this->db->table('spk_calculations')->insert([
            'spk_type' => 'basah',
            'calculation_scope' => 'combined_window',
            'scope_key' => 'basah|combined_window|2026-04-15|2026-04-16|' . $basahCategoryId,
            'version' => 2,
            'is_latest' => true,
            'calculation_date' => '2026-04-16',
            'target_date_start' => '2026-04-15',
            'target_date_end' => '2026-04-16',
            'target_month' => null,
            'daily_patient_id' => (int) $dailyPatient['id'],
            'user_id' => (int) $spkUser['id'],
            'category_id' => (int) $basahCategoryId,
            'estimated_patients' => 130,
            'is_finish' => false,
        ]);

        if ($basahV2Inserted === false) {
            throw new RuntimeException('SpkPersistenceSeeder failed to insert basah SPK header version 2.');
        }

        $basahSpkV2Id = (int) $this->db->insertID();
        if ($basahSpkV2Id <= 0) {
            throw new RuntimeException('SpkPersistenceSeeder failed to resolve basah SPK version 2 ID.');
        }

        $basahV2Recommendations = [];
        foreach ($basahItems as $idx => $item) {
            $required = 980.00 + ($idx * 140.00);
            $system = max($required - (float) $item['qty'], 0.0);

            $basahV2Recommendations[] = [
                'spk_id' => (int) $basahSpkV2Id,
                'item_id' => (int) $item['id'],
                'target_date' => '2026-04-15',
                'current_stock_qty' => (float) $item['qty'],
                'required_qty' => $required,
                'system_recommended_qty' => $system,
                'recommended_qty' => $system,
                'is_overridden' => false,
                'override_reason' => null,
                'overridden_by' => null,
                'overridden_at' => null,
            ];
        }

        $this->db->table('spk_recommendations')->insertBatch($basahV2Recommendations);

        $keringInserted = $this->db->table('spk_calculations')->insert([
            'spk_type' => 'kering_pengemas',
            'calculation_scope' => 'monthly',
            'scope_key' => 'kering_pengemas|monthly|2026-04|' . $keringCategoryId,
            'version' => 1,
            'is_latest' => false,
            'calculation_date' => '2026-04-15',
            'target_date_start' => '2026-04-01',
            'target_date_end' => '2026-04-30',
            'target_month' => '2026-04',
            'daily_patient_id' => null,
            'user_id' => (int) $spkUser['id'],
            'category_id' => (int) $keringCategoryId,
            'estimated_patients' => 0,
            'is_finish' => true,
        ]);

        if ($keringInserted === false) {
            throw new RuntimeException('SpkPersistenceSeeder failed to insert kering/pengemas SPK header.');
        }

        $keringSpkId = (int) $this->db->insertID();
        if ($keringSpkId <= 0) {
            throw new RuntimeException('SpkPersistenceSeeder failed to resolve kering/pengemas SPK ID.');
        }

        $keringRecommendations = [];
        foreach ($keringItems as $idx => $item) {
            $required = 1200.00 + ($idx * 200.00);
            $system = max($required - (float) $item['qty'], 0.0);

            $recommended = $system;
            $isOverridden = false;
            $overrideReason = null;
            $overriddenBy = null;
            $overriddenAt = null;

            if ($idx === 0) {
                $recommended = $system + 150.00;
                $isOverridden = true;
                $overrideReason = 'Buffer stok awal bulan untuk antisipasi lonjakan permintaan.';
                $overriddenBy = (int) $spkUser['id'];
                $overriddenAt = '2026-04-15 09:30:00';
            }

            $keringRecommendations[] = [
                'spk_id' => (int) $keringSpkId,
                'item_id' => (int) $item['id'],
                'target_date' => null,
                'current_stock_qty' => (float) $item['qty'],
                'required_qty' => $required,
                'system_recommended_qty' => $system,
                'recommended_qty' => $recommended,
                'is_overridden' => $isOverridden,
                'override_reason' => $overrideReason,
                'overridden_by' => $overriddenBy,
                'overridden_at' => $overriddenAt,
            ];
        }

        $this->db->table('spk_recommendations')->insertBatch($keringRecommendations);

        $keringV2Inserted = $this->db->table('spk_calculations')->insert([
            'spk_type' => 'kering_pengemas',
            'calculation_scope' => 'monthly',
            'scope_key' => 'kering_pengemas|monthly|2026-04|' . $keringCategoryId,
            'version' => 2,
            'is_latest' => true,
            'calculation_date' => '2026-04-16',
            'target_date_start' => '2026-04-01',
            'target_date_end' => '2026-04-30',
            'target_month' => '2026-04',
            'daily_patient_id' => null,
            'user_id' => (int) $spkUser['id'],
            'category_id' => (int) $keringCategoryId,
            'estimated_patients' => 0,
            'is_finish' => false,
        ]);

        if ($keringV2Inserted === false) {
            throw new RuntimeException('SpkPersistenceSeeder failed to insert kering/pengemas SPK header version 2.');
        }

        $keringSpkV2Id = (int) $this->db->insertID();
        if ($keringSpkV2Id <= 0) {
            throw new RuntimeException('SpkPersistenceSeeder failed to resolve kering/pengemas SPK version 2 ID.');
        }

        $keringV2Recommendations = [];
        foreach ($keringItems as $idx => $item) {
            $required = 1300.00 + ($idx * 220.00);
            $system = max($required - (float) $item['qty'], 0.0);

            $recommended = $system;
            $isOverridden = false;
            $overrideReason = null;
            $overriddenBy = null;
            $overriddenAt = null;

            if ($idx === 0) {
                $recommended = $system + 100.00;
                $isOverridden = true;
                $overrideReason = 'Adjusted monthly reserve based on updated demand forecast.';
                $overriddenBy = (int) $spkUser['id'];
                $overriddenAt = '2026-04-16 09:45:00';
            }

            $keringV2Recommendations[] = [
                'spk_id' => (int) $keringSpkV2Id,
                'item_id' => (int) $item['id'],
                'target_date' => null,
                'current_stock_qty' => (float) $item['qty'],
                'required_qty' => $required,
                'system_recommended_qty' => $system,
                'recommended_qty' => $recommended,
                'is_overridden' => $isOverridden,
                'override_reason' => $overrideReason,
                'overridden_by' => $overriddenBy,
                'overridden_at' => $overriddenAt,
            ];
        }

        $this->db->table('spk_recommendations')->insertBatch($keringV2Recommendations);
    }
}
