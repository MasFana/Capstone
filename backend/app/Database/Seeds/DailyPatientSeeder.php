<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DailyPatientSeeder extends Seeder
{
    public function run(): void
    {
        $builder = $this->db->table('daily_patients');

        $rows = [
            [
                'service_date'   => '2026-04-15',
                'total_patients' => 128,
                'notes'          => 'Baseline pasien harian untuk simulasi SPK basah.',
            ],
            [
                'service_date'   => '2026-04-16',
                'total_patients' => 122,
                'notes'          => 'Data lanjutan untuk rentang target basah +1 hari.',
            ],
        ];

        $builder->insertBatch($rows);
    }
}
