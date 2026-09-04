<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PublicationComment extends Model {
 protected $fillable=['publication_id','public_user_id','geographic_unit_id','comment','status'];
}