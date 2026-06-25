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

    public function show(int $id): RawContentResource
    {
        $rawContent = RawContent::with(['blueprint', 'generatedPost'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return new RawContentResource($rawContent);
    }

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
