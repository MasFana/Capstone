<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateItemCategory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            "id" => [
                "type" => "BIGINT",
                "auto_increment" => true,
            ],
            "name" => [
                "type" => "VARCHAR",
                "constraint" => 50,
                "null" => false,
            ],
            "created_at" => [
                "type" => "DATETIME",
                "null" => true,
            ],
            "updated_at" => [
                "type" => "DATETIME",
                "null" => true,
            ],
        ]);

        // Primary key
        $this->forge->addKey("id", true);

        $this->forge->createTable("item_categories");
    }

    public function down()
    {
        $this->forge->dropTable("item_categories", true);
    }
}
