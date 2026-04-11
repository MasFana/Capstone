<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run()
    {
        $this->call('RoleSeeder');
        $this->call('UserSeeder');
        $this->call('ItemCategorySeeder');
        $this->call('TransactionTypeSeeder');
        $this->call('ApprovalStatusSeeder');
        $this->call('MealTimeSeeder');
        $this->call('ItemUnitSeeder');
        $this->call('ItemSeeder');
    }
}
