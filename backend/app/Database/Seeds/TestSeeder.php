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
        $this->call('MenuSeeder');
        $this->call('ItemUnitSeeder');
        $this->call('ItemSeeder');
        $this->call('DishSeeder');
        $this->call('DishCompositionSeeder');
        $this->call('MenuDishSeeder');
        $this->call('MenuScheduleSeeder');
        $this->call('DailyPatientSeeder');
        $this->call('StockTransactionSeeder');
        $this->call('StockOpnameSeeder');
        $this->call('SpkPersistenceSeeder');
    }
}
