<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\GlossaryService;

/**
 * GlossaryController - HTTP endpoints for glossary/terminology
 *
 * Provides REST API for glossary page frontend.
 * Helps users understand social media analytics terminology.
 *
 * Available endpoints:
 * - GET  /api/glossary/terms          - List all terms
 * - GET  /api/glossary/terms/:term    - Get single term details
 * - GET  /api/glossary/search?q=...   - Search terms
 * - GET  /api/glossary/category/:cat  - Get terms by category
 * - GET  /api/glossary/categories     - Get category list
 *
 * @package App\Controllers
 */
class GlossaryController {
    /**
     * Glossary service for business logic
     *
     * @var GlossaryService
     */
    private GlossaryService $glossaryService;

    /**
     * Constructor - Inject dependencies
     *
     * @param GlossaryService $glossaryService Service for glossary operations
     */
    public function __construct(GlossaryService $glossaryService) {
        $this->glossaryService = $glossaryService;
    }

    /**
     * List all glossary terms
     *
     * Endpoint: GET /api/glossary/terms
     *
     * Returns all terms ordered alphabetically.
     * No authentication required - glossary is public.
     *
     * Response (HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "total": 50,
     *   "terms": [
     *     {
     *       "id": 1,
     *       "term": "Engagement Rate",
     *       "definition": "Het percentage mensen dat interacteert...",
     *       "example": "Een post met 100 likes en 5000 views...",
     *       "category": "metric",
     *       "platforms": ["all"],
     *       "created_at": "2026-02-07 12:00:00",
     *       "updated_at": "2026-02-07 12:00:00"
     *     },
     *     ...
     *   ]
     * }
     * ```
     *
     * @return void Sends JSON response
     */
    public function index(): void {
        $terms = $this->glossaryService->getAllTerms();

        Response::json([
            'success' => true,
            'total' => count($terms),
            'terms' => $terms
        ]);
    }

    /**
     * Search glossary terms
     *
     * Endpoint: GET /api/glossary/search?q=engagement
     *
     * Searches across term names, definitions, and examples.
     * Case-insensitive, ordered by relevance.
     *
     * Query Parameters:
     * - q (required): Search query string
     *
     * Response (HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "query": "completion",
     *   "results": 3,
     *   "terms": [
     *     {
     *       "term": "Completion Rate",
     *       "definition": "Het percentage mensen dat je video helemaal...",
     *       "category": "metric",
     *       "platforms": ["tiktok", "instagram", "youtube"],
     *       ...
     *     }
     *   ]
     * }
     * ```
     *
     * Error Response (HTTP 400):
     * ```json
     * {
     *   "success": false,
     *   "error": "Search query is required"
     * }
     * ```
     *
     * @return void Sends JSON response
     */
    public function search(): void {
        $query = Request::get('q', '');

        if (empty(trim($query))) {
            Response::error('Search query is required', 400);
        }

        $terms = $this->glossaryService->searchTerms($query);

        Response::json([
            'success' => true,
            'query' => $query,
            'results' => count($terms),
            'terms' => $terms
        ]);
    }

    /**
     * Get single term by name
     *
     * Endpoint: GET /api/glossary/terms/:term
     *
     * Returns complete details for a specific term.
     * Useful for term detail popup/page.
     *
     * URL Parameters:
     * - :term - Exact term name (e.g., "Engagement Rate")
     *
     * Response (HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "term": {
     *     "id": 1,
     *     "term": "Engagement Rate",
     *     "definition": "Het percentage mensen...",
     *     "example": "Een post met 100 likes...",
     *     "category": "metric",
     *     "platforms": ["all"],
     *     "created_at": "2026-02-07 12:00:00",
     *     "updated_at": "2026-02-07 12:00:00"
     *   }
     * }
     * ```
     *
     * Error Response (HTTP 404):
     * ```json
     * {
     *   "success": false,
     *   "error": "Term not found"
     * }
     * ```
     *
     * @param string $termName Term name from URL
     *
     * @return void Sends JSON response
     */
    public function show(string $termName): void {
        // URL decode the term name (spaces encoded as %20)
        $termName = urldecode($termName);

        $term = $this->glossaryService->getTerm($termName);

        if (!$term) {
            Response::error('Term not found', 404);
        }

        Response::json([
            'success' => true,
            'term' => $term
        ]);
    }

    /**
     * Get terms by category
     *
     * Endpoint: GET /api/glossary/category/:category
     *
     * Returns all terms in a specific category.
     * Useful for category filtering in UI.
     *
     * URL Parameters:
     * - :category - Category name: 'metric', 'platform', 'general', 'business'
     *
     * Response (HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "category": "metric",
     *   "total": 18,
     *   "terms": [
     *     {
     *       "term": "Engagement Rate",
     *       "definition": "...",
     *       "category": "metric",
     *       ...
     *     }
     *   ]
     * }
     * ```
     *
     * Error Response (HTTP 400):
     * ```json
     * {
     *   "success": false,
     *   "error": "Invalid category. Valid categories: metric, platform, general, business"
     * }
     * ```
     *
     * @param string $category Category name from URL
     *
     * @return void Sends JSON response
     */
    public function byCategory(string $category): void {
        $validCategories = ['metric', 'platform', 'general', 'business'];

        if (!in_array($category, $validCategories)) {
            Response::error(
                'Invalid category. Valid categories: ' . implode(', ', $validCategories),
                400
            );
        }

        $terms = $this->glossaryService->getTermsByCategory($category);

        Response::json([
            'success' => true,
            'category' => $category,
            'total' => count($terms),
            'terms' => $terms
        ]);
    }

    /**
     * Get category list with counts
     *
     * Endpoint: GET /api/glossary/categories
     *
     * Returns list of categories with term counts.
     * Useful for navigation tabs/filters.
     *
     * Response (HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "categories": [
     *     {
     *       "name": "metric",
     *       "count": 18,
     *       "label": "Metrics"
     *     },
     *     {
     *       "name": "platform",
     *       "count": 5,
     *       "label": "Platforms"
     *     },
     *     {
     *       "name": "general",
     *       "count": 15,
     *       "label": "Algemeen"
     *     },
     *     {
     *       "name": "business",
     *       "count": 12,
     *       "label": "Business"
     *     }
     *   ]
     * }
     * ```
     *
     * @return void Sends JSON response
     */
    public function categories(): void {
        $counts = $this->glossaryService->getCategoryCounts();

        // Add friendly labels for frontend
        $categoryLabels = [
            'metric' => 'Metrics',
            'platform' => 'Platforms',
            'general' => 'Algemeen',
            'business' => 'Business'
        ];

        $categories = [];
        foreach ($counts as $name => $count) {
            $categories[] = [
                'name' => $name,
                'count' => $count,
                'label' => $categoryLabels[$name] ?? ucfirst($name)
            ];
        }

        Response::json([
            'success' => true,
            'categories' => $categories
        ]);
    }
}
