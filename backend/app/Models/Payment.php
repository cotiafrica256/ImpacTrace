<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
 protected $fillable=['public_user_id','access_package_id','method','provider','phone','amount_ugx','provider_reference','last5_reference','status','provider_payload','verified_by','paid_at'];
 protected $casts=['provider_payload'=>'array','paid_at'=>'datetime'];
 public function user(){return $this->belongsTo(PublicUser::class,'public_user_id');}
 public function package(){return $this->belongsTo(AccessPackage::class,'access_package_id');}
}