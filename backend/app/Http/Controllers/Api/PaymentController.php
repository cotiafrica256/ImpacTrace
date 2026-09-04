<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{AccessPackage,Payment,ReadingAccess};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class PaymentController extends Controller {
 private function activateAccess(Payment $payment): void {
  $pkg=$payment->package;
  ReadingAccess::updateOrCreate(['payment_id'=>$payment->id],['public_user_id'=>$payment->public_user_id,'publication_id'=>$pkg->publication_id,'starts_at'=>now(),'expires_at'=>$pkg->type==='reading'?now()->addMinutes((int)$pkg->duration_minutes):null,'can_download'=>$pkg->allows_download]);
 }
 public function initiate(Request $r, AccessPackage $package){
  $r->validate(['phone'=>'required|string|max:30','method'=>'required|in:gateway,momo_manual']);
  $amount=$r->input('method')==='momo_manual'&&$package->momo_amount_ugx ? $package->momo_amount_ugx : $package->amount_ugx;
  $p=Payment::create(['public_user_id'=>$r->user()->id,'access_package_id'=>$package->id,'method'=>$r->method,'provider'=>config('services.momo.provider'),'phone'=>$r->phone,'amount_ugx'=>$amount,'status'=>'pending']);
  if($r->input('method')==='momo_manual') return response()->json(['payment'=>$p,'merchant_code'=>config('services.momo.merchant_code'),'instructions'=>'Pay the displayed amount using the merchant code, then submit the last 5 characters of your transaction reference.']);
  return response()->json(['payment'=>$p,'message'=>'Payment request created. Connect the configured mobile-money provider to complete the push request.']);
 }
 public function submitReference(Request $r, Payment $payment){
  abort_unless($payment->public_user_id===$r->user()->id && $payment->method==='momo_manual',404);
  $d=$r->validate(['last5_reference'=>'required|string|min:5|max:5']);
  $payment->update(['last5_reference'=>strtoupper($d['last5_reference'])]);
  return ['payment'=>$payment,'status'=>'pending_verification'];
 }
 public function verify(Request $r, Payment $payment){
  abort_unless($r->user()->role==='super_admin',403);
  $d=$r->validate(['status'=>'required|in:paid,rejected','provider_reference'=>'nullable|string|max:120']);
  DB::transaction(function()use($payment,$d,$r){
   $payment->update(['status'=>$d['status'],'provider_reference'=>$d['provider_reference']??$payment->provider_reference,'verified_by'=>$r->user()->id,'paid_at'=>$d['status']==='paid'?now():null]);
   if($d['status']==='paid'){
    $pkg=$payment->package;
    $this->activateAccess($payment);
   }
  });
  return ['payment'=>$payment->fresh(),'message'=>$d['status']==='paid'?'Access activated.':'Payment rejected.'];
 }
 public function pending(Request $r){
  abort_unless($r->user()->role==='super_admin',403);
  return Payment::with(['user','package.publication'])->where('status','pending')->latest()->paginate(30);
 }
 public function access(Request $r, string $slug){
  $pub=\App\Models\Publication::where('slug',$slug)->where('status','published')->firstOrFail();
  $access=ReadingAccess::where('public_user_id',$r->user()->id)->where('publication_id',$pub->id)->latest()->first();
  if(!$access||!$access->active()) return response()->json(['has_access'=>false,'message'=>'Purchase access to continue.'],403);
  $access->update(['last_seen_at'=>now()]);
  return ['has_access'=>true,'publication'=>$pub,'access'=>$access];
 }
 public function webhook(Request $r){
  // Provider adapters should verify their signature before calling this endpoint.
  $secret=config('services.momo.webhook_secret');
  if($secret && !hash_equals($secret,(string)$r->header('X-Momo-Secret'))) abort(401);
  $ref=$r->input('provider_reference'); $status=$r->input('status');
  $p=Payment::where('provider_reference',$ref)->first();
  if(!$p) return ['received'=>true];
    if($status==='paid' && $p->status!=='paid') {$p->update(['status'=>'paid','provider_reference'=>$ref,'paid_at'=>now()]);$this->activateAccess($p->fresh('package'));}
  return ['received'=>true];
 }
 public function download(Request $r,string $slug){
    $pub=\App\Models\Publication::where('slug',$slug)->where('status','published')->firstOrFail();
    $access=ReadingAccess::where('public_user_id',$r->user()->id)->where('publication_id',$pub->id)->latest()->first();
    abort_unless($access && $access->active() && $access->can_download,403,'A download package is required.');
    return response()->streamDownload(fn()=>print($pub->content ?? ''),\Illuminate\Support\Str::slug($pub->title).'.txt',['Content-Type'=>'text/plain; charset=UTF-8']);
 }
}