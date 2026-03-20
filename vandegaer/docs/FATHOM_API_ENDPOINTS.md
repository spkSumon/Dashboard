# Fathom Analytics API Endpoints

**Documentation for Frontend Integration - Task #10**

## Base URL

```
Local: http://localhost/socialbit-live/public/index2.php
Production: https://socialbit.g-bit.be/
```

All endpoints are prefixed with `/api/analytics/fathom`

---

## Authentication

Currently **no authentication required** (public endpoints). Can be secured later with Auth middleware if needed.

---

## Endpoints

### 1. Save Credentials

**Endpoint:** `POST /api/analytics/fathom/credentials`

**Purpose:** Store Fathom API credentials in database

**Request Body (JSON):**
```json
{
  "api_token": "YOUR_FATHOM_API_TOKEN",
  "site_id": "YOUR_SITE_ID"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Fathom credentials saved successfully"
}
```

**Error Response (400):**
```json
{
  "error": "Missing required fields: api_token, site_id"
}
```

**Frontend Example:**
```javascript
async function saveFathomCredentials(apiToken, siteId) {
  const response = await fetch('/api/analytics/fathom/credentials', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ api_token: apiToken, site_id: siteId })
  });
  return await response.json();
}
```

---

### 2. Test Connection

**Endpoint:** `GET /api/analytics/fathom/test`

**Purpose:** Validate stored credentials by testing Fathom API connection

**Query Parameters:** None (uses stored credentials)

**Success Response (200):**
```json
{
  "success": true,
  "message": "Fathom connection successful"
}
```

**Error Response (401):**
```json
{
  "success": false,
  "error": "Connection failed - check credentials"
}
```

**Error Response (400):**
```json
{
  "success": false,
  "error": "Credentials not configured"
}
```

**Frontend Example:**
```javascript
async function testFathomConnection() {
  const response = await fetch('/api/analytics/fathom/test');
  const data = await response.json();
  if (data.success) {
    alert('✓ Connection successful!');
  } else {
    alert('✗ ' + data.error);
  }
}
```

---

### 3. Get Aggregated Stats

**Endpoint:** `GET /api/analytics/fathom/stats`

**Purpose:** Get summary statistics (visits, uniques, pageviews, duration, bounce rate)

**Query Parameters:**
- `date_from` (optional): Start date in `YYYY-MM-DD` format (default: 30 days ago)
- `date_to` (optional): End date in `YYYY-MM-DD` format (default: today)

**Success Response (200):**
```json
{
  "visits": 15230,
  "uniques": 8420,
  "pageviews": 18500,
  "avg_duration": 142.5,
  "bounce_rate": 45.2
}
```

**Frontend Example:**
```javascript
async function getFathomStats(dateFrom, dateTo) {
  const params = new URLSearchParams();
  if (dateFrom) params.set('date_from', dateFrom);
  if (dateTo) params.set('date_to', dateTo);

  const response = await fetch(`/api/analytics/fathom/stats?${params}`);
  return await response.json();
}

// Usage
const stats = await getFathomStats('2026-01-01', '2026-01-31');
console.log(`Visits: ${stats.visits}, Unique visitors: ${stats.uniques}`);
```

---

### 4. Get Referrers (Traffic Sources)

**Endpoint:** `GET /api/analytics/fathom/referrers`

**Purpose:** Get top referring websites/platforms (crucial for social media correlation)

**Query Parameters:**
- `date_from` (optional): Start date in `YYYY-MM-DD` format (default: 30 days ago)
- `date_to` (optional): End date in `YYYY-MM-DD` format (default: today)
- `limit` (optional): Number of referrers to return (default: 20, max: 50)

**Success Response (200):**
```json
[
  {
    "referrer": "t.co",
    "visits": 450,
    "uniques": 320
  },
  {
    "referrer": "instagram.com",
    "visits": 280,
    "uniques": 210
  },
  {
    "referrer": "tiktok.com",
    "visits": 150,
    "uniques": 120
  }
]
```

