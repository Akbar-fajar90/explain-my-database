<?php

namespace App\Services;

class AnalyzerService
{
    /**
     * Analyze parsed database structure for potential problems and missing indexes.
     */
    public function analyze(array $tables): array
    {
        $problems = [];
        $indexRecommendations = [];

        foreach ($tables as $tableName => $table) {
            $columns = $table['columns'] ?? [];
            $foreignKeys = $table['foreign_keys'] ?? [];
            $indexes = $table['indexes'] ?? [];

            // Extract indexed columns for fast lookup
            $indexedColumns = [];
            foreach ($indexes as $idx) {
                foreach ($idx['columns'] as $c) {
                    $indexedColumns[] = $c;
                }
            }
            foreach ($table['primary_keys'] as $pk) {
                $indexedColumns[] = $pk;
            }

            // 1. Check missing index on Foreign Keys
            foreach ($foreignKeys as $fk) {
                $fkCol = $fk['column'];
                if (!in_array($fkCol, $indexedColumns)) {
                    $problems[] = [
                        'table' => $tableName,
                        'column' => $fkCol,
                        'issue' => "{$tableName}.{$fkCol} adalah foreign key tapi tidak memiliki index.",
                        'severity' => 'high',
                    ];

                    $indexRecommendations[] = [
                        'table' => $tableName,
                        'column' => $fkCol,
                        'reason' => 'Mencegah table scan saat melakukan join dengan tabel referensi.',
                        'ddl' => "CREATE INDEX idx_{$tableName}_{$fkCol} ON {$tableName}({$fkCol});",
                    ];
                }
            }

            // 2. Check excessive nullable columns or suspicious data types
            foreach ($columns as $colName => $col) {
                if ($col['nullable'] && !in_array($colName, $table['primary_keys'])) {
                    // Just an informational check or potential problem if too many
                }

                // Check text fields without length or generic issues
                if (stripos($col['type'], 'TEXT') !== false && stripos($colName, '_id') !== false) {
                    $problems[] = [
                        'table' => $tableName,
                        'column' => $colName,
                        'issue' => "{$tableName}.{$colName} menggunakan tipe TEXT untuk kolom relasi ID. Seharusnya BIGINT atau INT.",
                        'severity' => 'critical',
                    ];
                }
            }
        }

        return [
            'potential_problems' => $problems,
            'index_recommendations' => $indexRecommendations,
        ];
    }
}
