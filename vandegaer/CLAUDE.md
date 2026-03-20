# SocialBit - Claude Code Project Context

**Multi-tenant social media analytics platform - Data-first approach**

> **For Agent Teams:** This file provides project context for all Claude Code agents working on SocialBit. Read this completely before starting work.
>
> Whenever actions are done/completed by an agent, write the task description in actions.md with a summary and date. This to evaluate en possible update this file.

---

## 🎯 Project Overview

**Mission:** Build a comprehensive multi-platform analytics & business management platform for social media businesses.

**Vision:** All-in-one platform combining:
- Social media analytics (TikTok, Instagram, Facebook, YouTube)
- Website analytics (Google Business, Fathom Analytics)
- Hashtag tracking & recommendations
- Competitor analysis & benchmarking
- Website traffic correlation (which posts drive site visits)
- Future: Branding tools, POS system, reservations

**Key Principle:** Data quality and collection > UI polish

**Status:** POC v1.0 → Multi-platform MVP
**Live URL:** https://socialbit.g-bit.be/
**Developer:** Bjorn (part-time) + AI agents (full-time)
**Financial Runway:** 24 months

---

## 🚀 CURRENT PRIORITIES (Updated 2026-02-07)

**Immediate Focus (Week 1-4):**
1. ✅ **Fix TikTok API integration** (developer account blocked - needs troubleshooting)
2. 🔴 **Database migrations** - Add client_id to metrics_history, 2026 algorithm metrics
3. 🔴 **Metricool integration** - Primary data source (upgrade to Advanced plan)
4. 🔴 **Google Business API** - Local business stats, reviews, Q&A
5. 🔴 **Fathom Analytics** - Privacy-first website tracking
6. 🔴 **Hashtag tracking system** - Track performance, generate recommendations
7. 🔴 **Website traffic correlation** - Link social posts to site visits

**Strategy Decisions:**
- ❌ **NO phased rollout** - Multi-platform from start
- ❌ **NO TikTok-only MVP** - All platforms simultaneously
- ✅ **Multi-source data** - Metricool (primary) + Direct APIs (gaps) + CSV (fallback)
- ✅ **Competitor analysis** - Track competitor metrics for benchmarking

---

## 🏗️ Tech Stack

### Backend

- **Language:** Vanilla PHP 8.4+ (NO framework - deliberate choice for simplicity)
- **Architecture:** 3-layer MVC pattern
  - `Controllers/` - HTTP handling, validation, response formatting
  - `Services/` - Business logic, orchestration
  - `Repositories/` - Database access layer (PDO)
- **Database:** MySQL/MariaDB
- **Authentication:** Simple session-based (production: JWT planned)

### Frontend

- **Vanilla JavaScript** (no React/Vue - keep it simple)
- **CSS:** Plain CSS (no Tailwind/Bootstrap yet)
- **Charts:** Chart.js for visualizations

### Infrastructure

- **Local:** XAMPP (Windows) - MySQL on port 3306
- **Production:** Plesk hosting (cPanel-style)
- **Version Control:** Git (GitHub: Gbit-bjorn/socialbit-live)

---

## 📂 Project Structure