**Frontend Example:**
```javascript
async function getFathomReferrers(dateFrom, dateTo, limit = 20) {
  const params = new URLSearchParams();
  if (dateFrom) params.set('date_from', dateFrom);
  if (dateTo) params.set('date_to', dateTo);
  params.set('limit', limit);

  const response = await fetch(`/api/analytics/fathom/referrers?${params}`);
  return await response.json();
}

// Usage - Get top 10 referrers
const referrers = await getFathomReferrers('2026-01-01', '2026-01-31', 10);

// Display in UI with platform icons
referrers.forEach(ref => {
  const platformIcon = getPlatformIcon(ref.referrer); // Helper function
  console.log(`${platformIcon} ${ref.referrer}: ${ref.visits} visits`);
});

function getPlatformIcon(referrer) {
  if (referrer.includes('instagram')) return '📷';
  if (referrer.includes('tiktok')) return '🎵';
  if (referrer.includes('facebook')) return '📘';
  if (referrer.includes('t.co')) return '🐦'; // Twitter
  return '🌐';
}
```

---

### 5. Get Pages

**Endpoint:** `GET /api/analytics/fathom/pages`

**Purpose:** Get page-level traffic metrics

**Query Parameters:**
- `date_from` (optional): Start date in `YYYY-MM-DD` format (default: 30 days ago)
- `date_to` (optional): End date in `YYYY-MM-DD` format (default: today)
- `limit` (optional): Number of pages to return (default: 20, max: 50)

**Success Response (200):**
```json
[
  {
    "pathname": "/menu",
    "visits": 1250,
    "uniques": 890
  },
  {
    "pathname": "/reservations",
    "visits": 820,
    "uniques": 650
  },
  {
    "pathname": "/",
    "visits": 2100,
    "uniques": 1450
  }
]
```

**Frontend Example:**
```javascript
async function getFathomPages(dateFrom, dateTo, limit = 20) {
  const params = new URLSearchParams();
  if (dateFrom) params.set('date_from', dateFrom);
  if (dateTo) params.set('date_to', dateTo);
  params.set('limit', limit);

  const response = await fetch(`/api/analytics/fathom/pages?${params}`);
  return await response.json();
}

// Usage - Display top 5 pages
const pages = await getFathomPages('2026-01-01', '2026-01-31', 5);
pages.forEach((page, index) => {
  console.log(`${index + 1}. ${page.pathname} - ${page.visits} visits`);
});
```

---

### 6. Get Time Series (Daily Breakdown)

**Endpoint:** `GET /api/analytics/fathom/timeseries`

**Purpose:** Get daily traffic breakdown for chart visualization

**Query Parameters:**
- `date_from` (optional): Start date in `YYYY-MM-DD` format (default: 30 days ago)
- `date_to` (optional): End date in `YYYY-MM-DD` format (default: today)

**Success Response (200):**
```json
[
  {
    "date": "2026-01-15",
    "visits": 450,
    "uniques": 320
  },
  {
    "date": "2026-01-16",
    "visits": 520,
    "uniques": 380
  },
  {
    "date": "2026-01-17",
    "visits": 480,
    "uniques": 350
  }
]
```

**Frontend Example (Chart.js):**
```javascript
async function renderTrafficChart(dateFrom, dateTo) {
  const timeseries = await getFathomTimeSeries(dateFrom, dateTo);

  const labels = timeseries.map(d => d.date);
  const visits = timeseries.map(d => d.visits);
  const uniques = timeseries.map(d => d.uniques);

  new Chart(document.getElementById('trafficChart'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Visits',
          data: visits,
          borderColor: '#6d28d9',
          backgroundColor: 'rgba(109, 40, 217, 0.1)'
        },
        {
          label: 'Unique Visitors',
          data: uniques,
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)'
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        title: { display: true, text: 'Website Traffic Trend' }
      }
    }
  });
}
```

---

## Complete Dashboard Example

