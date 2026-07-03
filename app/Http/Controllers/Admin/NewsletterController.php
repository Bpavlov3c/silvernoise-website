<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewsletterCampaign;
use App\Models\NewsletterCampaign;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            NewsletterCampaign::with('creator:id,name,surname')
                ->latest()
                ->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_bg' => 'required|string',
            'subject_en' => 'required|string',
            'body_bg'    => 'required|string',
            'body_en'    => 'required|string',
            'segment'    => 'required|in:all,active,inactive,artists,labels',
        ]);

        $campaign = NewsletterCampaign::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($campaign, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(
            NewsletterCampaign::with(['creator', 'emailLogs'])->findOrFail($id)
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $campaign = NewsletterCampaign::where('status', 'draft')->findOrFail($id);

        $campaign->update($request->validate([
            'subject_bg' => 'sometimes|string',
            'subject_en' => 'sometimes|string',
            'body_bg'    => 'sometimes|string',
            'body_en'    => 'sometimes|string',
            'segment'    => 'sometimes|in:all,active,inactive,artists,labels',
        ]));

        return response()->json($campaign->fresh());
    }

    public function send(int $id): JsonResponse
    {
        $campaign = NewsletterCampaign::where('status', 'draft')->findOrFail($id);
        $campaign->update(['status' => 'sending']);

        SendNewsletterCampaign::dispatch($campaign);

        return response()->json(['message' => 'Campaign queued for sending.']);
    }

    public function schedule(Request $request, int $id): JsonResponse
    {
        $request->validate(['scheduled_at' => 'required|date|after:now']);

        $campaign = NewsletterCampaign::where('status', 'draft')->findOrFail($id);
        $campaign->update([
            'status'       => 'scheduled',
            'scheduled_at' => $request->scheduled_at,
        ]);

        return response()->json(['message' => 'Campaign scheduled.', 'scheduled_at' => $campaign->scheduled_at]);
    }
}
