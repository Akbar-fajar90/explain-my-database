<?php

namespace App\Services;

class SchemaParserService
{
    /**
     * Parse SQL dump or CREATE TABLE statements into a structured array.
     */
    public function parse(string $sql): array
    {
        $tables = [];

        // Normalize newlines and strip comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Split by CREATE TABLE
        $pattern = '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?([`"\[]?\w+[`"\]]?)\s*\((.*?)\)\s*(?:;|\z)/is';
        preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $tableName = trim($match[1], '`"[]');
            $body = $match[2];

            $columns = [];
            $primaryKeys = [];
            $foreignKeys = [];
            $indexes = [];

            // Split body by lines, respecting parentheses if needed (simplified for common DDL)
            $lines = array_map('trim', explode(',', $body));

            foreach ($lines as $line) {
                if (empty($line)) continue;

                // Check for PRIMARY KEY constraint inline or standalone
                if (stripos($line, 'PRIMARY KEY') === 0) {
                    preg_match('/PRIMARY\s+KEY\s*\((.*?)\)/i', $line, $pkMatch);
                    if (isset($pkMatch[1])) {
                        $pks = array_map(fn($col) => trim($col, '`"[] '), explode(',', $pkMatch[1]));
                        $primaryKeys = array_merge($primaryKeys, $pks);
                    }
                    continue;
                }

                // Check for FOREIGN KEY constraint
                if (stripos($line, 'FOREIGN KEY') === 0 || stripos($line, 'CONSTRAINT') !== false && stripos($line, 'FOREIGN KEY') !== false) {
                    preg_match('/FOREIGN\s+KEY\s*\((.*?)\)\s*REFERENCES\s+([`"\[]?\w+[`"\]]?)\s*\((.*?)\)/i', $line, $fkMatch);
                    if (isset($fkMatch[1], $fkMatch[2], $fkMatch[3])) {
                        $foreignKeys[] = [
                            'column' => trim($fkMatch[1], '`"[] '),
                            'references_table' => trim($fkMatch[2], '`"[] '),
                            'references_column' => trim($fkMatch[3], '`"[] '),
                        ];
                    }
                    continue;
                }

                // Check for INDEX / KEY
                if (stripos($line, 'INDEX') === 0 || stripos($line, 'KEY') === 0) {
                    preg_match('/(?:INDEX|KEY)\s+([`"\[]?\w+[`"\]]?)\s*\((.*?)\)/i', $line, $idxMatch);
                    if (isset($idxMatch[2])) {
                        $indexes[] = [
                            'name' => isset($idxMatch[1]) ? trim($idxMatch[1], '`"[] ') : null,
                            'columns' => array_map(fn($col) => trim($col, '`"[] '), explode(',', $idxMatch[2])),
                        ];
                    }
                    continue;
                }

                // Regular Column definition
                $colParts = preg_split('/\s+/', $line, 3);
                if (count($colParts) >= 2) {
                    $colName = trim($colParts[0], '`"[]');
                    $dataType = strtoupper($colParts[1]);
                    $rest = $colParts[2] ?? '';

                    $isNullable = true;
                    if (stripos($rest, 'NOT NULL') !== false) {
                        $isNullable = false;
                    }
                    if (stripos($rest, 'PRIMARY KEY') !== false) {
                        $primaryKeys[] = $colName;
                        $isNullable = false;
                    }

                    $columns[$colName] = [
                        'type' => $dataType,
                        'nullable' => $isNullable,
                        'extra' => $rest,
                    ];
                }
            }

            $tables[$tableName] = [
                'name' => $tableName,
                'columns' => $columns,
                'primary_keys' => array_unique($primaryKeys),
                'foreign_keys' => $foreignKeys,
                'indexes' => $indexes,
            ];
        }

        return $tables;
    }
}
