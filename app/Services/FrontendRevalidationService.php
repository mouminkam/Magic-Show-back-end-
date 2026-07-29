<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Triggers Next.js on-demand revalidation when Backend cache is invalidated.
 * Sends POST to Next.js /api/revalidate so frontend cache is cleared immediately.
 * Runs asynchronously after response (fire-and-forget) so Admin gets fast feedback.
 */
class FrontendRevalidationService
{
    public static function revalidate(array $tags): void
    {
        $url = config('services.nextjs.revalidate_url');
        $secret = config('services.nextjs.revalidation_secret');

        if (empty($url) || empty($secret) || empty($tags)) {
            return;
        }

        dispatch(function () use ($url, $secret, $tags) {
            try {
                Http::timeout(5)
                    ->post($url, [
                        'secret' => $secret,
                        'tags' => $tags,
                    ]);
            } catch (\Throwable $e) {
                Log::warning('Frontend revalidation failed', [
                    'tags' => $tags,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }
}
