# SocialBit Implementation Plan
## Multi-Platform Analytics with AI Agents

**Created:** 2026-02-07
**Based On:** User feedback from strategy report
**Timeline:** 4-week sprint to multi-platform MVP

---

## 🎯 Mission

Build **multi-platform analytics platform** with:
- ✅ Social media tracking (TikTok, Instagram, Facebook, YouTube)
- ✅ Website analytics (Google Business, Fathom)
- ✅ Hashtag tracking & recommendations
- ✅ Competitor analysis
- ✅ Website traffic correlation

**Strategy:** Multi-platform from start (NO phased rollout)
**Resources:** Part-time developer + Full-time AI agents
**Runway:** 24 months

---

## 📋 Phase 1: Foundation (Week 1-2)

### Task 1.1: Database Migrations (P0 - CRITICAL)

**Owner:** Database specialist agent
**Time:** 6 hours
**Priority:** BLOCKING ALL OTHER WORK

**Migrations Needed:**

**Migration 010: Fix Multi-Tenant Performance**
```sql
-- Add client_id to metrics_history (24× faster queries!)
ALTER TABLE metrics_history ADD COLUMN client_id INT NOT NULL AFTER id;
ALTER TABLE metrics_history ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;
CREATE INDEX idx_metrics_client_date ON metrics_history(client_id, snapshot_date);

-- Add client_id to hashtag_performance
ALTER TABLE hashtag_performance ADD COLUMN client_id INT NOT NULL AFTER id;
ALTER TABLE hashtag_performance ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;
CREATE INDEX idx_hashtag_perf_client ON hashtag_performance(client_id, hashtag);

-- Add client_id to import_history
ALTER TABLE import_history ADD COLUMN client_id INT NOT NULL AFTER id;
ALTER TABLE import_history ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;
CREATE INDEX idx_import_client ON import_history(client_id, imported_at);
```

**Migration 011: 2026 Algorithm Metrics**
```sql
-- Add watch time and completion metrics
ALTER TABLE posts ADD COLUMN watch_time INT DEFAULT 0 AFTER views;
ALTER TABLE posts ADD COLUMN average_watch_time INT DEFAULT 0 AFTER watch_time;
ALTER TABLE posts ADD COLUMN completion_rate DECIMAL(5,2) DEFAULT 0.00 AFTER average_watch_time;
ALTER TABLE posts ADD COLUMN duration INT DEFAULT 0 AFTER completion_rate;

-- Add engagement quality metrics
ALTER TABLE posts ADD COLUMN sends_count INT DEFAULT 0 AFTER shares;
ALTER TABLE posts ADD COLUMN profile_visits INT DEFAULT 0 AFTER sends_count;
ALTER TABLE posts ADD COLUMN skip_rate DECIMAL(5,2) DEFAULT 0.00 AFTER profile_visits;

-- Add follower growth tracking
ALTER TABLE posts ADD COLUMN follower_growth INT DEFAULT 0 AFTER profile_visits;

-- Add crossposted views (Instagram 2026 feature)
ALTER TABLE posts ADD COLUMN crossposted_views INT DEFAULT 0 AFTER views;
```

