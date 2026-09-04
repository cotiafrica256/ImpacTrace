<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\ParCycle;
use Illuminate\Http\Request;
class ParController extends Controller {
 private function assertProject(Request $r,int $projectId): void {
  $project=\App\Models\Project::findOrFail($projectId); $user=$r->user();
  if($user->role!=='super_admin') abort_unless($project->organization_id===$user->organization_id,404);
  elseif($r->header('X-Organization-Id')) abort_unless($project->organization_id===(int)$r->header('X-Organization-Id'),404);
 }
 public function index(Request $r,$project){$this->assertProject($r,(int)$project);return ParCycle::where('project_id',$project)->orderBy('cycle_number')->orderBy('id')->get();}
 public function store(Request $r,$project){$this->assertProject($r,(int)$project);$d=$r->validate(['cycle_number'=>'required|integer|min:1','stage'=>'required|in:plan,act,observe,reflect','title'=>'required|string','problem'=>'nullable|string','activities'=>'nullable|string','observations'=>'nullable|string','reflection'=>'nullable|string','decisions'=>'nullable|string']);$d['project_id']=$project;$d['created_by']=$r->user()->id;return ParCycle::create($d);}
 public function update(Request $r,ParCycle $parCycle){$this->assertProject($r,$parCycle->project_id);$d=$r->validate(['stage'=>'sometimes|in:plan,act,observe,reflect','title'=>'sometimes|string','problem'=>'nullable|string','activities'=>'nullable|string','observations'=>'nullable|string','reflection'=>'nullable|string','decisions'=>'nullable|string']);$parCycle->update($d);return $parCycle;}
}