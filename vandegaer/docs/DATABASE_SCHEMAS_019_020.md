# Database Schemas: Glossary & Inbox Systems

**Migration Files:** `019_glossary_system.sql`, `020_inbox_messages.sql`
**Created:** 2026-02-07
**Database Architect:** Claude Sonnet 4.5

---

## Migration 019: Glossary System

### Purpose

Help non-technical users understand social media analytics terminology through an interactive glossary with:
- Plain-language definitions
- Industry benchmarks (2026)
- Calculation formulas
- Platform-specific notes
- Usage tracking

### Tables

#### 1. `glossary_terms`

**Primary glossary/dictionary table with term definitions**

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT PK | Auto-increment primary key |
| `term` | VARCHAR(100) UNIQUE | Term name (e.g., "Engagement Rate") |
| `slug` | VARCHAR(100) UNIQUE | URL-friendly version (e.g., "engagement-rate") |
| `short_definition` | VARCHAR(255) | Brief 1-sentence definition |
| `long_definition` | TEXT | Detailed explanation with context |
| `category` | VARCHAR(50) | 'metric', 'platform', 'content-type', 'algorithm' |
| `subcategory` | VARCHAR(50) | 'engagement', 'reach', 'quality', 'discovery' |
| `platforms` | JSON | `["tiktok", "instagram", "all"]` - Which platforms this applies to |
| `platform_specific` | BOOLEAN | TRUE if only applies to specific platforms |
| `example_text` | TEXT | Real-world example |
| `calculation_formula` | VARCHAR(255) | How it's calculated (e.g., "(likes + comments) / reach × 100") |
| `benchmark_value` | DECIMAL(10,2) | Industry average (e.g., 3.7% for TikTok engagement) |
| `benchmark_good` | DECIMAL(10,2) | "Good" threshold (e.g., 5.3%) |
| `benchmark_excellent` | DECIMAL(10,2) | "Excellent" threshold (e.g., 10%) |
| `benchmark_source` | VARCHAR(255) | Source citation (e.g., "Hootsuite 2026 Report") |
| `benchmark_updated_at` | DATE | When benchmarks were last updated |
| `related_terms` | JSON | `["reach", "impressions", "views"]` - Related glossary terms |
| `synonyms` | JSON | `["ER", "interaction rate"]` - Alternative names |
| `importance` | ENUM | 'critical', 'high', 'medium', 'low' |
| `is_published` | BOOLEAN | Show in glossary? |
| `display_order` | INT | Manual sorting (default 999) |
| `created_at` | TIMESTAMP | Auto-set on insert |
| `updated_at` | TIMESTAMP | Auto-updated on modify |

**Indexes:**
- `idx_category` - Filter by category
- `idx_importance` - Sort by importance
- `idx_published` - Show only published terms
- `FULLTEXT idx_search` - Full-text search on term/definitions

**Sample Terms Included:**
- Engagement Rate (critical)
- Reach (critical)
- Impressions (high)
- Views (high)
- Completion Rate (critical - 2026 algorithm metric!)
- Watch Time (high)
- Saves (critical - high-intent signal)
- Sends/DM Shares (critical - strongest signal)
- Skip Rate (high - Instagram Reels)
- Profile Visits (medium)

#### 2. `glossary_user_views`

**Tracks which terms users have viewed (analytics for knowledge gaps)**

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT PK | Auto-increment primary key |
| `user_id` | INT FK | References `users.id` |
| `term_id` | INT FK | References `glossary_terms.id` |
| `viewed_from` | VARCHAR(50) | 'dashboard', 'post-detail', 'tooltip', 'glossary-page' |
| `view_count` | INT | How many times user viewed this term |
| `first_viewed_at` | TIMESTAMP | First view timestamp |
| `last_viewed_at` | TIMESTAMP | Most recent view timestamp |

**Constraints:**
- `UNIQUE (user_id, term_id)` - One record per user per term
- `ON DELETE CASCADE` - Clean up when user/term deleted

**Use Cases:**
- Identify which terms confuse users most
- Show "You haven't learned this yet" indicators
- Personalized onboarding recommendations

### Views

#### `glossary_popular_terms`

Shows top 20 most-viewed terms across all users

#### `glossary_critical_terms`

Returns only critical-importance terms (for onboarding)

---

## Migration 020: Inbox/Messages System

### Purpose

Metricool-style unified inbox for managing social media interactions:
- Comments on posts
- Direct messages (DMs)
- Story replies
- Reviews (Google Business, Facebook)
- Mentions

### Architecture Overview

