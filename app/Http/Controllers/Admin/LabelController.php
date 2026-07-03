<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Label;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LabelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $labels = Label::withCount('releases')
            ->with('customers:id,name,surname,company_name')
            ->when($request->search, fn($q) =>
                $q->where('name', 'ilike', "%{$request->search}%")
            )
            ->latest()
            ->paginate(25);

        return response()->json($labels);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:labels',
            'description' => 'nullable|string',
            'logo_url'    => 'nullable|url',
        ]);

        $label = Label::create([
            ...$data,
            'slug' => Str::slug($data['name']),
        ]);

        return response()->json($label, 201);
    }

    public function show(int $id): JsonResponse
    {
        $label = Label::with(['customers', 'releases' => fn($q) => $q->latest()->limit(20)])
            ->withCount('releases')
            ->findOrFail($id);

        return response()->json($label);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $label = Label::findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255|unique:labels,name,' . $id,
            'description' => 'nullable|string',
            'logo_url'    => 'nullable|url',
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $label->update($data);

        return response()->json($label);
    }

    public function assignCustomer(Request $request, int $id): JsonResponse
    {
        $label = Label::findOrFail($id);

        $request->validate([
            'customer_id' => 'required|exists:users,id',
            'is_primary'  => 'boolean',
        ]);

        $label->customers()->syncWithoutDetaching([
            $request->customer_id => ['is_primary' => $request->boolean('is_primary')],
        ]);

        return response()->json(['message' => 'Customer assigned to label.']);
    }
}
