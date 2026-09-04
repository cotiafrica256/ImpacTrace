<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Models\FinanceImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class FinanceController extends Controller {
 private function organizationId(Request $r): int {
    $user=$r->user();
    if($user->role!=='super_admin') return (int)$user->organization_id;
    return (int)($r->header('X-Organization-Id') ?: $r->input('organization_id'));
 }
 public function summary(Request $r){
    $org=$this->organizationId($r); abort_if(!$org,422,'Select an organisation first.'); $q=FinanceTransaction::where('organization_id',$org);
  $income=(clone $q)->where('type','income')->sum('amount');$expense=(clone $q)->where('type','expense')->sum('amount');
  $byCategory=(clone $q)->where('type','expense')->select('category',DB::raw('SUM(amount) total'))->groupBy('category')->orderByDesc('total')->get();
  $monthly=(clone $q)->select(DB::raw("DATE_FORMAT(transaction_date,'%Y-%m') month"),DB::raw("SUM(CASE WHEN type='income' THEN amount ELSE 0 END) income"),DB::raw("SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) expense"))->groupBy('month')->orderBy('month')->get();
  return ['income'=>(float)$income,'expense'=>(float)$expense,'balance'=>(float)($income-$expense),'by_category'=>$byCategory,'monthly'=>$monthly];
 }
 public function import(Request $r){
    $org=$this->organizationId($r);abort_if(!$org,422,'Select an organisation first.');
    $d=$r->validate(['file'=>'required|file|mimes:csv,txt|max:10240']);
    $imp=FinanceImport::create(['organization_id'=>$org,'source'=>'quickbooks','uploaded_by'=>$r->user()->id,'status'=>'pending']);
  $handle=fopen($d['file']->getRealPath(),'r');$header=fgetcsv($handle);$count=0;
  while(($row=fgetcsv($handle))!==false){$m=array_combine($header,$row);if(!$m)continue;$amount=(float)($m['Amount']??$m['amount']??0);$type=strtolower($m['Type']??$m['type']??'expense');if(!in_array($type,['income','expense','transfer']))$type='expense';FinanceTransaction::create(['organization_id'=>$org,'finance_import_id'=>$imp->id,'transaction_date'=>$m['Date']??$m['date']??now()->toDateString(),'type'=>$type,'account'=>$m['Account']??$m['account']??null,'category'=>$m['Category']??$m['category']??null,'project_code'=>$m['Project']??$m['project']??null,'reference'=>$m['Reference']??$m['reference']??null,'description'=>$m['Description']??$m['description']??null,'amount'=>$amount,'currency'=>$m['Currency']??'UGX']);$count++;}
  fclose($handle);$imp->update(['rows_imported'=>$count,'status'=>'processed']);return ['import'=>$imp];
 }
}