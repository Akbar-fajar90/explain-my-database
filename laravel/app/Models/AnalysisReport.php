<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalysisReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'uploaded_schema_id',
        'erd_mermaid',
        'relationship_explanation',
        'potential_problems',
        'index_recommendations',
        'normalization_analysis',
        'migration_documentation',
    ];

    protected $casts = [
        'relationship_explanation' => 'array',
        'potential_problems' => 'array',
        'index_recommendations' => 'array',
        'normalization_analysis' => 'array',
    ];

    public function uploadedSchema()
    {
        return $this->belongsTo(UploadedSchema::class);
    }
}
