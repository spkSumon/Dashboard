# Migration 019 & 020 Summary

**Date:** 2026-02-07
**Database Architect:** Claude Sonnet 4.5
**Status:** ✅ Ready for Execution

---

## Overview

Two critical database migrations ready to deploy:
1. **Migration 019** - Glossary system for analytics terminology
2. **Migration 020** - Inbox/messages system for social media interactions

---

## Migration 019: Glossary System

### Purpose
Help non-technical users (restaurant owners) understand social media analytics terminology through an interactive glossary.

### Database Schema

```sql
CREATE TABLE glossary (
  id INT AUTO_INCREMENT PRIMARY KEY,
  term VARCHAR(100) NOT NULL,
  definition TEXT NOT NULL,
  example TEXT,
  category ENUM('metrics', 'algorithm', 'content_types', 'tools', 'general'),
  platforms JSON,  -- ['tiktok', 'instagram', 'facebook']
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_term (term),
  INDEX idx_category (category)
);
```

### Pre-loaded Content

**50 Essential Terms** across 5 categories:

**Metrics (17 terms):**
- Engagement Rate, Likes, Comments, Shares, Saves
- Reach, Impressions, Views, Organic Reach, Paid Reach
- Completion Rate, Watch Time, Skip Rate, Sends, Profile Visits
- Followers, Follower Growth

**Algorithm (2026 Focus - 5 terms):**
- Algorithm, Completion Rate, Watch Time, Skip Rate, Sends

**Platforms (8 terms):**
- TikTok, Instagram, Instagram Feed, Instagram Stories, Instagram Reels
- Facebook, YouTube, Google Business

**Content Types (4 terms):**
- Reel, Story, Carousel, Feed Post

**General Analytics (11 terms):**
- KPI, Analytics, Benchmark, Hashtag, Trending, Viral
- Audience, Demographics, Target Audience, Content Strategy, Engagement

**Business Terms (5 terms):**
- CTR, Conversion, ROI, Call-to-Action, User-Generated Content

### Key Features

- **100% Dutch language** - Plain language for business owners
- **Real-world examples** - Restaurant/pizzeria context
- **Platform-specific notes** - JSON field tracks which platforms each term applies to
- **Category organization** - Easy filtering and navigation
- **No jargon** - Explains technical terms in simple words

### Example Entries

```sql
('Engagement Rate',
 'Het percentage mensen dat interacteert met je content (likes, comments, shares) in verhouding tot het aantal mensen dat het ziet.',
 'Een post met 100 likes en 5000 views heeft een engagement rate van 2%.',
 'metrics',
 '["all"]'
),

('Completion Rate',
 'Het percentage mensen dat je video helemaal tot het einde bekijkt. Dit is ZEER belangrijk voor het TikTok/Instagram algoritme in 2026.',
 'Een completion rate van 75% betekent dat 3 van de 4 mensen je video helemaal uitkijken - uitstekend!',
 'algorithm',
 '["tiktok", "instagram", "youtube"]'
)
```

---

## Migration 020: Inbox/Messages System

### Purpose
Metricool-style unified inbox for managing social media interactions across all platforms.

### Database Schema

#### 1. Conversations Table

```sql
CREATE TABLE conversations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  post_id INT,  -- Link to post (optional for DMs)
  platform ENUM('tiktok', 'instagram', 'facebook', 'google_business'),
  platform_conversation_id VARCHAR(255),
  participant_name VARCHAR(255),
  participant_username VARCHAR(255),
  participant_avatar_url TEXT,
  is_resolved BOOLEAN DEFAULT FALSE,
  is_starred BOOLEAN DEFAULT FALSE,
  unread_count INT DEFAULT 0,
  last_message_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL
);
```

#### 2. Messages Table

```sql
CREATE TABLE messages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  conversation_id INT NOT NULL,
  platform_message_id VARCHAR(255),
  sender_type ENUM('customer', 'business'),
  sender_name VARCHAR(255),
  message_text TEXT,
  is_read BOOLEAN DEFAULT FALSE,
  sent_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);
```

#### 3. Conversation Participants Table

```sql
CREATE TABLE conversation_participants (
  id INT PRIMARY KEY AUTO_INCREMENT,
  conversation_id INT NOT NULL,
  platform_user_id VARCHAR(255),
  username VARCHAR(255),
  display_name VARCHAR(255),
  avatar_url TEXT,
  role ENUM('owner', 'participant') DEFAULT 'participant',
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);
```

#### 4. Inbox Filters Table

```sql
CREATE TABLE inbox_filters (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  filter_name VARCHAR(50),
  filter_config JSON,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);
```

### Helper Views

