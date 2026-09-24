<?php

namespace App\Services;

class ErGeneratorService
{
    /**
     * Generate Mermaid.js erDiagram from parsed table structure.
     */
    public function generate(array $tables): string
    {
        $mermaid = "erDiagram\n";

        foreach ($tables as $tableName => $table) {
            $mermaid .= "    {$tableName} {\n";
            foreach ($table['columns'] as $colName => $col) {
                $type = strtolower($col['type']);
                $pkMark = in_array($colName, $table['primary_keys']) ? " PK" : "";
                $fkMark = "";
                foreach ($table['foreign_keys'] as $fk) {
                    if ($fk['column'] === $colName) {
                        $fkMark = " FK";
                    }
                }
                $mermaid .= "        {$type} {$colName}{$pkMark}{$fkMark}\n";
            }
            $mermaid .= "    }\n";
        }

        // Add relationships
        foreach ($tables as $tableName => $table) {
            foreach ($table['foreign_keys'] as $fk) {
                $refTable = $fk['references_table'];
                // Cardinality: parent ||--o{ child
                $mermaid .= "    {$refTable} ||--o{ {$tableName} : \"memiliki\"\n";
            }
        }

        return $mermaid;
    }
}
