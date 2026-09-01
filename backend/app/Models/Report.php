<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'project_id', 'type', 'period_start', 'period_end', 'auto_stats',
        'narrative', 'status', 'prepared_by', 'approved_by', 'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'auto_stats' => 'array',
            'narrative' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
