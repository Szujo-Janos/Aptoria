<?php

namespace App\Services\Database;

use App\Services\Setup\SetupStateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class DatabaseMaintenanceService
{
    public const EXPORT_TYPE = 'aptoria_full_database_export';

    public function __construct(private readonly SetupStateService $setupState)
    {
    }

    /** @return array{driver: string, schema_hash: string, table_count: int, row_count: int, tables: array<string, int>} */
    public function summary(): array
    {
        $tables = $this->tableNames();
        $counts = $this->rowCounts($tables);

        return [
            'driver' => DB::connection()->getDriverName(),
            'schema_hash' => $this->schemaHash($tables),
            'table_count' => count($tables),
            'row_count' => array_sum($counts),
            'tables' => $counts,
        ];
    }

    /** @return array<string, mixed> */
    public function exportPayload(): array
    {
        $tables = $this->tableNames();
        $payloadTables = [];

        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            $query = DB::table($table);

            if (in_array('id', $columns, true)) {
                $query->orderBy('id');
            }

            $rows = $query->get()
                ->map(fn (object $row): array => $this->normalizeRow((array) $row))
                ->values()
                ->all();

            $payloadTables[$table] = [
                'columns' => $columns,
                'row_count' => count($rows),
                'rows' => $rows,
            ];
        }

        return [
            'type' => self::EXPORT_TYPE,
            'product' => 'Aptoria',
            'version' => config('aptoria.version'),
            'generated_at' => now()->toIso8601String(),
            'database' => [
                'driver' => DB::connection()->getDriverName(),
                'schema_hash' => $this->schemaHash($tables),
                'tables' => $tables,
            ],
            'notes' => [
                'restore_requires_matching_schema' => true,
                'encrypted_values_require_same_app_key' => true,
                'environment_files_are_not_included' => true,
            ],
            'tables' => $payloadTables,
        ];
    }

    /** @param array<string, mixed> $payload @return array{tables: int, rows: int} */
    public function importPayload(array $payload): array
    {
        $this->validatePayload($payload);

        /** @var array<string, array<string, mixed>> $payloadTables */
        $payloadTables = $payload['tables'];
        $currentTables = $this->tableNames();
        $importTables = $this->dependencyOrderedTableNames($currentTables);
        $deleteTables = array_reverse($importTables);
        $rowsImported = 0;

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($deleteTables, $importTables, $payloadTables, &$rowsImported): void {
                foreach ($deleteTables as $table) {
                    DB::table($table)->delete();
                }

                foreach ($importTables as $table) {
                    $rows = $payloadTables[$table]['rows'] ?? [];

                    if (! is_array($rows) || $rows === []) {
                        continue;
                    }

                    foreach (array_chunk($rows, 250) as $chunk) {
                        /** @var array<int, array<string, mixed>> $chunk */
                        DB::table($table)->insert($chunk);
                        $rowsImported += count($chunk);
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->assertForeignKeyIntegrity();
        $this->resetAutoIncrement($this->tablesWithIdColumn($currentTables));

        return [
            'tables' => count($currentTables),
            'rows' => $rowsImported,
        ];
    }

    /** @return array{tables: int, rows_deleted: int} */
    public function hardReset(): array
    {
        $tables = $this->tableNames();
        $resetTables = array_values(array_filter($tables, fn (string $table): bool => $table !== 'migrations'));
        $deleteTables = array_reverse($this->dependencyOrderedTableNames($resetTables));
        $rowsDeleted = 0;

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($deleteTables, &$rowsDeleted): void {
                foreach ($deleteTables as $table) {
                    $rowsDeleted += DB::table($table)->count();
                    DB::table($table)->delete();
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->assertForeignKeyIntegrity();
        $this->resetAutoIncrement($this->tablesWithIdColumn($resetTables));

        $this->setupState->clearLock();

        return [
            'tables' => max(0, count($tables) - (in_array('migrations', $tables, true) ? 1 : 0)),
            'rows_deleted' => $rowsDeleted,
        ];
    }

    /** @return array<int, string> */
    public function tableNames(): array
    {
        $driver = DB::connection()->getDriverName();

        try {
            $tables = match ($driver) {
                'sqlite' => $this->sqliteTableNames(),
                'mysql', 'mariadb' => $this->mysqlTableNames(),
                default => $this->portableTableNames(),
            };
        } catch (Throwable $exception) {
            throw new RuntimeException('Unable to read database table list: '.$exception->getMessage(), previous: $exception);
        }

        $tables = array_values(array_unique(array_filter($tables, fn (string $table): bool => $table !== '' && ! str_starts_with($table, 'sqlite_'))));
        sort($tables, SORT_NATURAL);

        return $tables;
    }

    /** @param array<int, string>|null $tables */
    public function schemaHash(?array $tables = null): string
    {
        return hash('sha256', json_encode($this->schemaSignature($tables), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }

    /** @param array<int, string>|null $tables @return array<string, array<int, string>> */
    private function schemaSignature(?array $tables = null): array
    {
        $signature = [];
        $tables ??= $this->tableNames();

        foreach ($tables as $table) {
            $signature[$table] = Schema::getColumnListing($table);
        }

        ksort($signature, SORT_NATURAL);

        return $signature;
    }

    /** @param array<int, string> $tables @return array<string, int> */
    private function rowCounts(array $tables): array
    {
        $counts = [];

        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    /** @return array<int, string> */
    private function sqliteTableNames(): array
    {
        return collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
            ->map(fn (object $row): string => (string) ($row->name ?? ''))
            ->all();
    }

    /** @return array<int, string> */
    private function mysqlTableNames(): array
    {
        $database = (string) DB::connection()->getDatabaseName();

        return collect(DB::select('SELECT table_name AS name FROM information_schema.tables WHERE table_schema = ? AND table_type = ?', [$database, 'BASE TABLE']))
            ->map(fn (object $row): string => (string) ($row->name ?? ''))
            ->all();
    }

    /** @return array<int, string> */
    private function portableTableNames(): array
    {
        $schema = DB::connection()->getSchemaBuilder();

        if (method_exists($schema, 'getTableListing')) {
            /** @var array<int, string> $tables */
            $tables = $schema->getTableListing();

            return $tables;
        }

        if (method_exists($schema, 'getTables')) {
            /** @var array<int, array<string, mixed>> $tables */
            $tables = $schema->getTables();

            return collect($tables)
                ->map(fn (array $table): string => (string) ($table['name'] ?? ''))
                ->all();
        }

        throw new RuntimeException('The current database driver does not expose table listing support.');
    }


    /** @param array<int, string> $tables @return array<int, string> */
    private function dependencyOrderedTableNames(array $tables): array
    {
        $tables = array_values(array_unique($tables));
        $tableSet = array_fill_keys($tables, true);
        $ordered = [];
        $dependencies = [];

        foreach ($tables as $table) {
            $dependencies[$table] = array_values(array_filter(
                $this->referencedTables($table),
                fn (string $referencedTable): bool => isset($tableSet[$referencedTable]) && $referencedTable !== $table
            ));
        }

        while (count($ordered) < count($tables)) {
            $progress = false;

            foreach ($tables as $table) {
                if (in_array($table, $ordered, true)) {
                    continue;
                }

                $missingDependencies = array_values(array_diff($dependencies[$table] ?? [], $ordered));

                if ($missingDependencies !== []) {
                    continue;
                }

                $ordered[] = $table;
                $progress = true;
            }

            if ($progress) {
                continue;
            }

            // Cyclic or driver-invisible dependency fallback: keep deterministic order.
            foreach ($tables as $table) {
                if (! in_array($table, $ordered, true)) {
                    $ordered[] = $table;
                }
            }
        }

        return $ordered;
    }

    /** @return array<int, string> */
    private function referencedTables(string $table): array
    {
        $driver = DB::connection()->getDriverName();

        try {
            $references = match ($driver) {
                'sqlite' => $this->sqliteReferencedTables($table),
                'mysql', 'mariadb' => $this->mysqlReferencedTables($table),
                default => [],
            };
        } catch (Throwable) {
            return [];
        }

        $references = array_values(array_unique(array_filter($references, fn (string $referencedTable): bool => $referencedTable !== '')));
        sort($references, SORT_NATURAL);

        return $references;
    }

    /** @return array<int, string> */
    private function sqliteReferencedTables(string $table): array
    {
        $quotedTable = '"'.str_replace('"', '""', $table).'"';

        return collect(DB::select('PRAGMA foreign_key_list('.$quotedTable.')'))
            ->map(fn (object $row): string => (string) ($row->table ?? ''))
            ->all();
    }

    /** @return array<int, string> */
    private function mysqlReferencedTables(string $table): array
    {
        $database = (string) DB::connection()->getDatabaseName();

        return collect(DB::select(
            'SELECT referenced_table_name AS name FROM information_schema.key_column_usage WHERE table_schema = ? AND table_name = ? AND referenced_table_name IS NOT NULL',
            [$database, $table]
        ))
            ->map(fn (object $row): string => (string) ($row->name ?? ''))
            ->all();
    }

    /** @param array<int, string> $tables @return array<int, string> */
    private function tablesWithIdColumn(array $tables): array
    {
        return array_values(array_filter($tables, fn (string $table): bool => Schema::hasColumn($table, 'id')));
    }

    private function assertForeignKeyIntegrity(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        try {
            $violations = DB::select('PRAGMA foreign_key_check');
        } catch (Throwable) {
            return;
        }

        if ($violations !== []) {
            throw new RuntimeException('Database import completed with foreign key integrity violations.');
        }
    }


    /** @param array<int, string> $tables */
    private function resetAutoIncrement(array $tables): void
    {
        if ($tables === []) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        try {
            if ($driver === 'sqlite') {
                DB::table('sqlite_sequence')->whereIn('name', $tables)->delete();

                return;
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                foreach ($tables as $table) {
                    $quoted = '`'.str_replace('`', '``', $table).'`';
                    DB::statement("ALTER TABLE {$quoted} AUTO_INCREMENT = 1");
                }
            }
        } catch (Throwable) {
            // Resetting auto-increment counters is best-effort only; data deletion is already complete.
        }
    }

    /** @param array<string, mixed> $payload */
    private function validatePayload(array $payload): void
    {
        if (($payload['type'] ?? null) !== self::EXPORT_TYPE) {
            throw new InvalidArgumentException('The uploaded file is not an Aptoria full database export.');
        }

        if (($payload['product'] ?? null) !== 'Aptoria') {
            throw new InvalidArgumentException('The uploaded export does not belong to Aptoria.');
        }

        if (! isset($payload['database']['schema_hash'], $payload['tables']) || ! is_array($payload['tables'])) {
            throw new InvalidArgumentException('The uploaded export is missing required database metadata.');
        }

        $currentTables = $this->tableNames();
        $currentHash = $this->schemaHash($currentTables);

        if (! hash_equals($currentHash, (string) $payload['database']['schema_hash'])) {
            throw new InvalidArgumentException('The uploaded export schema does not match this Aptoria installation. Use an export from the same schema/version.');
        }

        foreach ($currentTables as $table) {
            if (! isset($payload['tables'][$table]) || ! is_array($payload['tables'][$table])) {
                throw new InvalidArgumentException("The uploaded export is missing the {$table} table.");
            }

            $payloadColumns = $payload['tables'][$table]['columns'] ?? null;
            $currentColumns = Schema::getColumnListing($table);

            if (! is_array($payloadColumns) || array_values($payloadColumns) !== array_values($currentColumns)) {
                throw new InvalidArgumentException("The uploaded export column list does not match the {$table} table.");
            }
        }
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_resource($value)) {
                $row[$key] = stream_get_contents($value) ?: null;
            }
        }

        return $row;
    }
}
