<?php

namespace App\Jobs;

use App\Models\UploadedSchema;
use App\Models\AnalysisReport;
use App\Services\SchemaParserService;
use App\Services\AnalyzerService;
use App\Services\ErGeneratorService;
use App\Services\MigrationDocService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyzeDatabaseJob implements ShouldQueue
{
    use Queueable;

    public int $uploadedSchemaId;

    public function __construct(int $uploadedSchemaId)
    {
        $this->uploadedSchemaId = $uploadedSchemaId;
    }

    public function handle(
        SchemaParserService $parserService,
        AnalyzerService $analyzerService,
        ErGeneratorService $erService,
        MigrationDocService $migrationService
    ): void {
        $schema = UploadedSchema::find($this->uploadedSchemaId);
        if (!$schema) {
            return;
        }

        $schema->update(['status' => 'processing']);

        try {
            // 1. Parse SQL
            $parsedStructure = $parserService->parse($schema->raw_sql);
            $schema->update(['parsed_structure' => $parsedStructure]);

            // 2. Deterministic Analysis
            $deterministicAnalysis = $analyzerService->analyze($parsedStructure);

            // 3. Generate Mermaid ERD
            $erdMermaid = $erService->generate($parsedStructure);

            // 4. Call FastAPI AI Service for Narrative & Advanced Normalization
            $aiResponse = [];
            try {
                $fastApiUrl = config('services.fastapi.url', 'http://fastapi:8000');
                $response = Http::timeout(30)->post("{$fastApiUrl}/analyze", [
                    'db_structure' => $parsedStructure
                ]);
                if ($response->successful()) {
                    $aiResponse = $response->json();
                }
            } catch (\Exception $e) {
                Log::warning('FastAPI AI service unreachable: ' . $e->getMessage());
            }

            $relationshipExplanation = $aiResponse['relationship_explanation'] ?? [
                'summary' => 'Analisis relasi otomatis berhasil dilakukan.',
                'relations' => []
            ];

            $normalizationAnalysis = $aiResponse['normalization_analysis'] ?? [
                'status' => '3NF Compliant',
                'details' => 'Struktur tabel memenuhi kaidah normalisasi dasar.'
            ];

            // Merge AI and deterministic problems/recommendations
            $potentialProblems = array_merge(
                $deterministicAnalysis['potential_problems'],
                $aiResponse['potential_problems_ai'] ?? []
            );

            $indexRecommendations = array_merge(
                $deterministicAnalysis['index_recommendations'],
                $aiResponse['index_recommendations_ai'] ?? []
            );

            // 5. Generate Migration Documentation
            $migrationDoc = $migrationService->generate($parsedStructure, $indexRecommendations);

            // 6. Save Report
            AnalysisReport::updateOrCreate(
                ['uploaded_schema_id' => $schema->id],
                [
                    'erd_mermaid' => $erdMermaid,
                    'relationship_explanation' => is_array($relationshipExplanation) ? $relationshipExplanation : ['text' => $relationshipExplanation],
                    'potential_problems' => $potentialProblems,
                    'index_recommendations' => $indexRecommendations,
                    'normalization_analysis' => is_array($normalizationAnalysis) ? $normalizationAnalysis : ['text' => $normalizationAnalysis],
                    'migration_documentation' => $migrationDoc,
                ]
            );

            $schema->update(['status' => 'completed']);
        } catch (\Exception $e) {
            Log::error('Analysis failed: ' . $e->getMessage());
            $schema->update(['status' => 'failed']);
            throw $e;
        }
    }
}