```javascript
// Initialize dashboard on page load
async function initWebsiteTrafficDashboard() {
  const dateFrom = '2026-01-01';
  const dateTo = '2026-01-31';

  try {
    // 1. Load aggregated stats
    const stats = await getFathomStats(dateFrom, dateTo);
    document.getElementById('totalVisits').textContent = stats.visits.toLocaleString();
    document.getElementById('uniqueVisitors').textContent = stats.uniques.toLocaleString();
    document.getElementById('bounceRate').textContent = `${stats.bounce_rate}%`;

    // 2. Load referrers (social media sources)
    const referrers = await getFathomReferrers(dateFrom, dateTo, 10);
    renderReferrersList(referrers);

    // 3. Load top pages
    const pages = await getFathomPages(dateFrom, dateTo, 10);
    renderPagesList(pages);

    // 4. Render traffic chart
    await renderTrafficChart(dateFrom, dateTo);

    console.log('✓ Website traffic dashboard loaded');
  } catch (error) {
    console.error('✗ Failed to load traffic data:', error);
    alert('Failed to load website traffic data. Please check Fathom credentials.');
  }
}

// Helper: Render referrers list with platform icons
function renderReferrersList(referrers) {
  const container = document.getElementById('referrersList');
  container.innerHTML = referrers.map(ref => `
    <div class="referrer-item">
      <span class="platform-icon">${getPlatformIcon(ref.referrer)}</span>
      <span class="referrer-name">${ref.referrer}</span>
      <span class="referrer-visits">${ref.visits} visits</span>
      <span class="referrer-uniques">${ref.uniques} unique</span>
    </div>
  `).join('');
}

// Helper: Render pages list
function renderPagesList(pages) {
  const container = document.getElementById('pagesList');
  container.innerHTML = pages.map((page, index) => `
    <div class="page-item">
      <span class="page-rank">#${index + 1}</span>
      <span class="page-path">${page.pathname}</span>
      <span class="page-visits">${page.visits} visits</span>
    </div>
  `).join('');
}
```

---

## Error Handling

All endpoints return JSON responses. Always check `response.ok` before parsing:

```javascript
async function safeFetch(url, options = {}) {
  try {
    const response = await fetch(url, options);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error || `HTTP ${response.status}`);
    }

    return data;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

// Usage
try {
  const stats = await safeFetch('/api/analytics/fathom/stats?date_from=2026-01-01');
  console.log('Stats loaded:', stats);
} catch (error) {
  alert('Failed to load stats: ' + error.message);
}
```

---

### 7. Collect and Store Analytics Data (NEW - Task #4)

**Endpoint:** `POST /api/analytics/fathom/collect`

**Purpose:** Fetch data from Fathom API and store in database (designed for scheduled jobs/cron)

**Query Parameters:**
- `client_id` (required): Tenant ID
- `date_from` (optional): Start date in `YYYY-MM-DD` format (default: yesterday)
- `date_to` (optional): End date in `YYYY-MM-DD` format (default: yesterday)

**Success Response (200):**
```json
{
  "success": true,
  "message": "Collected 1 days of analytics data",
  "stored_records": 1,
  "correlated_posts": 3
}
```

**Frontend Example:**
```javascript
async function collectFathomData(clientId, dateFrom, dateTo) {
  const params = new URLSearchParams();
  params.set('client_id', clientId);
  if (dateFrom) params.set('date_from', dateFrom);
  if (dateTo) params.set('date_to', dateTo);

  const response = await fetch(`/api/analytics/fathom/collect?${params}`, {
    method: 'POST'
  });
  return await response.json();
}

// Usage - Daily collection (call from cron job or manual button)
const result = await collectFathomData(1); // Yesterday's data by default
console.log(`Stored ${result.stored_records} records, correlated ${result.correlated_posts} posts`);
```

---

### 8. Get Stored Website Analytics (NEW - Task #4)

**Endpoint:** `GET /api/analytics/website/stats`

**Purpose:** Get cached website analytics from database (faster than hitting Fathom API)

