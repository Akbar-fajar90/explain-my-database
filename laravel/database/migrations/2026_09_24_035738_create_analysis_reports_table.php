<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_schema_id')->constrained('uploaded_schemas')->onDelete('cascade');
            $table->text('erd_mermaid');
            $table->json('relationship_explanation');
            $table->json('potential_problems');
            $table->json('index_recommendations');
            $table->json('normalization_analysis');
            $table->text('migration_documentation');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_reports');
    }
};
