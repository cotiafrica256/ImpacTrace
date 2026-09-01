<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectForm extends Model
{
    protected $fillable = [
        'project_id', 'title', 'slug', 'version', 'form_schema',
        'requires_consent', 'requires_signature', 'requires_id_capture',
        'requires_photo', 'allows_voice_note', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'form_schema' => 'array',
            'requires_consent' => 'boolean',
            'requires_signature' => 'boolean',
            'requires_id_capture' => 'boolean',
            'requires_photo' => 'boolean',
            'allows_voice_note' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }
}
