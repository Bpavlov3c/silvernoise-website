<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    public function genres(): JsonResponse
    {
        return response()->json(Genre::orderBy('name')->get());
    }

    public function stores(): JsonResponse
    {
        return response()->json(Store::where('is_active', true)->orderBy('name')->get());
    }

    public function artists(Request $request): JsonResponse
    {
        return response()->json(
            Artist::when($request->search, fn($q) =>
                $q->where('name', 'ilike', "%{$request->search}%")
            )
            ->orderBy('name')
            ->limit(50)
            ->get()
        );
    }
}
