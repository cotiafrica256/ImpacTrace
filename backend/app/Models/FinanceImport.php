<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FinanceImport extends Model {
 protected $fillable=['organization_id','source','file_path','rows_imported','status','error_message','uploaded_by'];
}