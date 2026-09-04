<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AccessPackage extends Model {
 protected $fillable=['publication_id','name','type','duration_minutes','amount_ugx','momo_amount_ugx','allows_download','is_active'];
 protected function casts(): array{return ['allows_download'=>'boolean','is_active'=>'boolean'];}
 public function publication(){return $this->belongsTo(Publication::class);}
}