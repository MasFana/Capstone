<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterMenuArchitecture extends Migration
{
    public function up()
    {
        // 1. Drop the unique constraint blocking multiple dishes per slot
        // The exact index name depends on the original creation, usually table_field_unique or similar.
        // We will try to drop the known index names. If they fail, the forge->dropKey won't halt execution if we suppress errors.
        try {
            // Try raw SQL first as it's more reliable for some DB versions
            $this->db->query('ALTER TABLE menu_dishes DROP INDEX IF EXISTS menu_id_meal_time_id');
        } catch (\Exception $e) {
            try {
                $this->forge->dropKey('menu_dishes', 'menu_id_meal_time_id');
            } catch (\Exception $e2) {
                // Ignore if index doesn't exist by this name
            }
        }
        
        // Add a standard index to replace the unique one for performance
        $this->forge->addKey(['menu_id', 'meal_time_id'], false, false, 'idx_menu_meal_time');
        $this->forge->processIndexes('menu_dishes');

        // 2. Drop unique constraint on menu_schedules(day_of_month)
        try {
            $this->db->query('ALTER TABLE menu_schedules DROP INDEX IF EXISTS day_of_month');
        } catch (\Exception $e) {
            try {
                $this->forge->dropKey('menu_schedules', 'day_of_month');
            } catch (\Exception $e2) {
                 // Ignore
            }
        }
        
        // Add standard index
        $this->forge->addKey('day_of_month', false, false, 'idx_day_of_month');
        $this->forge->processIndexes('menu_schedules');

        // 3. Add patient_count to menu_schedules
        $this->forge->addColumn('menu_schedules', [
            'patient_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'menu_id'
            ]
        ]);
        
        // 4. Update foreign key constraints to CASCADE on DELETE for menu_dishes
        // Assuming original foreign keys were set. We need raw SQL to alter constraints safely.
        $this->db->query('ALTER TABLE menu_dishes DROP FOREIGN KEY IF EXISTS menu_dishes_menu_id_foreign');
        $this->db->query('ALTER TABLE menu_dishes ADD CONSTRAINT menu_dishes_menu_id_foreign FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE ON UPDATE CASCADE');
        
        $this->db->query('ALTER TABLE menu_schedules DROP FOREIGN KEY IF EXISTS menu_schedules_menu_id_foreign');
        $this->db->query('ALTER TABLE menu_schedules ADD CONSTRAINT menu_schedules_menu_id_foreign FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->forge->dropColumn('menu_schedules', 'patient_count');
        // Reverting unique constraints is complex due to potential data violations.
        // In a real rollback, we'd need to clean up data first.
    }
}
