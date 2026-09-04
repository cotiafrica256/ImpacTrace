<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PresentationDeck extends Model {
 protected $fillable=['organization_id','report_id','title','slides','status','created_by'];
 protected $casts=['slides'=>'array'];
}