<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundraisingCampaign extends Model
{
    protected $fillable = ['organization_id', 'title', 'slug', 'summary', 'message', 'photo_url', 'target_amount_ugx', 'show_progress', 'status', 'created_by', 'published_at'];

    protected $casts = ['show_progress' => 'boolean', 'published_at' => 'datetime'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function donations()
    {
        return $this->hasMany(FundraisingDonation::class);
    }
}