**Migration 012: Hashtag Tracking System**
```sql
CREATE TABLE hashtag_tracking (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  hashtag VARCHAR(100) NOT NULL,
  total_uses INT DEFAULT 0,
  avg_views DECIMAL(12,2) DEFAULT 0.00,
  avg_engagement DECIMAL(5,2) DEFAULT 0.00,
  avg_completion_rate DECIMAL(5,2) DEFAULT 0.00,
  best_performing_post_id INT,
  worst_performing_post_id INT,
  last_used_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  FOREIGN KEY (best_performing_post_id) REFERENCES posts(id) ON DELETE SET NULL,
  UNIQUE KEY (client_id, hashtag),
  INDEX idx_client_performance (client_id, avg_engagement DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hashtag_recommendations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  hashtag VARCHAR(100) NOT NULL,
  score DECIMAL(5,2) DEFAULT 0.00,
  reason TEXT,
  competitors_using INT DEFAULT 0,
  trending BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_client_score (client_id, score DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Migration 013: Competitor Analysis**
```sql
CREATE TABLE competitors (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  competitor_name VARCHAR(100) NOT NULL,
  platform VARCHAR(50) NOT NULL,
  profile_url TEXT,
  profile_handle VARCHAR(100),
  tracking_enabled BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_client_platform (client_id, platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE competitor_metrics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  competitor_id INT NOT NULL,
  date DATE NOT NULL,
  followers INT DEFAULT 0,
  following INT DEFAULT 0,
  total_posts INT DEFAULT 0,
  avg_engagement_rate DECIMAL(5,2) DEFAULT 0.00,
  avg_views INT DEFAULT 0,
  posting_frequency DECIMAL(4,2) DEFAULT 0.00,  -- posts per day
  top_hashtags JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE,
  UNIQUE KEY (competitor_id, date),
  INDEX idx_competitor_date (competitor_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Migration 014: Website Traffic Correlation**
```sql
CREATE TABLE website_analytics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  source VARCHAR(50) NOT NULL,  -- 'google_business', 'fathom'
  date DATE NOT NULL,
  page_views INT DEFAULT 0,
  unique_visitors INT DEFAULT 0,
  referral_visits INT DEFAULT 0,
  referral_source VARCHAR(100),  -- 'tiktok', 'instagram', etc.
  bounce_rate DECIMAL(5,2) DEFAULT 0.00,
  avg_session_duration INT DEFAULT 0,  -- seconds
  conversions INT DEFAULT 0,
  data_json JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  UNIQUE KEY (client_id, source, date),
  INDEX idx_client_date (client_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_website_traffic (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  post_id INT NOT NULL,
  date DATE NOT NULL,
  referral_visits INT DEFAULT 0,
  referral_source VARCHAR(50),
  bounce_rate DECIMAL(5,2) DEFAULT 0.00,
  avg_session_duration INT DEFAULT 0,
  conversions INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  INDEX idx_client_post (client_id, post_id),
  INDEX idx_client_date (client_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Migration 015: Data Lineage (Conflict Resolution)**
```sql
CREATE TABLE data_lineage (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  entity_type VARCHAR(50) NOT NULL,  -- 'post', 'metric', 'hashtag'
  entity_id INT NOT NULL,
  field_name VARCHAR(100) NOT NULL,
  old_value TEXT,
  new_value TEXT,
  source VARCHAR(50) NOT NULL,  -- 'api', 'metricool', 'csv', 'manual'
  priority INT NOT NULL,  -- 1=API, 2=Metricool, 3=CSV, 4=Manual
  resolution_reason VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_entity (entity_type, entity_id),
  INDEX idx_client_created (client_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Migration 016: Google Business Integration**
```sql
CREATE TABLE google_business_locations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  location_id VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  address TEXT,
  phone VARCHAR(50),
  website VARCHAR(255),
  enabled BOOLEAN DEFAULT TRUE,
  last_synced_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  UNIQUE KEY (client_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE google_business_metrics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  location_id INT NOT NULL,
  date DATE NOT NULL,
  search_views INT DEFAULT 0,
  maps_views INT DEFAULT 0,
  calls INT DEFAULT 0,
  direction_requests INT DEFAULT 0,
  website_clicks INT DEFAULT 0,
  photo_views INT DEFAULT 0,
  reviews_count INT DEFAULT 0,
  avg_rating DECIMAL(2,1) DEFAULT 0.0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (location_id) REFERENCES google_business_locations(id) ON DELETE CASCADE,
  UNIQUE KEY (location_id, date),
  INDEX idx_location_date (location_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Deliverable:**
- All 7 migrations tested locally
- SQL files in `scripts/010_*.sql` through `scripts/016_*.sql`
- Migration log document

---

### Task 1.2: TikTok API Troubleshooting (P0 - BLOCKING)

**Owner:** API integration specialist
**Status:** BLOCKED - Developer account stuck
**Priority:** HIGH

**Current Situation:**
- ✅ TikTok Developer account exists
- ❌ Stuck/blocked somewhere in approval process

**Troubleshooting Steps:**
1. Access TikTok Developer Console
2. Check App Review status
3. Verify requested scopes:
   - `user.info.basic`
   - `video.list`
   - `video.insights`
4. Check OAuth redirect URIs configuration
5. Test OAuth flow end-to-end
6. Document any error messages

**Fallback Plan:**
- If API blocked for >1 week → Use Metricool + CSV only
- Metricool covers basic TikTok metrics
- CSV import for completion rate (TikTok Studio export has this)

**Deliverable:**
- TikTok API working OR documented reason why not
- Fallback strategy confirmed

---

### Task 1.3: Metricool Integration (P1 - HIGH)

**Owner:** Integration specialist
**Time:** 8-10 hours
**Prerequisites:** Database migrations complete

**Steps:**

**1. Account Setup**
- Upgrade to Metricool Advanced plan (user will do this)
- Generate API key from Metricool dashboard
- Store in `settings` table: `metricool_api_key`

**2. API Research**
- Read Metricool API docs: https://metricool.com/api/
- Identify endpoints for:
  - List connected profiles
  - Fetch posts (TikTok, Instagram, Facebook, YouTube)
  - Fetch metrics per post
  - Fetch account-level metrics
- Document rate limits and quotas

**3. Build Service**
```php
// src/Services/MetricoolApiService.php
class MetricoolApiService {
    private string $apiKey;
    private string $baseUrl = 'https://api.metricool.com/v1';

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getConnectedProfiles(): array { ... }
    public function getPosts(string $profileId, array $filters = []): array { ... }
    public function getPostMetrics(string $postId): array { ... }
    public function syncPosts(int $clientId, string $profileId): array { ... }
}
```

**4. Data Mapping**
```php
// Map Metricool response to SocialBit schema
private function mapMetricoolPost(array $metricoolData): array {
    return [
        'platform' => $metricoolData['network'],
        'platform_post_id' => $metricoolData['id'],
        'caption' => $metricoolData['text'],
        'views' => $metricoolData['impressions'],
        'likes' => $metricoolData['likes'],
        'comments' => $metricoolData['comments'],
        'shares' => $metricoolData['shares'],
        'saves' => $metricoolData['saves'] ?? 0,
        'posted_date' => $metricoolData['published_at'],
        // ... map all fields
    ];
}
```

**5. Scheduled Collection**
```php
// scripts/scheduled/metricool_sync.php
// Cron: 0 4 * * * (daily at 4 AM)

require_once __DIR__ . '/../../vendor/autoload.php';

$clientRepo = new ClientRepository($db);
$metricoolService = new MetricoolApiService($apiKey);

foreach ($clientRepo->findAll() as $client) {
    try {
        $profiles = $metricoolService->getConnectedProfiles();
        foreach ($profiles as $profile) {
            $metricoolService->syncPosts($client['id'], $profile['id']);
        }
        echo "✓ Synced client {$client['id']}\n";
    } catch (Exception $e) {
        echo "✗ Error for client {$client['id']}: {$e->getMessage()}\n";
    }
}
```

**Deliverable:**
- `MetricoolApiService.php` with full API coverage
- Scheduled sync script
- Data mapping documented
- Test with at least 2 platforms

---

## 📋 Phase 2: Platform Integrations (Week 2-3)

### Task 2.1: Google Business API Integration

**Owner:** Integration specialist
**Time:** 8-10 hours
**Prerequisites:** Database migration 016 complete

**API:** Google My Business API v4.9
**Docs:** https://developers.google.com/my-business/reference/rest

**Required Scopes:**
- `https://www.googleapis.com/auth/business.manage`

**Steps:**

**1. OAuth Setup**
```php
// Google Business OAuth flow
class GoogleBusinessOAuthService {
    public function getAuthorizationUrl(): string { ... }
    public function handleCallback(string $code): array { ... }
    public function refreshAccessToken(string $refreshToken): string { ... }
}
```

**2. API Service**
```php
class GoogleBusinessApiService {
    public function getLocations(string $accountId): array { ... }
    public function getInsights(string $locationId, array $filters): array { ... }
    public function getReviews(string $locationId): array { ... }
}
```

**3. Metrics Collection**
- Search views (Google Search, Maps)
- Actions (calls, direction requests, website clicks)
- Photo views
- Reviews (count, avg rating)

**4. Daily Sync**
```php
// scripts/scheduled/google_business_sync.php
// Cron: 0 5 * * * (daily at 5 AM)
```

**Deliverable:**
- OAuth flow working
- Daily metrics sync
- Reviews dashboard (bonus)

---

### Task 2.2: Fathom Analytics Integration

**Owner:** Integration specialist
**Time:** 6-8 hours
**Prerequisites:** Database migration 014 complete

**API:** Fathom Analytics API v1
**Docs:** https://usefathom.com/api

**Steps:**

**1. API Key Setup**
- User provides Fathom API key
- Store in `settings` table

**2. Site Configuration**
```php
CREATE TABLE fathom_sites (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  site_id VARCHAR(50) NOT NULL,
  site_name VARCHAR(255),
  site_url VARCHAR(255),
  enabled BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);
```

**3. API Service**
```php
class FathomAnalyticsService {
    public function getSites(): array { ... }
    public function getAggregations(string $siteId, array $filters): array { ... }
    public function getReferrers(string $siteId, array $filters): array { ... }
}
```

**4. Traffic Correlation**
```php
// Match Fathom referrer data to posts
class TrafficCorrelationService {
    public function correlatePostTraffic(int $clientId, string $date): void {
        // 1. Get Fathom referrers for date
        // 2. Parse referrer URLs (tiktok.com, instagram.com, etc.)
        // 3. Match to posts published in previous 7 days
        // 4. Store in post_website_traffic table
    }
}
```

**5. Daily Sync**
```php
// scripts/scheduled/fathom_sync.php
// Cron: 0 6 * * * (daily at 6 AM)
```

**Deliverable:**
- Fathom API working
- Traffic correlation algorithm
- "Posts that drove site traffic" report

---

## 📋 Phase 3: Analytics Features (Week 3-4)

### Task 3.1: Hashtag Tracking System

**Owner:** Analytics specialist
**Time:** 10-12 hours
**Prerequisites:** Migration 012 complete

**Features:**

**1. Hashtag Extraction & Tracking**
```php
class HashtagTrackingService {
    public function extractHashtags(string $caption): array {
        preg_match_all('/#(\w+)/u', $caption, $matches);
        return $matches[1];
    }

    public function updateHashtagStats(int $clientId, int $postId): void {
        // 1. Extract hashtags from post
        // 2. Update hashtag_tracking table
        // 3. Recalculate avg_views, avg_engagement
        // 4. Update best/worst performing post references
    }
}
```

**2. Hashtag Recommendations**
```php
class HashtagRecommendationService {
    public function generateRecommendations(int $clientId): array {
        // 1. Analyze historical performance
        // 2. Identify high-performing hashtags (>avg engagement)
        // 3. Check competitor usage
        // 4. Generate score (0-100)
        // 5. Write to hashtag_recommendations table
    }

    public function suggestForNextPost(int $clientId, int $limit = 10): array {
        // Return top N recommended hashtags with reasons
    }
}
```

**3. Dashboard Views**
```sql
CREATE VIEW hashtag_leaderboard AS
SELECT
    ht.client_id,
    ht.hashtag,
    ht.total_uses,
    ht.avg_views,
    ht.avg_engagement,
    ht.last_used_date,
    CASE
        WHEN ht.avg_engagement > cl_avg.avg_engagement THEN 'above_average'
        ELSE 'below_average'
    END as performance_category
FROM hashtag_tracking ht
LEFT JOIN (
    SELECT client_id, AVG(avg_engagement) as avg_engagement
    FROM hashtag_tracking
    GROUP BY client_id
) cl_avg ON ht.client_id = cl_avg.client_id
ORDER BY ht.avg_engagement DESC;
```

**UI Components:**
- Hashtag leaderboard (top 20)
- Hashtag performance trend chart
- "Suggested hashtags" card on dashboard
- Hashtag detail page (all posts using this hashtag)

**Deliverable:**
- Hashtag extraction working
- Recommendations algorithm
- Dashboard UI components

---

### Task 3.2: Competitor Analysis Dashboard

**Owner:** Analytics specialist
**Time:** 12-15 hours
**Prerequisites:** Migration 013 complete

**Features:**

**1. Competitor Setup UI**
```html
<!-- Add competitor form -->
<form id="addCompetitorForm">
  <input name="competitor_name" placeholder="Competitor Name">
  <select name="platform">
    <option value="tiktok">TikTok</option>
    <option value="instagram">Instagram</option>
    <option value="facebook">Facebook</option>
  </select>
  <input name="profile_handle" placeholder="@username">
  <button type="submit">Add Competitor</button>
</form>
```

**2. Data Collection**
```php
class CompetitorDataService {
    public function collectMetrics(int $competitorId): void {
        // 1. Fetch competitor profile data (manual CSV or scraping if allowed)
        // 2. Calculate metrics (followers, engagement rate, posting frequency)
        // 3. Extract top hashtags
        // 4. Store in competitor_metrics table
    }
}
```

**3. Comparison Dashboard**
```php
class CompetitorComparisonService {
    public function getComparison(int $clientId): array {
        // Return side-by-side metrics:
        // - Your performance vs competitors
        // - Hashtag overlap
        // - Posting frequency comparison
        // - Content gap analysis
    }
}
```

**4. UI Dashboard**
- Side-by-side comparison table
- Performance gap visualization
- Hashtag overlap analysis
- "Topics competitors cover that you don't"

**Deliverable:**
- Competitor tracking working
- Comparison dashboard live
- Manual CSV import option

---

### Task 3.3: Website Traffic Correlation

**Owner:** Analytics specialist
**Time:** 8-10 hours
**Prerequisites:** Fathom integration complete

**Algorithm:**

```php
class TrafficCorrelationService {
    public function correlateTraffic(int $clientId, string $date): void {
        // 1. Get all posts from last 7 days
        $posts = $this->postRepo->findRecent($clientId, 7);

        // 2. Get Fathom referrers for date
        $referrers = $this->fathomService->getReferrers($siteId, ['date' => $date]);

        // 3. Match referrers to posts
        foreach ($referrers as $referrer) {
            // Parse referrer URL (e.g., "tiktok.com/...")
            $platform = $this->extractPlatform($referrer['url']);

            // Find posts from that platform in timeframe
            $matchedPosts = array_filter($posts, fn($p) =>
                $p['platform'] === $platform &&
                $this->isWithinTimeframe($p['posted_date'], $date, 7)
            );

            // Distribute traffic across matched posts (or use UTM if available)
            foreach ($matchedPosts as $post) {
                $this->recordTraffic($post['id'], $referrer['visits'], $date);
            }
        }
    }
}
```

**UI:**
- "Top posts by website traffic" leaderboard
- Traffic impact badge on post cards
- "This post drove X website visits"
- Traffic correlation chart (post date vs traffic spike)

**Deliverable:**
- Correlation algorithm working
- Traffic metrics displayed on posts
- "Traffic drivers" report

---

## 📋 Phase 4: Testing & Polish (Week 4)

### Task 4.1: End-to-End Testing

**Owner:** QA specialist
**Time:** 8-10 hours

**Test Scenarios:**

1. **Multi-Tenant Isolation**
   - Create 2 test clients
   - Import data for both
   - Verify no data leakage

2. **Data Collection**
   - Metricool sync successful
   - Google Business sync successful
   - Fathom sync successful
   - Conflict resolution working

3. **Analytics**
   - Hashtag tracking accurate
   - Recommendations make sense
   - Competitor data correct
   - Traffic correlation working

4. **Performance**
   - Dashboard loads <2 seconds
   - Queries with client_id <100ms
   - No N+1 queries

**Deliverable:**
- Test report with pass/fail
- Bug list with priorities

---

### Task 4.2: Documentation

**Owner:** Documentation specialist
**Time:** 4-6 hours

**Documents:**

1. **API Integration Guide**
   - How to connect each platform
   - OAuth flows
   - Troubleshooting

2. **User Guide**
   - How to setup competitors
   - How to interpret hashtag recommendations
   - How to use traffic correlation data

3. **Developer Docs**
   - Database schema
   - API endpoints
   - Service architecture

**Deliverable:**
- 3 markdown docs in `docs/`

---

## 🚀 Success Criteria

**Phase 1 (Week 2):**
- ✅ All 7 database migrations applied
- ✅ TikTok API working OR fallback confirmed
- ✅ Metricool integration functional

**Phase 2 (Week 3):**
- ✅ Google Business data flowing
- ✅ Fathom Analytics connected
- ✅ Multi-source data collection working

**Phase 3 (Week 4):**
- ✅ Hashtag tracking live
- ✅ Competitor analysis working
- ✅ Website traffic correlation accurate

**Phase 4 (Week 4 end):**
- ✅ All features tested
- ✅ Documentation complete
- ✅ Ready for first paying client

---

## 🤝 Agent Collaboration

**Daily Standup (Async):**
- Each agent posts progress update
- Blockers flagged immediately
- Dependencies communicated

**Parallel Work:**
- Database + TikTok API (no dependency)
- Metricool + Google Business (after DB migrations)
- Hashtag tracking + Competitor analysis (independent)

**Integration Points:**
- Week 2 end: All APIs collecting data
- Week 3 mid: Analytics features consuming data
- Week 4: Polish and testing

---

## 📞 Escalation

**If Blocked:**
1. Try to unblock yourself (research, docs)
2. Ask other agents for help
3. Escalate to Bjorn (with context)

**TikTok API Blocker:**
- If stuck >3 days → Use Metricool + CSV fallback
- Document issue for future resolution

**Performance Issues:**
- If queries >500ms → Optimize immediately
- Use EXPLAIN to debug
- Add indexes as needed

---

**Ready to start? Let's build! 🚀**
