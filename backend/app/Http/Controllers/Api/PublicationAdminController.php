<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Publication,AccessPackage};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class PublicationAdminController extends Controller {
 public function index(){return Publication::with('packages')->latest()->paginate(30);}
 public function store(Request $r){
    $d=$r->validate(['report_id'=>'nullable|integer|exists:reports,id','title'=>'required|string|max:255','summary'=>'required|string','content'=>'nullable|string','category'=>'nullable|string|max:100','cover_image'=>'nullable|string','status'=>'in:draft,review']);
    if($r->user()->role!=='super_admin' && !empty($d['report_id'])) abort_unless(\App\Models\Report::whereKey($d['report_id'])->whereHas('project',fn($q)=>$q->where('organization_id',$r->user()->organization_id))->exists(),404);
  $d['slug']=Str::slug($d['title']).'-'.Str::lower(Str::random(5));$d['published_by']=null;$d['published_at']=null;
  return response()->json(Publication::create($d),201);
 }
 public function update(Request $r,Publication $publication){
  $d=$r->validate(['title'=>'sometimes|string|max:255','summary'=>'sometimes|string','content'=>'nullable|string','category'=>'nullable|string|max:100','status'=>'sometimes|in:draft,review,approved']);
  $publication->update($d);return $publication->fresh('packages');
 }
 public function publish(Request $r,Publication $publication){
  abort_unless($r->user()->role==='super_admin',403,'Only the Super Admin can publish.');
  $publication->update(['status'=>'published','published_by'=>$r->user()->id,'published_at'=>now()]);
  return $publication->fresh('packages');
 }
 public function package(Request $r,Publication $publication){
  if($r->user()->role!=='super_admin'){
   abort_unless($publication->report && $publication->report->project->organization_id===$r->user()->organization_id,404);
  }
  $d=$r->validate(['name'=>'required|string','type'=>'required|in:reading,download','duration_minutes'=>'nullable|integer|min:1','amount_ugx'=>'required|integer|min:0','momo_amount_ugx'=>'nullable|integer|min:0','allows_download'=>'boolean','is_active'=>'boolean']);
  return $publication->packages()->create($d);
 }
}