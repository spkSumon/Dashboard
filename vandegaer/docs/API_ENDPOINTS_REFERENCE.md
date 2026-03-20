# SocialBit API Endpoints Reference

**Quick reference for Instagram & Facebook API integration**

---

## Instagram Endpoints

### Save Credentials
```http
POST /api/instagram/credentials
Content-Type: application/json

{
  "app_id": "optional_meta_app_id",
  "app_secret": "optional_meta_app_secret",
  "access_token": "required_long_lived_token"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Instagram credentials saved successfully"
}
```

---

### Test Connection
```http
GET /api/instagram/test
```

**Response:**
```json
{
  "success": true,
  "message": "Instagram connection successful",
  "data": {
    "id": "17841400123456789",
    "username": "your_username",
    "account_type": "BUSINESS",
    "media_count": 142
  }
}
```

---

### Fetch Posts (Read-Only)
```http
GET /api/instagram/posts?limit=25
```

**Query Parameters:**
- `limit` (optional) - Number of posts (1-100, default: 25)

**Response:**
```json
{
  "success": true,
  "message": "Posts fetched successfully",
  "count": 25,
  "data": [
    {
      "id": "17841400123456789",
      "caption": "Amazing sunset! #nature #photography",
      "media_type": "IMAGE",
      "permalink": "https://www.instagram.com/p/ABC123/",
      "timestamp": "2026-02-15T18:30:00+0000",
      "like_count": 342,
      "comments_count": 28
    }
  ]
}
```

---

### Sync Posts to Database
```http
POST /api/instagram/sync?limit=50&client_id=1
Authorization: Bearer {jwt-token}
```

**Query Parameters:**
- `limit` (optional) - Number of posts (1-100, default: 25)
- `client_id` (optional) - Multi-tenant client ID

**Response:**
```json
{
  "success": true,
  "message": "Successfully saved 42 Instagram posts",
  "fetched": 50,
  "saved": 42
}
```

---

### Refresh Access Token
```http
POST /api/instagram/refresh-token
Authorization: Bearer {jwt-token}
```

**Response:**
```json
{
  "success": true,
  "message": "Access token refreshed successfully",
  "expires_in": 5184000
}
```

---

## Facebook Endpoints

### Save Credentials
```http
POST /api/facebook/credentials
Content-Type: application/json

{
  "access_token": "required_page_access_token",
  "page_id": "required_facebook_page_id"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Facebook credentials saved successfully"
}
```

---

### Test Connection
```http
GET /api/facebook/test
```

**Response:**
```json
{
  "success": true,
  "message": "Facebook connection successful",
  "data": {
    "id": "123456789",
    "name": "Your Page Name",
    "fan_count": 5432,
    "about": "Your page description",
    "category": "Local Business"
  }
}
```

---

### Fetch Posts (Read-Only)
```http
GET /api/facebook/posts?limit=25
```

**Query Parameters:**
- `limit` (optional) - Number of posts (1-100, default: 25)

**Response:**
```json
{
  "success": true,
  "message": "Posts fetched successfully",
  "count": 25,
  "data": [
    {
      "id": "123456789_987654321",
      "message": "Check out our new product! #newrelease",
      "created_time": "2026-02-15T10:30:00+0000",
      "permalink_url": "https://www.facebook.com/...",
      "type": "photo"
    }
  ]
}
```

---

### Sync Posts to Database
```http
POST /api/facebook/sync?limit=50&client_id=1
Authorization: Bearer {jwt-token}
```

**Query Parameters:**
- `limit` (optional) - Number of posts (1-100, default: 25)
- `client_id` (optional) - Multi-tenant client ID

**Response:**
```json
{
  "success": true,
  "message": "Successfully saved 38 Facebook posts",
  "fetched": 50,
  "saved": 38
}
```

---

### Get Page Insights
```http
GET /api/facebook/insights?metrics=page_impressions,page_engaged_users&period=day
```

**Query Parameters:**
- `metrics` (optional) - Comma-separated metrics (default: page_impressions,page_engaged_users,page_fans)
- `period` (optional) - Time period: day, week, days_28 (default: day)

**Available Metrics:**
- `page_impressions` - Total page impressions
- `page_engaged_users` - People who engaged with page
- `page_fans` - Total page likes
- `page_views_total` - Total page views
- `page_post_engagements` - Total post engagements

