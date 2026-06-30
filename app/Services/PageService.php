<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PageService
{
    public const CACHE_KEY_PUBLISHED = 'pages:published';

    public const CACHE_KEY_MENU = 'pages:menu';

    public const CACHE_TTL = 3600;

    /**
     * All pages (admin view — includes unpublished).
     */
    public function all(): Collection
    {
        return Page::orderBy('sort_order')->get();
    }

    /**
     * All published pages (for public API).
     */
    public function publishedList(): Collection
    {
        return Cache::remember(self::CACHE_KEY_PUBLISHED, self::CACHE_TTL, function () {
            return Page::published()->orderBy('sort_order')->get([
                'id', 'title', 'slug', 'sort_order', 'show_in_menu',
            ]);
        });
    }

    /**
     * Published pages flagged for the public website menu.
     */
    public function menuList(): Collection
    {
        return Cache::remember(self::CACHE_KEY_MENU, self::CACHE_TTL, function () {
            return Page::published()
                ->where('show_in_menu', true)
                ->orderBy('sort_order')
                ->get(['id', 'title', 'slug', 'sort_order', 'show_in_menu']);
        });
    }

    /**
     * Find a published page by slug.
     */
    public function findBySlug(string $slug): ?Page
    {
        return Cache::remember($this->slugCacheKey($slug), self::CACHE_TTL, function () use ($slug) {
            return Page::published()->where('slug', $slug)->first();
        });
    }

    /**
     * Find any page by ID (admin).
     */
    public function findById(int $id): Page
    {
        return Page::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Page
    {
        $page = Page::create($data);
        $this->forgetCache($page->slug);

        return $page;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Page $page, array $data): Page
    {
        $oldSlug = $page->slug;
        $page->update($data);
        $page = $page->fresh();
        $this->forgetCache($oldSlug, $page->slug);

        return $page;
    }

    public function delete(Page $page): void
    {
        $slug = $page->slug;
        $page->delete();
        $this->forgetCache($slug);
    }

    public function forgetCache(string ...$slugs): void
    {
        Cache::forget(self::CACHE_KEY_PUBLISHED);
        Cache::forget(self::CACHE_KEY_MENU);

        foreach (array_unique(array_filter($slugs)) as $slug) {
            Cache::forget($this->slugCacheKey($slug));
        }

        app(FrontendRevalidationService::class)->revalidatePages(...$slugs);
    }

    private function slugCacheKey(string $slug): string
    {
        return 'pages:slug:' . $slug;
    }
}
