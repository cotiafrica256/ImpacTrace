<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectForm;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Lets the ED / M&E Officer design a NEW data-collection instrument for a
 * NEW project (or a new round of an existing one) without any code change —
 * this is what makes the system reusable beyond the MECPA household survey.
 * The `schema` is an array of sections, each with fields
 * (text, number, single_select, multi_select, date, gps, photo, signature...).
 */
class ProjectFormController extends Controller
{
    public function index(Request $request, Project $project)
    {
        if ($request->user()->role !== 'super_admin') {
            abort_unless($project->organization_id === $request->user()->organization_id, 404);
        }

        return $project->forms()->orderByDesc('version')->get();
    }

    public function store(Request $request, Project $project)
    {
        if ($request->user()->role !== 'super_admin') {
            abort_unless($project->organization_id === $request->user()->organization_id, 404);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'form_schema' => 'required|array',
            'requires_consent' => 'boolean',
            'requires_signature' => 'boolean',
            'requires_id_capture' => 'boolean',
            'requires_photo' => 'boolean',
            'allows_voice_note' => 'boolean',
        ]);

        $slug = Str::slug($data['title']);
        $version = ProjectForm::where('project_id', $project->id)->where('slug', $slug)->max('version') + 1;

        $form = ProjectForm::create([
            ...$data,
            'project_id' => $project->id,
            'slug' => $slug,
            'version' => $version,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($form, 201);
    }

    public function show(Request $request, ProjectForm $projectForm)
    {
        if ($request->user()->role !== 'super_admin') {
            abort_unless($projectForm->project->organization_id === $request->user()->organization_id, 404);
        }

        return $projectForm;
    }

    public function update(Request $request, ProjectForm $projectForm)
    {
        if ($request->user()->role !== 'super_admin') {
            abort_unless($projectForm->project->organization_id === $request->user()->organization_id, 404);
        }

        // Forms already used for live data are versioned, not edited in place —
        // editing in place would corrupt historical reports.
        $data = $request->validate([
            'is_active' => 'sometimes|boolean',
        ]);

        $projectForm->update($data);

        return response()->json($projectForm);
    }
}