```
socialbit-live/
├── config/
│   ├── app.php              # Auto-detects local vs production environment
│   └── app.example.php      # Example configuration file
├── public/
│   ├── index.html           # Main dashboard
│   ├── index2.php           # Alternative PHP entry point
│   ├── .htaccess            # URL rewriting rules
│   └── assets/
│       ├── app.js           # Frontend JavaScript
│       └── styles.css       # Frontend CSS
├── src/
│   ├── Controllers/         # HTTP request handlers
│   │   ├── AnalyticsController.php
│   │   ├── AuthController.php
│   │   ├── ImportController.php
│   │   ├── LabelController.php
│   │   ├── PlanningController.php
│   │   ├── PostController.php
│   │   ├── PostEditController.php
│   │   ├── SettingsController.php
│   │   ├── TikTokAnalyticsController.php
│   │   └── TikTokOAuthController.php
│   ├── Services/            # Business logic
│   │   ├── EngagementService.php
│   │   ├── GenericCsvImporter.php
│   │   ├── TikTokAnalyticsService.php
│   │   ├── TikTokCsvImporter.php
│   │   └── TikTokOAuthService.php
│   ├── Repositories/        # Database access layer (PDO)
│   │   ├── AnalyticsRepository.php
│   │   ├── ClientRepository.php
│   │   ├── PlanningRepository.php
│   │   ├── PostRepository.php
│   │   ├── SettingsRepository.php
│   │   ├── TikTokRepository.php
│   │   └── UserRepository.php
│   ├── Middleware/
│   │   └── Auth.php         # Authentication middleware
│   ├── Helpers/             # Utilities
│   │   ├── Request.php      # HTTP request helper
│   │   ├── Response.php     # JSON response formatter
│   │   └── Validation.php   # Input validation
│   └── Core/                # Core framework classes
│       ├── Database.php     # PDO database wrapper
│       └── Router.php       # URL routing
├── scripts/                 # Database migrations & CLI tools
│   ├── 000_create_database_schema.sql      # Initial schema
│   ├── 001_create_settings_table.sql
│   ├── 002_add_post_type_topic.sql
│   ├── 003_create_content_planning.sql
│   ├── 004_add_internal_fields_posts.sql
│   ├── 005_create_tiktok_tokens_table.sql
│   ├── 006_multi_tenant_foundation.sql     # Multi-tenant migration
│   ├── 007_analytics_enhancement.sql       # Analytics improvements
│   ├── 010_multi_tenant_performance.sql    # Performance optimization
│   ├── 011_algorithm_metrics_2026.sql      # 2026 algorithm metrics
│   ├── 012_hashtag_tracking.sql            # Hashtag tracking tables
│   ├── 013_competitor_analysis.sql         # Competitor analysis tables
│   ├── 014_website_traffic.sql             # Website traffic correlation
│   ├── 015_data_lineage.sql                # Data source tracking
│   ├── 016_google_business.sql             # Google Business integration
│   ├── migrate_csv_to_db.php               # CSV import CLI
│   ├── smoke-test.php                      # Basic smoke tests
│   └── content_tracker_enhanced.csv        # Sample data
├── storage/
│   ├── logs/                # Application logs
│   │   └── .gitkeep
│   └── uploads/             # CSV file uploads (not tracked)
├── docs/                    # Project documentation
│   ├── archive/             # Archived strategy documents
│   │   └── 2026-02-07-strategy-analysis/
│   │       ├── 2026-social-media-data-strategy.md
│   │       ├── ANALYTICS_STRATEGY.md
│   │       ├── CRITICAL_RISK_ASSESSMENT.md
│   │       ├── database-recommendations-2026-02-07.md
│   │       ├── DATA_PIPELINE_ARCHITECTURE.md
│   │       └── PRIORITY-METRICS-SUMMARY.md
│   ├── IMPLEMENTATION_PLAN.md              # Current implementation plan
│   ├── TIKTOK_API_TROUBLESHOOTING.md       # TikTok API debugging guide
│   ├── migration_010_log.md                # Migration 010 execution log
│   ├── migrations_011_016_log.md           # Migrations 011-016 log
│   └── STRATEGY_REPORT_SUMMARY.html        # Strategy report overview
├── CLAUDE.md                # Project context for Claude agents (this file)
├── actions.md               # Agent task log and completed actions
├── g-bit_socialbit.sql      # Latest production database dump
├── .claude/                 # Claude Code configuration
├── .serena/                 # Serena agent configuration
└── .worktrees/              # Git worktrees (if used)
```

---

## 💾 Database Architecture

### Local Database

```
Host:     127.0.0.1:3306
Database: social_media_analytics
User:     root
Password: (empty)
```

### Production Database

```
Database: g-bit_socialbit
User:     g-bit_socialbit
Password: (see config/app.php)
```

### Core Tables

**Multi-tenant Foundation (Migration 006):**

- `clients` - Tenant/customer accounts
- All tables have `client_id` foreign key
- CASCADE DELETE for data isolation

**Content Tables:**

- `posts` - Core social media posts (all platforms)
- `metrics_history` - Time-series snapshots
- `post_hashtags` - Hashtag associations
- `content_planning` - Editorial calendar

**Platform Integration:**

- `tiktok_tokens` - OAuth tokens (multi-tenant)
- `tiktok_demographics` - Audience insights
- (Instagram/Facebook tables planned)

