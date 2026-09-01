<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * COTIA-only (role:super_admin). Onboards a new client organisation onto
 * the platform and gives it its own Executive Director account — from
 * there, that ED runs their organisation independently: creates their own
 * users, projects, and data-collection forms, seeing only their own data.
 */
class OrganizationController extends Controller
{
    public function index()
    {
        return Organization::withCount(['users', 'projects'])->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100|unique:organizations,code',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:30',
            'primary_color' => 'nullable|string|max:32',
            'secondary_color' => 'nullable|string|max:32',
            'logo' => 'nullable|image|max:2048',

            // first Executive Director account for this organisation
            'ed_name' => 'required|string|max:255',
            'ed_email' => 'required|email|unique:users,email',
            'ed_password' => 'required|string|min:8',
        ]);

        $result = DB::transaction(function () use ($data, $request) {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('organization-logos', 'public');
            }

            $organization = Organization::create([
                'name' => $data['name'],
                'code' => $data['code'] ?: Str::slug($data['name']),
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'primary_color' => $data['primary_color'] ?? '#0d1d2d',
                'secondary_color' => $data['secondary_color'] ?? '#d9b15d',
                'logo_path' => $logoPath,
                'created_by' => $request->user()->id,
            ]);

            $ed = User::create([
                'organization_id' => $organization->id,
                'name' => $data['ed_name'],
                'email' => $data['ed_email'],
                'password' => Hash::make($data['ed_password']),
                'role' => User::ROLE_ED,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'organization.created',
                'subject_type' => Organization::class,
                'subject_id' => $organization->id,
                'meta' => ['ed_email' => $ed->email],
            ]);

            return $organization->fresh()->load('users');
        });

        return response()->json($result, 201);
    }

    public function show(Organization $organization)
    {
        return $organization->load('users')->loadCount('projects');
    }

    public function update(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:30',
            'primary_color' => 'nullable|string|max:32',
            'secondary_color' => 'nullable|string|max:32',
            'logo' => 'nullable|image|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('organization-logos', 'public');
        }

        $organization->update($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'organization.updated',
            'subject_type' => Organization::class,
            'subject_id' => $organization->id,
            'meta' => $data,
        ]);

        return response()->json($organization->fresh());
    }
}
