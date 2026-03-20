# Metricool Integration - Quick Start Guide

**Get started with Metricool in 5 minutes**

---

## ⚡ Quick Setup Checklist

- [ ] Metricool Advanced plan active
- [ ] API key from Metricool settings
- [ ] Credentials stored in database
- [ ] Test script passes
- [ ] Cron job configured

---

## 🚀 Step-by-Step Setup

### 1. Store API Credentials (2 minutes)

Open your database (phpMyAdmin or MySQL CLI) and run:

```sql
USE social_media_analytics;  -- or g-bit_socialbit on production

INSERT INTO settings (`key`, `value`, updated_at) VALUES
  ('metricool_api_key', 'YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB', NOW()),
  ('metricool_user_id', 'YOUR_USER_ID_HERE', NOW())
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW();
```

**Where to find your credentials:**
1. Go to: https://app.metricool.com/account/settings/api
2. Copy **API Key** (userToken)
3. Note your **User ID** (shown on same page)

---

### 2. Run Test Script (1 minute)

```bash
cd C:\xampp3\htdocs\socialbit-live

# Local (XAMPP)
php scripts/test_metricool_api.php

# Production (Plesk/SSH)
php scripts/test_metricool_api.php
```

**Expected output:**

```
✓ API connection successful!
✓ Found 2 connected profile(s):
  1. INSTAGRAM - @yourhandle (Blog ID: 12345)
  2. FACEBOOK - Your Page (Blog ID: 67890)
✓ Found 8 post(s) in last 7 days
✓ Data mapping successful!
```

**If you see errors:**
- Check API key is correct (no extra spaces)
- Verify Metricool Advanced plan is active
- Ensure social media accounts are connected in Metricool

---

### 3. Store Blog IDs (1 minute)

From the test output above, note the **Blog ID** numbers. Then run:

```sql
-- For your first client (client_id = 1)
-- Use the Blog ID from the test output
INSERT INTO settings (`key`, `value`) VALUES
  ('metricool_blog_id_1', '12345');  -- Replace with your actual Blog ID
```

**Multi-client setup:**

```sql
-- Client 1
INSERT INTO settings (`key`, `value`) VALUES ('metricool_blog_id_1', '12345');

-- Client 2 (if you have multiple clients)
INSERT INTO settings (`key`, `value`) VALUES ('metricool_blog_id_2', '67890');
```

---

### 4. Test Manual Sync (1 minute)

```bash
php scripts/scheduled/metricool_sync.php
```

**Expected output:**

```
===== Metricool Sync Started =====
✓ Database connection established
✓ Metricool API connection successful

Syncing: Giuditta Leuven (ID: 1)
  → Syncing instagram...
    ✓ Created: 5, Updated: 3
  → Syncing facebook...
    ✓ Created: 2, Updated: 1

SYNC COMPLETED
Posts created: 7
Posts updated: 4
```

**Verify in database:**

```sql
SELECT platform, caption, views, likes, posted_date
FROM posts
WHERE import_source = 'api'
ORDER BY created_at DESC
LIMIT 10;
```

You should see your social media posts!

---

### 5. Setup Automated Sync (Cron Job)

**On Windows (XAMPP) - Task Scheduler:**

1. Open **Task Scheduler**
2. Click **Create Basic Task**
3. Name: "Metricool Sync"
4. Trigger: **Daily** at **4:00 AM**
5. Action: **Start a program**
   - Program: `C:\xampp3\php\php.exe`
   - Arguments: `C:\xampp3\htdocs\socialbit-live\scripts\scheduled\metricool_sync.php`
6. **Finish**

**On Linux/Plesk - Cron Job:**

```bash
crontab -e
```

Add this line:

```bash
0 4 * * * /usr/bin/php /var/www/vhosts/socialbit.g-bit.be/htdocs/scripts/scheduled/metricool_sync.php >> /var/log/metricool_sync.log 2>&1
```

**Verify cron:**

```bash
crontab -l  # List cron jobs
tail -f /var/log/metricool_sync.log  # Watch logs
```

---

## ✅ Verification Checklist

After setup, verify everything works:

### Database Check

```sql
-- 1. Credentials stored?
SELECT `key`, LEFT(`value`, 10) as value_preview
FROM settings
WHERE `key` LIKE 'metricool%';

-- Expected: 3 rows (api_key, user_id, blog_id_1)
```

```sql
-- 2. Posts imported?
SELECT COUNT(*) as total_api_posts
FROM posts
WHERE import_source = 'api';

-- Expected: > 0
```

```sql
-- 3. Recent data?
SELECT platform, COUNT(*) as count, MAX(last_updated) as last_sync
FROM posts
WHERE import_source = 'api'
GROUP BY platform;

-- Expected: Recent timestamp (within 24 hours)
```

### API Check

Run test again to confirm:

```bash
php scripts/test_metricool_api.php
```

All tests should pass with ✓ green checkmarks.

---

## 🎯 Next Steps

### Immediate (Week 1)

- [ ] Monitor first 3 days of automated syncs
- [ ] Verify no duplicate posts in database
- [ ] Check sync logs for errors

### Soon (Week 2)

- [ ] Enable hashtag auto-extraction (Migration 012)
- [ ] Setup competitor tracking
- [ ] Configure demographics sync

### Later (Month 2)

- [ ] Add more clients/blogIds
- [ ] Implement conflict resolution (API priority)
- [ ] Setup monitoring alerts

---

## 🐛 Common Issues & Quick Fixes

### "Credentials not found"

```bash
# Check if settings exist
mysql -u root -p social_media_analytics -e "SELECT * FROM settings WHERE \`key\` LIKE 'metricool%';"
```

**Fix:** Re-run Step 1 (Store credentials)

### "No connected profiles"

**Problem:** No social media accounts linked in Metricool

**Fix:**
1. Go to https://app.metricool.com
2. Click "Add Profile"
3. Connect Instagram, Facebook, etc.
4. Re-run test script

### "Platform not supported"

**Problem:** TikTok endpoint not documented

**Workaround:**
- Use Instagram/Facebook for now
- TikTok: fallback to CSV import
- Report to Bjorn for investigation

### Sync creates duplicates

**Should NOT happen** - posts table has unique constraint on (platform, platform_post_id)

**If it does:**
```sql
-- Find duplicates
SELECT platform, platform_post_id, COUNT(*) as count
FROM posts
GROUP BY platform, platform_post_id
HAVING count > 1;
```

**Fix:** Contact developer (Bjorn) - possible schema issue

---

## 📞 Get Help

**Test script output:**
Save output and share with developer:

```bash
php scripts/test_metricool_api.php > metricool_test_output.txt
```

**Sync logs:**

```bash
# Show last 50 lines
tail -n 50 /var/log/metricool_sync.log
```

**Database state:**

```sql
-- Export settings for review
SELECT * FROM settings WHERE `key` LIKE 'metricool%';

-- Export recent API posts
SELECT * FROM posts WHERE import_source = 'api' ORDER BY created_at DESC LIMIT 20;
```

---

## 📚 Full Documentation

For detailed information, see:

- **Full Integration Guide:** `docs/METRICOOL_INTEGRATION.md`
- **Implementation Plan:** `docs/IMPLEMENTATION_PLAN.md`
- **Database Schema:** `scripts/000_create_database_schema.sql`

---

**Setup Time:** ~5 minutes
**Status:** Ready to use
**Support:** Ask Bjorn in project chat
