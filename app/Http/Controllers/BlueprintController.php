<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlueprintRequest;
use App\Http\Requests\UpdateBlueprintRequest;
use App\Http\Resources\BlueprintResource;
use App\Models\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlueprintController extends Controller
{
    /**
     * List all blueprints for the authenticated user.
     *
     * @authenticated
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Tech Twitter Style",
     *       "tone": "professional yet relaxed",
     *       "max_hashtags": 3,
     *       "max_characters": 280,
     *       "regles_supplementaires": null,
     *       "posts_count": 5,
     *       "created_at": "2026-01-01 00:00:00"
     *     }
     *   ]
     * }
     */
    public function index(): AnonymousResourceCollection
    {
        $blueprints = Blueprint::withCount('rawContents')
            ->where('user_id', auth()->id())
            ->get();

        return BlueprintResource::collection($blueprints);
    }

    /**
     * Create a new blueprint.
     *
     * @authenticated
     *
     * @bodyParam name string required The blueprint name. Example: Tech Twitter Style
     * @bodyParam tone string required The desired tone of voice. Example: professional yet relaxed
     * @bodyParam max_hashtags integer Maximum number of hashtags (default 1). Example: 3
     * @bodyParam max_characters integer Maximum character count (default 280). Example: 280
     * @bodyParam regles_supplementaires string Extra rules as a JSON string. Example: ["Keep it concise","Use code examples"]
     *
     * @response 201 {
     *   "data": {
     *     "id": 1,
     *     "name": "Tech Twitter Style",
     *     "tone": "professional yet relaxed",
     *     "max_hashtags": 3,
     *     "max_characters": 280,
     *     "regles_supplementaires": "[\"Keep it concise\",\"Use code examples\"]",
     *     "posts_count": 0,
     *     "created_at": "2026-01-01 00:00:00"
     *   }
     * }
     */
    public function store(StoreBlueprintRequest $request): BlueprintResource
    {
        $blueprint = Blueprint::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'tone' => $request->tone,
            'max_hashtags' => $request->input('max_hashtags', 1),
            'max_characters' => $request->input('max_characters', 280),
            'regles_supplementaires' => $request->input('regles_supplementaires'),
        ]);

        return new BlueprintResource($blueprint);
    }

    /**
     * Get a single blueprint by ID.
     *
     * @authenticated
     *
     * @urlParam id integer required The blueprint ID. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "Tech Twitter Style",
     *     "tone": "professional yet relaxed",
     *     "max_hashtags": 3,
     *     "max_characters": 280,
     *     "regles_supplementaires": null,
     *     "posts_count": 5,
     *     "created_at": "2026-01-01 00:00:00"
     *   }
     * }
     * @response 404 {
     *   "message": "Resource not found."
     * }
     */
    public function show(int $id): BlueprintResource
    {
        $blueprint = Blueprint::withCount('rawContents')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return new BlueprintResource($blueprint);
    }

    /**
     * Update an existing blueprint.
     *
     * @authenticated
     *
     * @urlParam id integer required The blueprint ID. Example: 1
     *
     * @bodyParam name string The blueprint name. Example: Updated Style
     * @bodyParam tone string The desired tone of voice. Example: humorous
     * @bodyParam max_hashtags integer Maximum number of hashtags. Example: 5
     * @bodyParam max_characters integer Maximum character count. Example: 400
     * @bodyParam regles_supplementaires string Extra rules as a JSON string. Example: ["New rule"]
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "Updated Style",
     *     "tone": "humorous",
     *     "max_hashtags": 5,
     *     "max_characters": 400,
     *     "regles_supplementaires": "[\"New rule\"]",
     *     "posts_count": 5,
     *     "created_at": "2026-01-01 00:00:00"
     *   }
     * }
     */
    public function update(UpdateBlueprintRequest $request, int $id): BlueprintResource
    {
        $blueprint = Blueprint::where('user_id', auth()->id())
            ->findOrFail($id);

        $blueprint->update($request->validated());

        return new BlueprintResource($blueprint->loadCount('rawContents'));
    }

    /**
     * Delete a blueprint.
     *
     * @authenticated
     *
     * @urlParam id integer required The blueprint ID. Example: 1
     *
     * @response 204
     */
    public function destroy(int $id): JsonResponse
    {
        $blueprint = Blueprint::where('user_id', auth()->id())
            ->findOrFail($id);

        $blueprint->delete();

        return response()->json(null, 204);
    }
}
