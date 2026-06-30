<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FrontendRevalidationService
{
    /**
     * Ask the Next.js frontend to purge its ISR/data cache for the given tags/paths.
     *
     * @param  list<string>  $tags
     * @param  list<string>  $paths
     */
    public function revalidate(array $tags = [], array $paths = []): void
    {
        $secret  = config('frontend.revalidate_secret');
        $baseUrl = config('frontend.base_url');

        if (empty($secret) || empty($baseUrl)) {
            return;
        }

        if ($tags === [] && $paths === []) {
            return;
        }

        try {
            Http::timeout(5)
                ->withHeaders(['x-revalidate-secret' => $secret])
                ->post(rtrim($baseUrl, '/') . '/api/revalidate', [
                    'tags'  => $tags,
                    'paths' => $paths,
                ])
                ->throw();
        } catch (\Throwable $e) {
            Log::warning('[FrontendRevalidation] Failed to revalidate frontend cache.', [
                'tags'  => $tags,
                'paths' => $paths,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function revalidatePages(string ...$slugs): void
    {
        $tags = ['cms-pages'];

        foreach (array_unique(array_filter($slugs)) as $slug) {
            $tags[] = 'cms-page:' . $slug;
        }

        $this->revalidate($tags, ['/']);
    }

    public function revalidateSettings(): void
    {
        $this->revalidate(['site-settings'], ['/']);
    }

    public function revalidateRecentMembers(): void
    {
        $this->revalidate(['recent-members'], ['/']);
    }
}
