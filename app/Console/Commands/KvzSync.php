<?php

namespace App\Console\Commands;

use App\Jobs\SyncKvzReleases;
use Illuminate\Console\Command;

class KvzSync extends Command
{
    protected $signature   = 'kvz:sync {--page=1 : Start from this page}';
    protected $description = 'Sync releases from the KVZ Music API into the database';

    public function handle(): int
    {
        $apiKey = config('services.kvz.api_key');

        if (! $apiKey) {
            $this->error('KVZ_API_KEY is not set in .env');
            return self::FAILURE;
        }

        $page = (int) $this->option('page');

        $this->info("Dispatching KVZ sync job (starting from page {$page})...");

        SyncKvzReleases::dispatchSync(null, $page);

        $this->info('KVZ sync complete. Check logs/laravel.log for details.');

        return self::SUCCESS;
    }
}
