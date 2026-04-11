<?php

namespace App\Database\Seeds;

use App\Models\ItemCategoryModel;
use App\Models\ItemUnitModel;
use CodeIgniter\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run()
    {
        $categoryModel = new ItemCategoryModel();
        $itemUnitModel = new ItemUnitModel();

        $basah     = $categoryModel->where('name', 'BASAH')->first();
        $kering    = $categoryModel->where('name', 'KERING')->first();
        $pengemas  = $categoryModel->where('name', 'PENGEMAS')->first();

        $gramId   = $itemUnitModel->getIdByName('gram');
        $kgId     = $itemUnitModel->getIdByName('kg');
        $mlId     = $itemUnitModel->getIdByName('ml');
        $literId  = $itemUnitModel->getIdByName('liter');
        $butirId  = $itemUnitModel->getIdByName('butir');
        $packId   = $itemUnitModel->getIdByName('pack');

        $this->db->table('items')->insertBatch([
            [
                'item_category_id'     => $kering['id'],
                'name'                 => 'Beras',
                'unit_base'            => 'gram',
                'unit_convert'         => 'kg',
                'item_unit_base_id'    => $gramId,
                'item_unit_convert_id' => $kgId,
                'conversion_base'      => 1000,
                'is_active'            => true,
                'qty'                  => 0,
            ],
            [
                'item_category_id'     => $basah['id'],
                'name'                 => 'Ayam',
                'unit_base'            => 'gram',
                'unit_convert'         => 'kg',
                'item_unit_base_id'    => $gramId,
                'item_unit_convert_id' => $kgId,
                'conversion_base'      => 1000,
                'is_active'            => true,
                'qty'                  => 0,
            ],
            [
                'item_category_id'     => $basah['id'],
                'name'                 => 'Minyak Goreng',
                'unit_base'            => 'ml',
                'unit_convert'         => 'liter',
                'item_unit_base_id'    => $mlId,
                'item_unit_convert_id' => $literId,
                'conversion_base'      => 1000,
                'is_active'            => true,
                'qty'                  => 0,
            ],
            [
                'item_category_id'     => $pengemas['id'],
                'name'                 => 'Telur',
                'unit_base'            => 'butir',
                'unit_convert'         => 'pack',
                'item_unit_base_id'    => $butirId,
                'item_unit_convert_id' => $packId,
                'conversion_base'      => 10,
                'is_active'            => true,
                'qty'                  => 0,
            ],
        ]);
    }
}
