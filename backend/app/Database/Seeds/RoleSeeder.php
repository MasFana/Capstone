<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['name' => 'Super Admin'],
            ['name' => 'SPK/Gizi'],
            ['name' => 'Gudang'],
        ];

        $this->db->table('roles')->insertBatch($data);
    }
}
