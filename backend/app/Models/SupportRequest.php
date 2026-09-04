<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportRequest extends Model
{
    protected $fillable = ['public_user_id', 'name', 'email', 'subject', 'message', 'status', 'response', 'responded_by', 'responded_at'];
    protected $casts = ['responded_at' => 'datetime'];
    public function user() { return $this->belongsTo(PublicUser::class, 'public_user_id'); }
    public function responder() { return $this->belongsTo(User::class, 'responded_by'); }
}
