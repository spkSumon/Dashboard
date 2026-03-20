# Inbox UI Design - Metricool Style

**Reference:** `docs/metriinbox.png`
**Design System:** `docs/DESIGN_SYSTEM_METRICOOL.md`
**Status:** Design Phase
**Created:** 2026-02-07

---

## 📐 Layout Structure

```
┌─────────────────────────────────────────────────────────────┐
│ Topbar (Dark Purple #2D1B3D)                                │
│ [Logo] Analytics | Inbox* | Planning | SmartLinks | Ads    │
└─────────────────────────────────────────────────────────────┘
┌──────────────────────┬──────────────────────────────────────┐
│ Sidebar (Inbox List) │ Main Panel (Conversation View)       │
│                      │                                      │
│ [Platform Icons]     │ [User Avatar] Michal f               │
│ [Search...]          │ ⭐⭐⭐⭐⭐ (5/5)                      │
│                      │                                      │
│ Unresolved | Unread  │ Feb 7, 2026 12:43 AM                 │
│                      │                                      │
│ ┌──────────────────┐ │ [Message content...]                 │
│ │ [M] Michal f     │ │                                      │
│ │ ⭐⭐⭐⭐⭐      │ │                                      │
│ │ Feb 7, 12:43 AM  │ │                                      │
│ └──────────────────┘ │                                      │
│                      │                                      │
│ [IL] ilsedevelter    │ ┌────────────────────────────────────┐
│ [LU] luz_de_lux      │ │ [Reply input...]                   │
│ [CH] christinejen... │ │ Send (Ctrl + Enter)                │
│ ...                  │ └────────────────────────────────────┘
└──────────────────────┴──────────────────────────────────────┘
```

---

## 🎨 Component Breakdown

### 1. Platform Filter Icons (Top Left)

**Layout:**
- Horizontal row of circular icons
- 44px × 44px each
- 8px gap between icons
- "+ Add" button at end

**Platform Icons:**
```html
<div class="platform-filters">
  <button class="platform-filter" data-platform="facebook">
    <img src="icons/facebook.svg" alt="Facebook" />
  </button>
  <button class="platform-filter" data-platform="instagram">
    <img src="icons/instagram.svg" alt="Instagram" />
  </button>
  <button class="platform-filter" data-platform="google">
    <img src="icons/google.svg" alt="Google Business" />
  </button>
  <button class="platform-filter" data-platform="tiktok">
    <img src="icons/tiktok.svg" alt="TikTok" />
  </button>
  <button class="platform-filter platform-filter--add">
    <span class="icon-plus">+</span>
  </button>
</div>
```

**CSS:**
```css
.platform-filters {
  display: flex;
  gap: 8px;
  padding: 16px;
  border-bottom: 1px solid var(--border-light);
}

.platform-filter {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-full);
  border: 1px solid var(--border-medium);
  background: var(--bg-card);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.platform-filter:hover {
  border-color: var(--brand-primary);
  transform: scale(1.05);
}

.platform-filter.is-active {
  border-color: var(--brand-primary);
  background: var(--brand-primary-light);
}

.platform-filter--add {
  border-style: dashed;
  color: var(--text-tertiary);
}

.platform-filter--add:hover {
  color: var(--brand-primary);
}
```

---

### 2. Search Input

**Layout:**
- Full width minus padding
- 40px height
- Rounded pill shape
- Search icon on left
- Filter icon on right

**HTML:**
```html
<div class="search-container">
  <span class="search-icon">🔍</span>
  <input
    type="text"
    class="input--search"
    placeholder="Search conversation..."
  />
  <button class="btn--icon filter-btn" title="Filter">
    <span>⚙</span>
  </button>
</div>
```

**CSS:**
```css
.search-container {
  position: relative;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-light);
}

.search-icon {
  position: absolute;
  left: 28px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-tertiary);
  font-size: 16px;
}

.input--search {
  width: 100%;
  padding-left: 40px;
  padding-right: 40px;
}

.filter-btn {
  position: absolute;
  right: 24px;
  top: 50%;
  transform: translateY(-50%);
}
```

---

### 3. Tab Navigation (Unresolved, Unread, All)

**Layout:**
- 3 tabs
- Horizontal layout
- "Unread" has orange notification dot
- "All" has overflow menu (3 dots)

**HTML:**
```html
<div class="inbox-tabs">
  <button class="inbox-tab is-active" data-tab="unresolved">
    Unresolved
  </button>
  <button class="inbox-tab" data-tab="unread">
    Unread
    <span class="notification-dot"></span>
  </button>
  <button class="inbox-tab" data-tab="all">
    All
  </button>
  <button class="btn--icon tab-menu">
    <span>⋮</span>
  </button>
</div>
```