**Query Parameters:**
- `client_id` (required): Tenant ID
- `source` (optional): Filter by source ('fathom', 'google_business', or null for all)
- `date_from` (optional): Start date (default: 30 days ago)
- `date_to` (optional): End date (default: today)

**Success Response (200):**
```json
[
  {
    "id": 123,
    "source": "fathom",
    "date": "2026-02-19",
    "page_views": 1250,
    "unique_visitors": 890,
    "referral_visits": 120,
    "referral_source": "instagram",
    "bounce_rate": 42.5,
    "avg_session_duration": 165,
    "conversions": 12,
    "created_at": "2026-02-20 04:15:00"
  }
]
```

**Frontend Example:**
```javascript
async function getStoredAnalytics(clientId, source = null, dateFrom, dateTo) {
  const params = new URLSearchParams();
  params.set('client_id', clientId);
  if (source) params.set('source', source);
  if (dateFrom) params.set('date_from', dateFrom);
  if (dateTo) params.set('date_to', dateTo);

  const response = await fetch(`/api/analytics/website/stats?${params}`);
  return await response.json();
}

// Usage - Get last 30 days from database (instant response)
const analytics = await getStoredAnalytics(1, 'fathom');
console.log(`Total visitors: ${analytics.reduce((sum, d) => sum + d.unique_visitors, 0)}`);
```

---

### 9. Get Top Traffic-Driving Posts (NEW - Task #4)

**Endpoint:** `GET /api/analytics/website/top-traffic-posts`

**Purpose:** Get social posts ordered by website traffic they generated

**Query Parameters:**
- `client_id` (required): Tenant ID
- `limit` (optional): Number of posts to return (default: 10, max: 50)
- `date_from` (optional): Filter by traffic date
- `date_to` (optional): Filter by traffic date

**Success Response (200):**
```json
[
  {
    "post_id": 123,
    "platform": "tiktok",
    "topic": "menu_item",
    "caption_preview": "Check out our new spring menu! 🌸",
    "posted_date": "2026-02-15",
    "total_visits": 450,
    "total_conversions": 12,
    "avg_bounce_rate": 35.5,
    "avg_session_duration": 180
  },
  {
    "post_id": 456,
    "platform": "instagram",
    "topic": "event",
    "caption_preview": "Join us tonight for live music! 🎵",
    "posted_date": "2026-02-18",
    "total_visits": 320,
    "total_conversions": 8,
    "avg_bounce_rate": 40.2,
    "avg_session_duration": 155
  }
]
```

**Frontend Example:**
```javascript
async function getTopTrafficPosts(clientId, limit = 10) {
  const params = new URLSearchParams();
  params.set('client_id', clientId);
  params.set('limit', limit);

  const response = await fetch(`/api/analytics/website/top-traffic-posts?${params}`);
  return await response.json();
}

// Usage - Display posts that drove most traffic
const topPosts = await getTopTrafficPosts(1, 10);
topPosts.forEach((post, index) => {
  console.log(`#${index + 1}: ${post.caption_preview}`);
  console.log(`  → ${post.total_visits} website visits, ${post.total_conversions} conversions`);
  console.log(`  → ${post.avg_session_duration}s avg session, ${post.avg_bounce_rate}% bounce`);
});

