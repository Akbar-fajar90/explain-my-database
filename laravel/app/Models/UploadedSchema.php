<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadedSchema extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'original_filename',
        'raw_sql',
        'parsed_structure',
        'status',
    ];

    protected $casts = [
        'parsed_structure' => 'array',
    ];

    public function analysisReport()
    {
        return $this->hasOne(AnalysisReport::class);
    }
}
