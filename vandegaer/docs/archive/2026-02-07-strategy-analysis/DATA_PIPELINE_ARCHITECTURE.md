# Data Collection Pipeline Architecture

**Author:** Pipeline Engineer (Claude Agent)
**Date:** 2026-02-07
**Status:** Design Document
**Project:** SocialBit Multi-Tenant Analytics Platform

---

## Executive Summary

This document outlines a robust data collection pipeline architecture for SocialBit that handles multiple data sources (TikTok, Instagram, Facebook, Google Analytics, Fathom, Metricool), multiple formats (API, CSV, database dumps), and solves the critical challenge of **conflict resolution** when the same metric arrives from different sources with different values.

**Key Design Principles:**
- **Source Priority Hierarchy** - API data > CSV exports > Manual input
- **Timestamp-based Conflict Resolution** - Most recent wins within same priority tier
- **Data Lineage Tracking** - Always know where metrics came from
- **Graceful Degradation** - Pipeline continues even if one source fails
- **Vanilla PHP Compatible** - No Laravel, uses Composer for lightweight libraries

---

## 1. Architecture Overview

### 1.1 Conceptual Diagram (Text Format)

```
┌─────────────────────────────────────────────────────────────────────┐
│                        DATA SOURCES LAYER                            │
├─────────────────────────────────────────────────────────────────────┤
│  TikTok API  │  Instagram API  │  Facebook API  │  Metricool API    │
│  TikTok CSV  │  Instagram CSV  │  Facebook CSV  │  Google Analytics │
│  Manual      │  Fathom         │  Database Dump │  Webhooks         │
└──────┬──────┴──────┬───────────┴────────┬───────────────┬───────────┘
       │             │                    │               │
       ▼             ▼                    ▼               ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     INGESTION LAYER (Collectors)                     │
├─────────────────────────────────────────────────────────────────────┤
│  • API Collectors (OAuth, rate limiting, pagination)                │
│  • CSV Parsers (UTF-8, column mapping, validation)                  │
│  • Webhook Receivers (async, signature validation)                  │
│  • Manual Entry Forms (admin UI)                                    │
└──────┬──────────────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     QUEUE LAYER (Job Management)                     │
├─────────────────────────────────────────────────────────────────────┤
│  • MySQL-based Job Queue (no Redis needed)                          │
│  • Priority: Critical > High > Normal > Low                          │
│  • Retry Logic: Exponential backoff with jitter                     │
│  • Dead Letter Queue: Failed jobs for manual review                 │
└──────┬──────────────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────────────┐
│              CONFLICT RESOLUTION LAYER (Smart Merge)                 │
├─────────────────────────────────────────────────────────────────────┤
│  • Source Priority Algorithm (API > CSV > Manual)                   │
│  • Timestamp-based Last-Write-Wins (within same tier)               │
│  • Data Lineage Tracking (audit trail)                              │
│  • Validation Rules (business logic checks)                         │
└──────┬──────────────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     STORAGE LAYER (Database)                         │
├─────────────────────────────────────────────────────────────────────┤
│  • posts (current metrics + metadata)                               │
│  • metrics_history (time-series snapshots)                          │
│  • data_lineage (source tracking, conflict logs)                    │
│  • sync_status (per client/account sync state)                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 1.2 Data Flow Example

**Scenario:** TikTok post exists in database with 1000 views (from CSV yesterday). Today, API returns 1050 views, but user also uploads CSV with 1025 views.

**Flow:**
1. **API Collector** fetches TikTok data → creates job "Update post XYZ from TikTok API"
2. **CSV Parser** reads uploaded file → creates job "Update post XYZ from TikTok CSV"
3. **Queue Worker** processes API job first (higher priority)
   - Checks existing post: 1000 views (source: CSV, timestamp: yesterday)
   - New data: 1050 views (source: API, timestamp: now)
   - **Conflict Resolution:** API > CSV priority → Accept 1050 views
   - Writes to `posts.views = 1050`, logs to `data_lineage`
   - Creates `metrics_history` snapshot
4. **Queue Worker** processes CSV job next
   - Checks existing post: 1050 views (source: API, timestamp: 2 mins ago)
   - New data: 1025 views (source: CSV, timestamp: now)
   - **Conflict Resolution:** API > CSV priority → Reject 1025 views (stale)
   - Logs conflict to `data_lineage` with reason "Lower priority source"

**Result:** Database has 1050 views (from API), with full audit trail.

---

## 2. Conflict Resolution Algorithm

### 2.1 Source Priority Hierarchy

**Priority Tiers (Highest to Lowest):**

| Tier | Source Type | Rationale | Examples |
|------|-------------|-----------|----------|
| 1 | **Official Platform APIs** | Direct from source, real-time, most accurate | TikTok API, Instagram Graph API, Facebook Graph API |
| 2 | **Third-Party Analytics APIs** | Aggregated data, reliable but may lag | Metricool API, Google Analytics API |
| 3 | **Official CSV Exports** | User-downloaded from platform, accurate but manual | TikTok CSV export, Instagram Insights CSV |
| 4 | **Third-Party CSV Exports** | Exported from tools like Metricool | Metricool CSV, Buffer CSV |
| 5 | **Manual Entry** | User input via admin UI | Admin dashboard manual post creation |

### 2.2 Conflict Resolution Rules

**Rule 1: Source Priority Wins**
```
IF new_data.source_tier < existing_data.source_tier THEN
  ACCEPT new_data
  LOG conflict with reason "Higher priority source"
