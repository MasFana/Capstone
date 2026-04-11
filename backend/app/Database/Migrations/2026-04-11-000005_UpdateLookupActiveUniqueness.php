<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateLookupActiveUniqueness extends Migration
{
    public function up(): void
    {
        if ($this->db->getPlatform() === 'SQLite3') {
            return;
        }

        $this->dropUniqueIndexIfExists('item_categories', 'name');
        $this->dropUniqueIndexIfExists('item_units', 'name');

        $this->ensureNormalizedActiveKey('item_categories', 'uq_item_categories_active_name');
        $this->ensureNormalizedActiveKey('item_units', 'uq_item_units_active_name');
    }

    public function down(): void
    {
        if ($this->db->getPlatform() === 'SQLite3') {
            return;
        }

        $this->dropIndexIfExists('item_categories', 'uq_item_categories_active_name');
        $this->dropIndexIfExists('item_units', 'uq_item_units_active_name');

        $this->db->query('ALTER TABLE item_categories ADD UNIQUE KEY name (name)');
        $this->db->query('ALTER TABLE item_units ADD UNIQUE KEY name (name)');
    }

    private function ensureNormalizedActiveKey(string $table, string $indexName): void
    {
        $this->db->query(
            sprintf(
                'CREATE UNIQUE INDEX %s ON %s ((CASE WHEN deleted_at IS NULL THEN LOWER(TRIM(name)) ELSE NULL END))',
                $indexName,
                $table,
            ),
        );
    }

    private function dropUniqueIndexIfExists(string $table, string $indexName): void
    {
        $query = $this->db->query('SHOW INDEX FROM ' . $table . ' WHERE Key_name = ?', [$indexName]);
        if ($query->getRowArray() !== null) {
            $this->db->query('ALTER TABLE ' . $table . ' DROP INDEX ' . $indexName);
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $query = $this->db->query('SHOW INDEX FROM ' . $table . ' WHERE Key_name = ?', [$indexName]);
        if ($query->getRowArray() !== null) {
            $this->db->query('DROP INDEX ' . $indexName . ' ON ' . $table);
        }
    }
}
