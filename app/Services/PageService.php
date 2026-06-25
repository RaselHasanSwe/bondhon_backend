<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PageService
{
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
        return Page::published()->orderBy('sort_order')->get(['id', 'title', 'slug', 'sort_order']);
    }

    /**
     * Find a published page by slug (throws 404 if not found/not published).
     */
    public function findBySlug(string $slug): ?Page
    {
        return Page::published()->where('slug', $slug)->first();
    }

    /**
     * Find any page by ID (admin).
     */
    public function findById(int $id): Page
    {
        return Page::findOrFail($id);
    }

    /**
     * Update a page.
     *
     * @param array<string, mixed> $data
     */
    public function update(Page $page, array $data): Page
    {
        $page->update($data);

        return $page->fresh();
    }


    public function create(array $data): Page
    {
        return Page::create($data);
    }
}

