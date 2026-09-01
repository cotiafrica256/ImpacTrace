<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionMedia extends Model
{
    protected $fillable = ['form_submission_id', 'type', 'path', 'caption'];

    public function submission()
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }
}
