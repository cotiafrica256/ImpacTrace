<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Publication extends Model {
 protected $fillable=['report_id','title','slug','summary','content','cover_image','youtube_url','category','status','is_featured','published_by','published_at'];
 protected function casts(): array{return ['is_featured'=>'boolean','published_at'=>'datetime'];}
 public function packages(){return $this->hasMany(AccessPackage::class);}
 public function report(){return $this->belongsTo(Report::class);}
}