<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Respondent extends Model
{
    protected $fillable = [
        'organization_id', 'respondent_code', 'full_name', 'sex', 'age', 'id_type',
        'id_number_hash', 'id_number_last4', 'fuzzy_key',
        'village', 'parish', 'sub_county', 'district', 'phone',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }
}
