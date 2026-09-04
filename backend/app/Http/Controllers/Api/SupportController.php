<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:255', 'subject' => 'nullable|string|max:255', 'message' => 'required|string|max:5000']);
        $data['public_user_id'] = $request->user()?->id;
        return response()->json(SupportRequest::create($data), 201);
    }

    public function index(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['super_admin', 'customer_service'], true), 403);
        $query = SupportRequest::with('responder')->latest();
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('email', 'like', '%'.$request->string('q').'%')->orWhere('message', 'like', '%'.$request->string('q').'%'));
        return $query->paginate(30);
    }

    public function update(Request $request, SupportRequest $supportRequest)
    {
        abort_unless(in_array($request->user()->role, ['super_admin', 'customer_service'], true), 403);
        $data = $request->validate(['status' => 'required|in:open,in_progress,resolved', 'response' => 'nullable|string|max:5000']);
        $supportRequest->update([...$data, 'responded_by' => $request->user()->id, 'responded_at' => now()]);
        return $supportRequest->fresh('responder');
    }
}