**Analytics Views:**

- `top_posts` - Best performing content
- `platform_comparison` - Cross-platform metrics
- `hashtag_leaderboard` - Hashtag performance
- `hashtag_performance` - Detailed hashtag analytics

**Schema Files:**

- `scripts/000_create_database_schema.sql` - Full schema DDL
- `scripts/006_multi_tenant_foundation.sql` - Multi-tenant migration
- See `DATABASE_EVOLUTION.md` for migration strategy

---

## 🔐 Coding Standards

### PHP Code Style

- **PSR-12** standard (use `phpcbf` for auto-formatting if available)
- **Type hints** on all function parameters and return types
- **Prepared statements** - ALWAYS (no raw SQL, prevent injection)
- **Error handling** - Try/catch with meaningful error messages
- **Naming:**
  - Classes: `PascalCase`
  - Methods/functions: `camelCase`
  - Variables: `$camelCase`
  - Constants: `SCREAMING_SNAKE_CASE`

### Architecture Patterns

**Repository Pattern (Database Access):**

```php
class PostRepository {
    public function findById(int $id): ?array
    public function findByClient(int $clientId): array
    public function create(array $data): int
    public function update(int $id, array $data): bool
}
```

**Service Layer (Business Logic):**

```php
class TikTokAnalyticsService {
    public function __construct(
        private PostRepository $postRepo,
        private MetricsRepository $metricsRepo
    ) {}

    public function calculateEngagementRate(int $postId): float {
        // Business logic here
    }
}
```

**Controller (Thin - orchestration only):**

```php
class AnalyticsController {
    public function getTopPosts(Request $request): Response {
        $clientId = $request->session('client_id');
        $posts = $this->analyticsService->getTopPosts($clientId);
        return Response::json($posts);
    }
}
```

### SQL Best Practices

- **Always use prepared statements** (PDO with named parameters)
- **Index foreign keys** and frequently queried columns
- **Avoid SELECT \*** - specify columns explicitly
- **Use database views** for complex/repeated queries
- **Timezone:** Store all timestamps in UTC, convert in application layer

---

## ⚠️ Important Constraints

### DO NOT

- ❌ Install frameworks (Laravel/Symfony) - vanilla PHP by design
- ❌ Use ORMs (Eloquent/Doctrine) - direct PDO queries
- ❌ Add heavy dependencies - keep it lightweight
- ❌ Create UI/UX improvements without data functionality first
- ❌ Make database schema changes without migration scripts
- ❌ Commit directly to `master` - use feature branches

### DO

- ✅ Write migration scripts for ALL schema changes
- ✅ Add comprehensive error handling and logging
- ✅ Use prepared statements for ALL database queries
- ✅ Test locally in XAMPP before deploying to Plesk
- ✅ Document complex logic in code comments
- ✅ Follow existing architecture patterns

---

## 🧪 Testing

**Current Approach:** Manual testing (no PHPUnit yet - planned for Month 3)

**Local Testing:**