#### inbox_unresolved
```sql
-- Returns all unresolved conversations with message counts
CREATE VIEW inbox_unresolved AS
SELECT
  c.*,
  COUNT(m.id) as total_messages,
  SUM(CASE WHEN m.is_read = FALSE THEN 1 ELSE 0 END) as unread_messages
FROM conversations c
LEFT JOIN messages m ON c.id = m.conversation_id
WHERE c.is_resolved = FALSE
GROUP BY c.id
ORDER BY c.last_message_at DESC;
```

#### inbox_unread_counts
```sql
-- Aggregate unread counts per client per platform (for badges)
CREATE VIEW inbox_unread_counts AS
SELECT
  client_id,
  platform,
  COUNT(*) as unread_conversations,
  SUM(unread_count) as total_unread_messages
FROM conversations
WHERE is_resolved = FALSE AND unread_count > 0
GROUP BY client_id, platform;
```

#### inbox_recent_activity
```sql
-- Last 50 messages across all conversations (activity feed)
CREATE VIEW inbox_recent_activity AS
SELECT
  c.id as conversation_id,
  c.client_id,
  c.platform,
  c.participant_name,
  m.message_text,
  m.sender_type,
  m.sent_at,
  c.is_resolved
FROM conversations c
INNER JOIN messages m ON c.id = m.conversation_id
ORDER BY m.sent_at DESC
LIMIT 50;
```

### Key Features

- **Multi-platform unified inbox** - All social networks in one place
- **Conversation threading** - Messages grouped by conversation
- **Status tracking** - Resolved/unresolved, read/unread, starred
- **Platform filtering** - Filter by TikTok, Instagram, Facebook, etc.
- **Multi-tenant secure** - Full client_id isolation with CASCADE DELETE
- **Group DM support** - conversation_participants table for multi-user threads
- **Search ready** - Structured for full-text search (can add later)
- **Filter presets** - Users can save custom filter configurations

---

## Execution Instructions

### Prerequisites

- XAMPP running with MySQL
- Database: `social_media_analytics`
- User: `root` (no password)
- Migration 006 applied (multi-tenant foundation with `clients` table)

### Execute Migrations

**Option 1: Command Line**

```bash
cd C:\xampp3\htdocs\socialbit-live

# Execute Migration 019
mysql -u root social_media_analytics < scripts/019_glossary.sql

# Execute Migration 020
mysql -u root social_media_analytics < scripts/020_inbox_messages.sql
```

**Option 2: Browser (phpMyAdmin)**

1. Navigate to: `http://localhost/phpmyadmin`
2. Select database: `social_media_analytics`
3. Go to "Import" tab
4. Choose file: `scripts/019_glossary.sql`
5. Click "Go"
6. Repeat for `scripts/020_inbox_messages.sql`

### Verify Success

**Run Diagnostic Script:**

```bash
php scripts/check_database_status.php
```

Or via browser: `http://localhost/socialbit-live/scripts/check_database_status.php`

**Expected Output:**

```
✓ glossary table exists
✓ conversations table exists
✓ messages table exists
✓ conversation_participants table exists
✓ inbox_filters table exists
✓ 50 terms in glossary table
✓ Views created: inbox_unresolved, inbox_unread_counts, inbox_recent_activity
```

**Manual Verification:**

```sql
-- Check glossary table
SELECT COUNT(*) as total_terms FROM glossary;
-- Expected: 50

-- Check glossary categories
SELECT category, COUNT(*) as count FROM glossary GROUP BY category;
-- Expected: metrics (17), algorithm (5), platforms (8), content_types (4), general (11), business (5)

-- Check inbox tables
SHOW TABLES LIKE '%conversation%';
SHOW TABLES LIKE '%message%';
-- Expected: conversations, messages, conversation_participants

-- Check indexes
SHOW INDEX FROM glossary;
SHOW INDEX FROM conversations;
SHOW INDEX FROM messages;
```

---

## What This Unblocks

### Frontend Development

**Task #19: Build Glossary Page**
- Database ready with 50 pre-loaded terms
- API endpoint needed: `GET /api/glossary/terms`
- Filter by category: `?category=metrics`
- Search: `?search=engagement`

**Task #13: Implement Conversation Threading**
- Database schema ready
- API endpoint needed: `GET /api/inbox/conversations/:id/messages`

**Task #14: Add Platform Filtering**
- Database supports platform filtering
- API endpoint: `GET /api/inbox/conversations?platform=instagram`

### Backend Development

**Task #12: Fetch Comments & DMs from Social Platforms**
- Database schema ready to store messages
- Need to build:
  - `InboxSyncService` - Fetch from Metricool/direct APIs
  - `InboxService` - CRUD operations
  - API endpoints for frontend

**Services to Build:**

