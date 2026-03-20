<?php
namespace App\Services;

use App\Core\Database;

/**
 * Generic CSV Importer - supports multiple CSV formats:
 * 1. TikTok Studio export (post_id, views, likes, etc.)
 * 2. content_tracker_enhanced.csv (Content_ID, Bereik, Likes, etc.)
 */
class GenericCsvImporter {
    private $db;
    public function __construct(Database $db) {
        $this->db = $db;
    }

    private function normalizeHeader(array $header): array {
        $out = [];
        foreach ($header as $i => $h) {
            $k = (string)$h;
            // Strip BOM from first column
            if ($i === 0) {
                $k = preg_replace('/^\xEF\xBB\xBF/', '', $k);
            }
            $k = strtolower(trim($k));
            $k = preg_replace('/\s+/', '_', $k);
            $out[] = $k;
        }
        return $out;
    }

    private function v($x): ?string {
        if ($x === null) return null;
        $x = trim((string)$x);
        return $x === '' ? null : $x;
    }

    private function toFloat($x): float {
        if ($x === null) return 0.0;
        $x = trim((string)$x);
        $x = str_replace(',', '.', $x);
        return (float)$x;
    }

    private function toInt($x): int {
        if ($x === null) return 0;
        $x = trim((string)$x);
        $x = str_replace(',', '.', $x);
        return (int)floatval($x);
    }

    /**
     * Detect CSV format based on headers
     */
    private function detectFormat(array $headers): string {
        $headers_lower = array_map('strtolower', $headers);

        // Check for content_tracker format
        if (in_array('content_id', $headers_lower) && (in_array('bereik', $headers_lower) || in_array('platform', $headers_lower))) {
            return 'content_tracker';
        }

        // Check for TikTok export format
        if (in_array('post_id', $headers_lower) || in_array('video_id', $headers_lower)) {
            return 'tiktok_export';
        }

        return 'unknown';
    }

    /**
     * Map content_tracker row to standard format
     */
    private function mapContentTrackerRow(array $rec): ?array {
        // Only import posts that are published (not "Gepland")
        $status = strtolower($this->v($rec['status'] ?? '') ?? '');
        if ($status === 'gepland' || $status === '') {
            return null; // Skip planned posts
        }

        $platform = strtolower($this->v($rec['platform'] ?? '') ?? '');
        if (!$platform || !in_array($platform, ['instagram', 'tiktok', 'facebook'])) {
            return null; // Skip if no valid platform
        }

        $post_id = $this->v($rec['content_id'] ?? null);
        if (!$post_id) return null;

        // Parse date (format: 2024-04-08 or other variations)
        $date = $this->v($rec['datum'] ?? null);
        $time = $this->v($rec['post_tijd'] ?? null);

        // Content type -> post_type
        $postType = $this->v($rec['content_type'] ?? null);

        // Post_Onderwerp -> topic, Caption_Kort -> caption
        $topic = $this->v($rec['post_onderwerp'] ?? null);
        $caption = $this->v($rec['caption_kort'] ?? $rec['post_onderwerp'] ?? null);

        // Hashtags
        $hashtags = $this->v($rec['hashtags'] ?? null);

        // Metrics - bereik = reach, video_views = views
        $reach = $this->toInt($rec['bereik'] ?? 0);
        $videoViews = $this->toInt($rec['video_views'] ?? 0);
        $views = $videoViews > 0 ? $videoViews : $reach;

        $likes = $this->toInt($rec['likes'] ?? 0);
        $comments = $this->toInt($rec['comments'] ?? 0);
        $shares = $this->toInt($rec['shares'] ?? 0);
        $saves = $this->toInt($rec['saves'] ?? 0);
        $impressions = $this->toInt($rec['impressies'] ?? 0);

        // Engagement rate
        $engagement = $this->toFloat($rec['engagement'] ?? 0);

        // Internal notes
        $internalNotes = $this->v($rec['notities'] ?? null);

        return [
            'platform' => $platform,
            'platform_post_id' => $post_id,
            'post_url' => null,
            'post_type' => $postType,
            'topic' => $topic,
            'caption' => $caption,
            'hashtags' => $hashtags,
            'posted_date' => $date,
            'posted_time' => $time,
            'views' => $views,
            'likes' => $likes,
            'comments' => $comments,
            'shares' => $shares,
            'saves' => $saves,
            'reach' => $reach,
            'impressions' => $impressions,
            'engagement_rate' => $engagement,
            'internal_notes' => $internalNotes,
            'snapshot_date' => date('Y-m-d')
        ];
    }

