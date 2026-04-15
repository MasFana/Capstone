<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MenuScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $builder = $this->db->table('menu_schedules');

        // Deterministic schedule overrides used by calendar resolution.
        $rows = [
            ['day_of_month' => 1, 'menu_id' => 1],
            ['day_of_month' => 5, 'menu_id' => 5],
            ['day_of_month' => 10, 'menu_id' => 10],
            ['day_of_month' => 11, 'menu_id' => 11],
            ['day_of_month' => 15, 'menu_id' => 4],
            ['day_of_month' => 20, 'menu_id' => 8],
            ['day_of_month' => 25, 'menu_id' => 2],
            ['day_of_month' => 30, 'menu_id' => 6],
            ['day_of_month' => 31, 'menu_id' => 11],
        ];

        $builder->insertBatch($rows);
    }
}