```
conversations (1)  ───┬───  messages (N)
                      │
                      └───  conversation_participants (N)
```

**Key Design Decisions:**
1. **Conversation-centric:** Group related messages into conversations
2. **Multi-tenant:** Full `client_id` isolation
3. **Platform-agnostic:** Works with TikTok, Instagram, Facebook, YouTube, Google Business
4. **Stored data:** Messages are stored (not fetched on-demand) for offline access and search
5. **Metadata-rich:** Track read status, priority, stars, resolution state

### Tables

#### 1. `conversations`

**Main table: groups related messages into conversation threads**

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT PK | Auto-increment primary key |
| `client_id` | INT FK | Multi-tenant isolation |
| `platform` | ENUM | 'tiktok', 'instagram', 'facebook', 'youtube', 'google_business' |
| `platform_conversation_id` | VARCHAR(255) | Platform's internal conversation ID |
| `platform_user_id` | VARCHAR(255) | User's ID on the platform |
| `username` | VARCHAR(100) | Display username (e.g., '@john_doe') |
| `display_name` | VARCHAR(255) | Full name (e.g., 'John Doe') |
| `avatar_url` | TEXT | Profile picture URL |
| `conversation_type` | ENUM | 'comment', 'dm', 'review', 'story_reply', 'mention' |
| `post_id` | INT FK | Related post (if comment/reply) |
| `post_url` | TEXT | Direct link to the post |
| `status` | ENUM | 'unresolved', 'resolved', 'archived' |
| `is_read` | BOOLEAN | Has client read the latest message? |
| `is_starred` | BOOLEAN | Flagged for follow-up |
| `priority` | ENUM | 'low', 'medium', 'high', 'urgent' |
| `participant_count` | INT | Usually 2, but group DMs exist |
| `last_message_preview` | TEXT | First 100 chars of last message |
| `last_message_at` | TIMESTAMP | When last message was sent |
| `last_message_sender` | VARCHAR(50) | 'client' or 'user' |
| `first_message_at` | TIMESTAMP | When conversation started |
| `created_at` | TIMESTAMP | Auto-set on insert |
| `updated_at` | TIMESTAMP | Auto-updated on modify |

**Indexes:**
- `idx_client` - Filter by client
- `idx_platform` - Filter by platform
- `idx_status` - Filter by resolution status
- `idx_is_read` - Show unread conversations
- `idx_last_message` - Sort by recency (DESC)
- `idx_client_status` - Composite for common queries
- `UNIQUE (client_id, platform, platform_user_id)` - One conversation per user per platform

**Constraints:**
- `ON DELETE CASCADE` - Remove all conversations when client deleted
- `ON DELETE SET NULL` - Keep conversation if post deleted

#### 2. `messages`

**Individual messages within conversations**

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT PK | Auto-increment primary key |
| `conversation_id` | INT FK | References `conversations.id` |
| `platform_message_id` | VARCHAR(255) | Platform's internal message ID |
| `message_text` | TEXT | Plain text content |
| `message_html` | TEXT | HTML version (if available) |
| `sender_type` | ENUM | 'client', 'user', 'system' |
| `sender_platform_id` | VARCHAR(255) | Platform user ID of sender |
| `sender_name` | VARCHAR(255) | Display name |
| `message_type` | ENUM | 'text', 'image', 'video', 'audio', 'sticker', 'link' |
| `attachments` | JSON | `[{"type": "image", "url": "...", "thumbnail": "..."}]` |
| `is_read` | BOOLEAN | Has this specific message been read? |
| `read_at` | TIMESTAMP | When message was read |
| `sent_at` | TIMESTAMP | When message was sent (platform time) |
| `imported_at` | TIMESTAMP | When imported into SocialBit |

**Indexes:**
- `idx_conversation` - Filter by conversation
- `idx_sent_at` - Sort by recency (DESC)
- `idx_sender_type` - Filter by sender
- `idx_is_read` - Show unread messages
- `FULLTEXT idx_message_search` - Search message content

**Constraints:**
- `ON DELETE CASCADE` - Remove all messages when conversation deleted

#### 3. `conversation_participants`

**Track all participants in a conversation (for group DMs)**

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT PK | Auto-increment primary key |
| `conversation_id` | INT FK | References `conversations.id` |
| `platform_user_id` | VARCHAR(255) | Platform user ID |
| `username` | VARCHAR(100) | Display username |
| `display_name` | VARCHAR(255) | Full name |
| `avatar_url` | TEXT | Profile picture URL |
| `role` | ENUM | 'owner', 'participant' |
| `joined_at` | TIMESTAMP | When joined conversation |

