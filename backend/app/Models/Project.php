<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'organization_id', 'code', 'name', 'theme', 'description', 'donor_funder',
        'start_date', 'end_date', 'districts', 'created_by', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'districts' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function forms()
    {
        return $this->hasMany(ProjectForm::class);
    }

    public function officers()
    {
        return $this->belongsToMany(User::class);
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
