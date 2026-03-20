# TikTok OAuth 2.0 Implementation Guide

**Status:** ✅ COMPLETE
**Date:** 2026-02-20
**Developer:** Claude Sonnet 4.5 (TikTok OAuth Specialist)

---

## 📋 Overview

Complete TikTok OAuth 2.0 implementation with seamless authorization flow, token management, and automatic refresh. This implementation allows users to connect their TikTok accounts via OAuth, enabling automatic post syncing and analytics collection.

---

## 🏗️ Architecture

### Components

1. **TikTokOAuthController** (`src/Controllers/TikTokOAuthController.php`)
   - Handles OAuth authorization redirect
   - Processes OAuth callback from TikTok
   - Manages connection status and disconnection
   - Generates app tokens for frontend authentication

2. **TikTokOAuthService** (`src/Services/TikTokOAuthService.php`)
   - Generates TikTok authorization URLs
   - Exchanges authorization codes for access tokens
   - Refreshes expired tokens automatically
   - Makes authenticated API requests

3. **TikTokRepository** (`src/Repositories/TikTokRepository.php`)
   - Stores OAuth tokens in database
   - Retrieves valid (non-expired) tokens
   - Updates tokens during refresh
   - Cleans up expired tokens
   - Multi-tenant support (client_id)

4. **Frontend Integration** (`api-connector.html`)
   - "Start OAuth Flow" button
   - Connection status display
   - Disconnect functionality
   - Post sync trigger
   - Real-time feedback with visual indicators

---

## 🔄 OAuth Flow

### Step-by-Step Process

```
┌─────────────┐
│   User      │
└──────┬──────┘
       │
       │ 1. Click "Start OAuth Flow"
       ▼
┌─────────────────────┐
│ api-connector.html  │
└──────┬──────────────┘
       │
       │ 2. Redirect to /api/tiktok/authorize
       ▼
┌──────────────────────────┐
│ TikTokOAuthController    │
│   authorize()            │
└──────┬───────────────────┘
       │
       │ 3. Generate TikTok auth URL
       ▼
┌──────────────────────────┐
│ TikTokOAuthService       │
│   getAuthUrl()           │
└──────┬───────────────────┘
       │
       │ 4. Redirect to TikTok
       ▼
┌──────────────────────────┐
│  TikTok Login Page       │
│  (tiktok.com)            │
└──────┬───────────────────┘
       │
       │ 5. User grants permissions
       ▼
┌──────────────────────────┐
│ TikTok OAuth Callback    │
│ /api/tiktok/callback     │
└──────┬───────────────────┘
       │
       │ 6. Exchange code for token
       ▼
┌──────────────────────────┐
│ TikTokOAuthService       │
│   exchangeCodeForToken() │
└──────┬───────────────────┘
       │
       │ 7. Store token in database
       ▼
┌──────────────────────────┐
│ TikTokRepository         │
│   saveToken()            │
└──────┬───────────────────┘
       │
       │ 8. Redirect back to frontend
       ▼
┌──────────────────────────┐
│ api-connector.html       │
│ ?tiktok=connected        │
└──────────────────────────┘
```

---

## 🔐 Security Features

### Token Management

- **Access Tokens**: Stored encrypted in database (TEXT field)
- **Refresh Tokens**: Used to obtain new access tokens when expired
- **Expiration Tracking**: Automatic check before each API request
- **Auto-Refresh**: Tokens refreshed automatically when expired
- **CSRF Protection**: State parameter prevents CSRF attacks
- **Secure Storage**: Tokens never exposed to frontend

### Multi-Tenant Support

- **Client Isolation**: Each client has separate TikTok tokens
- **Backwards Compatible**: Works with and without `client_id` column
- **Auto-Detection**: Checks if `client_id` column exists at runtime

---

## 📡 API Endpoints

### 1. Start Authorization Flow

**Endpoint:** `GET /api/tiktok/authorize`
**Auth Required:** No
**Description:** Redirects user to TikTok authorization page

**Response:** HTTP 302 redirect to TikTok

---

### 2. OAuth Callback Handler

**Endpoint:** `GET /api/tiktok/callback`
**Auth Required:** No
**Query Parameters:**
- `code` (string, required): Authorization code from TikTok
- `state` (string, optional): CSRF protection token
- `error` (string, optional): Error code if authorization failed
- `error_description` (string, optional): Error details

