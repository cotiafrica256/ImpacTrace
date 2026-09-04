<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Publication, PublicationComment};
use Illuminate\Http\Request;

class PublicationCommentController extends Controller
{
    public function index(string $slug)
    {
        $publication = Publication::where('slug', $slug)->where('status', 'published')->firstOrFail();
        return PublicationComment::where('publication_id', $publication->id)->where('status', 'approved')->latest()->get();
    }

    public function store(Request $request, string $slug)
    {
        $publication = Publication::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $data = $request->validate(['comment' => 'required|string|max:3000', 'geographic_unit_id' => 'nullable|exists:geographic_units,id']);
        return response()->json(PublicationComment::create([...$data, 'publication_id' => $publication->id, 'public_user_id' => $request->user()->id]), 201);
    }

    public function moderate(Request $request, PublicationComment $comment)
    {
        $data = $request->validate(['status' => 'required|in:approved,rejected,pending']);
        $comment->update($data);
        return $comment->fresh();
    }
}
