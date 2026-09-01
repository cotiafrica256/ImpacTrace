<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    protected function effectiveOrgId(Request $request): ?int
    {
        $user = $request->user();
        $selected = $request->header('X-Organization-Id') ?? $request->input('organization_id');

        if ($user->role === 'super_admin' && $selected) {
            return (int) $selected;
        }

        return $user->organization_id;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $effectiveOrgId = $this->effectiveOrgId($request);

        $query = Project::with([
            'organization',
            'forms' => fn ($q) => $q->where('is_active', true),
            'officers',
        ]);

        if ($user->role === 'super_admin') {
            if ($effectiveOrgId) {
                $query->where('organization_id', $effectiveOrgId);
            }

            return $query->orderByDesc('created_at')->get();
        }

        $query->where('organization_id', $effectiveOrgId);

        // Field/Project Officers only see projects they're assigned to
        // (within their own organisation).
        if (in_array($user->role, ['fo', 'po'], true)) {
            $query->whereHas('officers', fn ($q) => $q->where('users.id', $user->id));
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'theme' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'donor_funder' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'districts' => 'nullable|array',
            'officer_ids' => 'nullable|array',
        ]);

        $orgId = $this->effectiveOrgId($request);
        abort_if(! $orgId, 422, 'Select an organisation before creating a project.');

        $exists = Project::where('organization_id', $orgId)->where('code', $data['code'])->exists();
        abort_if($exists, 422, 'A project with this code already exists in your organisation.');

        $project = Project::create([
            ...$data,
            'organization_id' => $orgId,
            'created_by' => $request->user()->id,
        ]);

        if (! empty($data['officer_ids'])) {
            // Only ever attach officers who belong to this same organisation.
            $validOfficerIds = $project->organization->users()->whereIn('id', $data['officer_ids'])->pluck('id');
            $project->officers()->sync($validOfficerIds);
        }

        return response()->json($project->load('officers'), 201);
    }

    public function show(Request $request, Project $project)
    {
        $effectiveOrgId = $this->effectiveOrgId($request);

        if ($request->user()->role === 'super_admin') {
            if ($effectiveOrgId && $project->organization_id !== $effectiveOrgId) {
                abort(404);
            }

            return $project->load(['organization', 'forms', 'officers']);
        }

        abort_unless($project->organization_id === $effectiveOrgId, 404);

        return $project->load(['organization', 'forms', 'officers']);
    }

    public function update(Request $request, Project $project)
    {
        $effectiveOrgId = $this->effectiveOrgId($request);
        if ($request->user()->role === 'super_admin') {
            abort_unless($project->organization_id === $effectiveOrgId, 404);
        } else {
            abort_unless($project->organization_id === $request->user()->organization_id, 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'theme' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'donor_funder' => 'nullable|string|max:255',
            'end_date' => 'nullable|date',
            'districts' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'officer_ids' => 'nullable|array',
        ]);

        $project->update($data);

        if (isset($data['officer_ids'])) {
            $validOfficerIds = $project->organization->users()->whereIn('id', $data['officer_ids'])->pluck('id');
            $project->officers()->sync($validOfficerIds);
        }

        return response()->json($project->load('officers'));
    }
}