// Display in jungle dashboard
const container = document.getElementById('traffic-driving-posts');
container.innerHTML = topPosts.map((post, index) => `
  <div class="traffic-post">
    <div class="post-rank">#${index + 1}</div>
    <div class="post-platform">${getPlatformIcon(post.platform)}</div>
    <div class="post-caption">${post.caption_preview}</div>
    <div class="post-traffic">
      <span class="visits">🌐 ${post.total_visits} visits</span>
      <span class="conversions">✅ ${post.total_conversions} conversions</span>
    </div>
  </div>
`).join('');
```

---

## Social Media Correlation

**Key Use Case:** Identify which social posts drove website traffic

```javascript
async function correlateSocialPosts(postDate) {
  // Get referrers for 24h after post
  const dateFrom = postDate;
  const dateTo = new Date(postDate);
  dateTo.setDate(dateTo.getDate() + 1);

  const referrers = await getFathomReferrers(
    dateFrom,
    dateTo.toISOString().split('T')[0],
    20
  );

  // Filter social media referrers
  const socialReferrers = referrers.filter(ref =>
    ['instagram.com', 'tiktok.com', 'facebook.com', 't.co'].some(
      platform => ref.referrer.includes(platform)
    )
  );

  const totalSocialTraffic = socialReferrers.reduce((sum, ref) => sum + ref.visits, 0);

  return {
    totalVisits: totalSocialTraffic,
    breakdown: socialReferrers
  };
}

// Display impact on post detail page
const impact = await correlateSocialPosts('2026-01-15');
document.getElementById('postImpact').innerHTML = `
  <strong>Website Impact:</strong> ${impact.totalVisits} visits
  ${impact.breakdown.map(ref => `<br>${ref.referrer}: ${ref.visits}`).join('')}
`;
```

---

## Next Steps

1. **Get Fathom Credentials:**
   - Log into Fathom account
   - Go to Settings → API
   - Copy API token and site ID

2. **Save Credentials:**
   ```javascript
   await saveFathomCredentials('YOUR_TOKEN', 'YOUR_SITE_ID');
   ```

3. **Test Connection:**
   ```javascript
   await testFathomConnection();
   ```

4. **Build UI:** Create website traffic section (Task #10)

5. **Daily Sync:** Setup cron job to cache data in database

---

## Database Tables (Migration 014 - IMPLEMENTED)

Website analytics data is now stored in database for performance:

**`website_analytics`** - Daily aggregated website metrics
- `client_id`, `source` ('fathom', 'google_business'), `date`
- `page_views`, `unique_visitors`, `referral_visits`, `referral_source`
- `bounce_rate`, `avg_session_duration`, `conversions`
- `data_json` (raw API response for debugging)
- UNIQUE KEY on (client_id, source, date)

**`post_website_traffic`** - Traffic attribution to social posts
- `client_id`, `post_id`, `date`
- `referral_visits`, `referral_source`
- `bounce_rate`, `avg_session_duration`, `conversions`
- Automatically populated by correlation algorithm

**Correlation Algorithm:**
The system automatically matches Fathom referrers to social posts:
1. Maps referrer hostname to platform (e.g., 't.co' → 'tiktok')
2. Finds posts from that platform posted within 7 days before traffic spike
3. Attributes traffic to the most recent post (assumption: recent posts drive traffic)

This enables powerful insights like "This TikTok drove 450 website visits!"

---

## Daily Data Collection Workflow

**Recommended Schedule:** Daily at 6 AM (after Metricool at 4 AM, Google Business at 5 AM)

```javascript
// Pseudo-code for scheduled job (cron/task scheduler)
async function dailyFathomCollection() {
  const yesterday = new Date();
  yesterday.setDate(yesterday.getDate() - 1);
  const dateStr = yesterday.toISOString().split('T')[0];

  // Collect for all active clients
  const clients = await getActiveClients();

  for (const client of clients) {
    try {
      const result = await collectFathomData(client.id, dateStr, dateStr);
      console.log(`✓ Client ${client.id}: ${result.correlated_posts} posts correlated`);
    } catch (error) {
      console.error(`✗ Client ${client.id} failed:`, error.message);
    }
  }
}
```

**Windows Task Scheduler Command:**
```bash
php C:\xampp3\htdocs\socialbit-live\scripts\scheduled\fathom_daily_collection.php
```

**Linux Cron Entry:**
```cron
0 6 * * * cd /var/www/socialbit-live && php scripts/scheduled/fathom_daily_collection.php
```

---

**Created:** 2026-02-07
**Updated:** 2026-02-20 (Added data collection & correlation endpoints)
**Backend Lead:** Complete ✅
**Ready for:** Frontend Task #10
