<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            "id" => [
                "type" => "BIGINT",
                "auto_increment" => true,
            ],
            "item_category_id" => [
                "type" => "BIGINT",
                "null" => false,
            ],
            "name" => [
                "type" => "VARCHAR",
                "constraint" => 100,
                "null" => false,
            ],
            "unit_base" => [
                "type" => "VARCHAR",
                "constraint" => 20,
                "null" => false,
            ],
            "unit_convert" => [
                "type" => "VARCHAR",
                "constraint" => 20,
                "null" => true,
            ],
            "is_active" => [
                "type" => "BOOLEAN",
                "default" => true,
            ],
            "qty" => [
                "type" => "DECIMAL",
                "constraint" => "12,2",
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
            "deleted_at" => [
                "type" => "DATETIME",
                "null" => true,
            ],
        ]);

        $this->forge->addForeignKey(
            "item_category_id",
            "item_categories",
            "id",
            "CASCADE",
            "CASCADE",
        );

        // Primary key
        $this->forge->addKey("id", true);

        $this->forge->createTable("items");
    }

    public function down()
    {
        $this->forge->dropTable("items", true);
    }
}