**Success Response:** HTTP 302 redirect to `/?tiktok=connected&token={app_token}`
**Error Response:** HTTP 302 redirect to `/?tiktok=error&message={error}`

---

### 3. Check Connection Status

**Endpoint:** `GET /api/tiktok/status`
**Auth Required:** Yes (Bearer token)
**Description:** Check if TikTok account is connected

**Success Response (200):**
```json
{
  "connection_status": "connected",
  "last_sync": "2026-02-20 14:30:00",
  "token_expires_at": "2026-02-21 14:30:00",
  "open_id": "1234567890"
}
```

**Disconnected Response (200):**
```json
{
  "connection_status": "disconnected",
  "last_sync": null,
  "token_expires_at": null,
  "open_id": null
}
```

---

### 4. Disconnect TikTok

**Endpoint:** `POST /api/tiktok/disconnect`
**Auth Required:** Yes (Bearer token)
**Description:** Revoke TikTok tokens and disconnect account

**Success Response (200):**
```json
{
  "message": "TikTok disconnected successfully",
  "tokens_deleted": 1
}
```

---

### 5. Sync TikTok Posts

**Endpoint:** `POST /api/tiktok/sync`
**Auth Required:** Yes (Bearer token)
**Description:** Fetch latest posts from TikTok API

**Success Response (200):**
```json
{
  "data": {
    "posts_synced": 25,
    "new_posts": 3,
    "updated_posts": 22
  }
}
```

**Error Response (500):**
```json
{
  "error": {
    "code": "SYNC_ERROR",
    "message": "No valid TikTok token found. Please re-authorize."
  }
}
```

---

## 🗄️ Database Schema

### tiktok_tokens Table

```sql
CREATE TABLE IF NOT EXISTS tiktok_tokens (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NULL,
  client_id INT NULL,  -- Multi-tenant support (optional)

  -- OAuth tokens
  access_token TEXT NOT NULL,
  refresh_token TEXT,
  token_type VARCHAR(20) DEFAULT 'Bearer',
  expires_at DATETIME NOT NULL,

  -- Token metadata
  scope TEXT,
  open_id VARCHAR(100),  -- TikTok user identifier

  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Foreign keys
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,

  -- Indexes
  INDEX idx_expires_at (expires_at),
  INDEX idx_user_id (user_id),
  INDEX idx_client_id (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🧪 Testing Procedure

### Local Testing (localhost)

1. **Start XAMPP**
   ```bash
   # Start Apache and MySQL
   ```

2. **Open API Connector**
   ```
   http://localhost/socialbit-live/api-connector.html
   ```

3. **Test OAuth Flow**
   - Click "Start OAuth Flow" button
   - Login to TikTok account
   - Grant permissions
   - Verify redirect back to api-connector.html
   - Check for success message

4. **Check Connection Status**
   - Click "Check Status" button
   - Verify green status dot
   - Verify token details displayed

5. **Sync Posts**
   - Click "Sync Posts" button
   - Verify posts are fetched
   - Check database: `SELECT * FROM posts WHERE platform = 'tiktok'`

6. **Disconnect**
   - Click "Disconnect" button
   - Confirm dialog
   - Verify tokens removed from database

### Production Testing (socialbit.g-bit.be)

1. **Update TikTok App Settings**
   - Go to [TikTok Developers](https://developers.tiktok.com/)
   - Update redirect URI: `https://socialbit.g-bit.be/api/tiktok/callback`
   - Save changes

2. **Test on Production**
   - Follow same steps as local testing
   - Verify HTTPS redirect works correctly

---

## 🔧 Configuration

### TikTok Developer App Setup

