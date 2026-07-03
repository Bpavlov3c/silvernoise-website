<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReleaseResource;
use App\Models\Release;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $releases = Release::where('customer_id', $request->user()->id)
            ->with(['label', 'artists'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return response()->json(ReleaseResource::collection($releases));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $release = Release::where('customer_id', $request->user()->id)
            ->with(['label', 'artists', 'genres', 'tracks.artists', 'stores'])
            ->findOrFail($id);

        return response()->json(new ReleaseResource($release));
    }
}
