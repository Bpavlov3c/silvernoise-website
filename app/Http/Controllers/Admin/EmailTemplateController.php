<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(EmailTemplate::all(['id', 'key', 'name', 'updated_at']));
    }

    public function show(string $key): JsonResponse
    {
        return response()->json(EmailTemplate::where('key', $key)->firstOrFail());
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $template = EmailTemplate::where('key', $key)->firstOrFail();

        $data = $request->validate([
            'subject_bg' => 'sometimes|string',
            'subject_en' => 'sometimes|string',
            'body_bg'    => 'sometimes|string',
            'body_en'    => 'sometimes|string',
        ]);

        $template->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json($template->fresh());
    }

    public function sendTest(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'lang'  => 'required|in:bg,en',
        ]);

        $template = EmailTemplate::where('key', $key)->firstOrFail();
        $rendered = $template->render($request->lang, [
            'first_name'   => 'Test',
            'reset_link'   => url('/reset-password/test-token'),
            'expiry_time'  => '24 hours',
            'amount'       => '100.00',
            'currency'     => 'EUR',
            'iban'         => 'BG80BNBG96611020345678',
            'period'       => 'Q1 2024',
            'total_earnings' => '100.00',
            'release_title'  => 'Test Release',
            'release_date'   => '2024-03-01',
        ]);

        Mail::raw($rendered['body'], function ($mail) use ($request, $rendered) {
            $mail->to($request->email)->subject($rendered['subject']);
        });

        return response()->json(['message' => "Test email sent to {$request->email}"]);
    }
}
