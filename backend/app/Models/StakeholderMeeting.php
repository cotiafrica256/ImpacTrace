<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StakeholderMeeting extends Model {
 protected $fillable=['organization_id','project_id','title','meeting_type','starts_at','ends_at','location','agenda','minutes','action_points'];
 protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime','action_points'=>'array'];
}