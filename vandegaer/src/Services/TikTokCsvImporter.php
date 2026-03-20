<?php
namespace App\Services;

use App\Core\Database;

/**
 * Service = business logica (import), los van HTTP.
 *
 * In controllers moet je liefst geen CSV parsing doen.
 * Controllers: input lezen -> service aanroepen -> response sturen.
 */
class TikTokCsvImporter {
    private $db;
    public function __construct(Database $db) {
        $this->db = $db;
    }

    private function normalizeHeader(array $header): array {
        $out = [];
        foreach ($header as $h) {
            $k = strtolower(trim((string)$h));
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

    /**
     * Import TikTok CSV data for a specific client.
     *
     * Verwacht $_FILES['file'] input.
     * Doet:
     * - header detectie
     * - upsert in posts (met client_id)
     * - snapshot insert in metrics_history
     * - log in import_history
     *
     * @param array $file $_FILES array element with CSV file
     * @param int $clientId Client ID to associate imported posts with
     * @return array Import result: ['processed' => int, 'skipped' => int, 'error' => string|null]
     */
    public function import(array $file, int $clientId = 1): array {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['processed' => 0, 'skipped' => 0, 'error' => 'Upload mislukt'];
        }

        $tmp = $file['tmp_name'];
        $processed = 0;
        $skipped = 0;

        $conn = $this->db->conn();
        $conn->begin_transaction();

        try {
            $postsUpsert = $conn->prepare("
                INSERT INTO posts (client_id, platform, platform_post_id, post_url, caption, posted_date, posted_time, views, likes, comments, shares)
                VALUES (?, 'tiktok', ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    post_url = VALUES(post_url),
                    caption = VALUES(caption),
                    posted_date = VALUES(posted_date),
                    posted_time = VALUES(posted_time),
                    views = VALUES(views),
                    likes = VALUES(likes),
                    comments = VALUES(comments),
                    shares = VALUES(shares)
            ");

            $getPostId = $conn->prepare("SELECT id FROM posts WHERE platform='tiktok' AND platform_post_id=? LIMIT 1");
            $mhInsert = $conn->prepare("
                INSERT INTO metrics_history (post_id, views, likes, comments, shares, saves, snapshot_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            if (($fh = fopen($tmp, 'r')) === false) {
                throw new \RuntimeException('Kan CSV niet openen');
            }

            $header = fgetcsv($fh);
            if (!$header) throw new \RuntimeException('Lege CSV');
            $header = $this->normalizeHeader($header);

            while (($row = fgetcsv($fh)) !== false) {
                $rec = array_combine($header, $row);

                // Flexibele keys: pas aan naar je exacte export indien nodig
                $ppid = $this->v($rec['post_id'] ?? $rec['video_id'] ?? $rec['id'] ?? null);
                if (!$ppid) { $skipped++; continue; }

                $caption = $this->v($rec['caption'] ?? $rec['title'] ?? null);
                $url = $this->v($rec['url'] ?? $rec['link'] ?? null);
                $date = $this->v($rec['posted_date'] ?? $rec['date'] ?? $rec['published_date'] ?? null);
                $time = $this->v($rec['posted_time'] ?? $rec['time'] ?? null);

                $views = (int)($rec['views'] ?? $rec['play_count'] ?? 0);
                $likes = (int)($rec['likes'] ?? $rec['like_count'] ?? 0);
                $comments = (int)($rec['comments'] ?? $rec['comment_count'] ?? 0);
                $shares = (int)($rec['shares'] ?? $rec['share_count'] ?? 0);
                $saves = (int)($rec['saves'] ?? $rec['save_count'] ?? 0);
                $snapshot = $this->v($rec['snapshot_date'] ?? date('Y-m-d'));

                // posts upsert (with client_id)
                $postsUpsert->bind_param("isssssiiii", $clientId, $ppid, $url, $caption, $date, $time, $views, $likes, $comments, $shares);
                $postsUpsert->execute();

                // find post_id
                $getPostId->bind_param("s", $ppid);
                $getPostId->execute();
                $res = $getPostId->get_result();
                $pidRow = $res ? $res->fetch_assoc() : null;
                $pid = $pidRow ? (int)$pidRow['id'] : 0;

                if ($pid) {
                    $mhInsert->bind_param("iiiiiis", $pid, $views, $likes, $comments, $shares, $saves, $snapshot);
                    $mhInsert->execute();
                }

                $processed++;
            }

            fclose($fh);

            // import_history
            $fn = $file['name'] ?? 'upload.csv';
            $imp = $conn->prepare("
                INSERT INTO import_history (filename, platform, import_type, rows_imported, rows_skipped, imported_at)
                VALUES (?, 'tiktok', 'csv', ?, ?, NOW())
            ");
            $imp->bind_param("sii", $fn, $processed, $skipped);
            $imp->execute();

            $conn->commit();
            return ['processed' => $processed, 'skipped' => $skipped];
        } catch (\Throwable $e) {
            $conn->rollback();
            return ['processed' => $processed, 'skipped' => $skipped, 'error' => $e->getMessage()];
        }
    }
}
