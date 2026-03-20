# SocialBit - Current Priorities (2025-12-31)

## IMMEDIATE NEXT STEPS (Week 1-2)

### 1. Database Migration 006 - Multi-Tenant Foundation
**Priority**: P0 (Critical)
**Status**: Ready to implement

**What to do**:
- Create `scripts/006_multi_tenant_foundation.sql`
- Add `clients` table (client management)
- Add `social_accounts` table (OAuth tokens storage)
- Add `content_groups` table (cross-platform linking)
- Update `posts` table: add `client_id`, `social_account_id`, `content_group_id`
- Update `users` table: add `email`, `plan`, `max_clients`
- Create default client and migrate existing posts
- Create helper views for client analytics

**Files to check**:
- See DATABASE_EVOLUTION.md lines 120-397 for complete SQL script
- Migration script is fully written and ready to execute

### 2. Repository Layer Updates
**Priority**: P0 (Critical)
**Status**: To do after migration

**What to do**:
- Update `PostRepository::getAllPosts()` - add `$clientId` parameter
- Update `PostRepository::upsertPost()` - require `client_id`
- Create `ClientRepository.php` - CRUD for clients table
- Update `AnalyticsRepository` - add client filtering to all queries

### 3. Service Layer Implementation
**Priority**: P0 (Critical)
**Status**: To do

**What to do**:
- Create `src/Services/ClientService.php`
  - `getClientPosts($clientId)`
  - `getClientAnalytics($clientId)`
  - `createClient($userId, $data)`
  - `updateClient($clientId, $data)`

### 4. Controller Layer
**Priority**: P0 (Critical)
**Status**: To do

**What to do**:
- Create `src/Controllers/ClientController.php`
  - `listClients()` - GET /api/clients
  - `showDashboard($clientId)` - GET /api/clients/{id}/dashboard
  - `createClient()` - POST /api/clients
  - `updateClient($clientId)` - PUT /api/clients/{id}
- Update existing controllers to accept `client_id` context

## WEEK 3-4 PRIORITIES

### 5. Testing Multi-Tenant Isolation
- Create 3 dummy clients via API/UI
- Import different CSV files per client
- Verify data isolation (client A cannot see client B data)
- Test client switching functionality
- Performance test with multiple clients

### 6. UI Updates (if time permits)
- Client selector dropdown
- Client management page
- Update dashboard to show current client context

## MONTH 2 PRIORITIES
See FEATURE_ROADMAP.md Month 1-2 section for complete breakdown.

## DO NOT DO YET
- ❌ Instagram integration (Month 3-4)
- ❌ Cross-platform linking (Month 5)
- ❌ UI redesign
- ❌ Laravel migration
- ❌ PostgreSQL switch

## Success Criteria (End of Month 2)
- [ ] Support 5 clients with complete data isolation
- [ ] < 3 seconds dashboard load time per client
- [ ] Zero data leakage between clients
- [ ] CSV import works for all clients
- [ ] Client switching is seamless

## Reference Files
- DATABASE_EVOLUTION.md - Complete migration script
- FEATURE_ROADMAP.md - Full 6-month plan
- PROJECT_CONTEXT.md - Current state overview