**CSS:**
```css
.inbox-tabs {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  gap: 4px;
  border-bottom: 1px solid var(--border-light);
}

.inbox-tab {
  position: relative;
  padding: 8px 16px;
  border: none;
  background: transparent;
  color: var(--text-secondary);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  border-radius: var(--radius-md);
  transition: all 0.2s;
}

.inbox-tab:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.inbox-tab.is-active {
  background: var(--bg-selected);
  color: var(--text-primary);
  font-weight: 600;
}

.notification-dot {
  position: absolute;
  top: 6px;
  right: 8px;
  width: 6px;
  height: 6px;
  background: var(--color-warning);
  border-radius: var(--radius-full);
}
```

---

### 4. Conversation List Items

**Layout:**
- Avatar (40px) on left
- Platform badge on avatar (18px)
- Name + preview text
- Timestamp top right
- Star rating (for reviews)
- Action icons (mute, check) on hover

**HTML:**
```html
<div class="conversation-item is-active">
  <div class="avatar avatar--magenta">
    M
    <span class="avatar__badge avatar__badge--instagram"></span>
  </div>

  <div class="conversation-content">
    <div class="conversation-header">
      <span class="conversation-name">Michal f</span>
      <span class="conversation-time">Feb 7, 2026 12:43 AM</span>
    </div>

    <div class="conversation-preview">
      <div class="rating">
        <span class="rating__star">⭐</span>
        <span class="rating__star">⭐</span>
        <span class="rating__star">⭐</span>
        <span class="rating__star">⭐</span>
        <span class="rating__star">⭐</span>
      </div>
    </div>
  </div>

  <div class="conversation-actions">
    <button class="btn--icon" title="Mute">🔕</button>
    <button class="btn--icon" title="Mark as read">✓</button>
  </div>
</div>
```

**CSS:**
```css
.conversation-item {
  display: flex;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-light);
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.conversation-item:hover {
  background: var(--bg-hover);
}

.conversation-item.is-active {
  background: var(--bg-selected);
  border-left: 3px solid var(--brand-primary);
}

.conversation-content {
  flex: 1;
  min-width: 0; /* Allow text truncation */
}

.conversation-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 4px;
}

.conversation-name {
  font-weight: 600;
  font-size: 14px;
  color: var(--text-primary);
}

.conversation-time {
  font-size: 11px;
  color: var(--text-tertiary);
  white-space: nowrap;
}

.conversation-preview {
  font-size: 13px;
  color: var(--text-secondary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.conversation-actions {
  display: none;
  gap: 4px;
}

.conversation-item:hover .conversation-actions {
  display: flex;
}
```

---

### 5. Badge Types (Story reply, Story mention)

**HTML:**
```html
<!-- Story Reply -->
<span class="conversation-badge badge--platform badge--instagram">
  <span class="badge-icon">📷</span>
  Story reply
</span>

<!-- Story Mention -->
<span class="conversation-badge badge--platform badge--google">
  <span class="badge-icon">@</span>
  Story mention
</span>
```

**CSS:**
```css
.conversation-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: var(--radius-md);
  font-size: 11px;
  font-weight: 600;
}

.badge-icon {
  font-size: 12px;
}
```

---

### 6. Main Panel (Conversation View)

**Layout:**
- Header with user info
- Messages area (scrollable)
- Reply input at bottom (sticky)

**HTML:**
```html
<div class="conversation-panel">
  <!-- Header -->
  <div class="conversation-panel__header">
    <div class="avatar avatar--magenta">
      M
      <span class="avatar__badge avatar__badge--instagram"></span>
    </div>

    <div class="conversation-panel__user">
      <h2 class="conversation-panel__name">Michal f</h2>
      <span class="badge badge--warning">⭐ REVIEW</span>
    </div>

    <div class="conversation-panel__actions">
      <button class="btn--icon" title="Google Translate">🌐</button>
      <button class="btn--icon" title="Show details">ℹ</button>
      <button class="btn--icon" title="Archive">📁</button>
      <button class="btn--icon" title="More">⋮</button>
    </div>
  </div>

  <!-- Message -->
  <div class="conversation-panel__body">
    <div class="message">
      <div class="message__header">
        <div class="rating">
          <span class="rating__star">⭐</span>
          <span class="rating__star">⭐</span>
          <span class="rating__star">⭐</span>
          <span class="rating__star">⭐</span>
          <span class="rating__star">⭐</span>
          <span class="rating__score">(5/5)</span>
        </div>
        <span class="message__time">Feb 7, 2026 12:43 AM</span>
      </div>
      <div class="message__content">
        <!-- Message text here -->
      </div>
    </div>
  </div>

  <!-- Reply Input -->
  <div class="conversation-panel__footer">
    <button class="btn--icon">😊</button>
    <button class="btn--icon">📎</button>
    <textarea
      class="reply-input"
      placeholder="Type your reply..."
      rows="1"
    ></textarea>
    <div class="reply-meta">
      <span class="reply-count">0 / 4096</span>
      <button class="btn--primary btn--sm">
        Send (Ctrl + Enter)
      </button>
    </div>
  </div>
</div>
```

