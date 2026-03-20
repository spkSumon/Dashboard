<?php
namespace App\Services;

use App\Core\Database;

/**
 * GlossaryService - Business logic for glossary/terminology management
 *
 * Provides plain-language definitions of social media analytics terms.
 * Helps non-technical users understand metrics and concepts.
 *
 * Features:
 * - 50+ pre-loaded terms with examples
 * - Category filtering (metric, platform, general, business)
 * - Platform-specific terms (TikTok, Instagram, Facebook, YouTube)
 * - Full-text search across terms and definitions
 *
 * @package App\Services
 */
class GlossaryService {
    /**
     * Database instance
     *
     * @var Database
     */
    private Database $db;

    /**
     * Constructor - Inject database dependency
     *
     * @param Database $db Database connection
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Get all glossary terms
     *
     * Returns all terms ordered alphabetically by term name.
     * Useful for displaying complete glossary page or dropdown list.
     *
     * @return array Array of term objects with all fields
     *
     * @example
     * ```php
     * $service = new GlossaryService($db);
     * $terms = $service->getAllTerms();
     * // Returns: [
     * //   {term: "Engagement Rate", definition: "...", category: "metric", ...},
     * //   {term: "Followers", definition: "...", category: "metric", ...},
     * //   ...
     * // ]
     * ```
     */
    public function getAllTerms(): array {
        $sql = "SELECT
                    id,
                    term,
                    definition,
                    example,
                    category,
                    platforms,
                    created_at,
                    updated_at
                FROM glossary
                ORDER BY term ASC";

        $terms = $this->db->fetchAll($sql);

        // Decode JSON platforms field
        return array_map(function($term) {
            $term['platforms'] = json_decode($term['platforms'], true);
            return $term;
        }, $terms);
    }

    /**
     * Search glossary terms by query string
     *
     * Searches across term names, definitions, and examples using LIKE matching.
     * Case-insensitive search.
     *
     * @param string $query Search query (e.g., "engagement", "TikTok")
     *
     * @return array Array of matching terms ordered by relevance
     *               - Exact term matches first
     *               - Then term partial matches
     *               - Then definition matches
     *
     * @example
     * ```php
     * $results = $service->searchTerms("completion");
     * // Returns terms containing "completion" in term/definition/example
     * ```
     */
    public function searchTerms(string $query): array {
        if (empty(trim($query))) {
            return [];
        }

        $searchPattern = '%' . $query . '%';

        $sql = "SELECT
                    id,
                    term,
                    definition,
                    example,
                    category,
                    platforms,
                    created_at,
                    updated_at,
                    -- Relevance score for ordering
                    CASE
                        WHEN term LIKE ? THEN 3          -- Exact term match (highest)
                        WHEN definition LIKE ? THEN 2     -- Definition match
                        WHEN example LIKE ? THEN 1        -- Example match
                        ELSE 0
                    END as relevance
                FROM glossary
                WHERE term LIKE ?
                   OR definition LIKE ?
                   OR example LIKE ?
                ORDER BY relevance DESC, term ASC";

        $terms = $this->db->fetchAll($sql, "ssssss", [
            $searchPattern,
            $searchPattern,
            $searchPattern,
            $searchPattern,
            $searchPattern,
            $searchPattern
        ]);

        // Decode JSON platforms and remove relevance score
        return array_map(function($term) {
            $term['platforms'] = json_decode($term['platforms'], true);
            unset($term['relevance']);
            return $term;
        }, $terms);
    }

    /**
     * Get terms by category
     *
     * Returns all terms in a specific category.
     *
     * @param string $category Category name: 'metric', 'platform', 'general', 'business'
     *
     * @return array Array of terms in the category, ordered alphabetically
     *
     * @example
     * ```php
     * $metrics = $service->getTermsByCategory('metric');
     * // Returns: [
     * //   {term: "Engagement Rate", ...},
     * //   {term: "Followers", ...},
     * //   ...
     * // ]
     * ```
     */
    public function getTermsByCategory(string $category): array {
        $validCategories = ['metric', 'platform', 'general', 'business'];

        if (!in_array($category, $validCategories)) {
            return [];
        }

        $sql = "SELECT
                    id,
                    term,
                    definition,
                    example,
                    category,
                    platforms,
                    created_at,
                    updated_at
                FROM glossary
                WHERE category = ?
                ORDER BY term ASC";

        $terms = $this->db->fetchAll($sql, "s", [$category]);

        // Decode JSON platforms field
        return array_map(function($term) {
            $term['platforms'] = json_decode($term['platforms'], true);
            return $term;
        }, $terms);
    }

    /**
     * Get a single term by exact term name
     *
     * Returns complete details for a specific glossary term.
     * Case-insensitive lookup.
     *
     * @param string $termName Exact term name (e.g., "Engagement Rate")
     *
     * @return array|null Term object with all fields, or null if not found
     *
     * @example
     * ```php
     * $term = $service->getTerm("Engagement Rate");
     * // Returns: {
     * //   id: 1,
     * //   term: "Engagement Rate",
     * //   definition: "Het percentage mensen...",
     * //   example: "Een post met 100 likes...",
     * //   category: "metric",
     * //   platforms: ["all"],
     * //   created_at: "2026-02-07 12:00:00",
     * //   updated_at: "2026-02-07 12:00:00"
     * // }
     * ```
     */
    public function getTerm(string $termName): ?array {
        if (empty(trim($termName))) {
            return null;
        }

        $sql = "SELECT
                    id,
                    term,
                    definition,
                    example,
                    category,
                    platforms,
                    created_at,
                    updated_at
                FROM glossary
                WHERE term = ?
                LIMIT 1";

        $term = $this->db->fetchOne($sql, "s", [$termName]);

        if (!$term) {
            return null;
        }

        // Decode JSON platforms field
        $term['platforms'] = json_decode($term['platforms'], true);

        return $term;
    }

    /**
     * Get category counts for navigation
     *
     * Returns count of terms per category for UI navigation/filtering.
     *
     * @return array Associative array: ['metric' => 20, 'platform' => 5, ...]
     *
     * @example
     * ```php
     * $counts = $service->getCategoryCounts();
     * // Returns: {
     * //   "metric": 18,
     * //   "platform": 5,
     * //   "general": 15,
     * //   "business": 12
     * // }
     * ```
     */
    public function getCategoryCounts(): array {
        $sql = "SELECT category, COUNT(*) as count
                FROM glossary
                GROUP BY category
                ORDER BY category ASC";

        $results = $this->db->fetchAll($sql);

        // Transform to associative array
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['category']] = (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Get available categories
     *
     * Returns list of all categories present in glossary.
     *
     * @return array Array of category names
     *
     * @example
     * ```php
     * $categories = $service->getCategories();
     * // Returns: ["metric", "platform", "general", "business"]
     * ```
     */
    public function getCategories(): array {
        $sql = "SELECT DISTINCT category
                FROM glossary
                ORDER BY category ASC";

        $results = $this->db->fetchAll($sql);

        return array_column($results, 'category');
    }
}
