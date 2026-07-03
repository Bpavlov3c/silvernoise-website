<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReleaseRequest;
use App\Http\Resources\ReleaseResource;
use App\Models\Release;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $releases = Release::with(['label', 'customer', 'artists'])
            ->when($request->search, fn($q) =>
                $q->where('title', 'ilike', "%{$request->search}%")
                  ->orWhere('upc', $request->search)
                  ->orWhere('catalog_id', $request->search)
            )
            ->when($request->status, fn($q) =>
                $q->where('status', $request->status)
            )
            ->when($request->label_id, fn($q) =>
                $q->where('label_id', $request->label_id)
            )
            ->when($request->customer_id, fn($q) =>
                $q->where('customer_id', $request->customer_id)
            )
            ->latest()
            ->paginate(25);

        return response()->json(ReleaseResource::collection($releases));
    }

    public function store(StoreReleaseRequest $request): JsonResponse
    {
        $release = Release::create($request->validated());

        if ($request->has('artist_ids')) {
            $release->artists()->sync(
                collect($request->artist_ids)->mapWithKeys(fn($a) => [
                    $a['id'] => ['role' => $a['role'] ?? 'Main Artist', 'sort_order' => $a['order'] ?? 0]
                ])
            );
        }

        if ($request->has('genre_ids')) {
            $release->genres()->sync($request->genre_ids);
        }

        return response()->json(new ReleaseResource($release->load(['label', 'artists', 'genres'])), 201);
    }

    public function show(int $id): JsonResponse
    {
        $release = Release::with([
            'label', 'customer', 'artists', 'genres',
            'tracks.artists', 'tracks.genres', 'stores'
        ])->findOrFail($id);

        return response()->json(new ReleaseResource($release));
    }

    public function update(StoreReleaseRequest $request, int $id): JsonResponse
    {
        $release = Release::findOrFail($id);
        $release->update($request->validated());

        if ($request->has('artist_ids')) {
            $release->artists()->sync(
                collect($request->artist_ids)->mapWithKeys(fn($a) => [
                    $a['id'] => ['role' => $a['role'] ?? 'Main Artist', 'sort_order' => $a['order'] ?? 0]
                ])
            );
        }

        if ($request->has('genre_ids')) {
            $release->genres()->sync($request->genre_ids);
        }

        return response()->json(new ReleaseResource($release->fresh(['label', 'artists', 'genres'])));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:draft,pending,approved,delivered,live,takedown',
        ]);

        $release = Release::findOrFail($id);
        $release->update(['status' => $request->status]);

        return response()->json(['status' => $release->status]);
    }
}
