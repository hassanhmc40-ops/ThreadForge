<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePostStatusRequest;
use App\Http\Resources\PostResource;
use App\Models\GeneratedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $posts = GeneratedPost::with('rawContent')
            ->whereHas('rawContent', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->when(request('statut'), function ($query, $statut) {
                $query->where('statut', $statut);
            })
            ->get();

        return PostResource::collection($posts);
    }

    public function show(int $id): PostResource
    {
        $post = GeneratedPost::with('rawContent')
            ->whereHas('rawContent', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->findOrFail($id);

        return new PostResource($post);
    }

    public function updateStatus(UpdatePostStatusRequest $request, int $id): JsonResponse
    {
        $post = GeneratedPost::with('rawContent')
            ->whereHas('rawContent', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->findOrFail($id);

        $post->update(['statut' => $request->statut]);

        return response()->json([
            'message' => 'Post status updated successfully.',
            'post' => new PostResource($post),
        ]);
    }
}
