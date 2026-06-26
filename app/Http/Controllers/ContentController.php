<?php

namespace App\Http\Controllers;

use App\Http\Requests\RepurposeRequest;
use App\Http\Resources\RawContentResource;
use App\Jobs\ProcessContentJob;
use App\Models\RawContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContentController extends Controller
{
    /**
     * List all raw contents for the authenticated user.
     *
     * @authenticated
     *
     * @queryParam statut string Filter by status. Example: completed
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "contenu_brut": "Raw markdown content...",
     *       "statut": "completed",
     *       "blueprint": { "id": 1, "name": "Tech Style" },
     *       "generated_post": null,
     *       "created_at": "2026-01-01 00:00:00"
     *     }
     *   ]
     * }
     */
    public function index(): AnonymousResourceCollection
    {
        $rawContents = RawContent::with(['blueprint', 'generatedPost'])
            ->where('user_id', auth()->id())
            ->when(request('statut'), function ($query, $statut) {
                $query->where('statut', $statut);
            })
            ->latest()
            ->get();

        return RawContentResource::collection($rawContents);
    }

    /**
     * Get a single raw content by ID.
     *
     * @authenticated
     *
     * @urlParam id integer required The raw content ID. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "contenu_brut": "Raw markdown content...",
     *     "statut": "completed",
     *     "blueprint": { "id": 1, "name": "Tech Style" },
     *     "generated_post": { "id": 1, "hook_propose": "..." },
     *     "created_at": "2026-01-01 00:00:00"
     *   }
     * }
     */
    public function show(int $id): RawContentResource
    {
        $rawContent = RawContent::with(['blueprint', 'generatedPost'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return new RawContentResource($rawContent);
    }

    /**
     * Submit raw content for AI processing.
     *
     * Dispatches a queue job to process the content asynchronously.
     * Returns immediately with HTTP 202.
     *
     * @authenticated
     *
     * @bodyParam blueprint_id integer required The blueprint ID to apply. Example: 1
     * @bodyParam contenu_brut string required The raw content to process (markdown/text). Example: # Hello World\n\nThis is a test post.
     *
     * @response 202 {
     *   "message": "Content accepted for processing.",
     *   "raw_content_id": 1
     * }
     */
    public function repurpose(RepurposeRequest $request): JsonResponse
    {
        $rawContent = RawContent::create([
            'user_id' => auth()->id(),
            'blueprint_id' => $request->blueprint_id,
            'contenu_brut' => $request->contenu_brut,
            'statut' => 'en_attente',
        ]);

        ProcessContentJob::dispatch($rawContent);

        return response()->json([
            'message' => 'Content accepted for processing.',
            'raw_content_id' => $rawContent->id,
        ], 202);
    }
}
