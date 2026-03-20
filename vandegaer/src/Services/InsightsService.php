<?php
namespace App\Services;

/**
 * InsightsService - Generates actionable insights and recommendations
 *
 * Converts raw analytics data into plain language insights with context
 * and actionable recommendations for business users.
 *
 * Key principles:
 * - Plain language, no technical jargon
 * - Context with benchmarks (industry averages)
 * - Actionable recommendations ("Post more Reels" not just "Reels perform well")
 * - Visual indicators (emoji, status colors)
 *
 * @package App\Services
 */
class InsightsService {

    /**
     * 2026 Social Media Benchmarks (from MEMORY.md)
     */
    private const BENCHMARKS = [
        'tiktok' => [
            'avg_engagement' => 3.7,
            'good_engagement' => 5.3,
            'excellent_engagement' => 7.0,
            'avg_completion_rate' => 60,
            'good_completion_rate' => 75
        ],
        'instagram' => [
            'avg_engagement' => 0.48,
            'good_engagement' => 3.0,
            'excellent_engagement' => 6.0
        ],
        'facebook' => [
            'avg_engagement' => 0.15,
            'good_engagement' => 0.5,
            'excellent_engagement' => 1.0
        ],
        'overall' => [
            'avg_engagement' => 1.8,
            'good_engagement' => 3.0,
            'excellent_engagement' => 5.0
        ]
    ];

    /**
     * Generate comprehensive insights from analytics data
     *
     * @param array $posts All posts data
     * @param array $hashtags Top hashtags performance
     * @param array $postTypes Post type statistics
     * @return array Insights array with plain language recommendations
     */
    public function generateInsights(array $posts, array $hashtags, array $postTypes): array {
        $insights = [];

        // 1. Overall performance vs benchmark
        $overallInsight = $this->analyzeOverallPerformance($posts);
        if ($overallInsight) $insights[] = $overallInsight;

        // 2. Best performing content type
        $contentTypeInsight = $this->analyzeBestContentType($postTypes);
        if ($contentTypeInsight) $insights[] = $contentTypeInsight;

        // 3. Top hashtag recommendation
        $hashtagInsight = $this->analyzeTopHashtag($hashtags);
        if ($hashtagInsight) $insights[] = $hashtagInsight;

        // 4. Best posting time
        $timingInsight = $this->analyzeBestPostingTime($posts);
        if ($timingInsight) $insights[] = $timingInsight;

        // 5. Engagement quality assessment
        $qualityInsight = $this->analyzeEngagementQuality($posts);
        if ($qualityInsight) $insights[] = $qualityInsight;

        return $insights;
    }

    /**
     * Analyze overall performance vs industry benchmark
     */
    private function analyzeOverallPerformance(array $posts): ?array {
        if (empty($posts)) return null;

        // Calculate average engagement rate
        $totalEngagement = 0;
        $count = 0;
        $platform = null;

        foreach ($posts as $post) {
            if ($post['views'] > 0) {
                $engagement = (($post['likes'] + $post['comments'] + $post['shares']) / $post['views']) * 100;
                $totalEngagement += $engagement;
                $count++;
                if (!$platform) $platform = $post['platform'];
            }
        }

        if ($count === 0) return null;
        $avgEngagement = $totalEngagement / $count;

        // Get benchmark for platform
        $benchmark = $this->getBenchmark($platform);
        $ratio = $avgEngagement / $benchmark['avg_engagement'];

        // Determine status and message
        if ($avgEngagement >= $benchmark['excellent_engagement']) {
            $status = 'excellent';
            $icon = ''; // Removed emoji
            $multiplier = round($ratio, 1);
            $message = "Fantastisch! Je engagement is {$multiplier}× beter dan gemiddeld";
            $recommendation = "Blijf doen wat je doet - dit werkt perfect!";
        } elseif ($avgEngagement >= $benchmark['good_engagement']) {
            $status = 'good';
            $icon = ''; // Removed emoji
            $multiplier = round($ratio, 1);
            $message = "Goed bezig! Je scoort {$multiplier}× beter dan gemiddeld";
            $recommendation = "Analyseer je best presterende posts en herhaal die strategie.";
        } elseif ($avgEngagement >= $benchmark['avg_engagement']) {
            $status = 'average';
            $icon = ''; // Removed emoji
            $message = "Je zit rond het gemiddelde (engagement: " . round($avgEngagement, 2) . "%)";
            $recommendation = "Probeer meer video's en gebruik trending hashtags.";
        } else {
            $status = 'below_average';
            $icon = ''; // Removed emoji
            $percentage = round(($benchmark['avg_engagement'] - $avgEngagement) / $benchmark['avg_engagement'] * 100);
            $message = "Je engagement ligt {$percentage}% onder het gemiddelde";
            $recommendation = "Focus op kwaliteit boven kwantiteit. Post minder, maar beter.";
        }

        return [
            'type' => 'performance',
            'title' => 'Algemene Prestaties',
            'status' => $status,
            'icon' => $icon,
            'message' => $message,
            'recommendation' => $recommendation,
            'metrics' => [
                'your_engagement' => round($avgEngagement, 2),
                'benchmark' => $benchmark['avg_engagement'],
                'difference' => round($avgEngagement - $benchmark['avg_engagement'], 2)
            ]
        ];
    }