**Response:**
```json
{
  "success": true,
  "message": "Page insights fetched successfully",
  "data": [
    {
      "name": "page_impressions",
      "period": "day",
      "values": [
        {
          "value": 1234,
          "end_time": "2026-02-15T08:00:00+0000"
        }
      ]
    }
  ]
}
```

---

## Error Responses

All endpoints return consistent error format:

```json
{
  "success": false,
  "error": "Error message describing what went wrong"
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `400` - Bad request (missing parameters, validation failed)
- `401` - Unauthorized (missing or invalid auth token)
- `500` - Internal server error

---

## Authentication

**Auth Required Endpoints:**
- `POST /api/instagram/sync`
- `POST /api/instagram/refresh-token`
- `POST /api/facebook/sync`

**How to Authenticate:**
1. Login first: `POST /api/auth/login`
2. Get JWT token from response
3. Include in Authorization header:
   ```http
   Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
   ```

**Public Endpoints (No Auth Required):**
- `POST /api/instagram/credentials`
- `GET /api/instagram/test`
- `GET /api/instagram/posts`
- `POST /api/facebook/credentials`
- `GET /api/facebook/test`
- `GET /api/facebook/posts`
- `GET /api/facebook/insights`

---

## Rate Limits

**Instagram Graph API:**
- 200 calls/hour per user
- Errors: `"Error: (#4) Application request limit reached"`

**Facebook Graph API:**
- 200 calls/hour per user
- Errors: `"Error: (#17) User request limit reached"`

**Best Practices:**
- Cache API responses when possible
- Use scheduled batch jobs (daily at 4 AM)
- Don't sync same posts repeatedly
- Monitor API error logs

---

## Quick Start

### 1. Save Credentials (UI Method)

1. Open: `https://socialbit.g-bit.be/api-connector.html`
2. Fill in Instagram/Facebook credentials
3. Click "Test Connection"
4. Click "Save Config"

### 2. Save Credentials (API Method)

```bash
# Instagram
curl -X POST http://localhost/socialbit-live/api/instagram/credentials \
  -H "Content-Type: application/json" \
  -d '{"access_token":"YOUR_INSTAGRAM_TOKEN"}'

# Facebook
curl -X POST http://localhost/socialbit-live/api/facebook/credentials \
  -H "Content-Type: application/json" \
  -d '{"access_token":"YOUR_FB_TOKEN","page_id":"YOUR_PAGE_ID"}'
```

### 3. Test Connection

```bash
curl http://localhost/socialbit-live/api/instagram/test
curl http://localhost/socialbit-live/api/facebook/test
```

### 4. Sync Posts

```bash
# Get JWT token first
curl -X POST http://localhost/socialbit-live/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}' \
  | jq -r '.token'

# Use token to sync
curl -X POST "http://localhost/socialbit-live/api/instagram/sync?limit=50" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## Database Storage

Posts are saved to the `posts` table:

```sql
SELECT
  platform,
  platform_post_id,
  caption,
  hashtags,
  posted_date,
  likes,
  comments,
  shares
FROM posts
WHERE platform IN ('instagram', 'facebook')
ORDER BY posted_date DESC
LIMIT 10;
```

Credentials are saved to the `settings` table:

```sql
SELECT `key`, `value`
FROM settings
WHERE `key` LIKE 'instagram%' OR `key` LIKE 'facebook%';
```

---

## Troubleshooting

### "No access token configured"
**Solution:** Save credentials first via `/credentials` endpoint

### "Instagram connection failed - Invalid user ID"
**Solution:** Use Business/Creator account, not personal account

### "Facebook - Permissions error"
**Solution:** Page token needs `pages_read_engagement`, `pages_read_user_content` permissions

### "Token expired" (Instagram)
**Solution:** Use refresh endpoint: `POST /api/instagram/refresh-token`

### Posts not saving
**Check:**
1. Credentials saved? `SELECT * FROM settings WHERE key LIKE 'instagram%'`
2. Database connection working?
3. Error logs: `storage/logs/app.log`

---

**For detailed documentation, see:**
`docs/INSTAGRAM_FACEBOOK_API_IMPLEMENTATION.md`
