<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ParCycle extends Model {
 protected $fillable=['project_id','cycle_number','stage','title','problem','activities','observations','reflection','decisions','created_by'];
}