<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ReadingAccess extends Model {
 protected $fillable=['public_user_id','publication_id','payment_id','starts_at','expires_at','can_download','device_hash','last_seen_at'];
 protected $casts=['starts_at'=>'datetime','expires_at'=>'datetime','last_seen_at'=>'datetime','can_download'=>'boolean'];
 public function publication(){return $this->belongsTo(Publication::class);}
 public function user(){return $this->belongsTo(PublicUser::class,'public_user_id');}
 public function active(){return $this->expires_at===null || $this->expires_at->isFuture();}
}