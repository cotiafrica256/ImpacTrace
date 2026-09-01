<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * User & role management within ONE organisation — restricted to that
 * organisation's ED via 'role:ed' + 'org' middleware. An ED can only ever
 * see and manage users in their own organization_id; they never see other
 * organisations' staff. (COTIA-level organisation onboarding is handled
 * separately by OrganizationController, as role:super_admin.)
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('supervisor')->orderBy('name');

        if ($request->user()->role === 'super_admin') {
            $selected = $request->header('X-Organization-Id') ?? $request->integer('organization_id');
            if ($selected) {
                $query->where('organization_id', $selected);
            }

            return $query->get();
        }

        return $query->where('organization_id', $request->user()->organization_id)->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:ed,meo,po,fo',
            'supervisor_id' => 'nullable|exists:users,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'password' => 'required|string|min:8',
        ]);

        $user = $request->user();
        $selectedOrgId = $request->header('X-Organization-Id') ?? $data['organization_id'] ?? null;
        $orgId = $user->role === 'super_admin' ? $selectedOrgId : $user->organization_id;
        abort_if($user->role !== 'super_admin' && $orgId !== $user->organization_id, 404);
        abort_if($user->role === 'super_admin' && ! $orgId, 422, 'Select the organisation for this user.');

        $data['organization_id'] = $orgId;
        $data['password'] = Hash::make($data['password']);
        $userRecord = User::create($data);

        ActivityLog::create([
            'organization_id' => $userRecord->organization_id,
            'user_id' => $request->user()->id,
            'action' => 'user.created',
            'subject_type' => User::class,
            'subject_id' => $userRecord->id,
            'meta' => ['role' => $userRecord->role],
        ]);

        return response()->json($userRecord, 201);
    }

    public function update(Request $request, User $user)
    {
        $actor = $request->user();
        if ($actor->role === 'super_admin') {
            $selectedOrgId = $request->header('X-Organization-Id') ?? $request->input('organization_id');
            if ($selectedOrgId) {
                abort_unless($user->organization_id === (int) $selectedOrgId, 404);
            }
        } else {
            abort_unless($user->organization_id === $actor->organization_id, 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:30',
            'role' => 'sometimes|in:ed,meo,po,fo',
            'supervisor_id' => 'nullable|exists:users,id',
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|string|min:8',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        ActivityLog::create([
            'organization_id' => $user->organization_id,
            'user_id' => $actor->id,
            'action' => 'user.updated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'meta' => $data,
        ]);

        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        $actor = $request->user();
        if ($actor->role === 'super_admin') {
            $selectedOrgId = $request->header('X-Organization-Id') ?? $request->input('organization_id');
            if ($selectedOrgId) {
                abort_unless($user->organization_id === (int) $selectedOrgId, 404);
            }
        } else {
            abort_unless($user->organization_id === $actor->organization_id, 404);
        }

        // Soft-deactivate rather than hard delete, to preserve data lineage
        // on everything that user ever collected.
        $user->update(['is_active' => false]);

        ActivityLog::create([
            'organization_id' => $user->organization_id,
            'user_id' => $actor->id,
            'action' => 'user.deactivated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);

        return response()->json(['message' => 'User deactivated.']);
    }
}
