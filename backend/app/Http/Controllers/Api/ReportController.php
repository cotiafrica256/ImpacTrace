<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use App\Models\Project;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Generates the four report types the ED / funders need:
 * weekly_activity, monthly_activity (+ monthly_me), quarterly_progress, annual.
 * The system auto-computes the numbers (auto_stats) from real submissions for
 * the chosen period; the officer then adds the narrative sections
 * (achievements, challenges, lessons learned, next steps) before it's
 * submitted up the chain (Field/Project Officer -> M&E Officer -> ED approval).
 */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with('project');

        if ($request->user()->role === 'super_admin') {
            $selectedOrgId = $request->header('X-Organization-Id') ?? $request->integer('organization_id');
            if ($selectedOrgId) {
                $query->whereHas('project', fn ($q) => $q->where('organization_id', $selectedOrgId));
            }
        } else {
            $orgId = $request->user()->organization_id;
            $query->whereHas('project', fn ($q) => $q->where('organization_id', $orgId));
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return $query->orderByDesc('period_start')->paginate(20);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'type' => 'required|in:weekly_activity,monthly_activity,monthly_me,quarterly_progress,annual',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $project = Project::findOrFail($data['project_id']);
        if ($request->user()->role === 'super_admin') {
            $selectedOrgId = $request->header('X-Organization-Id') ?? $request->input('organization_id');
            abort_unless(! $selectedOrgId || $project->organization_id === (int) $selectedOrgId, 404);
        } else {
            abort_unless($project->organization_id === $request->user()->organization_id, 404);
        }

        $stats = $this->computeStats($data['project_id'], $data['period_start'], $data['period_end']);

        $report = Report::create([
            'project_id' => $data['project_id'],
            'type' => $data['type'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'auto_stats' => $stats,
            'narrative' => [
                'summary' => '',
                'achievements' => '',
                'challenges' => '',
                'lessons_learned' => '',
                'next_steps' => '',
            ],
            'status' => 'draft',
            'prepared_by' => $request->user()->id,
        ]);

        return response()->json($report, 201);
    }

    public function update(Request $request, Report $report)
    {
        if ($request->user()->role === 'super_admin') {
            $selectedOrgId = $request->header('X-Organization-Id') ?? $request->input('organization_id');
            if ($selectedOrgId) {
                abort_unless($report->project->organization_id === (int) $selectedOrgId, 404);
            }
        } else {
            abort_unless($report->project->organization_id === $request->user()->organization_id, 404);
        }

        $data = $request->validate([
            'narrative' => 'sometimes|array',
            'status' => 'sometimes|in:draft,submitted_for_review,approved,published',
        ]);

        if (isset($data['status']) && $data['status'] === 'approved') {
            $data['approved_by'] = $request->user()->id;
        }

        $report->update($data);

        return response()->json($report);
    }

    public function downloadPdf(Request $request, Report $report)
    {
        if ($request->user()->role === 'super_admin') {
            $selectedOrgId = $request->header('X-Organization-Id') ?? $request->input('organization_id');
            if ($selectedOrgId) {
                abort_unless($report->project->organization_id === (int) $selectedOrgId, 404);
            }
        } else {
            abort_unless($report->project->organization_id === $request->user()->organization_id, 404);
        }

        $organization = $report->project->organization;
        $primary = $organization->primary_color ?? '#0d1d2d';
        $secondary = $organization->secondary_color ?? '#d9b15d';

        $logoDataUri = null;
        if ($organization->logo_path) {
            $path = storage_path('app/public/' . ltrim($organization->logo_path, '/'));
            if (file_exists($path)) {
                $logoDataUri = 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        $html = view('pdf.report', [
            'report' => $report,
            'project' => $report->project,
            'organization' => $organization,
            'logoDataUri' => $logoDataUri,
            'primaryColor' => $primary,
            'secondaryColor' => $secondary,
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->download('report-'.$report->id.'.pdf');
    }

    protected function computeStats(int $projectId, string $from, string $to): array
    {
        $base = FormSubmission::where('project_id', $projectId)
            ->whereDate('activity_date', '>=', $from)
            ->whereDate('activity_date', '<=', $to);

        $totalSubmissions = (clone $base)->count();
        $uniqueRespondents = (clone $base)->distinct('respondent_id')->count('respondent_id');

        $bySex = (clone $base)->join('respondents', 'respondents.id', '=', 'form_submissions.respondent_id')
            ->select('respondents.sex', DB::raw('count(*) as total'))
            ->groupBy('respondents.sex')->pluck('total', 'sex');

        $byVillage = (clone $base)->select('village', DB::raw('count(*) as total'))
            ->groupBy('village')->orderByDesc('total')->limit(10)->pluck('total', 'village');

        $byOfficer = (clone $base)->join('users', 'users.id', '=', 'form_submissions.collected_by')
            ->select('users.name', DB::raw('count(*) as total'))
            ->groupBy('users.name')->orderByDesc('total')->pluck('total', 'name');

        $vulnDistribution = (clone $base)->whereNotNull('vulnerability_class')
            ->select('vulnerability_class', DB::raw('count(*) as total'))
            ->groupBy('vulnerability_class')->pluck('total', 'vulnerability_class');

        $avgVulnScore = (clone $base)->whereNotNull('vulnerability_score')->avg('vulnerability_score');

        return [
            'total_submissions' => $totalSubmissions,
            'unique_respondents' => $uniqueRespondents,
            'by_sex' => $bySex,
            'by_village_top10' => $byVillage,
            'by_field_officer' => $byOfficer,
            'vulnerability_distribution' => $vulnDistribution,
            'average_vulnerability_score' => $avgVulnScore ? round($avgVulnScore, 1) : null,
            'period' => ['from' => $from, 'to' => $to],
        ];
    }
}
