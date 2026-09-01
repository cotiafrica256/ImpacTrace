<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    protected $fillable = [
        'form_submission_id', 'consent_given', 'permission_for_learning_advocacy',
        'permission_for_photos', 'consent_statement_version', 'id_document_path',
        'signature_path', 'respondent_photo_path', 'voice_note_path', 'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'consent_given' => 'boolean',
            'permission_for_learning_advocacy' => 'boolean',
            'permission_for_photos' => 'boolean',
            'captured_at' => 'datetime',
        ];
    }

    public function submission()
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }
}