    /**
     * Analyze which content type performs best
     */
    private function analyzeBestContentType(array $postTypes): ?array {
        if (empty($postTypes)) return null;

        // Find best performing type
        $best = null;
        $bestEngagement = 0;

        foreach ($postTypes as $type) {
            if ($type['avg_engagement_rate'] > $bestEngagement) {
                $bestEngagement = $type['avg_engagement_rate'];
                $best = $type;
            }
        }

        if (!$best || $best['post_type'] === 'unknown') return null;

        // Calculate multiplier vs other types
        $others = array_filter($postTypes, fn($t) => $t['post_type'] !== $best['post_type'] && $t['post_type'] !== 'unknown');
        if (empty($others)) return null;

        $avgOthers = array_sum(array_column($others, 'avg_engagement_rate')) / count($others);

        // FIX: Prevent divide by zero and handle edge cases
        if ($avgOthers === 0 || $avgOthers === 0.0) {
            // Not enough data to compare
            return null;
        }

        $multiplier = round($bestEngagement / $avgOthers, 1);

        // FIX: If multiplier is still 0 or invalid, skip this insight
        if ($multiplier === 0 || $multiplier === 0.0) {
            return null;
        }

        $typeName = $this->translatePostType($best['post_type']);

        return [
            'type' => 'content_type',
            'title' => 'Beste Content Type',
            'status' => 'tip',
            'icon' => '', // Removed emoji
            'message' => "{$typeName} krijgen {$multiplier}× meer engagement",
            'recommendation' => "Post minimaal 3 {$typeName} per week voor maximaal bereik.",
            'metrics' => [
                'best_type' => $best['post_type'],
                'engagement' => round($bestEngagement, 2),
                'multiplier' => $multiplier,
                'posts_count' => $best['posts']
            ]
        ];
    }

    /**
     * Analyze top performing hashtag
     */
    private function analyzeTopHashtag(array $hashtags): ?array {
        if (empty($hashtags)) return null;

        $top = $hashtags[0];
        $avgViews = round($top['avg_views']);

        return [
            'type' => 'hashtag',
            'title' => 'Top Hashtag',
            'status' => 'success',
            'icon' => '', // Removed emoji
            'message' => "#{$top['hashtag']} haalt gemiddeld " . number_format($avgViews, 0, ',', '.') . " views",
            'recommendation' => "Gebruik #{$top['hashtag']} in je volgende posts voor maximaal bereik.",
            'metrics' => [
                'hashtag' => $top['hashtag'],
                'avg_views' => $avgViews,
                'total_posts' => $top['total_posts'],
                'engagement_rate' => round($top['avg_engagement_rate'], 2)
            ]
        ];
    }

    /**
     * Analyze best posting time based on historical data
     */
    private function analyzeBestPostingTime(array $posts): ?array {
        if (empty($posts)) return null;

        // Group by day of week and hour
        $timeStats = [];

        foreach ($posts as $post) {
            if (empty($post['posted_date']) || $post['views'] === 0) continue;

            $timestamp = strtotime($post['posted_date']);
            $dayOfWeek = date('N', $timestamp); // 1=Monday, 7=Sunday
            $hour = !empty($post['posted_time']) ? (int)substr($post['posted_time'], 0, 2) : 12;

            $key = $dayOfWeek . '_' . $hour;
            if (!isset($timeStats[$key])) {
                $timeStats[$key] = [
                    'day' => $dayOfWeek,
                    'hour' => $hour,
                    'views' => 0,
                    'engagement' => 0,
                    'count' => 0
                ];
            }

            $engagement = (($post['likes'] + $post['comments'] + $post['shares']) / $post['views']) * 100;
            $timeStats[$key]['views'] += $post['views'];
            $timeStats[$key]['engagement'] += $engagement;
            $timeStats[$key]['count']++;
        }

        if (empty($timeStats)) return null;

        // Find best time slot (by average engagement)
        $best = null;
        $bestAvgEngagement = 0;

        foreach ($timeStats as $slot) {
            $avgEngagement = $slot['engagement'] / $slot['count'];
            if ($avgEngagement > $bestAvgEngagement) {
                $bestAvgEngagement = $avgEngagement;
                $best = $slot;
            }
        }

        if (!$best) return null;

        $dayName = $this->getDayName($best['day']);
        $timeRange = $best['hour'] . ':00-' . ($best['hour'] + 1) . ':00';

        return [
            'type' => 'timing',
            'title' => 'Beste Post Tijd',
            'status' => 'tip',
            'icon' => '', // Removed emoji
            'message' => "Posts op {$dayName} om {$best['hour']}:00 presteren het best",
            'recommendation' => "Plan je belangrijkste content voor {$dayName} tussen {$timeRange}.",
            'metrics' => [
                'day' => $dayName,
                'hour' => $best['hour'],
                'avg_engagement' => round($bestAvgEngagement, 2),
                'sample_size' => $best['count']
            ]
        ];
    }

