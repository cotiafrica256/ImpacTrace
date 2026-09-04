<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DevelopmentPlan extends Model {
 protected $fillable=['geographic_unit_id','title','year_from','year_to','content','document_path','status','created_by'];
 public function geography(){return $this->belongsTo(GeographicUnit::class,'geographic_unit_id');}
}