    /**
     * Map TikTok export row to standard format
     */
    private function mapTikTokExportRow(array $rec): ?array {
        $post_id = $this->v($rec['post_id'] ?? $rec['video_id'] ?? $rec['id'] ?? null);
        if (!$post_id) return null;

        $caption = $this->v($rec['caption'] ?? $rec['title'] ?? null);
        $url = $this->v($rec['url'] ?? $rec['link'] ?? null);
        $date = $this->v($rec['posted_date'] ?? $rec['date'] ?? $rec['published_date'] ?? null);
        $time = $this->v($rec['posted_time'] ?? $rec['time'] ?? null);

        $views = $this->toInt($rec['views'] ?? $rec['play_count'] ?? 0);
        $likes = $this->toInt($rec['likes'] ?? $rec['like_count'] ?? 0);
        $comments = $this->toInt($rec['comments'] ?? $rec['comment_count'] ?? 0);
        $shares = $this->toInt($rec['shares'] ?? $rec['share_count'] ?? 0);
        $saves = $this->toInt($rec['saves'] ?? $rec['save_count'] ?? 0);

        return [
            'platform' => 'tiktok',
            'platform_post_id' => $post_id,
            'post_url' => $url,
            'post_type' => 'video',
            'topic' => null,
            'caption' => $caption,
            'hashtags' => null,
            'posted_date' => $date,
            'posted_time' => $time,
            'views' => $views,
            'likes' => $likes,
            'comments' => $comments,
            'shares' => $shares,
            'saves' => $saves,
            'reach' => 0,
            'impressions' => 0,
            'engagement_rate' => 0,
            'internal_notes' => null,
            'snapshot_date' => $this->v($rec['snapshot_date'] ?? date('Y-m-d'))
        ];
    }