ELSE IF new_data.source_tier > existing_data.source_tier THEN
  REJECT new_data
  LOG conflict with reason "Lower priority source"
ELSE
  GO TO Rule 2 (same tier)
END IF
```

**Rule 2: Timestamp-Based Last-Write-Wins (Same Tier)**
```
IF new_data.source_tier == existing_data.source_tier THEN
  IF new_data.collected_at > existing_data.collected_at THEN
    IF new_data.metric_value >= existing_data.metric_value THEN
      ACCEPT new_data
      LOG conflict with reason "Newer data, value increased"
    ELSE
      // Metrics should never decrease (edge case: platform correction)
      IF abs(new_data.metric_value - existing_data.metric_value) / existing_data.metric_value > 0.10 THEN
        // >10% decrease = suspicious, flag for review
        ACCEPT new_data WITH flag "suspicious_decrease"
        LOG conflict with reason "Metric decreased >10%, flagged"
      ELSE
        ACCEPT new_data
        LOG conflict with reason "Minor decrease (<10%), accepted"
      END IF
    END IF
  ELSE
    REJECT new_data
    LOG conflict with reason "Stale timestamp"
  END IF
END IF
```

**Rule 3: Value Validation**
```
// Never allow negative metrics or impossibly large jumps
IF new_data.metric_value < 0 THEN
  REJECT new_data
  LOG error "Negative metric value"
ELSE IF existing_data.metric_value > 0 AND
        new_data.metric_value / existing_data.metric_value > 10 THEN
  // 10x increase in one update = suspicious
  ACCEPT new_data WITH flag "suspicious_spike"
  LOG conflict with reason "Metric increased >10x, flagged"
END IF
```

### 2.3 Implementation Example (PHP)

```php
class ConflictResolver {
    private const SOURCE_PRIORITIES = [
        'platform_api' => 1,    // TikTok API, Instagram API
        'analytics_api' => 2,   // Google Analytics, Fathom
        'platform_csv' => 3,    // Official platform exports
        'thirdparty_csv' => 4,  // Metricool CSV
        'manual' => 5           // User input
    ];

