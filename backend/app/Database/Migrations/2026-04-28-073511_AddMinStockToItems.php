<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMinStockToItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('items', [
            'min_stock' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'after'      => 'qty'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('items', 'min_stock');
    }
}
