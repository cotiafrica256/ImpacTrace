<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GeographicUnit extends Model {
 protected $fillable=['parent_id','type','name','code'];
 public function parent(){return $this->belongsTo(self::class,'parent_id');}
 public function children(){return $this->hasMany(self::class,'parent_id');}
 public function plans(){return $this->hasMany(DevelopmentPlan::class);}
}