<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class DishCompositionSeeder extends Seeder
{
    public function run(): void
    {
        $itemIds = $this->resolveItemIds(['Beras', 'Ayam', 'Minyak Goreng', 'Telur']);

        if (count($itemIds) !== 4) {
            throw new RuntimeException('DishCompositionSeeder requires the seeded items: Beras, Ayam, Minyak Goreng, and Telur.');
        }

        $dishes = $this->db->table('dishes')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if ($dishes === []) {
            throw new RuntimeException('DishCompositionSeeder requires dishes to be seeded first.');
        }

        $itemCycle = array_values($itemIds);
        $itemCount = count($itemCycle);
        $rows      = [];

        foreach ($dishes as $index => $dish) {
            $itemId = $itemCycle[$index % $itemCount];
            $rows[] = [
                'dish_id'         => $dish['id'],
                'item_id'         => $itemId,
                'qty_per_patient' => '100.00',
            ];
        }

        $this->db->table('dish_compositions')->insertBatch($rows);
    }

    private function resolveItemIds(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            $row = $this->db->table('items')
                ->where('name', $name)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if ($row !== null) {
                $ids[$name] = $row['id'];
            }
        }

        return $ids;
    }
}
