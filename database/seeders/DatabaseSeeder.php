<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Store;
use App\Models\User;
use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default admin
        User::firstOrCreate(
            ['email' => 'admin@silvernoise.bg'],
            [
                'name'              => 'Admin',
                'surname'           => 'Silvernoise',
                'password'          => Hash::make('change-me-immediately'),
                'role'              => 'admin',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        // Genres
        $genres = [
            'Pop', 'Rock', 'Folk', 'Chalga', 'Hip-Hop', 'Electronic',
            'R&B', 'Jazz', 'Classical', 'Turbo Folk', 'Ethno', 'Dance',
        ];
        foreach ($genres as $name) {
            Genre::firstOrCreate(
                ['name' => $name],
                ['slug' => \Illuminate\Support\Str::slug($name)]
            );
        }

        // Stores / DSPs
        $stores = [
            'Spotify', 'Apple Music', 'YouTube Music', 'Deezer',
            'Tidal', 'Amazon Music', 'Shazam', 'SoundCloud',
            'Beatport',
        ];
        foreach ($stores as $name) {
            Store::firstOrCreate(
                ['name' => $name],
                ['slug' => \Illuminate\Support\Str::slug($name)]
            );
        }

        // Email templates — Bulgarian body text to be completed via Admin Central
        $templates = [
            [
                'key'        => 'password_reset',
                'name'       => 'Password Reset',
                'subject_bg' => '[Silvernoise] Zaявка за нулиране на парола',
                'subject_en' => '[Silvernoise] Password Reset Request',
                'body_bg'    => 'Zdravey {{first_name}}, klikni na {{reset_link}}',
                'body_en'    => "Hi {{first_name}},\n\nClick below to reset your password (expires {{expiry_time}}):\n\n{{reset_link}}\n\nIf you did not request this, ignore this email.\n\nThe Silvernoise Team",
                'variables'  => json_encode(['{{first_name}}', '{{reset_link}}', '{{expiry_time}}']),
            ],
            [
                'key'        => 'payment_notification',
                'name'       => 'Payment Notification',
                'subject_bg' => '[Silvernoise] Plashteneto e izprateno',
                'subject_en' => '[Silvernoise] Your Payment Has Been Sent',
                'body_bg'    => 'Zdravey {{first_name}}, platane {{amount}} {{currency}} e izprateno.',
                'body_en'    => "Hi {{first_name}},\n\nA payment of {{amount}} {{currency}} has been sent to IBAN {{iban}}.\n\nPeriod: {{period}}\n\nThe Silvernoise Team",
                'variables'  => json_encode(['{{first_name}}', '{{amount}}', '{{currency}}', '{{iban}}', '{{period}}']),
            ],
            [
                'key'        => 'release_approved',
                'name'       => 'Release Approved',
                'subject_bg' => '[Silvernoise] Релизът е одобрен',
                'subject_en' => '[Silvernoise] Your Release Has Been Approved',
                'body_bg'    => 'Zdravey {{first_name}}, релизът {{release_title}} е одобрен.',
                'body_en'    => "Hi {{first_name}},\n\nYour release \"{{release_title}}\" has been approved and is being distributed.\nExpect it live by {{release_date}}.\n\nThe Silvernoise Team",
                'variables'  => json_encode(['{{first_name}}', '{{release_title}}', '{{release_date}}']),
            ],
            [
                'key'        => 'quarterly_report',
                'name'       => 'Quarterly Report Ready',
                'subject_bg' => '[Silvernoise] Отчетът за {{period}} е готов',
                'subject_en' => '[Silvernoise] Your {{period}} Report Is Ready',
                'body_bg'    => 'Zdravey {{first_name}}, отчетът за {{period}} е наличен.',
                'body_en'    => "Hi {{first_name}},\n\nYour report for {{period}} is available in Seller Central.\nTotal: {{total_earnings}} {{currency}}\n\nThe Silvernoise Team",
                'variables'  => json_encode(['{{first_name}}', '{{period}}', '{{total_earnings}}', '{{currency}}']),
            ],
        ];

        foreach ($templates as $tpl) {
            EmailTemplate::firstOrCreate(['key' => $tpl['key']], $tpl);
        }
    }
}
