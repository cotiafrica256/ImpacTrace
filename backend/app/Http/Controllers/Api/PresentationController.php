<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PresentationDeck;
use Illuminate\Http\Request;

class PresentationController extends Controller
{
    private function orgId(Request $request): int
    {
        $user = $request->user();
        $id = $user->role === 'super_admin' ? $request->header('X-Organization-Id') : $user->organization_id;
        abort_if(! $id, 422, 'Select an organisation first.');
        return (int) $id;
    }

    public function index(Request $request)
    {
        return PresentationDeck::where('organization_id', $this->orgId($request))->latest()->paginate(30);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'report_id' => 'nullable|exists:reports,id', 'title' => 'required|string|max:255',
            'slides' => 'required|array|min:1', 'status' => 'in:draft,published',
        ]);
        return response()->json(PresentationDeck::create([...$data, 'organization_id' => $this->orgId($request), 'created_by' => $request->user()->id]), 201);
    }

    public function update(Request $request, PresentationDeck $deck)
    {
        abort_unless($deck->organization_id === $this->orgId($request), 404);
        $deck->update($request->validate(['title' => 'sometimes|string|max:255', 'slides' => 'sometimes|array|min:1', 'status' => 'sometimes|in:draft,published']));
        return $deck->fresh();
    }
}