    public function resolve(array $existingData, array $newData): array {
        $decision = [
            'action' => 'accept',  // accept, reject, flag
            'reason' => '',
            'flags' => [],
            'conflict_detected' => false
        ];

        // Rule 1: Source Priority
        $existingPriority = self::SOURCE_PRIORITIES[$existingData['source']] ?? 99;
        $newPriority = self::SOURCE_PRIORITIES[$newData['source']] ?? 99;

        if ($newPriority < $existingPriority) {
            $decision['reason'] = 'Higher priority source';
            return $decision;
        } elseif ($newPriority > $existingPriority) {
            $decision['action'] = 'reject';
            $decision['reason'] = 'Lower priority source';
            $decision['conflict_detected'] = true;
            return $decision;
        }

        // Rule 2: Timestamp (same tier)
        if (strtotime($newData['collected_at']) <= strtotime($existingData['collected_at'])) {
            $decision['action'] = 'reject';
            $decision['reason'] = 'Stale timestamp';
            $decision['conflict_detected'] = true;
            return $decision;
        }

        // Rule 3: Value Validation
        $existingValue = $existingData['metric_value'];
        $newValue = $newData['metric_value'];

        if ($newValue < 0) {
            $decision['action'] = 'reject';
            $decision['reason'] = 'Negative metric value';
            return $decision;
        }

        // Check for suspicious decrease
        if ($newValue < $existingValue && $existingValue > 0) {
            $decreasePct = abs($newValue - $existingValue) / $existingValue;
            if ($decreasePct > 0.10) {
                $decision['flags'][] = 'suspicious_decrease';
                $decision['reason'] = sprintf('Metric decreased %.1f%%, flagged', $decreasePct * 100);
            } else {
                $decision['reason'] = 'Minor decrease (<10%), accepted';
            }
            $decision['conflict_detected'] = true;
        }

        // Check for suspicious spike
        if ($existingValue > 0 && $newValue / $existingValue > 10) {
            $decision['flags'][] = 'suspicious_spike';
            $decision['reason'] = sprintf('Metric increased %.1fx, flagged', $newValue / $existingValue);
            $decision['conflict_detected'] = true;
        }

        return $decision;
    }
}
```

---

## 3. Technology Stack Recommendations

### 3.1 Queue Management (Vanilla PHP)

**Option 1: MySQL-based Queue (Recommended for MVP)**

**Pros:**
- No additional infrastructure (already have MySQL)
- Simple to implement and debug
- Transactional support (ACID guarantees)
- Works on Plesk hosting without extra setup

**Cons:**
- Not as fast as Redis for high-volume (but fine for <10K jobs/day)
- Polling overhead (can mitigate with SLEEP/WAIT)

**Implementation:**
```sql
CREATE TABLE job_queue (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  job_type ENUM('api_sync', 'csv_import', 'metric_update') NOT NULL,
  priority TINYINT DEFAULT 50,  -- 1=highest, 100=lowest
  payload JSON NOT NULL,
  status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
  attempts INT DEFAULT 0,
  max_attempts INT DEFAULT 3,
  scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  started_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  error_message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_status_priority (status, priority, scheduled_at),
  INDEX idx_client (client_id),
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

**Option 2: Redis Queue (Future Scaling)**

Use library: [php-resque](https://github.com/php-resque/php-resque) or custom implementation

**When to migrate:** When job volume exceeds 10K/day or need <1s job latency

### 3.2 Job Scheduler (Cron)

**Phase 1: Plesk Cron Jobs**

```bash
# Run queue worker every 5 minutes
*/5 * * * * php /var/www/html/scripts/queue-worker.php >> /var/log/queue.log 2>&1

# Daily API sync (4 AM per tenant timezone)
0 4 * * * php /var/www/html/scripts/daily-sync.php >> /var/log/sync.log 2>&1

# Hourly lightweight metrics check
0 * * * * php /var/www/html/scripts/hourly-check.php >> /var/log/hourly.log 2>&1
```

**Phase 2: Self-Hosted Cron Service**

Use library: [crunz/crunz](https://github.com/crunzphp/crunz) for PHP-based cron management

### 3.3 Rate Limiting

**Composer Library:** [symfony/rate-limiter](https://symfony.com/doc/current/rate_limiter.html) (standalone, no framework needed)

**Implementation Example:**
```php
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

$factory = new RateLimiterFactory([
    'id' => 'tiktok_api',
    'policy' => 'sliding_window',
    'limit' => 1000,
    'interval' => '1 day',
], new InMemoryStorage());

$limiter = $factory->create('client_123_tiktok');

if ($limiter->consume(1)->isAccepted()) {
    // Make API request
} else {
    // Queue for retry later
}
```

**Platform-Specific Limits:**
- **TikTok API:** 1,000 requests/day per app
- **Instagram Graph API:** 200 requests/hour per user
- **Facebook Graph API:** 200 requests/hour per user
- **Google Analytics API:** 10 requests/second per project

**Strategy:** Distribute requests evenly throughout the day with jitter (randomize ±10% of scheduled time to avoid thundering herd)

### 3.4 Data Lineage Tracking

**New Database Table:**
```sql
CREATE TABLE data_lineage (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  post_id INT,
  metric_name VARCHAR(50) NOT NULL,  -- 'views', 'likes', 'engagement_rate'

  -- Source Information
  source_type ENUM('platform_api', 'analytics_api', 'platform_csv', 'thirdparty_csv', 'manual') NOT NULL,
  source_platform VARCHAR(50),  -- 'tiktok', 'instagram', 'metricool'
  import_job_id INT,  -- References job_queue.id

  -- Values
  old_value DECIMAL(15,2),
  new_value DECIMAL(15,2),

  -- Conflict Resolution
  conflict_detected BOOLEAN DEFAULT FALSE,
  resolution_action ENUM('accepted', 'rejected', 'flagged') DEFAULT 'accepted',
  resolution_reason TEXT,
  flags JSON,  -- ['suspicious_spike', 'suspicious_decrease']

  -- Timestamps
  collected_at TIMESTAMP NOT NULL,  -- When source collected the data
  processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- When we processed it

  INDEX idx_post_metric (post_id, metric_name),
  INDEX idx_client_date (client_id, processed_at),
  INDEX idx_conflicts (conflict_detected, resolution_action),
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

**Benefits:**
- Full audit trail for compliance
- Debug why metrics changed
- Identify unreliable data sources
- Generate "Data Quality Score" for each source

---

## 4. Phased Implementation Roadmap

### Phase 1: Foundation (Weeks 1-2)
**Goal:** Get basic multi-source ingestion working

**Tasks:**
1. ✅ Create database tables: `job_queue`, `data_lineage`, `sync_status`
2. ✅ Implement `ConflictResolver` service class
3. ✅ Build MySQL-based job queue manager
4. ✅ Update `PostRepository` to log lineage on every update
5. ✅ Create basic cron job for queue worker

**Deliverables:**
- CSV imports log to `data_lineage`
- Manual updates tracked with source = 'manual'
- Queue worker processes 1 job/minute

**Success Criteria:**
- No data loss when importing same post twice
- Audit trail shows why value X was chosen over value Y

---

### Phase 2: API Integration (Weeks 3-4)
**Goal:** Replace CSV with automated API syncs

**Tasks:**
1. ✅ TikTok API collector (reuse existing OAuth service)
2. ✅ Instagram Graph API collector
3. ✅ Facebook Graph API collector
4. ✅ Rate limiting middleware per platform
5. ✅ Priority-based job scheduling (API jobs = high priority)

**Deliverables:**
- Daily auto-sync for all connected accounts
- API data marked as `source_type = 'platform_api'`
- Rate limits respected (no 429 errors)

**Success Criteria:**
- 95% of posts auto-sync daily without user intervention
- API data always wins over CSV when both exist

---

### Phase 3: Advanced Features (Weeks 5-6)
**Goal:** Reliability, monitoring, and UX

**Tasks:**
1. ✅ Dead letter queue for failed jobs (>3 retries)
2. ✅ Admin UI: View sync status per account
3. ✅ Email alerts for sync failures
4. ✅ Retry with exponential backoff + jitter
5. ✅ Metrics dashboard: "Data Quality Score" per source

**Deliverables:**
- Users see "Last synced: 2 hours ago" per account
- Failed jobs visible in admin panel for manual retry
- Weekly email: "5 posts updated, 2 failed" summary

**Success Criteria:**
- Users trust the data (no "where did this number come from?")
- 99% job success rate (excluding API downtime)

---

### Phase 4: Optimization (Weeks 7-8)
**Goal:** Scale to 100+ clients, 100K+ posts

**Tasks:**
1. ✅ Migrate to Redis queue (if MySQL queue slows down)
2. ✅ Partition `metrics_history` by month
3. ✅ Database indexes optimization (query profiling)
4. ✅ Batch API requests (fetch 100 posts/request vs 1/request)
5. ✅ CDN for uploaded CSV files (offload storage)

**Deliverables:**
- Queue processes 100 jobs/minute
- API sync completes in <5 minutes per account
- Dashboard loads in <2 seconds

**Success Criteria:**
- System handles 1000 jobs/day per client
- Database queries <100ms on average

---

## 5. Risk Mitigation

### 5.1 API Rate Limits

**Risk:** TikTok API only allows 1,000 requests/day

**Mitigation:**
- Prioritize recent posts (last 30 days) for daily sync
- Older posts sync weekly/monthly
- Use webhooks when available (Instagram supports real-time updates)
- Fallback to CSV import if rate limit hit

### 5.2 Clock Skew (Timestamp Issues)

**Risk:** Servers in different timezones, incorrect timestamps

**Mitigation:**
- Store all timestamps in UTC
- Use `collected_at` from data source, not `NOW()`
- Add tolerance window (±5 minutes) for "stale" check
- Log timezone in `data_lineage` for debugging

### 5.3 Data Quality from Third-Party Tools

**Risk:** Metricool CSV might have stale/incorrect data

**Mitigation:**
- Always prioritize official platform APIs/CSVs over third-party
- Flag large discrepancies (>20% difference) for review
- Allow users to manually "trust this source" in settings

### 5.4 Vendor Lock-In

**Risk:** Relying too heavily on one analytics tool (Metricool)

**Mitigation:**
- Design pipeline to be source-agnostic (plugin architecture)
- Store raw API responses in JSON for future re-processing
- Keep original CSV files for 90 days

---

## 6. Success Metrics

**Data Quality:**
- Conflict rate: <5% of updates trigger conflict resolution
- Flagged anomalies: <1% of updates (suspicious spikes/decreases)
- Missing data: <2% of posts have NULL metrics

**Performance:**
- Queue latency: 95th percentile <5 minutes
- API sync duration: <10 minutes per account
- Database query time: 95th percentile <100ms

**Reliability:**
- Job success rate: >99% (excluding API downtime)
- Uptime: >99.9% for queue worker
- Data freshness: 90% of metrics <24 hours old

**User Experience:**
- Users can see "Last synced" timestamp per account
- Failed syncs trigger notification within 1 hour
- Admin can manually retry failed jobs in 1 click

---

## 7. References & Sources

**Conflict Resolution Research:**
- [Conflict Resolution for Replicated Data - Informatica](https://docs.informatica.com/data-replication/data-replication/9-8-0/user-guide/understanding-data-replication/apply-processing/conflict-resolution-for-replicated-data.html)
- [Data Conflict Resolution - Dremio](https://www.dremio.com/wiki/data-conflict-resolution/)
- [Conflict Resolution Strategies in Data Synchronization - Medium](https://mobterest.medium.com/conflict-resolution-strategies-in-data-synchronization-2a10be5b82bc)

**Last-Write-Wins Implementation:**
- [How to Implement Last-Write-Wins - OneUptime](https://oneuptime.com/blog/post/2026-01-30-last-write-wins/view)
- [Last Writer Wins in Distributed Systems - Number Analytics](https://www.numberanalytics.com/blog/last-writer-wins-distributed-systems)
- [Conflict Resolution Types - Azure Cosmos DB](https://learn.microsoft.com/en-us/azure/cosmos-db/conflict-resolution-policies)

**Data Lineage Best Practices:**
- [Data Lineage Tracking: Complete Guide for 2026 - Atlan](https://atlan.com/know/data-lineage-tracking/)
- [Data Lineage Best Practices for 2026 - OvalEdge](https://www.ovaledge.com/blog/data-lineage-best-practices?hs_amp=true)
- [Automated Data Lineage: Implementation Guide - Alation](https://www.alation.com/blog/automated-data-lineage/)

**PHP Queue & Background Jobs:**
- [Implementing a Redis Job Queue Without Laravel - Martin Joo](https://martinjoo.dev/implementing-a-redis-job-queue-without-laravel)
- [Build a Job Queue From Scratch with PHP and Redis - Graham Sutton](https://grahamsutton.dev/build-a-job-queue-with-php-and-redis/)
- [Handling Background Jobs and Queues in PHP - ZeroExp.Dev](https://zeroexp.dev/handling-background-jobs-and-queues-in-php-optimize-performance-like-a-pro/)

**Cron Job & Rate Limiting Best Practices:**
- [Our complete cron job guide for 2026 - UptimeRobot](https://uptimerobot.com/knowledge-hub/cron-monitoring/cron-job-guide/)
- [API Rate Limiting 2026 - Levo](https://www.levo.ai/resources/blogs/api-rate-limiting-guide-2026)
- [Cron Jobs in Data Engineering - DataCamp](https://www.datacamp.com/tutorial/cron-job-in-data-engineering)

---

**Document Version:** 1.0
**Next Review:** After Phase 1 implementation
**Feedback:** Send to team-lead via SendMessage