**CSS:**
```css
.conversation-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.conversation-panel__header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-light);
  background: var(--bg-card);
}

.conversation-panel__user {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;
}

.conversation-panel__name {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}

.conversation-panel__actions {
  display: flex;
  gap: 4px;
}

.conversation-panel__body {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  background: var(--bg-app);
}

.conversation-panel__footer {
  padding: 16px 20px;
  border-top: 1px solid var(--border-light);
  background: var(--bg-card);
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.reply-input {
  width: 100%;
  border: 1px solid var(--border-medium);
  border-radius: var(--radius-md);
  padding: 10px 12px;
  font-family: inherit;
  font-size: 14px;
  resize: vertical;
  min-height: 44px;
  max-height: 200px;
}

.reply-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.reply-count {
  font-size: 12px;
  color: var(--text-tertiary);
}
```

---

## 📊 Data Structure

### Conversation Object

```typescript
interface Conversation {
  id: string;
  platform: 'facebook' | 'instagram' | 'google' | 'tiktok';
  user: {
    id: string;
    name: string;
    avatar_url?: string;
    avatar_initials: string; // "M", "IL", etc.
  };
  type: 'message' | 'comment' | 'review' | 'story_reply' | 'story_mention';
  preview: string;
  last_message_at: string; // ISO 8601
  is_read: boolean;
  is_resolved: boolean;
  rating?: number; // 1-5 for reviews
  message_count: number;
}
```

### Message Object

```typescript
interface Message {
  id: string;
  conversation_id: string;
  sender: 'user' | 'business';
  content: string;
  created_at: string; // ISO 8601
  attachments?: Array<{
    type: 'image' | 'video' | 'file';
    url: string;
  }>;
  rating?: number; // For review messages
}
```

---

## 🔌 API Endpoints (Needed)

```
GET  /api/inbox/conversations
  ?platform=instagram
  &status=unresolved
  &page=1
  &limit=20

GET  /api/inbox/conversations/:id/messages
  ?page=1
  &limit=50

POST /api/inbox/conversations/:id/messages
  body: { content: string, attachments?: [] }

PATCH /api/inbox/conversations/:id
  body: { is_read?: boolean, is_resolved?: boolean }

DELETE /api/inbox/conversations/:id
  (Archive)
```

---

## ✅ Implementation Tasks

### Database (Backend)
- [ ] Create `inbox_conversations` table
- [ ] Create `inbox_messages` table
- [ ] Add platform API integration for fetching messages
- [ ] Add webhook handlers for real-time messages

### Backend API (PHP)
- [ ] Create `InboxController.php`
- [ ] Create `InboxRepository.php`
- [ ] Implement conversation listing endpoint
- [ ] Implement message fetching endpoint
- [ ] Implement send message endpoint
- [ ] Implement mark read/resolved endpoints

### Frontend
- [ ] Create inbox page HTML structure
- [ ] Implement conversation list rendering
- [ ] Implement message panel rendering
- [ ] Add platform filter functionality
- [ ] Add search functionality
- [ ] Add real-time updates (polling or WebSocket)
- [ ] Implement reply functionality
- [ ] Add keyboard shortcuts (Ctrl+Enter to send)

### Design/CSS
- [ ] Implement all inbox-specific components
- [ ] Add hover states and animations
- [ ] Ensure mobile responsiveness
- [ ] Test with long usernames/messages
- [ ] Add loading states
- [ ] Add empty states

---

## 📱 Mobile Responsive

```css
@media (max-width: 768px) {
  /* Stack sidebar and panel */
  .inbox-layout {
    grid-template-columns: 1fr;
  }

  /* Hide sidebar when conversation is open */
  .inbox-sidebar {
    display: none;
  }

  .inbox-sidebar.is-visible {
    display: block;
  }

  .conversation-panel {
    display: none;
  }

  .conversation-panel.is-open {
    display: flex;
  }

  /* Add back button in conversation header */
  .conversation-panel__back {
    display: block;
  }
}
```

---

## 🎯 User Experience Notes

**Key UX Principles:**
1. **Fast Access:** Unresolved messages first
2. **Context:** Show platform, type, and preview
3. **Batch Actions:** Select multiple, mark all read
4. **Keyboard Shortcuts:** Navigate with arrows, Esc to close
5. **Real-time:** Show new messages immediately
6. **Search:** Filter by user, platform, date, keyword
7. **Notifications:** Desktop notifications for new messages

**Edge Cases:**
- No conversations yet (empty state)
- Loading conversations (skeleton)
- Failed to load (error state)
- No internet (offline state)
- Long usernames (truncate)
- No avatar (initials fallback)
- Deleted conversations (show placeholder)

---

**Next Steps:**
1. Get approval from team-lead
2. Backend team: Create database tables
3. Backend team: Implement API endpoints
4. Frontend team: Build inbox UI
5. Design team: Refine based on user testing
