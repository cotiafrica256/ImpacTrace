<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Consent;
use App\Models\FormSubmission;
use App\Models\Project;
use App\Models\ProjectForm;
use App\Models\Respondent;
use App\Models\SubmissionMedia;
use App\Services\RespondentDeduplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    public function __construct(protected RespondentDeduplicationService $dedup)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = FormSubmission::with(['respondent', 'collector', 'project', 'form']);

        if ($user->role !== 'super_admin') {
            $orgId = $user->organization_id;
            $query->whereHas('project', fn ($q) => $q->where('organization_id', $orgId));
        }

        if ($user->role === 'fo') {
            $query->where('collected_by', $user->id);
        } elseif ($user->role === 'po') {
            $query->whereIn('project_id', $user->projects()->pluck('projects.id'));
        }
        // meo and ed see everything within their own organisation

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('activity_date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('activity_date', '<=', $request->date('to'));
        }

        return $query->orderByDesc('activity_date')->paginate(30);
    }

    /**
     * Step 1: check whether this person's ID (or fuzzy identity) already
     * exists BEFORE the field officer spends time on the full interview.
     * The frontend calls this right after the ID is scanned. Scoped to the
     * officer's own organisation only.
     */
    public function checkDuplicate(Request $request)
    {
        $data = $request->validate([
            'id_number' => 'nullable|string',
            'full_name' => 'required|string',
            'age_or_dob' => 'nullable|string',
            'village' => 'nullable|string',
        ]);

        $result = $this->dedup->check(
            $request->user()->organization_id,
            $data['id_number'] ?? null,
            $data['full_name'],
            $data['age_or_dob'] ?? null,
            $data['village'] ?? null
        );

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'project_form_id' => 'required|exists:project_forms,id',
            'activity_date' => 'required|date',
            'village' => 'nullable|string',
            'parish' => 'nullable|string',
            'sub_county' => 'nullable|string',
            'district' => 'nullable|string',
            'gps_lat' => 'nullable|numeric',
            'gps_lng' => 'nullable|numeric',
            'answers' => 'required', // JSON string when multipart, array when JSON body
            'vulnerability_score' => 'nullable|integer|min:0|max:80',
            'vulnerability_class' => 'nullable|string',

            // respondent identity (for dedup + the respondents table)
            'respondent.full_name' => 'required|string',
            'respondent.sex' => 'nullable|in:female,male,other',
            'respondent.age' => 'nullable|integer',
            'respondent.id_type' => 'nullable|string',
            'respondent.id_number' => 'nullable|string',
            'respondent.village' => 'nullable|string',
            'respondent.parish' => 'nullable|string',
            'respondent.sub_county' => 'nullable|string',
            'respondent.district' => 'nullable|string',
            'respondent.phone' => 'nullable|string',
            'override_duplicate' => 'nullable|boolean', // supervisor can force-create on a *possible* (fuzzy) duplicate

            // consent bundle
            'consent.consent_given' => 'required|boolean',
            'consent.permission_for_learning_advocacy' => 'nullable|boolean',
            'consent.permission_for_photos' => 'nullable|boolean',

            // files
            'id_document' => 'nullable|file|image|max:5120',
            'signature' => 'nullable|file|image|max:2048',
            'respondent_photo' => 'nullable|file|image|max:5120',
            'voice_note' => 'nullable|file|mimes:mp3,wav,m4a,ogg,webm|max:15360',
            'extra_photos.*' => 'nullable|file|image|max:5120',
        ]);

        $orgId = $request->user()->organization_id;

        $project = Project::findOrFail($data['project_id']);
        abort_unless($project->organization_id === $orgId, 404, 'That project does not belong to your organisation.');

        $form = ProjectForm::findOrFail($data['project_form_id']);
        abort_unless($form->project_id === $project->id, 404, 'That form does not belong to the selected project.');

        $answers = is_string($data['answers']) ? json_decode($data['answers'], true) : $data['answers'];

        if (! $data['consent']['consent_given'] && $form->requires_consent) {
            return response()->json(['message' => 'This form requires informed consent before any data can be recorded.'], 422);
        }

        $result = DB::transaction(function () use ($request, $data, $form, $answers, $orgId) {
            // --- Respondent dedup & create/reuse (scoped to this organisation) ---
            $r = $data['respondent'];
            $dedupResult = $this->dedup->check($orgId, $r['id_number'] ?? null, $r['full_name'], $r['age'] ?? null, $r['village'] ?? null);

            if ($dedupResult['status'] === 'exact_duplicate') {
                // Hard block: same ID number already used in this organisation.
                abort(response()->json([
                    'message' => 'This ID has already been registered in the system.',
                    'existing_respondent' => $dedupResult['respondent'],
                ], 409));
            }

            if ($dedupResult['status'] === 'possible_duplicate' && empty($data['override_duplicate'])) {
                abort(response()->json([
                    'message' => 'A person with a very similar name, age and village already exists. Confirm this is a different person to proceed.',
                    'existing_respondent' => $dedupResult['respondent'],
                    'requires_override' => true,
                ], 409));
            }

            if ($dedupResult['status'] === 'possible_duplicate') {
                $respondent = $dedupResult['respondent'];
            } else {
                $respondent = Respondent::create([
                    'organization_id' => $orgId,
                    'respondent_code' => $this->dedup->nextRespondentCode($orgId),
                    'full_name' => $r['full_name'],
                    'sex' => $r['sex'] ?? null,
                    'age' => $r['age'] ?? null,
                    'id_type' => $r['id_type'] ?? null,
                    'id_number_hash' => $dedupResult['id_number_hash'] ?? null,
                    'id_number_last4' => isset($r['id_number']) ? substr($r['id_number'], -4) : null,
                    'fuzzy_key' => $dedupResult['fuzzy_key'] ?? $this->dedup->fuzzyKey($r['full_name'], $r['age'] ?? null, $r['village'] ?? null),
                    'village' => $r['village'] ?? null,
                    'parish' => $r['parish'] ?? null,
                    'sub_county' => $r['sub_county'] ?? null,
                    'district' => $r['district'] ?? null,
                    'phone' => $r['phone'] ?? null,
                ]);
            }

            // --- Submission ---
            $submission = FormSubmission::create([
                'submission_code' => 'SUB-'.now()->format('Y').'-'.str_pad((string) (FormSubmission::count() + 1), 6, '0', STR_PAD_LEFT),
                'project_id' => $data['project_id'],
                'project_form_id' => $form->id,
                'respondent_id' => $respondent->id,
                'collected_by' => $request->user()->id,
                'activity_date' => $data['activity_date'],
                'village' => $data['village'] ?? $r['village'] ?? null,
                'parish' => $data['parish'] ?? null,
                'sub_county' => $data['sub_county'] ?? null,
                'district' => $data['district'] ?? null,
                'gps_lat' => $data['gps_lat'] ?? null,
                'gps_lng' => $data['gps_lng'] ?? null,
                'answers' => $answers,
                'vulnerability_score' => $data['vulnerability_score'] ?? null,
                'vulnerability_class' => $data['vulnerability_class'] ?? null,
                'status' => 'submitted',
                'synced_at' => now(),
            ]);

            // --- Consent bundle: files stored under storage/app/public/consent/{org_id}/{submission_code}/ ---
            $dir = 'consent/'.$orgId.'/'.$submission->submission_code;
            $paths = [];
            foreach (['id_document', 'signature', 'respondent_photo', 'voice_note'] as $field) {
                if ($request->hasFile($field)) {
                    $paths[$field] = $request->file($field)->store($dir, 'public');
                }
            }

            Consent::create([
                'form_submission_id' => $submission->id,
                'consent_given' => $data['consent']['consent_given'],
                'permission_for_learning_advocacy' => $data['consent']['permission_for_learning_advocacy'] ?? false,
                'permission_for_photos' => $data['consent']['permission_for_photos'] ?? false,
                'id_document_path' => $paths['id_document'] ?? null,
                'signature_path' => $paths['signature'] ?? null,
                'respondent_photo_path' => $paths['respondent_photo'] ?? null,
                'voice_note_path' => $paths['voice_note'] ?? null,
                'captured_at' => now(),
            ]);

            if ($request->hasFile('extra_photos')) {
                foreach ($request->file('extra_photos') as $photo) {
                    SubmissionMedia::create([
                        'form_submission_id' => $submission->id,
                        'type' => 'photo',
                        'path' => $photo->store($dir.'/extra', 'public'),
                    ]);
                }
            }

            ActivityLog::create([
                'organization_id' => $orgId,
                'user_id' => $request->user()->id,
                'action' => 'submission.created',
                'subject_type' => FormSubmission::class,
                'subject_id' => $submission->id,
                'meta' => ['project_id' => $submission->project_id, 'respondent_status' => $dedupResult['status']],
            ]);

            return $submission->load(['consent', 'respondent', 'media']);
        });

        return response()->json($result, 201);
    }

    public function show(Request $request, FormSubmission $submission)
    {
        if ($request->user()->role !== 'super_admin') {
            abort_unless($submission->project->organization_id === $request->user()->organization_id, 404);
        }

        return $submission->load(['consent', 'respondent', 'media', 'collector', 'form', 'project']);
    }

    public function review(Request $request, FormSubmission $submission)
    {
        if ($request->user()->role !== 'super_admin') {
            abort_unless($submission->project->organization_id === $request->user()->organization_id, 404);
        }

        $data = $request->validate([
            'status' => 'required|in:reviewed,approved,flagged_duplicate',
            'review_notes' => 'nullable|string',
        ]);

        $submission->update([
            'status' => $data['status'],
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
        ]);

        return response()->json($submission);
    }
}
