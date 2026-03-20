# Metricool API Integration Guide

**Complete guide for integrating Metricool with SocialBit**

---

## 📋 Overview

The Metricool integration enables automatic collection of social media posts and metrics from multiple platforms through a single API. This eliminates manual CSV imports and provides daily automated data synchronization.

### Supported Platforms

- ✅ Instagram (Feed Posts)
- ✅ Instagram Reels
- ✅ Instagram Stories
- ✅ Facebook Pages
- ✅ Facebook Groups
- ✅ LinkedIn
- ⚠️ TikTok (not explicitly documented, needs testing)
- ⚠️ YouTube (not explicitly documented, needs testing)

### Key Benefits

1. **Automated Data Collection** - Daily sync eliminates manual CSV imports
2. **Multi-Platform Support** - Single API for all connected platforms
3. **Historical Data** - Access to past metrics and posts
4. **Demographics** - Audience insights (age, gender, location)
5. **Real-time Updates** - Metrics update as posts perform

---

## 🔧 Setup Instructions

### Step 1: Metricool Account Setup

1. **Upgrade to Advanced Plan**
   - Visit: https://metricool.com/pricing
   - API access requires Advanced or Custom plan ($34/month minimum)
   - Free plan does NOT have API access

2. **Get API Credentials**
   - Log in to Metricool: https://app.metricool.com
   - Navigate to: Account Settings > API
   - Copy your **API Key** (userToken)
   - Note your **User ID** (shown in settings)

3. **Connect Social Media Accounts**
   - Go to: Add Profile > Connect your platforms
   - Connect Instagram, Facebook, TikTok accounts
   - Each connected account gets a unique **Blog ID**

### Step 2: Database Configuration

Store API credentials in the SocialBit settings table:

```sql
INSERT INTO settings (`key`, `value`, updated_at) VALUES
  ('metricool_api_key', 'YOUR_API_KEY_HERE', NOW()),
  ('metricool_user_id', 'YOUR_USER_ID_HERE', NOW())
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW();
```

**Finding Your Blog IDs:**

Run the test script to discover connected profiles and their Blog IDs:

```bash
php scripts/test_metricool_api.php
```

This will output something like:

```
Connected Profiles:
1. INSTAGRAM - @yourhandle (Blog ID: 12345)
2. FACEBOOK - Your Page (Blog ID: 67890)
```

Store each client's Blog ID:

```sql
-- For client_id = 1
INSERT INTO settings (`key`, `value`) VALUES
  ('metricool_blog_id_1', '12345');

-- For client_id = 2 (if multi-tenant)
INSERT INTO settings (`key`, `value`) VALUES
  ('metricool_blog_id_2', '67890');
```

### Step 3: Test Integration

Run the interactive test script:

```bash
php scripts/test_metricool_api.php
```

**Expected Output:**

```
✓ API connection successful!
✓ Found 2 connected profile(s)
✓ Found 15 post(s) in last 7 days
✓ Data mapping successful!
```

**Troubleshooting:**

| Error | Solution |
|-------|----------|
| `Invalid credentials` | Check API key and User ID in settings table |
| `No connected profiles` | Connect social media accounts in Metricool dashboard |
| `Platform not supported` | Try different platform name (instagram, facebook, linkedin) |
| `HTTP 403` | Upgrade to Advanced plan (API not available on Free) |

---

## 🔄 Scheduled Sync Setup

### Manual Sync (Testing)

Run sync manually to test:

```bash
php scripts/scheduled/metricool_sync.php
```

**Sample Output:**

```
===== Metricool Sync Started =====
✓ Database connection established
✓ Metricool credentials loaded
✓ Metricool API connection successful
→ Found 1 client(s) to sync

-------------------------------------------
Syncing: Giuditta Leuven (ID: 1)
-------------------------------------------
→ Using blogId: 12345
  → Syncing instagram...
    ✓ Created: 3, Updated: 12
  → Syncing facebook...
    ✓ Created: 1, Updated: 5

SYNC COMPLETED
Duration: 4.2s
Posts created: 4
Posts updated: 17
```

### Automated Daily Sync (Cron Job)

**Local Development (XAMPP on Windows):**

Use Windows Task Scheduler:

