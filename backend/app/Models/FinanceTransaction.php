<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FinanceTransaction extends Model {
 protected $fillable=['organization_id','finance_import_id','transaction_date','type','account','category','project_code','reference','description','amount','currency'];
 protected $casts=['transaction_date'=>'date','amount'=>'decimal:2'];
}