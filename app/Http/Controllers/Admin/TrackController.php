<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTrackRequest;
use App\Http\Resources\TrackResource;
use App\Models\Release;
use App\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrackController extends Controller
{
    public function index(int $releaseId): JsonResponse
    {
        $release = Release::findOrFail($releaseId);
        $tracks  = $release->tracks()->with(['artists', 'genres'])->get();

        return response()->json(TrackResource::collection($tracks));
    }

    public function store(StoreTrackRequest $request, int $releaseId): JsonResponse
    {
        Release::findOrFail($releaseId);

        $track = Track::create([
            ...$request->validated(),
            'release_id' => $releaseId,
        ]);

        if ($request->has('artist_ids')) {
            $track->artists()->sync(
                collect($request->artist_ids)->mapWithKeys(fn($a) => [
                    $a['id'] => ['role' => $a['role'] ?? 'Main Artist', 'sort_order' => $a['order'] ?? 0]
                ])
            );
        }

        if ($request->has('genre_ids')) {
            $track->genres()->sync($request->genre_ids);
        }

        return response()->json(new TrackResource($track->load('artists', 'genres')), 201);
    }

    public function show(int $id): JsonResponse
    {
        $track = Track::with(['artists', 'genres', 'release'])->findOrFail($id);

        return response()->json(new TrackResource($track));
    }

    public function update(StoreTrackRequest $request, int $id): JsonResponse
    {
        $track = Track::findOrFail($id);
        $track->update($request->validated());

        if ($request->has('artist_ids')) {
            $track->artists()->sync(
                collect($request->artist_ids)->mapWithKeys(fn($a) => [
                    $a['id'] => ['role' => $a['role'] ?? 'Main Artist', 'sort_order' => $a['order'] ?? 0]
                ])
            );
        }

        if ($request->has('genre_ids')) {
            $track->genres()->sync($request->genre_ids);
        }

        return response()->json(new TrackResource($track->fresh('artists', 'genres')));
    }

    public function uploadAudio(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,flac,aiff|max:524288', // 512MB
        ]);

        $track = Track::findOrFail($id);

        // Delete old file from R2 if exists
        if ($track->audio_file_path) {
            Storage::disk('r2')->delete($track->audio_file_path);
        }

        $file = $request->file('audio');
        $path = "audio/{$track->release_id}/{$id}_{$file->getClientOriginalName()}";

        Storage::disk('r2')->put($path, $file->getContent());

        $track->update([
            'audio_file_path' => $path,
            'audio_file_size' => $file->getSize(),
        ]);

        return response()->json([
            'audio_file_path' => $path,
            'audio_file_size' => $file->getSize(),
        ]);
    }
}
