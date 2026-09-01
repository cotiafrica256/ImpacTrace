<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $fillable = [
        'submission_code', 'project_id', 'project_form_id', 'respondent_id',
        'collected_by', 'activity_date', 'village', 'parish', 'sub_county', 'district',
        'gps_lat', 'gps_lng', 'answers', 'vulnerability_score', 'vulnerability_class',
        'status', 'review_notes', 'reviewed_by', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'activity_date' => 'date',
            'synced_at' => 'datetime',
            'gps_lat' => 'float',
            'gps_lng' => 'float',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function form()
    {
        return $this->belongsTo(ProjectForm::class, 'project_form_id');
    }

    public function respondent()
    {
        return $this->belongsTo(Respondent::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function consent()
    {
        return $this->hasOne(Consent::class);
    }

    public function media()
    {
        return $this->hasMany(SubmissionMedia::class);
    }
}
