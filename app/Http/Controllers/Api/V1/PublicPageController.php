<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PageService;
use Illuminate\Http\JsonResponse;

class PublicPageController extends Controller
{
    public function __construct(private readonly PageService $pageService) {}

    /**
     * GET /api/v1/pages
     * Returns all published pages. Pass ?menu=1 for menu-only pages.
     */
    public function index(): JsonResponse
    {
        $pages = request()->boolean('menu')
            ? $this->pageService->menuList()
            : $this->pageService->publishedList();

        return response()->json([
            'success' => true,
            'data'    => $pages,
            'message' => 'Pages retrieved successfully.',
            'errors'  => [],
        ]);
    }

    /**
     * GET /api/v1/pages/{slug}
     * Returns a single published page by slug. No auth required.
     */
    public function show(string $slug): JsonResponse
    {
        $page = $this->pageService->findBySlug($slug);

        if (! $page) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Page not found.',
                'errors'  => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $page,
            'message' => 'Page retrieved successfully.',
            'errors'  => [],
        ]);
    }
}