    /**
     * Process hashtags - insert into hashtag_performance and post_hashtags tables
     */
    private function processHashtags(\mysqli $conn, int $postId, string $platform, ?string $hashtagsStr, array $metrics): void {
        if (!$hashtagsStr) return;

        // Parse hashtags (comma or space separated, with or without #)
        $hashtags = preg_split('/[,\s]+/', $hashtagsStr);
        $hashtags = array_filter($hashtags, function($h) { return strlen(trim($h)) > 0; });

        foreach ($hashtags as $hashtag) {
            $hashtag = ltrim(trim($hashtag), '#');
            if (strlen($hashtag) === 0) continue;

            // Upsert into hashtag_performance
            $stmt = $conn->prepare("
                INSERT INTO hashtag_performance (hashtag, platform, total_posts, total_views, total_likes, last_used)
                VALUES (?, ?, 1, ?, ?, CURDATE())
                ON DUPLICATE KEY UPDATE
                    total_posts = total_posts + 1,
                    total_views = total_views + VALUES(total_views),
                    total_likes = total_likes + VALUES(total_likes),
                    last_used = CURDATE()
            ");
            $stmt->bind_param("ssii", $hashtag, $platform, $metrics['views'], $metrics['likes']);
            $stmt->execute();

            // Get hashtag_id
            $stmt = $conn->prepare("SELECT id FROM hashtag_performance WHERE hashtag = ? AND platform = ? LIMIT 1");
            $stmt->bind_param("ss", $hashtag, $platform);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row) {
                $hashtagId = (int)$row['id'];

                // Insert into post_hashtags (ignore duplicates)
                $stmt = $conn->prepare("INSERT IGNORE INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $postId, $hashtagId);
                $stmt->execute();
            }
        }
    }

    /**
     * Import CSV file - auto-detects format
     */
    public function import(array $file): array {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['processed' => 0, 'skipped' => 0, 'error' => 'Upload mislukt'];
        }

        $tmp = $file['tmp_name'];
        $processed = 0;
        $skipped = 0;

        $conn = $this->db->conn();
        $conn->begin_transaction();

        try {
            // Prepare statements - include ALL fields
            $postsUpsert = $conn->prepare("
                INSERT INTO posts (
                    platform, platform_post_id, post_url, post_type, topic, caption, hashtags,
                    posted_date, posted_time, views, likes, comments, shares, saves,
                    reach, impressions, engagement_rate, internal_notes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    post_url = COALESCE(VALUES(post_url), post_url),
                    post_type = COALESCE(VALUES(post_type), post_type),
                    topic = COALESCE(VALUES(topic), topic),
                    caption = COALESCE(VALUES(caption), caption),
                    hashtags = COALESCE(VALUES(hashtags), hashtags),
                    posted_date = COALESCE(VALUES(posted_date), posted_date),
                    posted_time = COALESCE(VALUES(posted_time), posted_time),
                    views = GREATEST(views, VALUES(views)),
                    likes = GREATEST(likes, VALUES(likes)),
                    comments = GREATEST(comments, VALUES(comments)),
                    shares = GREATEST(shares, VALUES(shares)),
                    saves = GREATEST(saves, VALUES(saves)),
                    reach = GREATEST(reach, VALUES(reach)),
                    impressions = GREATEST(impressions, VALUES(impressions)),
                    engagement_rate = GREATEST(engagement_rate, VALUES(engagement_rate)),
                    internal_notes = COALESCE(VALUES(internal_notes), internal_notes)
            ");

            $getPostId = $conn->prepare("SELECT id FROM posts WHERE platform=? AND platform_post_id=? LIMIT 1");

            $mhInsert = $conn->prepare("
                INSERT INTO metrics_history (post_id, views, likes, comments, shares, saves, reach, impressions, snapshot_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Open CSV
            if (($fh = fopen($tmp, 'r')) === false) {
                throw new \RuntimeException('Kan CSV niet openen');
            }

            // Read and normalize header
            $header = fgetcsv($fh);
            if (!$header) throw new \RuntimeException('Lege CSV');
            $header = $this->normalizeHeader($header);

            // Detect format
            $format = $this->detectFormat($header);
            if ($format === 'unknown') {
                throw new \RuntimeException('Onbekend CSV formaat. Ondersteunde formaten: TikTok export, content_tracker_enhanced.csv');
            }

            // Process rows
            while (($row = fgetcsv($fh)) !== false) {
                if (count($row) !== count($header)) {
                    $skipped++;
                    continue;
                }

                $rec = array_combine($header, $row);

                // Map row based on format
                $mapped = null;
                if ($format === 'content_tracker') {
                    $mapped = $this->mapContentTrackerRow($rec);
                } elseif ($format === 'tiktok_export') {
                    $mapped = $this->mapTikTokExportRow($rec);
                }

                if (!$mapped) {
                    $skipped++;
                    continue;
                }

                // Insert/update post - 18 parameters: sssssssssiiiiiidss
                $postsUpsert->bind_param(
                    "sssssssssiiiiiiids",
                    $mapped['platform'],
                    $mapped['platform_post_id'],
                    $mapped['post_url'],
                    $mapped['post_type'],
                    $mapped['topic'],
                    $mapped['caption'],
                    $mapped['hashtags'],
                    $mapped['posted_date'],
                    $mapped['posted_time'],
                    $mapped['views'],
                    $mapped['likes'],
                    $mapped['comments'],
                    $mapped['shares'],
                    $mapped['saves'],
                    $mapped['reach'],
                    $mapped['impressions'],
                    $mapped['engagement_rate'],
                    $mapped['internal_notes']
                );
                $postsUpsert->execute();

                // Get post_id
                $getPostId->bind_param("ss", $mapped['platform'], $mapped['platform_post_id']);
                $getPostId->execute();
                $res = $getPostId->get_result();
                $pidRow = $res ? $res->fetch_assoc() : null;
                $pid = $pidRow ? (int)$pidRow['id'] : 0;

                // Insert metrics snapshot
                if ($pid) {
                    $mhInsert->bind_param(
                        "iiiiiiiis",
                        $pid,
                        $mapped['views'],
                        $mapped['likes'],
                        $mapped['comments'],
                        $mapped['shares'],
                        $mapped['saves'],
                        $mapped['reach'],
                        $mapped['impressions'],
                        $mapped['snapshot_date']
                    );
                    $mhInsert->execute();

                    // Process hashtags into separate tables
                    $this->processHashtags($conn, $pid, $mapped['platform'], $mapped['hashtags'], $mapped);
                }

                $processed++;
            }

            fclose($fh);

            // Log import
            $fn = $file['name'] ?? 'upload.csv';
            $imp = $conn->prepare("
                INSERT INTO import_history (filename, platform, import_type, rows_imported, rows_skipped, imported_at)
                VALUES (?, 'multi', ?, ?, ?, NOW())
            ");
            $imp->bind_param("ssii", $fn, $format, $processed, $skipped);
            $imp->execute();

            $conn->commit();
            return [
                'processed' => $processed,
                'skipped' => $skipped,
                'format' => $format
            ];
        } catch (\Throwable $e) {
            $conn->rollback();
            return [
                'processed' => $processed,
                'skipped' => $skipped,
                'error' => $e->getMessage()
            ];
        }
    }
}
