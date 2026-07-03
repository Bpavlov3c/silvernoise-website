<?php

namespace App\Jobs;

use App\Models\ApiLog;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Label;
use App\Models\Release;
use App\Models\Track;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Syncs releases from the KVZ Music REST API into the local database.
 *
 * KVZ API field reference (from OpenAPI spec):
 *   Release: cover_art, upc, catalogid, title, title_version, label,
 *            copyright_c_line, copyright_p_line, status, genres, artists[], tracks[]
 *   Artist:  artist_name, role, primary (1/0)
 *   Track:   isrc, volume_number, track_number, title, title_version,
 *            language, explicit_lyrics (0/1), genres[], artists[]
 *
 * KVZ statuses: pending, approved, delivered, taken_down,
 *               media_pool, archive, for_takedown, unknown
 */
class SyncKvzReleases implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 3600;

    public function __construct(
        private readonly ?int $triggeredBy = null,
        private readonly int  $startPage   = 1,
    ) {}

    public function handle(): void
    {
        $apiKey  = config('services.kvz.api_key');
        $baseUrl = config('services.kvz.base_url', 'https://api.kvzmusic.com/rest');

        if (! $apiKey) {
            Log::error('KVZ sync aborted: KVZ_API_KEY not configured.');
            return;
        }

        $page       = $this->startPage;
        $totalPages = null;
        $synced     = 0;

        do {
            $start = microtime(true);

            $response = Http::withHeaders(['X-KVZ-APIKey' => $apiKey])
                ->timeout(30)
                ->get("{$baseUrl}/releases", ['page' => $page]);

            $elapsed = (int) ((microtime(true) - $start) * 1000);

            ApiLog::create([
                'source'           => 'kvz',
                'endpoint'         => "/releases?page={$page}",
                'method'           => 'GET',
                'status_code'      => $response->status(),
                'response_time_ms' => $elapsed,
                'triggered_by'     => $this->triggeredBy,
                'error_message'    => $response->failed() ? $response->body() : null,
            ]);

            if ($response->failed()) {
                Log::error("KVZ sync failed on page {$page}: " . $response->body());
                break;
            }

            $data = $response->json();

            // KVZ response envelope: status, message, page, per_page, total_pages, count, total, releases[]
            if ($totalPages === null) {
                $totalPages = $data['total_pages'] ?? 1;
            }

            foreach ($data['releases'] ?? [] as $raw) {
                try {
                    $this->upsertRelease($raw);
                    $synced++;
                } catch (\Throwable $e) {
                    Log::warning("KVZ sync: failed to upsert release UPC={$raw['upc']}: " . $e->getMessage());
                }
            }

            Log::info("KVZ sync: page {$page}/{$totalPages}, total synced: {$synced}");
            $page++;

            usleep(200000); // 200ms between pages

        } while ($page <= $totalPages);

        Log::info("KVZ sync complete. Total releases synced: {$synced}.");
    }

    private function upsertRelease(array $raw): void
    {
        // ── Extract KVZ numeric ID from cover_art URL ──────────────────────
        // cover_art = "https://api.kvzmusic.com/rest/releases/1001/cover_art"
        $kvzId = null;
        if (! empty($raw['cover_art'])) {
            preg_match('/\/releases\/(\d+)\/cover_art/', $raw['cover_art'], $matches);
            $kvzId = $matches[1] ?? null;
        }

        // ── Resolve or create label ───────────────────────────────────────
        $label = null;
        if (! empty($raw['label'])) {
            $label = Label::firstOrCreate(
                ['slug' => Str::slug($raw['label'])],
                ['name' => $raw['label']]
            );
        }

        // ── Build release data using correct KVZ field names ─────────────
        $releaseData = [
            // KVZ field: catalogid  (NOT catalog_id)
            'catalog_id'            => $raw['catalogid'] ?? null,
            'upc'                   => $raw['upc'] ?? null,
            'title'                 => $raw['title'] ?? 'Unknown',
            'title_version'         => ($raw['title_version'] ?? '') ?: null,

            // KVZ field: copyright_c_line  (NOT copyright_c)
            'copyright_c'           => $raw['copyright_c_line'] ?? null,
            // KVZ field: copyright_p_line  (NOT copyright_p)
            'copyright_p'           => $raw['copyright_p_line'] ?? null,

            // KVZ cover_art is a URL that requires the API key to fetch.
            // Store it so we can proxy/cache the image server-side later.
            'cover_art_url'         => $raw['cover_art'] ?? null,

            'status'                => $this->mapStatus($raw['status'] ?? 'unknown'),
            'label_id'              => $label?->id,

            // customer_id required — defaults to admin (id=1) for KVZ imports.
            // Match to real customer via label assignment in Admin Central.
            'customer_id'           => 1,

            'kvz_id'                => $kvzId,
            'kvz_synced_at'         => now(),
            'kvz_raw'               => $raw,
        ];

        // Anchor upsert on kvz_id if available, otherwise fall back to UPC
        $matchKey = $kvzId
            ? ['kvz_id' => $kvzId]
            : ['upc'    => $raw['upc'] ?? null];

        $release = Release::updateOrCreate($matchKey, $releaseData);

        // ── Sync genres ───────────────────────────────────────────────────
        if (! empty($raw['genres'])) {
            $release->genres()->sync($this->resolveGenreIds($raw['genres']));
        }

        // ── Sync artists ──────────────────────────────────────────────────
        if (! empty($raw['artists'])) {
            // KVZ artist fields: artist_name, role, primary (1/0)
            $release->artists()->sync($this->buildArtistPivot($raw['artists']));
        }

        // ── Sync tracks ───────────────────────────────────────────────────
        if (! empty($raw['tracks'])) {
            foreach ($raw['tracks'] as $trackRaw) {
                $this->upsertTrack($release->id, $trackRaw);
            }
        }
    }

    private function upsertTrack(int $releaseId, array $raw): void
    {
        $trackData = [
            'release_id'     => $releaseId,
            // KVZ field: volume_number  (= disc_number)
            'disc_number'    => $raw['volume_number'] ?? 1,
            'track_number'   => $raw['track_number'] ?? 1,
            'title'          => $raw['title'] ?? 'Unknown',
            'title_version'  => ($raw['title_version'] ?? '') ?: null,
            // KVZ field: language  (full name string, e.g. "English", "Bulgarian")
            'audio_language' => $raw['language'] ?? null,
            // KVZ field: explicit_lyrics  (0 or 1)
            'explicit_lyrics'=> ($raw['explicit_lyrics'] ?? 0) === 1,
        ];

        // Upsert anchor: ISRC if present, otherwise position within release
        $matchKey = ! empty($raw['isrc'])
            ? ['isrc' => $raw['isrc']]
            : [
                'release_id'   => $releaseId,
                'disc_number'  => $trackData['disc_number'],
                'track_number' => $trackData['track_number'],
            ];

        $track = Track::updateOrCreate($matchKey, $trackData);

        if (! empty($raw['genres'])) {
            $track->genres()->sync($this->resolveGenreIds($raw['genres']));
        }

        if (! empty($raw['artists'])) {
            $track->artists()->sync($this->buildArtistPivot($raw['artists']));
        }
    }

    /**
     * Build pivot array for artist sync.
     * KVZ artist: { artist_name, role, primary }
     */
    private function buildArtistPivot(array $artists): array
    {
        $pivot = [];
        foreach ($artists as $i => $artistData) {
            // KVZ field: artist_name  (NOT name)
            $name   = $artistData['artist_name'] ?? 'Unknown';
            $artist = Artist::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $pivot[$artist->id] = [
                'role'       => $artistData['role'] ?? 'Performer',
                // KVZ field: primary (1/0 integer)
                'is_primary' => ($artistData['primary'] ?? 0) === 1,
                'sort_order' => $i,
            ];
        }
        return $pivot;
    }

    /**
     * Resolve genre names to IDs, creating missing genres on the fly.
     */
    private function resolveGenreIds(array $genreNames): array
    {
        return collect($genreNames)->map(function (string $name) {
            return Genre::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            )->id;
        })->toArray();
    }

    /**
     * Map KVZ release statuses to internal Silvernoise statuses.
     *
     * KVZ statuses (from OpenAPI spec):
     *   pending, approved, delivered, taken_down,
     *   media_pool, archive, for_takedown, unknown
     */
    private function mapStatus(string $kvzStatus): string
    {
        return match ($kvzStatus) {
            'pending'      => 'pending',
            'approved'     => 'approved',
            'delivered'    => 'delivered',
            'media_pool'   => 'delivered',   // pooled = delivered but not yet live
            'taken_down'   => 'takedown',
            'for_takedown' => 'takedown',    // takedown requested, treat as taken down
            'archive'      => 'takedown',    // archived = effectively taken down
            default        => 'draft',       // 'unknown' and anything else
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('KVZ sync job failed: ' . $exception->getMessage());

        ApiLog::create([
            'source'        => 'kvz',
            'endpoint'      => '/releases',
            'method'        => 'GET',
            'error_message' => $exception->getMessage(),
            'triggered_by'  => $this->triggeredBy,
        ]);
    }
}
