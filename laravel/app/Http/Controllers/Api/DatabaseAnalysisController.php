<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UploadedSchema;
use App\Models\AnalysisReport;
use App\Jobs\AnalyzeDatabaseJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DatabaseAnalysisController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'sql_file' => 'nullable|file|mimes:sql,txt',
            'raw_sql' => 'nullable|string',
        ]);

        $rawSql = '';
        $originalFilename = 'raw_input.sql';

        if ($request->hasFile('sql_file')) {
            $file = $request->file('sql_file');
            $originalFilename = $file->getClientOriginalName();
            $rawSql = file_get_contents($file->getRealPath());
        } elseif ($request->filled('raw_sql')) {
            $rawSql = $request->input('raw_sql');
        } else {
            return response()->json(['error' => 'Harap sediakan file SQL atau teks raw_sql.'], 422);
        }

        $schema = UploadedSchema::create([
            'filename' => uniqid() . '_' . $originalFilename,
            'original_filename' => $originalFilename,
            'raw_sql' => $rawSql,
            'status' => 'pending',
        ]);

        AnalyzeDatabaseJob::dispatch($schema->id);

        return response()->json([
            'message' => 'Schema berhasil diupload dan antrian analisis dimulai.',
            'schema_id' => $schema->id,
            'status' => $schema->status,
        ], 202);
    }

    public function erd(int $id)
    {
        $report = AnalysisReport::where('uploaded_schema_id', $id)->first();

        if (!$report) {
            return response()->json(['error' => 'ERD belum tersedia atau skema sedang diproses.'], 404);
        }

        return response()->json([
            'schema_id' => $id,
            'erd_mermaid' => $report->erd_mermaid,
        ]);
    }

    public function report(int $id)
    {
        $cacheKey = "analysis_report_{$id}";

        $data = Cache::remember($cacheKey, 3600, function () use ($id) {
            $schema = UploadedSchema::with('analysisReport')->find($id);
            if (!$schema) {
                return null;
            }
            return [
                'schema' => [
                    'id' => $schema->id,
                    'filename' => $schema->original_filename,
                    'status' => $schema->status,
                    'parsed_structure' => $schema->parsed_structure,
                ],
                'report' => $schema->analysisReport,
            ];
        });

        if (!$data) {
            return response()->json(['error' => 'Laporan tidak ditemukan.'], 404);
        }

        return response()->json($data);
    }
}