1. Open Task Scheduler
2. Create Basic Task
3. Trigger: Daily at 4:00 AM
4. Action: Start a program
5. Program: `C:\xampp3\php\php.exe`
6. Arguments: `C:\xampp3\htdocs\socialbit-live\scripts\scheduled\metricool_sync.php`
7. Save task

**Production (Plesk/Linux):**

Add cron job via Plesk or SSH:

```bash
# Edit crontab
crontab -e

# Add this line (runs daily at 4 AM)
0 4 * * * /usr/bin/php /var/www/vhosts/socialbit.g-bit.be/htdocs/scripts/scheduled/metricool_sync.php >> /var/log/metricool_sync.log 2>&1
```

**Verify Cron Job:**

```bash
# Check if cron is running
crontab -l

# View sync logs
tail -f /var/log/metricool_sync.log
```

---

## 📊 Data Mapping

### Metricool → SocialBit Field Mapping

| Metricool Field | SocialBit Field | Notes |
|----------------|-----------------|-------|
| `id` | `platform_post_id` | Unique post identifier |
| `text` / `caption` | `caption` | Post text content |
| `published_at` / `date` | `posted_date`, `posted_time` | Split into date and time |
| `impressions` / `views` | `views` | Total view count |
| `likes` | `likes` | Like count |
| `comments` | `comments` | Comment count |
| `shares` | `shares` | Share count |
| `saves` | `saves` | Save/bookmark count |
| `reach` | `reach` | Unique viewers (Instagram/Facebook) |
| `engagement` | `engagement_rate` | Engagement percentage |
| `url` / `permalink` | `post_url` | Link to post |

### Platform-Specific Differences

**Instagram Posts:**
- Has `reach` and `impressions` (impressions ≥ reach)
- Stories have `exits` and `replies`
- Reels have same metrics as posts

**Facebook Posts:**
- Has `reactions` instead of just `likes`
- Includes `clicks` metric
- Groups have different engagement metrics

**TikTok (if available):**
- Focus on `views` and `likes`
- May include `watch_time` and `completion_rate`

---

## 🔍 API Endpoints Reference

### Authentication

All requests require:

- **Header:** `X-Mc-Auth: YOUR_API_KEY`
- **Query Params:** `userId=YOUR_USER_ID&blogId=BLOG_ID`

### Available Endpoints

**Profile Management:**

```
GET /admin/simpleProfiles
→ Returns: List of connected social media accounts
```

**Fetch Posts:**

```
GET /stats/instagram/posts?start=YYYYMMDD&end=YYYYMMDD&blogId=X
GET /stats/instagram/reels?start=YYYYMMDD&end=YYYYMMDD&blogId=X
GET /stats/facebook/posts?start=YYYYMMDD&end=YYYYMMDD&blogId=X
GET /stats/linkedin/posts?start=YYYYMMDD&end=YYYYMMDD&blogId=X
```

**Aggregated Metrics:**

```
GET /stats/aggregations/Instagram?start=YYYYMMDD&end=YYYYMMDD&blogId=X
→ Returns: Sum/average of metrics for period
```

**Demographics:**

```
GET /stats/country/instagram?blogId=X
GET /stats/gender/instagram?blogId=X
GET /stats/age/instagram?blogId=X
```

**Last Sync Times:**

```
GET /profile/lastsyncs?blogId=X
→ Returns: Last sync timestamp for each platform
```

### Rate Limits

- **Unknown official limit** - Metricool docs don't specify
- **Best practice:** Add 1-second delay between requests
- **Implemented in sync script:** `sleep(1)` between platforms

---

## 🛠️ Advanced Configuration

### Multi-Tenant Setup

For clients with multiple social media accounts:

```sql
-- Client 1: Restaurant with Instagram + Facebook
INSERT INTO settings (`key`, `value`) VALUES
  ('metricool_blog_id_1', '12345'),  -- Instagram
  ('metricool_blog_id_1_fb', '12346'); -- Facebook

-- Client 2: E-commerce with TikTok + Instagram
INSERT INTO settings (`key`, `value`) VALUES
  ('metricool_blog_id_2', '67890'),  -- TikTok
  ('metricool_blog_id_2_ig', '67891'); -- Instagram
```

**Modify sync script to loop through client's blogIds.**

### Custom Sync Frequency

Default: Daily at 4 AM