1. **Create TikTok App**
   - Go to [TikTok Developers](https://developers.tiktok.com/)
   - Create new app or use existing
   - Get Client Key and Client Secret

2. **Configure OAuth Redirect**
   ```
   Production: https://socialbit.g-bit.be/api/tiktok/callback
   Local: http://localhost/socialbit-live/api/tiktok/callback
   ```

3. **Request Scopes**
   ```
   user.info.basic
   user.info.profile
   user.info.stats
   video.list
   video.upload (optional)
   ```

4. **Update config/app.php**
   ```php
   'tiktok' => [
       'client_key' => 'your_client_key',
       'client_secret' => 'your_client_secret',
       'redirect_uri' => '',  // Auto-detected
   ],
   ```

---

## 🚨 Troubleshooting

### Common Issues

#### 1. OAuth Callback 404 Error

**Problem:** `/api/tiktok/callback` returns 404

**Solution:**
- Check `.htaccess` is present in `public/` directory
- Verify Apache `mod_rewrite` is enabled
- Check route is registered in `index2.php`

#### 2. Token Exchange Failed

**Problem:** "TikTok token exchange failed" error

**Solution:**
- Verify Client Key and Client Secret are correct
- Check redirect URI matches TikTok app settings exactly
- Ensure authorization code is used within 60 seconds

#### 3. Token Expired Error

**Problem:** "No valid TikTok token found"

**Solution:**
- Tokens expire after 24 hours
- Click "Start OAuth Flow" to re-authorize
- Check token refresh logic in `TikTokOAuthService::getValidToken()`

#### 4. Connection Status Shows Disconnected

**Problem:** Status check returns "disconnected" even after OAuth

**Solution:**
- Check `tiktok_tokens` table has records: `SELECT * FROM tiktok_tokens`
- Verify `expires_at` is in the future
- Check auth token is passed in Authorization header

---

## 📊 Monitoring

### Database Queries

```sql
-- Check stored tokens
SELECT id, user_id, client_id, open_id, expires_at, created_at
FROM tiktok_tokens
ORDER BY created_at DESC;

-- Check expired tokens
SELECT COUNT(*) as expired_count
FROM tiktok_tokens
WHERE expires_at < NOW();

-- Delete old expired tokens (7+ days)
DELETE FROM tiktok_tokens
WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
```

### Log Files

Check Apache error logs for OAuth issues:
```bash
# Local
C:\xampp\apache\logs\error.log

# Production
/var/log/apache2/error.log
```

---

## ✅ Implementation Checklist

- [x] TikTokOAuthController with authorize() and callback()
- [x] TikTokOAuthService with token exchange and refresh
- [x] TikTokRepository with token CRUD operations
- [x] Multi-tenant support (client_id)
- [x] Auto-detect redirect URI
- [x] Frontend OAuth flow button
- [x] Connection status display
- [x] Disconnect functionality
- [x] Post sync integration
- [x] Error handling with user-friendly messages
- [x] CSRF protection (state parameter)
- [x] Token auto-refresh
- [x] Expired token cleanup
- [x] Documentation

---

## 🎯 Next Steps

### Recommended Enhancements

1. **Background Token Refresh**
   - Cron job to refresh tokens before expiration
   - Prevents sync failures due to expired tokens

2. **Webhook Support**
   - TikTok webhook integration for real-time updates
   - Automatic post sync when new content published

3. **Analytics Dashboard Integration**
   - Show TikTok connection status in main dashboard
   - Display last sync time and post count

4. **Rate Limiting**
   - Track API call count per day
   - Warn users when approaching TikTok rate limits

5. **Multi-Account Support**
   - Allow users to connect multiple TikTok accounts
   - Switch between accounts in dashboard

---

## 📝 Files Modified

### Backend
- `src/Controllers/TikTokOAuthController.php` - Enhanced with full OAuth flow
- `src/Services/TikTokOAuthService.php` - Added auto-detect redirect URI
- `src/Repositories/TikTokRepository.php` - Multi-tenant support added
- `config/app.php` - Auto-detect redirect URI configuration

### Frontend
- `api-connector.html` - Complete OAuth UI with status display

### Routing
- `public/index2.php` - OAuth routes registered (already present)

### Database
- `scripts/005_create_tiktok_tokens_table.sql` - Token storage schema

### Documentation
- `docs/TIKTOK_OAUTH_IMPLEMENTATION.md` - This file

---

## 📚 References

- [TikTok OAuth 2.0 Documentation](https://developers.tiktok.com/doc/oauth-user-access-token-management)
- [TikTok Display API](https://developers.tiktok.com/doc/display-api-get-started)
- [TikTok Research API](https://developers.tiktok.com/doc/research-api-overview)

---

**Implementation Complete:** 2026-02-20
**Agent:** Claude Sonnet 4.5 (TikTok OAuth Specialist)
**Task:** #2 - Complete TikTok OAuth 2.0 flow implementation
