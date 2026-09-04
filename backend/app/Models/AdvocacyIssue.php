<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdvocacyIssue extends Model {
 protected $fillable=['organization_id','project_id','geographic_unit_id','title','problem','evidence','community_voices','recommendations','target_decision_maker','status'];
 public function geography(){return $this->belongsTo(GeographicUnit::class,'geographic_unit_id');}
}