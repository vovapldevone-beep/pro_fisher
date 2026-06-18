<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatchRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(CatchRecord $catchRecord): JsonResponse
    {
        $comments = $catchRecord->catchComments()
            ->with('user:id,name,avatar_url')
            ->oldest()
            ->get()
            ->map(fn ($c) => $this->formatComment($c));

        return response()->json($comments);
    }

    public function store(CatchRecord $catchRecord, Request $request): JsonResponse
    {
        $request->validate(['body' => 'required|string|max:500']);

        $comment = $catchRecord->catchComments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        $comment->load('user:id,name,avatar_url');

        return response()->json($this->formatComment($comment), 201);
    }

    private function formatComment($comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
                'avatar_url' => $comment->user->avatar_url,
            ],
            'created_at' => $comment->created_at->toISOString(),
        ];
    }
}