**Indexes:**
- `idx_conversation` - Filter by conversation
- `UNIQUE (conversation_id, platform_user_id)` - One record per user per conversation

#### 4. `inbox_filters`

**Store user's inbox filter preferences (optional - for future use)**

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT PK | Auto-increment primary key |
| `client_id` | INT FK | References `clients.id` |
| `filter_name` | VARCHAR(50) | 'unresolved', 'unread', 'starred', 'platform_instagram' |
| `filter_config` | JSON | `{"platforms": ["instagram"], "status": ["unresolved"]}` |
| `created_at` | TIMESTAMP | Auto-set on insert |
| `last_used_at` | TIMESTAMP | Auto-updated when filter used |

### Views

#### `inbox_unresolved`

Returns all unresolved conversations with message counts

**Columns:**
- conversation details
- `message_count` - total messages in thread
- `unread_count` - unread messages from users

**Use Case:** Main inbox view

#### `inbox_unread_counts`

Aggregates unread message counts per client per platform

**Columns:**
- `client_id`
- `platform`
- `unread_conversations` - distinct conversations with unread messages
- `unread_messages` - total unread messages

**Use Case:** Badge counts for navigation

#### `inbox_recent_activity`

Shows last 50 messages across all conversations (last 7 days)

**Use Case:** Activity feed, recent activity widget

---

## Data Collection Strategy

### Glossary Terms

**Maintenance:**
- **Initial setup:** 10 critical terms pre-loaded (see migration file)
- **Future additions:** Research and add terms as needed
- **Benchmark updates:** Quarterly (Q1, Q2, Q3, Q4) - update from industry reports
- **Source citations:** Always include source for benchmarks

**Content Quality:**
- Plain language (non-technical)
- Real-world examples
- Visual indicators for "good" vs "bad" values
- Link related terms (e.g., Engagement Rate → Reach, Likes, Comments)

### Inbox Messages

**Collection Schedule:**
- **Metricool API:** Daily at 4 AM (comments, DMs, reviews)
- **Direct Platform APIs:** Hourly (for real-time inbox)
- **Conflict Resolution:** Latest message wins (update `last_message_at`)

**Data Sources (Priority Order):**
1. **Direct API** (Instagram Graph API, TikTok API, etc.) - Real-time, authoritative
2. **Metricool API** - Daily batch, covers all platforms
3. **Manual Import** - CSV fallback if APIs unavailable

