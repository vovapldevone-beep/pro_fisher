<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * On the production database `lakes` was renamed to `lakes_old` and a fresh
     * `lakes` was created, but the foreign keys were never re-pointed. Inserts
     * into `catches` therefore validate against the wrong (stale) table.
     *
     * This finds every constraint still referencing `lakes_old`, nulls or removes
     * the rows that would violate the new key, then rebuilds it against `lakes`.
     *
     * Safe to run where `lakes_old` does not exist — it simply does nothing.
     */
    public function up(): void
    {
        if (! Schema::hasTable('lakes_old')) {
            return;
        }

        foreach ($this->staleForeignKeys() as $fk) {
            $table = $fk->TABLE_NAME;
            $column = $fk->COLUMN_NAME;
            $name = $fk->CONSTRAINT_NAME;
            $onDelete = $fk->DELETE_RULE; // CASCADE | SET NULL | RESTRICT | NO ACTION

            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");

            $this->reconcileOrphans($table, $column);

            $action = $onDelete === 'NO ACTION' ? 'RESTRICT' : $onDelete;
            DB::statement(
                "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` ".
                "FOREIGN KEY (`{$column}`) REFERENCES `lakes` (`id`) ON DELETE {$action}"
            );
        }
    }

    public function down(): void
    {
        // Deliberately irreversible: pointing keys back at a stale table is never wanted.
    }

    /** @return array<int, object> */
    private function staleForeignKeys(): array
    {
        return DB::select("
            SELECT k.TABLE_NAME, k.COLUMN_NAME, k.CONSTRAINT_NAME, r.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE k
            JOIN information_schema.REFERENTIAL_CONSTRAINTS r
              ON r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
             AND r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA
            WHERE k.TABLE_SCHEMA = DATABASE()
              AND k.REFERENCED_TABLE_NAME = 'lakes_old'
        ");
    }

    /**
     * Rows pointing at a lake that no longer exists would block the new key.
     * Nullable columns lose the link, non-nullable rows have to go.
     */
    private function reconcileOrphans(string $table, string $column): void
    {
        $orphans = DB::table($table)
            ->whereNotNull($column)
            ->whereNotIn($column, fn ($q) => $q->select('id')->from('lakes'));

        $count = (clone $orphans)->count();
        if ($count === 0) {
            return;
        }

        $nullable = collect(DB::select("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column]))
            ->first()?->Null === 'YES';

        if ($nullable) {
            $orphans->update([$column => null]);
            echo "  {$table}.{$column}: {$count} рядків відв'язано (NULL)\n";
        } else {
            $orphans->delete();
            echo "  {$table}.{$column}: {$count} рядків видалено (колонка NOT NULL)\n";
        }
    }
};
