<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * On the production database `lakes` was renamed to `lakes_old` and a fresh
     * `lakes` was created, but the foreign keys kept pointing at the stale table,
     * so inserts into `catches` were validated against the wrong rows.
     *
     * `lakes_old` itself is never touched — only the child tables' constraints.
     *
     * Written to be re-runnable from *any* state: DDL is not transactional in
     * MySQL/MariaDB, so a failed run can leave a constraint dropped. Each table
     * is therefore reconciled independently against the desired end state.
     */
    private const CHILD_TABLES = ['catches', 'lake_photos', 'lake_reviews', 'permits'];

    private const COLUMN = 'lake_id';

    public function up(): void
    {
        foreach (self::CHILD_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, self::COLUMN)) {
                continue;
            }

            $fk = $this->foreignKeyOn($table);

            if ($fk && $fk->REFERENCED_TABLE_NAME === 'lakes') {
                continue; // already correct
            }

            if ($fk) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            $this->reconcileOrphans($table);

            $name = $fk->CONSTRAINT_NAME ?? "{$table}_".self::COLUMN.'_foreign';
            DB::statement(
                "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` ".
                'FOREIGN KEY (`'.self::COLUMN.'`) REFERENCES `lakes` (`id`) ON DELETE CASCADE'
            );
        }
    }

    public function down(): void
    {
        // Deliberately irreversible: pointing keys back at a stale table is never wanted.
    }

    private function foreignKeyOn(string $table): ?object
    {
        return DB::select('
            SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ', [$table, self::COLUMN])[0] ?? null;
    }

    /**
     * Rows pointing at a lake that no longer exists would block the new key.
     * Nullable columns lose the link, non-nullable rows have to go.
     */
    private function reconcileOrphans(string $table): void
    {
        $orphans = DB::table($table)
            ->whereNotNull(self::COLUMN)
            ->whereNotIn(self::COLUMN, fn ($q) => $q->select('id')->from('lakes'));

        $count = (clone $orphans)->count();
        if ($count === 0) {
            return;
        }

        if ($this->columnIsNullable($table)) {
            $orphans->update([self::COLUMN => null]);
            echo "  {$table}: {$count} рядків відв'язано (NULL)\n";
        } else {
            $orphans->delete();
            echo "  {$table}: {$count} рядків видалено (колонка NOT NULL)\n";
        }
    }

    /** MariaDB rejects placeholders in `SHOW COLUMNS ... LIKE ?`, hence information_schema. */
    private function columnIsNullable(string $table): bool
    {
        $row = DB::select('
            SELECT IS_NULLABLE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ', [$table, self::COLUMN])[0] ?? null;

        return $row?->IS_NULLABLE === 'YES';
    }
};