**Storage Approach:**
- **Store all messages** (don't fetch on-demand)
- **Why?** Enables offline search, faster UI, message history retention
- **Cleanup:** Archive conversations older than 1 year

---

## Query Optimization

### Glossary Queries

**Common Query Patterns:**

```sql
-- Get all published terms by category
SELECT * FROM glossary_terms
WHERE is_published = TRUE AND category = 'metric'
ORDER BY importance DESC, display_order ASC;

-- Search terms
SELECT * FROM glossary_terms
WHERE MATCH(term, short_definition, long_definition)
AGAINST ('engagement completion' IN NATURAL LANGUAGE MODE);

-- Get critical onboarding terms
SELECT * FROM glossary_critical_terms;  -- Uses view
```

**Performance Notes:**
- FULLTEXT index enables fast searches
- `is_published` + `category` composite index recommended if slow

### Inbox Queries

**Common Query Patterns:**

```sql
-- Get unresolved conversations for client
SELECT * FROM inbox_unresolved
WHERE client_id = ?
ORDER BY last_message_at DESC
LIMIT 50;

-- Get messages for conversation
SELECT * FROM messages
WHERE conversation_id = ?
ORDER BY sent_at ASC;

-- Get unread badge counts
SELECT * FROM inbox_unread_counts
WHERE client_id = ?;

-- Search messages
SELECT c.*, m.*
FROM conversations c
INNER JOIN messages m ON c.id = m.conversation_id
WHERE c.client_id = ?
  AND MATCH(m.message_text) AGAINST (? IN BOOLEAN MODE);
```

**Performance Notes:**
- `idx_client_status` composite index critical for main inbox view
- `idx_last_message` DESC index for sorting by recency
- FULLTEXT index on `messages.message_text` for search
- Consider partitioning `messages` table by `sent_at` if >1M rows

---

## Multi-Tenant Isolation

### Security

**Glossary:**
- `glossary_terms` - GLOBAL (no client_id, shared across all clients)
- `glossary_user_views` - PER USER (tracks individual learning)

**Inbox:**
- `conversations` - PER CLIENT (full isolation via `client_id`)
- `messages` - PER CLIENT (inherited from conversation)
- ALL queries MUST include `WHERE client_id = ?`

### Data Integrity

**Cascade Deletes:**
- Delete client → Delete all conversations → Delete all messages
- Delete user → Delete all glossary views
- Delete post → Keep conversation (set `post_id = NULL`)
- Delete conversation → Delete all messages

---

## Integration Points

### Backend Services

**Glossary:**
```php
// src/Services/GlossaryService.php
class GlossaryService {
    public function getTerm(string $slug): ?array;
    public function searchTerms(string $query): array;
    public function trackView(int $userId, int $termId, string $viewedFrom): void;
    public function getCriticalTerms(): array;
}
```

**Inbox:**
```php
// src/Services/InboxService.php
class InboxService {
    public function getConversations(int $clientId, array $filters = []): array;
    public function getMessages(int $conversationId): array;
    public function sendMessage(int $conversationId, string $text): bool;
    public function markAsRead(int $conversationId): bool;
    public function resolveConversation(int $conversationId): bool;
}

// src/Services/InboxSyncService.php
class InboxSyncService {
    public function syncMetricoolInbox(int $clientId): array;
    public function syncInstagramComments(int $clientId): array;
    public function syncTikTokComments(int $clientId): array;
}
```

### API Endpoints

**Glossary:**
- `GET /api/glossary/terms` - List all terms
- `GET /api/glossary/terms/:slug` - Get term details
- `GET /api/glossary/search?q=engagement` - Search terms
- `POST /api/glossary/track-view` - Track term view

**Inbox:**
- `GET /api/inbox/conversations` - List conversations
- `GET /api/inbox/conversations/:id/messages` - Get messages
- `POST /api/inbox/conversations/:id/messages` - Send message
- `PATCH /api/inbox/conversations/:id/mark-read` - Mark as read
- `PATCH /api/inbox/conversations/:id/resolve` - Resolve conversation
- `GET /api/inbox/unread-counts` - Badge counts

---

## Testing Checklist

### Glossary

- [ ] Insert 10 sample terms successfully
- [ ] Full-text search returns relevant results
- [ ] Track term views for multiple users
- [ ] Critical terms view returns only critical terms
- [ ] Related terms JSON renders correctly in UI

### Inbox

- [ ] Create conversation with multi-tenant isolation
- [ ] Add messages to conversation
- [ ] Mark conversation as read/unread
- [ ] Star/unstar conversation
- [ ] Resolve conversation
- [ ] Search messages by text
- [ ] Filter by platform
- [ ] Filter by status (unresolved/resolved)
- [ ] Unread counts view accurate
- [ ] Cascade delete works (delete client → all conversations deleted)

---

## Migration Execution

### Local (Development)

```bash
# Migration 019: Glossary
mysql -u root social_media_analytics < scripts/019_glossary_system.sql

# Migration 020: Inbox
mysql -u root social_media_analytics < scripts/020_inbox_messages.sql
```

### Production (Plesk)

```bash
# Via Plesk phpMyAdmin or SSH
mysql -u g-bit_socialbit -p g-bit_socialbit < scripts/019_glossary_system.sql
mysql -u g-bit_socialbit -p g-bit_socialbit < scripts/020_inbox_messages.sql
```

### Rollback (if needed)

```sql
-- Migration 019 Rollback
DROP VIEW IF EXISTS glossary_critical_terms;
DROP VIEW IF EXISTS glossary_popular_terms;
DROP TABLE IF EXISTS glossary_user_views;
DROP TABLE IF EXISTS glossary_terms;

-- Migration 020 Rollback
DROP VIEW IF EXISTS inbox_recent_activity;
DROP VIEW IF EXISTS inbox_unread_counts;
DROP VIEW IF EXISTS inbox_unresolved;
DROP TABLE IF EXISTS inbox_filters;
DROP TABLE IF EXISTS conversation_participants;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversations;
```

---

## Next Steps

1. **Execute migrations** on local and production databases
2. **Build backend services** (GlossaryService, InboxService)
3. **Create API endpoints** (REST API for glossary & inbox)
4. **Build UI components:**
   - Glossary page with term list
   - Term detail modal/page
   - Inbox conversation list
   - Message thread view
   - Reply interface
5. **Setup data collection** (Metricool sync for inbox)
6. **Test multi-tenant isolation** (create 2 test clients, verify no data leakage)

---

**Database Architect:** Claude Sonnet 4.5
**Date:** 2026-02-07
**Status:** Ready for review and execution
