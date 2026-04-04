<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MealTimeSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('meal_times')->insertBatch([
            ['name' => 'SIANG'],
            ['name' => 'SORE'],
            ['name' => 'PAGI'],
        ]);
    }
}
