<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AdvocacyIssue, DevelopmentPlan, GeographicUnit, StakeholderMeeting};
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    private function orgId(Request $request): int
    {
        $user = $request->user();
        $id = $user->role === 'super_admin'
            ? ($request->header('X-Organization-Id') ?: $request->input('organization_id'))
            : $user->organization_id;
        abort_if(! $id, 422, 'Select an organisation first.');
        return (int) $id;
    }

    public function geography()
    {
        return GeographicUnit::with('children')->whereNull('parent_id')->withCount('plans')->orderBy('type')->orderBy('name')->get();
    }

    public function storeGeography(Request $request)
    {
        return GeographicUnit::create($request->validate([
            'parent_id' => 'nullable|exists:geographic_units,id', 'type' => 'required|in:district,county,sub_county,parish,village',
            'name' => 'required|string|max:160', 'code' => 'nullable|string|max:40',
        ]));
    }

    public function plans(Request $request)
    {
        return DevelopmentPlan::with('geography')->latest()->paginate(30);
    }

    public function storePlan(Request $request)
    {
        return DevelopmentPlan::create([...$request->validate([
            'geographic_unit_id' => 'required|exists:geographic_units,id', 'title' => 'required|string|max:255',
            'year_from' => 'nullable|integer|min:1900|max:2200', 'year_to' => 'nullable|integer|min:1900|max:2200',
            'content' => 'nullable|string', 'document_path' => 'nullable|string|max:500', 'status' => 'in:draft,published',
        ]), 'created_by' => $request->user()->id]);
    }

    public function meetings(Request $request)
    {
        return StakeholderMeeting::where('organization_id', $this->orgId($request))->latest('starts_at')->paginate(30);
    }

    public function storeMeeting(Request $request)
    {
        return StakeholderMeeting::create([...$request->validate([
            'project_id' => 'nullable|exists:projects,id', 'title' => 'required|string|max:255', 'meeting_type' => 'nullable|string|max:80',
            'starts_at' => 'required|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'location' => 'nullable|string|max:255',
            'agenda' => 'nullable|string', 'minutes' => 'nullable|string', 'action_points' => 'nullable|array',
        ]), 'organization_id' => $this->orgId($request)]);
    }

    public function issues(Request $request)
    {
        return AdvocacyIssue::where('organization_id', $this->orgId($request))->latest()->paginate(30);
    }

    public function storeIssue(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'nullable|exists:projects,id', 'geographic_unit_id' => 'nullable|exists:geographic_units,id',
            'title' => 'required|string|max:255', 'problem' => 'nullable|string', 'evidence' => 'nullable|string',
            'community_voices' => 'nullable|string', 'recommendations' => 'nullable|string', 'target_decision_maker' => 'nullable|string|max:255',
            'status' => 'in:identified,evidence_collected,engagement,action,resolved',
        ]);
        return AdvocacyIssue::create([...$data, 'organization_id' => $this->orgId($request)]);
    }
}
