<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * "Extract the attendance for that specific day" — every submission already
 * carries activity_date, respondent, village and the consent/signature
 * evidence, so daily attendance is just a filtered, exportable view of
 * form_submissions rather than a separate thing to maintain.
 */
class AttendanceController extends Controller
{
    public function forDate(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'date' => 'required|date',
        ]);

        $project = Project::findOrFail($data['project_id']);
        if ($request->user()->role !== 'super_admin') {
            abort_unless($project->organization_id === $request->user()->organization_id, 404);
        }

        $rows = FormSubmission::with(['respondent', 'collector', 'consent'])
            ->where('project_id', $data['project_id'])
            ->whereDate('activity_date', $data['date'])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($s) => [
                'respondent_code' => $s->respondent->respondent_code ?? null,
                'full_name' => $s->respondent->full_name ?? null,
                'sex' => $s->respondent->sex ?? null,
                'age' => $s->respondent->age ?? null,
                'village' => $s->village,
                'collected_by' => $s->collector->name ?? null,
                'time' => $s->created_at->format('H:i'),
                'has_signature' => (bool) ($s->consent->signature_path ?? null),
                'has_id_document' => (bool) ($s->consent->id_document_path ?? null),
                'submission_code' => $s->submission_code,
            ]);

        return response()->json([
            'date' => $data['date'],
            'project_id' => $data['project_id'],
            'total_attended' => $rows->count(),
            'female' => $rows->where('sex', 'female')->count(),
            'male' => $rows->where('sex', 'male')->count(),
            'attendees' => $rows->values(),
        ]);
    }
}