1. XAMPP on Windows (http://localhost/socialbit-live)
2. Import test CSV: `storage/uploads/sample_tiktok_export.csv`
3. Run smoke test: `php scripts/smoke-test.php`

**Production Testing:**

- Manual testing on Plesk staging before live deployment
- Database backups before risky migrations

---

## 🔧 Development Workflow

### Git Workflow

```bash
# Create feature branch
git checkout -b feature/multi-tenant-auth

# Commit with co-author
git commit -m "feat: add client authentication

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"

# Push and create PR
git push -u origin feature/multi-tenant-auth
gh pr create --title "Add multi-tenant authentication"
```

### Database Migrations

```bash
# Local migration
mysql -u root social_media_analytics < scripts/006_multi_tenant_foundation.sql

# Production (via Plesk phpMyAdmin or SSH)
mysql -u g-bit_socialbit -p g-bit_socialbit < scripts/006_multi_tenant_foundation.sql
```

---

## 🤝 Agent Team Guidelines

### For Research Agents

- Use web search to research 2026 social media API capabilities
- Check competitor features (Hootsuite, Buffer, ...)
- Validate industry benchmarks and best practices

### For Database Agents

- Always create migration scripts (numbered: `00X_description.sql`)
- Consider multi-tenant implications (client_id isolation)
- Optimize for time-series queries (metrics_history partitioning)
- Test with realistic data volumes (10K+ posts)

### For Backend Agents

- Follow the 3-layer pattern (Controller → Service → Repository)
- Never put business logic in controllers
- Always use dependency injection
- Error responses: JSON with `{success: false, error: "message"}`

### For Frontend Agents

- Keep JavaScript vanilla (no build tools yet)
- Mobile-first responsive design
- Chart.js for all visualizations
- API calls return JSON, handle errors gracefully

### For Business/UX Agents

- Focus on ACTIONABLE insights, not just data display
- Non-technical users need plain language ("2× better than average")
- Metrics need context (benchmarks, historical comparison)
- Prioritize features business owners actually use

---

## 🔌 Platform Integrations (Required)

### Primary Data Sources

**1. Metricool API (Primary - Multi-Platform)**
- **Status:** Upgrade to Advanced plan (IMMEDIATE)
- **Coverage:** TikTok, Instagram, Facebook, YouTube
- **Collection:** Daily batch (4 AM per tenant)
- **Use Case:** Standard metrics across all platforms
- **API Docs:** https://metricool.com/api/

**2. Google Business API (NEW - High Priority)**
- **Coverage:** Local business stats, reviews, Q&A, photos
- **Metrics:** Search appearances, actions (calls, directions, website clicks)
- **Use Case:** Local SEO performance, review management
- **API:** Google My Business API v4.9

**3. Fathom Analytics (NEW - High Priority)**
- **Coverage:** Privacy-first website analytics
- **Metrics:** Page views, unique visitors, referrers, goals
- **Use Case:** Website traffic correlation with social posts
- **API:** Fathom Analytics API v1
- **Note:** NOT in Metricool - separate integration needed

**4. Direct Platform APIs (Gap Filling)**
- **TikTok API:** Completion rate, watch time (when approved)
- **Instagram Graph API:** DM shares, skip rate, crossposted views
- **Facebook Graph API:** Similar gap metrics
- **Collection:** Weekly (to supplement Metricool data)

**5. CSV Import (Fallback)**
- **Use Case:** Historical data, API outages, manual corrections
- **Formats:** TikTok Studio export, Instagram Insights, custom formats

### Data Collection Strategy

**Multi-Source Conflict Resolution:**
```
Priority Hierarchy:
1. Direct Platform API (most authoritative)
2. Metricool API (standardized, reliable)
3. CSV Export (manual, might be outdated)
4. Manual Entry (lowest priority)

Rule: Last Write Wins within same tier, unless newer data available
Storage: Full audit trail in `data_lineage` table
```

**Collection Schedule:**
- **Metricool:** Daily at 4 AM (all platforms)
- **Google Business:** Daily at 5 AM
- **Fathom:** Daily at 6 AM
- **Direct APIs:** Weekly on Sundays (gap metrics)
- **CSV:** On-demand by user

---

## 📊 Required Analytics Features

### 1. Hashtag Tracking & Analysis (NEW - Critical)

**Purpose:** Help users choose effective hashtags for future posts

**Database Tables:**
```sql
hashtag_tracking (
  hashtag, total_uses, avg_views, avg_engagement,
  best_performing_post_id, last_used_date
)

hashtag_recommendations (
  hashtag, score, reason, competitors_using
)
```

**Features:**
- Track all hashtags used across posts
- Calculate average performance per hashtag
- Identify trending vs declining hashtags
- Recommend hashtags based on past success
- Show competitor hashtag usage

**UI:**
- Hashtag leaderboard (top 20 by engagement)
- Hashtag performance trend chart
- "Suggested hashtags for your next post"
- "Avoid these hashtags" (poor performers)

### 2. Competitor Analysis (NEW - Important)

**Purpose:** Benchmark against competitor performance

**Database Tables:**
```sql
competitors (
  client_id, competitor_name, platform, profile_url
)

competitor_metrics (
  competitor_id, date, followers, avg_engagement_rate,
  posting_frequency, top_hashtags JSON
)
```

**Features:**
- Track up to 5 competitors per client
- Daily snapshot of competitor metrics
- Side-by-side comparison dashboard
- Hashtag overlap analysis
- Content gap analysis (topics they cover, you don't)

**Collection:**
- Manual competitor profiles setup by user
- Automated daily scraping (where allowed by TOS)
- Or manual CSV import of competitor data

### 3. Website Traffic Correlation (NEW - Critical)

**Purpose:** Show which social posts drive website traffic

**Database Tables:**
```sql
post_website_traffic (
  post_id, fathom_date, referral_visits,
  referral_source, bounce_rate, avg_session_duration
)
```

**Features:**
- Link Fathom referral data to specific posts
- Show "Top 10 posts that drove site traffic"
- UTM parameter tracking (if used)
- Conversion tracking (goals from Fathom)

**Implementation:**
- Match Fathom referrer URLs to post platform
- Time-based correlation (traffic spike after post)
- Display traffic impact on post detail page

**UI Insights:**
- "This post drove 245 website visits (+15% bounce rate)"
- "Posts with [product links] drive 3× more traffic"
- "Best time to post for website traffic: Tuesday 9 AM"

### 4. 2026 Algorithm Metrics (Critical)

**Required Columns:**
```sql
ALTER TABLE posts ADD COLUMN watch_time INT;
ALTER TABLE posts ADD COLUMN completion_rate DECIMAL(5,2);
ALTER TABLE posts ADD COLUMN sends_count INT;  -- DM shares
ALTER TABLE posts ADD COLUMN profile_visits INT;
ALTER TABLE posts ADD COLUMN skip_rate DECIMAL(5,2);  -- Instagram Reels
ALTER TABLE posts ADD COLUMN duration INT;  -- video length in seconds
```

**Sources:**
- Direct API (when available)
- CSV import (TikTok Studio has completion rate)
- Calculated (completion_rate = watch_time / duration × 100)

---

## 💡 Architecture Decisions

**Why Vanilla PHP?**

- Single developer - framework overhead not justified
- Deployment simplicity (Plesk hosting, no Composer on production)
- Full control, no magic, easier to debug

**Why No ORM?**

- Direct control over SQL for performance optimization
- Simpler data access patterns for time-series analytics
- Avoid N+1 query problems with complex analytics

**Why Manual CSV Import?**

- TikTok API access is difficult (requires business verification)
- Users might have export from sources that contain valuable info
- CSV export is reliable and available to all users

**Why MySQL (not PostgreSQL)?**

- Existing Plesk infrastructure (MariaDB)
- Good enough for MVP (millions of rows tested)
- Time-series extensions available if needed
- Migration to PostgreSQL/TimescaleDB considered for future

---

## 🚨 CRITICAL SECURITY ALERT (2026-02-07)

**⚠️ BEFORE ANY COMMITS:**

A comprehensive security audit discovered **4 critical credential exposures**. Complete details in `docs/SECURITY_AUDIT_REPORT.md`.

**IMMEDIATE ACTION REQUIRED:**
1. 🔴 Rotate GitHub token `gho_T78GA...` (in untracked docs - DO FIRST!)
2. 🔴 Change production DB password `MiNiMiN1L5uv5n!` (in git history)
3. 🟡 Regenerate TikTok credentials (in git history)
4. Add `config/app.php` to `.gitignore`

**Total time:** ~45 minutes | **Action Plan:** `docs/ACTION_PLAN_2026-02-07.html`
**Full Audit:** `docs/reports/2026-02-07-audit/SECURITY_AUDIT_REPORT.md`

---

## 📋 PENDING OPTIMIZATIONS (2026-02-07)

**CLAUDE.md Optimization Scheduled:**
- Current: 593 lines (197% over recommended limit)
- Target: <250 lines (move details to dedicated docs)
- Status: Plan ready in `docs/ACTION_PLAN_2026-02-07.html`
- Implementation: Week of 2026-02-10 (4-day plan)

**Serena Deprecation:**
- Status: 90% outdated (last update 2025-12-31)
- Action: Archive to `docs/archive/` after CLAUDE.md optimization
- Details: `docs/reports/2026-02-07-audit/SERENA_DEPRECATION_ANALYSIS.md`

**See Full Reports:** `docs/reports/2026-02-07-audit/`

---

**Last Updated:** 2026-02-07 (Added security alert, competitor tracking, optimization plan)
**For Questions:** Ask user (Bjorn) or check `docs/ACTION_PLAN_2026-02-07.html`