For high-posting clients, increase frequency:

```bash
# Every 6 hours
0 */6 * * * /usr/bin/php /path/to/metricool_sync.php
```

### Historical Data Import

To import older data (beyond 30 days):

```php
// In metricool_sync.php, modify date range:
$startDate = date('Ymd', strtotime('-90 days')); // 3 months
$endDate = date('Ymd');
```

**Note:** Run this once manually, then revert to 30-day rolling window.

---

## 🐛 Troubleshooting

### Common Issues

**1. "Platform not supported" error**

```
✗ Platform 'tiktok' is not supported or endpoint not found
```

**Solution:** TikTok endpoint not documented in Swagger. Options:
- Use Metricool web interface to inspect network calls (F12 DevTools)
- Contact Metricool support for TikTok API endpoint
- Fallback to TikTok CSV export + manual import

**2. No posts returned (empty array)**

Possible causes:
- No posts in date range (try wider range)
- Platform not connected in Metricool
- Posts set to "Only show in ads" (not organic)

**Solution:** Check Metricool dashboard for post visibility.

**3. "Invalid blogId" error**

```
HTTP 400 - Invalid blogId parameter
```

**Solution:** Run test script to fetch correct blogIds for your account.

**4. Demographics return empty**

Some platforms/accounts don't provide demographics:
- Instagram Business accounts: ✅ Available
- Instagram Personal accounts: ❌ Not available
- Facebook Pages: ✅ Available
- TikTok: ⚠️ Unknown

---

## 📈 Monitoring & Maintenance

### Sync Health Checks

**Check last successful sync:**

```sql
SELECT * FROM posts
WHERE import_source = 'api'
ORDER BY last_updated DESC
LIMIT 10;
```

**Expected:** Recent timestamps (within 24 hours)

**Monitor sync logs:**

```bash
tail -f /var/log/metricool_sync.log
```

**Look for:**
- `✓ API connection successful`
- `Posts created: X, Posts updated: Y`
- `SYNC COMPLETED` message

**Alert on failures:**

Add monitoring to cron job:

```bash
0 4 * * * /usr/bin/php /path/to/metricool_sync.php || mail -s "Metricool Sync Failed" admin@example.com
```

### Data Quality Validation

**Check for duplicate posts:**

```sql
SELECT platform, platform_post_id, COUNT(*) as count
FROM posts
WHERE import_source = 'api'
GROUP BY platform, platform_post_id
HAVING count > 1;
```

**Expected:** No results (unique constraint should prevent this)

**Verify engagement rates:**

```sql
SELECT id, caption, views, likes, engagement_rate
FROM posts
WHERE import_source = 'api'
  AND engagement_rate = 0
  AND (likes > 0 OR comments > 0);
```

**Expected:** Empty (engagement_rate should be calculated)

---

## 🚀 Future Enhancements

### Planned Features

1. **Conflict Resolution** (Migration 015)
   - Data lineage tracking
   - Priority: API > Metricool > CSV > Manual
   - Audit log of data source changes

2. **Hashtag Auto-Extraction** (Migration 012)
   - Parse hashtags from Metricool captions
   - Populate hashtag_tracking table
   - Generate recommendations

3. **Demographics Sync**
   - Store audience data in `tiktok_demographics` table
   - Track follower growth over time

4. **Real-time Webhooks**
   - Investigate Metricool webhook support
   - Instant sync on new post published

5. **TikTok Integration**
   - Find TikTok-specific endpoint
   - Test with TikTok Business account
   - Document field mappings

---

## 📚 Additional Resources

**Metricool Documentation:**
- API Docs: https://app.metricool.com/resources/apidocs/
- Help Center: https://help.metricool.com/en/category/api-integrations-dc0snw/
- Swagger JSON: https://app.metricool.com/api/swagger.json

**SocialBit Documentation:**
- Database Schema: `scripts/000_create_database_schema.sql`
- Implementation Plan: `docs/IMPLEMENTATION_PLAN.md`
- Project Context: `CLAUDE.md`

**Support:**
- Metricool Support: support@metricool.com
- Developer (Bjorn): Ask in project chat

---

**Last Updated:** 2026-02-07
**Integration Status:** ✅ Ready for Testing
**Next Step:** Run `php scripts/test_metricool_api.php`
