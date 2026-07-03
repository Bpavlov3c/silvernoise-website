<?php

namespace App\Jobs;

use App\Models\EmailLog;
use App\Models\NewsletterCampaign;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendNewsletterCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 3600;

    public function __construct(
        private readonly NewsletterCampaign $campaign
    ) {}

    public function handle(): void
    {
        $recipients = $this->getRecipients();
        $sent       = 0;

        foreach ($recipients as $user) {
            // Use user's preferred language (default BG for Balkan market)
            $lang    = 'bg';
            $subject = $this->campaign->subject_bg;
            $body    = $this->campaign->body_bg;

            try {
                Mail::html($body, function ($mail) use ($user, $subject) {
                    $mail->to($user->email, $user->full_name)->subject($subject);
                });

                EmailLog::create([
                    'user_id'      => $user->id,
                    'campaign_id'  => $this->campaign->id,
                    'to_email'     => $user->email,
                    'subject'      => $subject,
                    'status'       => 'sent',
                ]);

                $sent++;
            } catch (\Exception $e) {
                EmailLog::create([
                    'user_id'       => $user->id,
                    'campaign_id'   => $this->campaign->id,
                    'to_email'      => $user->email,
                    'subject'       => $subject,
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                Log::warning("Newsletter send failed for {$user->email}: " . $e->getMessage());
            }

            // Rate limit: 5 emails/second max
            usleep(200000);
        }

        $this->campaign->update([
            'status'           => 'sent',
            'sent_at'          => now(),
            'recipients_count' => $sent,
        ]);
    }

    private function getRecipients()
    {
        $query = User::where('role', 'seller')->where('is_active', true);

        return match ($this->campaign->segment) {
            'active'   => $query->where('is_active', true)->get(),
            'inactive' => User::where('role', 'seller')->where('is_active', false)->get(),
            default    => $query->get(),
        };
    }

    public function failed(\Throwable $exception): void
    {
        $this->campaign->update(['status' => 'draft']);
        Log::error('Newsletter campaign failed: ' . $exception->getMessage());
    }
}