    /**
     * Analyze engagement quality metrics (completion rate, etc)
     */
    private function analyzeEngagementQuality(array $posts): ?array {
        // Check if we have completion_rate data (2026 algorithm metrics)
        $hasCompletionRate = false;
        $totalCompletion = 0;
        $count = 0;

        foreach ($posts as $post) {
            if (isset($post['completion_rate']) && $post['completion_rate'] > 0) {
                $hasCompletionRate = true;
                $totalCompletion += $post['completion_rate'];
                $count++;
            }
        }

        if (!$hasCompletionRate || $count === 0) {
            // Fallback: analyze saves count (high-intent signal)
            return $this->analyzeSavesCount($posts);
        }

        $avgCompletion = $totalCompletion / $count;
        $benchmark = 60; // TikTok average
        $goodBenchmark = 75;

        if ($avgCompletion >= $goodBenchmark) {
            $status = 'excellent';
            $icon = ''; // Removed emoji
            $message = "{$avgCompletion}% kijkt tot het einde - uitstekend!";
            $recommendation = "Je content is super engaging. Blijf deze stijl gebruiken.";
        } elseif ($avgCompletion >= $benchmark) {
            $status = 'good';
            $icon = ''; // Removed emoji
            $message = "{$avgCompletion}% kijkt tot het einde - goed bezig";
            $recommendation = "Probeer de eerste 3 seconden nog pakkender te maken.";
        } else {
            $status = 'warning';
            $icon = ''; // Removed emoji
            $percentage = round((($benchmark - $avgCompletion) / $benchmark) * 100);
            $message = "Completion rate is {$percentage}% onder gemiddeld";
            $recommendation = "Maak video's korter (15-30 sec) en start met een hook.";
        }

        return [
            'type' => 'quality',
            'title' => 'Engagement Kwaliteit',
            'status' => $status,
            'icon' => $icon,
            'message' => $message,
            'recommendation' => $recommendation,
            'metrics' => [
                'completion_rate' => round($avgCompletion, 1),
                'benchmark' => $benchmark,
                'sample_size' => $count
            ]
        ];
    }

    /**
     * Fallback quality analysis using saves count
     */
    private function analyzeSavesCount(array $posts): ?array {
        $totalSaves = 0;
        $totalViews = 0;

        foreach ($posts as $post) {
            if ($post['views'] > 0) {
                $totalSaves += $post['saves'] ?? 0;
                $totalViews += $post['views'];
            }
        }

        if ($totalViews === 0) return null;

        // FIX: Format save rate to 3 decimal places for readability
        $saveRate = ($totalSaves / $totalViews) * 100;
        $saveRateFormatted = round($saveRate, 3); // e.g., 0.002% instead of 0.0021809027691234%

        $benchmark = 2.1; // Industry average
        $goodBenchmark = 4.0;

        if ($saveRate >= $goodBenchmark) {
            $status = 'excellent';
            $icon = ''; // Removed emoji
            $message = "{$saveRateFormatted}% bewaart je posts - top!";
            $recommendation = "Je content is waardevol. Maak meer 'save-worthy' content.";
        } elseif ($saveRate >= $benchmark) {
            $status = 'good';
            $icon = ''; // Removed emoji
            $message = "{$saveRateFormatted}% bewaart je posts - goed";
            $recommendation = "Creëer meer 'how-to' en tutorial content voor meer saves.";
        } else {
            $status = 'average';
            $icon = ''; // Removed emoji
            $message = "Save rate: {$saveRateFormatted}% (laag)";
            $recommendation = "Maak meer educatieve en praktische content die mensen willen bewaren.";
        }

        return [
            'type' => 'quality',
            'title' => 'Content Waarde',
            'status' => $status,
            'icon' => $icon,
            'message' => $message,
            'recommendation' => $recommendation,
            'metrics' => [
                'save_rate' => round($saveRate, 2),
                'benchmark' => $benchmark,
                'total_saves' => $totalSaves
            ]
        ];
    }

    /**
     * Get benchmark for platform
     */
    private function getBenchmark(string $platform): array {
        return self::BENCHMARKS[$platform] ?? self::BENCHMARKS['overall'];
    }

    /**
     * Translate post type to Dutch
     */
    private function translatePostType(string $type): string {
        $translations = [
            'reel' => 'Reels',
            'video' => "Video's",
            'image' => "Foto's",
            'carousel' => 'Carousels',
            'story' => "Stories",
            'post' => 'Posts'
        ];

        return $translations[$type] ?? ucfirst($type);
    }

    /**
     * Get Dutch day name from day number (1=Monday, 7=Sunday)
     */
    private function getDayName(int $day): string {
        $days = [
            1 => 'maandag',
            2 => 'dinsdag',
            3 => 'woensdag',
            4 => 'donderdag',
            5 => 'vrijdag',
            6 => 'zaterdag',
            7 => 'zondag'
        ];

        return $days[$day] ?? 'onbekend';
    }
}
