<?php
namespace App\Services;

/**
 * EngagementService

 * 

 * Hier zetten we formules.

 * Zo blijft SQL simpel en kan je later makkelijk aanpassen.

 */
class EngagementService {
    /**
     * Voeg engagement_rate toe aan elke post.

     * formule: (likes+comments+shares)/views*100

     */
    public function addEngagementRate(array $rows): array {
        foreach ($rows as &$r) {
            $views = (float)($r['views'] ?? 0);
            $likes = (float)($r['likes'] ?? 0);
            $comments = (float)($r['comments'] ?? 0);
            $shares = (float)($r['shares'] ?? 0);

            $rate = 0.0;
            if ($views > 0) {
                $rate = (($likes + $comments + $shares) / $views) * 100.0;
            }
            $r['engagement_rate'] = round($rate, 2);
        }
        return $rows;
    }

    /**
     * Maak een simpele overview van een set posts.

     * - avg views

     * - avg engagement

     * - top posts by engagement

     */
    public function overview(array $rows): array {
        $rows = $this->addEngagementRate($rows);

        $count = count($rows);
        if ($count === 0) {
            return ['count' => 0, 'avg_views' => 0, 'avg_engagement_rate' => 0, 'top' => []];
        }

        $sumViews = 0.0;
        $sumEng = 0.0;

        foreach ($rows as $r) {
            $sumViews += (float)($r['views'] ?? 0);
            $sumEng += (float)($r['engagement_rate'] ?? 0);
        }

        // sort by engagement_rate desc

        usort($rows, function($a, $b) { return $b['engagement_rate'] <=> $a['engagement_rate']; });

        return [
            'count' => $count,
            'avg_views' => round($sumViews / $count, 2),
            'avg_engagement_rate' => round($sumEng / $count, 2),
            'top' => array_slice($rows, 0, min(10, $count)),
        ];
    }
}