```php
// src/Services/GlossaryService.php
class GlossaryService {
    public function getTerms(?string $category = null): array;
    public function getTerm(string $term): ?array;
    public function searchTerms(string $query): array;
}

// src/Services/InboxService.php
class InboxService {
    public function getConversations(int $clientId, array $filters = []): array;
    public function getMessages(int $conversationId): array;
    public function markAsRead(int $conversationId): bool;
    public function resolveConversation(int $conversationId): bool;
}

// src/Services/InboxSyncService.php
class InboxSyncService {
    public function syncMetricoolInbox(int $clientId): array;
    public function syncInstagramComments(int $clientId, int $postId): array;
    public function syncTikTokComments(int $clientId, int $postId): array;
}
```

---

## Integration Strategy

### Data Collection Flow

```
1. Metricool API (Daily at 4 AM)
   ↓
2. InboxSyncService fetches comments/DMs
   ↓
3. Store in conversations + messages tables
   ↓
4. Frontend fetches via InboxService API
   ↓
5. User manages inbox via UI
```

### Multi-Source Conflict Resolution

**Priority Order:**
1. Direct Platform API (most recent)
2. Metricool API (daily sync)
3. Manual entry

**Rule:** Last write wins within same priority tier

---

## Testing Checklist

### Migration 019 (Glossary)

- [ ] Execute migration successfully
- [ ] Verify 50 terms inserted
- [ ] Check category distribution (5 categories)
- [ ] Test term uniqueness (duplicate insert fails)
- [ ] Verify indexes created (idx_term, idx_category)
- [ ] Test JSON platforms field parsing

### Migration 020 (Inbox)

- [ ] Execute migration successfully
- [ ] Verify 4 tables created
- [ ] Verify 3 views created
- [ ] Check foreign key constraints
- [ ] Test CASCADE DELETE (delete client → conversations deleted)
- [ ] Verify indexes created
- [ ] Test multi-tenant isolation

### Integration Testing

- [ ] Insert test conversation
- [ ] Add test messages to conversation
- [ ] Query inbox_unresolved view
- [ ] Query inbox_unread_counts view
- [ ] Test platform filtering
- [ ] Test conversation resolution
- [ ] Test message read/unread toggle

---

## Rollback Procedure

If migrations cause issues:

```sql
-- Rollback Migration 020
DROP VIEW IF EXISTS inbox_recent_activity;
DROP VIEW IF EXISTS inbox_unread_counts;
DROP VIEW IF EXISTS inbox_unresolved;
DROP TABLE IF EXISTS inbox_filters;
DROP TABLE IF EXISTS conversation_participants;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversations;

-- Rollback Migration 019
DROP TABLE IF EXISTS glossary;
```

**Important:** Only rollback if absolutely necessary. Migrations are designed to be safe and non-destructive.

---

## Performance Considerations

### Glossary Performance

- **Small dataset** (50 rows) - No performance concerns
- **Indexes** on term and category enable fast lookups
- **No foreign keys** - Global table, no joins needed

### Inbox Performance

- **Expected volume:**
  - 10 clients × 100 conversations = 1,000 conversations
  - 100 messages per conversation = 100,000 messages

- **Indexes optimize:**
  - `idx_client_platform` - Filter conversations by client and platform
  - `idx_conversation_sent_at` - Sort messages by recency
  - `idx_unread` - Filter unresolved conversations

- **Views pre-calculate** aggregations (unread counts, recent activity)

- **Future optimization (if >100K messages):**
  - Partition messages table by date (yearly partitions)
  - Archive old conversations (>1 year)
  - Add full-text search index on message_text

---

## Production Deployment Notes

### Backup First

```bash
# Backup before migration
mysqldump -u root social_media_analytics > backup_before_019_020.sql
```

### Execute on Production

```bash
# Production database credentials
mysql -u g-bit_socialbit -p g-bit_socialbit < scripts/019_glossary.sql
mysql -u g-bit_socialbit -p g-bit_socialbit < scripts/020_inbox_messages.sql
```

### Verify Production

```bash
# Check tables created
mysql -u g-bit_socialbit -p -e "USE g-bit_socialbit; SHOW TABLES LIKE 'glossary'; SHOW TABLES LIKE 'conversations';"

# Check data
mysql -u g-bit_socialbit -p -e "USE g-bit_socialbit; SELECT COUNT(*) FROM glossary; SELECT COUNT(*) FROM conversations;"
```

---

## Documentation Reference

**Related Documents:**
- `docs/DATABASE_SCHEMAS_019_020.md` - Detailed technical documentation
- `docs/INBOX_UI_DESIGN.md` - Frontend UI design specifications
- `scripts/check_database_status.php` - Database diagnostic tool

**Created By:**
- Database Architect: Claude Sonnet 4.5
- Team Lead Approval: ✅ 2026-02-07
- Status: Ready for execution

---

**Questions or Issues?**
Contact: @db-architect (database team)

**Last Updated:** 2026-02-07 23:55 CET
