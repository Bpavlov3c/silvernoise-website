<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmtpSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SmtpController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = SmtpSetting::first();
        return response()->json($settings ? $settings->only(
            'id', 'host', 'port', 'username', 'encryption', 'from_email', 'from_name', 'is_active'
        ) : null);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'host'       => 'required|string',
            'port'       => 'required|integer|in:25,465,587,2525',
            'username'   => 'required|string',
            'password'   => 'sometimes|string|min:1',
            'encryption' => 'required|in:tls,ssl,none',
            'from_email' => 'required|email',
            'from_name'  => 'required|string',
        ]);

        $settings = SmtpSetting::firstOrNew([]);

        if (isset($data['password'])) {
            $settings->password = $data['password'];
            unset($data['password']);
        }

        $settings->fill([...$data, 'updated_by' => $request->user()->id]);
        $settings->save();

        return response()->json(['message' => 'SMTP settings updated.']);
    }

    public function test(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        try {
            Mail::raw('This is a test email from Silvernoise Admin.', function ($mail) use ($request) {
                $mail->to($request->email)->subject('[Silvernoise] SMTP Test');
            });

            return response()->json(['message' => 'Test email sent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 422);
        }
    }
}
