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
    public function index(): AnonymousResourceCollection
    {
        $blueprints = Blueprint::withCount('rawContents')
            ->where('user_id', auth()->id())
            ->get();

        return BlueprintResource::collection($blueprints);
    }

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

    public function show(int $id): BlueprintResource
    {
        $blueprint = Blueprint::withCount('rawContents')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return new BlueprintResource($blueprint);
    }

    public function update(UpdateBlueprintRequest $request, int $id): BlueprintResource
    {
        $blueprint = Blueprint::where('user_id', auth()->id())
            ->findOrFail($id);

        $blueprint->update($request->validated());

        return new BlueprintResource($blueprint->loadCount('rawContents'));
    }

    public function destroy(int $id): JsonResponse
    {
        $blueprint = Blueprint::where('user_id', auth()->id())
            ->findOrFail($id);

        $blueprint->delete();

        return response()->json(null, 204);
    }
}
