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

        // Email templates
        $templates = [
            [
                'key'        => 'password_reset',
                'name'       => 'Password Reset',
                'subject_bg' => '[Silvernoise] Заявка за нулиране на парола',
                'subject_en' => '[Silvernoise] Password Reset Request',
                'body_bg'    => "Здравей {{first_name}},\n\nПолучихме заявка за нулиране на паролата ти.\nКликни на линка по-долу — той е валиден {{expiry_time}}:\n\n{{reset_link}}\n\nАко не си правил тази заявка, игнорирай имейла.\n\nЕкипът на Silvernoise",
                'body_en'    => "Hi {{first_name}},\n\nWe received a request to reset your password.\nClick the link below — it expires in {{expiry_time}}:\n\n{{reset_link}}\n\nIf you didn't request this, you can safely ignore this email.\n\nThe Silvernoise Team",
                'variables'  => json_encode(['{{first_name}}', '{{reset_link}}', '{{expiry_time}}']),
            ],
            [
                'key'        => 'payment_notification',
                'name'       => 'Payment Notification',
                'subject_bg' => '[Silvernoise] Плащането ти е изпратено',
                'subject_en' => '[Silvernoise] Your Payment Has Been Sent',
                'body_bg'    => "Здравей {{first_name}},\n\nПлащане от {{amount}} {{currency}} беше изпратено към IBAN {{iban}}.\n\nПериод: {{period}}\n\nЕкипът на Silvernoise",
                'body_en'    => "Hi {{first_name}},\n\nA payment of {{amount}} {{currency}} has been sent to IBAN {{iban}}.\n\nPeriod: {{period}}\n\nThe Silvernoise Team",
                'variables'  => json_encode(['{{first_name}}', '{{amount}}', '{{currency}}', '{{iban}}', '{{period}}']),
            ],
            [
                'key'        => 'release_approved',
                'name'       => 'Release Approved',
                'subject_bg' => '[Silvernoise] Релизът ти беше одобрен',
                'subject_en' => '[Silvernoise] Your Release Has Been Approved',
                'body_bg'    => "Здравей {{first_name}},\n\nРелизът „{{release_title}}" беше одобрен и е в процес на дистрибуция.\nОчаквай го в магазините до {{release_date}}.\n\nЕкипът на Silvernoise",
                'body_en'    => "Hi {{first_name}},\n\nYour release \"{{release_title}}\" has been approved and is now being distributed.\nExpect it to go live by {{release_date}}.\n\nThe Silvernoise Team",
                'variables'  => json_encode(['{{first_name}}', '{{release_title}}', '{{release_date}}']),
            ],
            [
                'key'        => 'quarterly_report',
                'name'       => 'Quarterly Report Ready',
                'subject_bg' => '[Silvernoise] Твоят отчет за {{period}} е готов',
                'subject_en' => '[Silvernoise] Your {{period}} Report Is Ready',
                'body_bg'    => "Здравей {{first_name}},\n\nОтчетът ти за периода {{period}} е наличен в Seller Central.\nОбщи приходи: {{total_earnings}} {{currency}}\n\nВлез в системата, за да го изтеглиш и да заявиш плащане.\n\nЕкипът на Silvernoise",
                'body_en'    => "Hi {{first_name}},\n\nYour report for {{period}} is now available in Seller Central.\nTotal earnings: {{total_earnings}} {{currency}}\n\nLog in to download it and request payment.\n\nThe Silvernoise Team",
                'variables'  => json_encode(['{{first_name}}', '{{period}}', '{{total_earnings}}', '{{currency}}']),
            ],
        ];

        foreach ($templates as $tpl) {
            EmailTemplate::firstOrCreate(['key' => $tpl['key']], $tpl);
        }
    }
}
