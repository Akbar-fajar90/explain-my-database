<?php

namespace App\Services;

class MigrationDocService
{
    /**
     * Generate Laravel migration documentation and code based on problems & structure.
     */
    public function generate(array $tables, array $indexRecommendations): string
    {
        $doc = "# Dokumentasi Migrasi dan Perbaikan Skema\n\n";
        $doc .= "Dokumentasi ini dihasilkan secara otomatis untuk meremediasi masalah performa dan integritas data.\n\n";

        $doc .= "## Daftar Perintah DDL Siap Eksekusi\n\n";
        if (empty($indexRecommendations)) {
            $doc .= "Tidak ada rekomendasi index tambahan. Skema sudah optimal.\n";
        } else {
            foreach ($indexRecommendations as $rec) {
                $doc .= "- **Tabel**: `{$rec['table']}`\n";
                $doc .= "  - **Alasan**: {$rec['reason']}\n";
                $doc .= "  - **DDL**:\n";
                $doc .= "    ```sql\n    {$rec['ddl']}\n    ```\n\n";
            }
        }

        $doc .= "## Contoh Laravel Migration (Up / Down)\n\n";
        $doc .= "```php\n";
        $doc .= "use Illuminate\Database\Migrations\Migration;\n";
        $doc .= "use Illuminate\Database\Schema\Blueprint;\n";
        $doc .= "use Illuminate\Support\Facades\Schema;\n\n";
        $doc .= "return new class extends Migration {\n";
        $doc .= "    public function up(): void {\n";
        foreach ($indexRecommendations as $rec) {
            $tableName = $rec['table'];
            $colName = $rec['column'];
            $doc .= "        Schema::table('{$tableName}', function (Blueprint \$table) {\n";
            $doc .= "            \$table->index('{$colName}', 'idx_{$tableName}_{$colName}');\n";
            $doc .= "        });\n";
        }
        $doc .= "    }\n\n";
        $doc .= "    public function down(): void {\n";
        foreach ($indexRecommendations as $rec) {
            $tableName = $rec['table'];
            $colName = $rec['column'];
            $doc .= "        Schema::table('{$tableName}', function (Blueprint \$table) {\n";
            $doc .= "            \$table->dropIndex('idx_{$tableName}_{$colName}');\n";
            $doc .= "        });\n";
        }
        $doc .= "    }\n";
        $doc .= "};\n";
        $doc .= "```\n";

        return $doc;
    }
}
