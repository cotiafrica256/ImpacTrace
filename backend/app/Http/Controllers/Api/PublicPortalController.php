<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Publication;
use Illuminate\Http\Request;
class PublicPortalController extends Controller {
 public function index(Request $request){
  $q=Publication::query()->where('status','published')->with(['packages'=>fn($x)=>$x->where('is_active',true)]);
  if($request->filled('q')){$term=$request->string('q');$q->where(fn($x)=>$x->where('title','like',"%{$term}%")->orWhere('summary','like',"%{$term}%")->orWhere('content','like',"%{$term}%"));}
  if($request->filled('category')) $q->where('category',$request->string('category'));
  return $q->orderByDesc('is_featured')->orderByDesc('published_at')->paginate(12);
 }
 public function show(string $slug){
  return Publication::where('slug',$slug)->where('status','published')->with(['packages'=>fn($x)=>$x->where('is_active',true)])->firstOrFail()->makeHidden(['content']);
 }
}