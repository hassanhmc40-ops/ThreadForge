<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePostStatusRequest;
use App\Http\Resources\PostResource;
use App\Models\GeneratedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    /**
     * List all generated posts for the authenticated user.
     *
     * @authenticated
     *
     * @queryParam statut string Filter by status (draft, archived, posted). Example: draft
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "hook_propose": "Your hook here",
     *       "body_points": ["Point 1", "Point 2"],
     *       "technical_readability_score": 85,
     *       "suggested_hashtags": ["#Tech", "#Laravel"],
     *       "tone_compliance_justification": "Matches the professional tone",
     *       "statut": "draft",
     *       "created_at": "2026-01-01 00:00:00"
     *     }
     *   ]
     * }
     */
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

    /**
     * Get a single generated post by ID.
     *
     * @authenticated
     *
     * @urlParam id integer required The post ID. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "hook_propose": "Your hook here",
     *     "body_points": ["Point 1", "Point 2"],
     *     "technical_readability_score": 85,
     *     "suggested_hashtags": ["#Tech", "#Laravel"],
     *     "tone_compliance_justification": "Matches the professional tone",
     *     "statut": "draft",
     *     "created_at": "2026-01-01 00:00:00"
     *   }
     * }
     */
    public function show(int $id): PostResource
    {
        $post = GeneratedPost::with('rawContent')
            ->whereHas('rawContent', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->findOrFail($id);

        return new PostResource($post);
    }

    /**
     * Update the publication status of a generated post.
     *
     * @authenticated
     *
     * @urlParam id integer required The post ID. Example: 1
     *
     * @bodyParam statut string required The new status (draft, archived, posted). Example: posted
     *
     * @response 200 {
     *   "message": "Post status updated successfully.",
     *   "post": {
     *     "data": {
     *       "id": 1,
     *       "statut": "posted"
     *     }
     *   }
     * }
     */
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
