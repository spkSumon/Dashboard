# Security Audit Report - SocialBit Project

**Date:** 2026-02-07
**Auditor:** security-auditor (Claude Agent)
**Scope:** Complete credential exposure analysis
**Status:** 🔴 CRITICAL ISSUES FOUND

---

## Executive Summary

**Findings:** 4 critical security exposures identified
**Risk Level:** HIGH (credentials exposed in git history and documentation)
**Immediate Action Required:** Token rotation for all exposed credentials

### Critical Exposures

1. ✅ **GitHub Token** - Exposed in `.auto-claude/.env` (SAFE - gitignored)
2. 🔴 **GitHub Token** - Exposed in `docs/CRITICAL_QA_ANALYSIS_REPORT.md` (CRITICAL)
3. 🔴 **GitHub Token** - Exposed in `.auto-claude/CONFIG_GUIDE.md` (SAFE but documented)
4. 🔴 **Production Database Password** - Exposed in `config/app.php` (CRITICAL - in git history)
5. 🔴 **TikTok Client Secret** - Exposed in `config/app.php` (CRITICAL - in git history)
6. ⚠️ **Metricool API Key** - Example token in `docs/METRICOOL_QUICK_START.md` (LOW RISK - appears to be placeholder)

---

## 1. GitHub Token Exposure

### Location
```
Token: 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
```

**Exposed in:**
1. `.auto-claude/.env` (line 22) - ✅ SAFE (gitignored)
2. `.auto-claude/CONFIG_GUIDE.md` (line 36) - ✅ SAFE (gitignored)
3. `docs/CRITICAL_QA_ANALYSIS_REPORT.md` (line 163) - 🔴 CRITICAL (tracked in git)
4. `actions.md` (line 54) - 🔴 CRITICAL (tracked in git)

**Git Status:**
- `.auto-claude/` is gitignored ✅
- `docs/CRITICAL_QA_ANALYSIS_REPORT.md` is **untracked** (not yet committed) ⚠️
- `actions.md` is **untracked** (not yet committed) ⚠️

**Risk Assessment:**
- **Current Risk:** 🟡 MEDIUM (token visible in untracked files, could be committed)
- **If Committed:** 🔴 CRITICAL (public GitHub repo exposure)
- **Impact:** Full repository access (repo scope), create/delete issues, PR management

**Token Scope:**
- `repo` - Full control of private repositories
- Repository: `Gbit-bjorn/socialbit-live`

### Rotation Priority: 🔴 IMMEDIATE (before committing docs/)

---

## 2. Production Database Password

### Location
```
File: config/app.php (line 44)
Password: MiNiMiN1L5uv5n!
Database: g-bit_socialbit
User: g-bit_socialbit
```

**Git Status:**
- ✅ File IS in git history (commit: bcb7dfa "init")
- 🔴 CRITICAL: Password exposed in git history
- Cannot be removed without git history rewrite

**Risk Assessment:**
- **Current Risk:** 🔴 CRITICAL
- **Impact:** Full database access (read/write/delete all customer data)
- **Exposure:** Anyone with repo access can see password
- **Mitigation:** Password rotation required

**Additional Exposure:**
- Also in `.worktrees/001-lets-continue-to-optimise-the-tiktok-import-from-c/config/app.php`

### Rotation Priority: 🔴 IMMEDIATE

---

## 3. TikTok API Credentials

### Location
```
File: config/app.php (lines 18-19)
Client Key: sbawuymthutwnltywk
Client Secret: Gj3r1gpO1qP8dct7oVTaCZyDbmUxXPGM
```

**Git Status:**
- ✅ File IS in git history (commit: bcb7dfa "init")
- 🔴 CRITICAL: TikTok credentials in git history

**Risk Assessment:**
- **Current Risk:** 🔴 HIGH
- **Impact:** Unauthorized TikTok API access, potential account compromise
- **Exposure:** Visible in public git history
- **Note:** TikTok developer account is currently blocked (per CLAUDE.md)

### Rotation Priority: 🟡 MEDIUM (account blocked, but still rotate)

---

## 4. Metricool API Key (Example)

### Location
```
File: docs/METRICOOL_QUICK_START.md (line 27)
API Key: YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB
```

**Risk Assessment:**
- **Current Risk:** 🟢 LOW (appears to be placeholder/example)
- **Validation Needed:** Confirm this is not a real API key
- **File Status:** Untracked (not yet committed)

### Rotation Priority: 🟢 LOW (verify it's example data)

---

## 5. Config Files Security Status

### ✅ SAFE (Gitignored)
- `.auto-claude/.env` (contains GitHub token)
- `.auto-claude/CONFIG_GUIDE.md` (documents GitHub token)

### 🔴 CRITICAL (Tracked in Git)
- `config/app.php` - Contains production DB password and TikTok secrets
- Committed in initial commit (bcb7dfa)
- **SHOULD BE:** gitignored with example file pattern

### ⚠️ VULNERABLE (Untracked - Could Be Committed)
- `docs/CRITICAL_QA_ANALYSIS_REPORT.md` - Contains GitHub token
- `actions.md` - Contains partial GitHub token reference
- `docs/METRICOOL_QUICK_START.md` - Contains example API key

### ✅ GOOD PRACTICE
- `config/app.example.php` exists as template ✅

---

## Detailed Rotation Procedures

### IMMEDIATE ACTION 1: Rotate GitHub Token

**⏱️ Time Required:** 5-10 minutes
**Priority:** 🔴 CRITICAL (do BEFORE committing any files)

#### Step-by-Step Instructions

1. **Create New GitHub Token**
   - Go to: https://github.com/settings/tokens/new
   - Token name: `SocialBit Auto Claude (2026-02-07)`
   - Expiration: `90 days` (recommended)
   - Select scopes:
     - ✅ `repo` - Full control of private repositories
     - ✅ `read:org` (if using GitHub Projects)
   - Click "Generate token"
   - **Copy the new token immediately** (you won't see it again)

2. **Update Configuration Files**

   **File 1:** `.auto-claude/.env` (line 22)
   ```bash
   # Old (DELETE THIS LINE):
   GITHUB_TOKEN=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

   # New (REPLACE WITH):
   GITHUB_TOKEN=gho_YOUR_NEW_TOKEN_HERE
   ```

   **File 2:** `.auto-claude/CONFIG_GUIDE.md` (line 36)
   ```markdown
   # Old (DELETE THIS):
   GITHUB_TOKEN=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

   # New (REPLACE WITH - or use placeholder):
   GITHUB_TOKEN=gho_****_REDACTED_****
   ```

   **File 3:** `docs/CRITICAL_QA_ANALYSIS_REPORT.md` (line 163)
   ```markdown
   # Find and REDACT:
   - **Finding:** Token `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` exposed in file

   # Replace with:
   - **Finding:** Token `gho_****_REDACTED_****` exposed in file
   ```

   **File 4:** `actions.md` (line 54)
   ```markdown
   # Find and REDACT:
   - GitHub token `gho_T78GA...` in CONFIG_GUIDE.md

   # Replace with:
   - GitHub token (redacted) in CONFIG_GUIDE.md
   ```

3. **Test New Token**
   ```bash
   # From project directory
   cd C:\xampp3\htdocs\socialbit-live

   # Test token works
   gh auth status

   # Should show:
   # ✓ Logged in to github.com as Gbit-bjorn
   # ✓ Token: gho_****
   ```

4. **Revoke Old Token**
   - Go to: https://github.com/settings/tokens
   - Find token: `SocialBit Auto Claude` (old)
   - Click "Delete" or "Revoke"
   - Confirm deletion

5. **Verify Rotation**
   ```bash
   # Old token should fail
   curl -H "Authorization: token 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" https://api.github.com/user
   # Expected: {"message":"Bad credentials",...}

   # New token should work
   gh repo view Gbit-bjorn/socialbit-live
   # Expected: Repository details
   ```

#### Files to Update
- [x] `.auto-claude/.env` (line 22)
- [x] `.auto-claude/CONFIG_GUIDE.md` (line 36 - redact)
- [x] `docs/CRITICAL_QA_ANALYSIS_REPORT.md` (line 163 - redact)
- [x] `actions.md` (line 54 - redact)

---

### IMMEDIATE ACTION 2: Rotate Production Database Password

**⏱️ Time Required:** 15-20 minutes
**Priority:** 🔴 CRITICAL
**Requires:** Plesk/cPanel access

#### Step-by-Step Instructions

1. **Access Plesk/cPanel**
   - Log into hosting control panel
   - Navigate to: Databases → MySQL/MariaDB
   - Find database: `g-bit_socialbit`

2. **Change Database Password**
   - Click on database user: `g-bit_socialbit`
   - Generate strong password (use password manager)
   - **Recommended:** 20+ characters, alphanumeric + symbols
   - Example: `K7#mP9$xQw2@nL4&vB8!zR6^tY3*jH5`
   - Save password to password manager immediately
   - Apply changes

3. **Update Production Configuration**

   **File:** `config/app.php` (line 44)
   ```php
   // Old (DELETE THIS):
   'pass' => 'MiNiMiN1L5uv5n!',

   // New (REPLACE WITH):
   'pass' => 'K7#mP9$xQw2@nL4&vB8!zR6^tY3*jH5',
   ```

4. **Test Database Connection**
   ```bash
   # SSH into production server
   ssh bjorn@socialbit.g-bit.be

   # Test connection
   mysql -u g-bit_socialbit -p'K7#mP9$xQw2@nL4&vB8!zR6^tY3*jH5' g-bit_socialbit

   # Run test query
   SELECT COUNT(*) FROM posts;
   ```

5. **Test Application**
   - Visit: https://socialbit.g-bit.be/
   - Verify login works
   - Check dashboard loads (confirms DB connection)
   - Test CSV import (confirms write permissions)

6. **Update `.worktrees/` Copy (if needed)**
   ```bash
   # Update worktree copy to match
   # File: .worktrees/001-lets-continue-to-optimise-the-tiktok-import-from-c/config/app.php
   ```

#### Critical Notes
- ⚠️ **DO NOT commit new password to git**
- ⚠️ **Update password in production first, then config file**
- ⚠️ **Keep old password in password manager for 24h** (rollback safety)

#### Files to Update
- [x] `config/app.php` (line 44)
- [x] `.worktrees/001-lets-continue-to-optimise-the-tiktok-import-from-c/config/app.php` (line 44)

---

### MEDIUM PRIORITY: Rotate TikTok API Credentials

**⏱️ Time Required:** 10-15 minutes
**Priority:** 🟡 MEDIUM (account currently blocked)
**Note:** Consider doing this when TikTok account is unblocked

#### Step-by-Step Instructions

1. **Access TikTok Developer Portal**
   - Go to: https://developers.tiktok.com/
   - Log in with business account
   - Navigate to: My Apps → SocialBit

2. **Regenerate Client Secret**
   - Click: "Reset Client Secret"
   - **Warning:** This will invalidate existing OAuth tokens
   - Copy new `client_secret`

3. **Update Configuration**

   **File:** `config/app.php` (line 19)
   ```php
   // Old:
   'client_secret' => 'Gj3r1gpO1qP8dct7oVTaCZyDbmUxXPGM',

   // New:
   'client_secret' => 'NEW_SECRET_FROM_TIKTOK_PORTAL',
   ```

4. **Update Database Tokens**
   ```sql
   -- All existing OAuth tokens are now invalid
   -- Users will need to re-authenticate
   DELETE FROM tiktok_tokens WHERE expires_at < NOW();
   ```

5. **Test OAuth Flow**
   - Visit: https://socialbit.g-bit.be/
   - Click: "Connect TikTok"
   - Complete OAuth flow
   - Verify tokens saved to database

#### Files to Update
- [x] `config/app.php` (line 19)
- [x] `.worktrees/001-lets-continue-to-optimise-the-tiktok-import-from-c/config/app.php` (line 19)

---

### LOW PRIORITY: Verify Metricool API Key

**⏱️ Time Required:** 2 minutes
**Priority:** 🟢 LOW

#### Step-by-Step Instructions

1. **Check if Token is Real**
   ```bash
   # Test API key
   curl -X GET "https://api.metricool.com/v1/accounts" \
     -H "Authorization: Bearer YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB"
   ```

2. **If Real Token (ERROR response):**
   - Token is real, needs rotation
   - Follow Metricool rotation procedure below

3. **If Invalid (401 Unauthorized):**
   - ✅ Token is example/placeholder
   - No action needed
   - Consider adding comment: `# EXAMPLE TOKEN - NOT REAL`

#### If Real Token Found

**Rotate Metricool API Key:**
1. Log into: https://metricool.com/
2. Settings → API → Regenerate Token
3. Update `settings` table:
   ```sql
   UPDATE settings
   SET value = 'NEW_METRICOOL_TOKEN'
   WHERE key = 'metricool_api_key';
   ```

---

## Git History Remediation

### ⚠️ WARNING: Git History Contains Secrets

**Problem:** `config/app.php` committed in initial commit (bcb7dfa)

**Exposed:**
- Production database password
- TikTok client secret

**Options:**

#### Option 1: Rotate Credentials (RECOMMENDED)
- ✅ **Pros:** Simple, safe, no git history rewrite
- ✅ **Best for:** Active projects with collaborators
- ⚠️ **Cons:** Secrets remain in git history (but invalidated)

**Action:**
1. Rotate all credentials (see procedures above)
2. Old credentials become useless
3. Add `config/app.php` to `.gitignore`
4. Use `config/app.example.php` for template

#### Option 2: Rewrite Git History (ADVANCED - NOT RECOMMENDED)
- ⚠️ **Pros:** Removes secrets from history
- 🔴 **Cons:** Breaks clones, risky, complex
- 🔴 **Risk:** Data loss if done incorrectly

**Only use if:**
- Project is private with 1 developer (you)
- No production deployments yet
- Comfortable with `git filter-repo`

**Commands (USE WITH CAUTION):**
```bash
# Backup first
git clone C:\xampp3\htdocs\socialbit-live C:\socialbit-backup

# Install git-filter-repo
pip install git-filter-repo

# Remove config/app.php from history
cd C:\xampp3\htdocs\socialbit-live
git filter-repo --path config/app.php --invert-paths

# Force push (DESTRUCTIVE)
git push origin --force --all
```

**⚠️ RECOMMENDATION:** Use Option 1 (rotate credentials)

---

## .gitignore Improvements

### Current .gitignore
```gitignore
# Auto Claude data directory
.auto-claude/
```

### ✅ RECOMMENDED .gitignore
```gitignore
# Auto Claude data directory
.auto-claude/

# Configuration files with secrets
config/app.php
.env
.env.local

# Keep example files
!config/app.example.php
!.env.example

# Storage (uploads, logs)
storage/uploads/*
!storage/uploads/.gitkeep
storage/logs/*
!storage/logs/.gitkeep

# Temporary files
*.log
*.tmp
.DS_Store
Thumbs.db

# IDE files
.idea/
.vscode/
*.swp
*.swo

# Backup files
*.bak
*.backup
*~
```

### Implementation
1. Update `.gitignore` with recommended content
2. Remove `config/app.php` from git tracking:
   ```bash
   git rm --cached config/app.php
   git commit -m "chore: remove config/app.php from tracking (contains secrets)"
   ```
3. Verify `config/app.example.php` is tracked:
   ```bash
   git add config/app.example.php
   ```

---

## Post-Rotation Verification Checklist

### After GitHub Token Rotation
- [ ] `gh auth status` shows new token
- [ ] Can create test issue: `gh issue create --title "Test" --body "Token test"`
- [ ] Can close test issue: `gh issue close <issue-number>`
- [ ] Old token returns 401: `curl -H "Authorization: token OLD_TOKEN" https://api.github.com/user`

### After Database Password Rotation
- [ ] Application loads at https://socialbit.g-bit.be/
- [ ] Can log in to dashboard
- [ ] Posts page loads (confirms SELECT works)
- [ ] CSV import works (confirms INSERT works)
- [ ] No errors in production logs

### After TikTok Secret Rotation
- [ ] OAuth flow completes successfully
- [ ] Tokens saved to `tiktok_tokens` table
- [ ] Can fetch TikTok data via API
- [ ] Old tokens invalidated

### After .gitignore Updates
- [ ] `git status` does NOT show `config/app.php`
- [ ] `git status` DOES show `config/app.example.php` (if modified)
- [ ] `.auto-claude/` ignored
- [ ] `storage/uploads/*` ignored (except `.gitkeep`)

---

## Long-Term Security Recommendations

### 1. Environment Variable Strategy

**Problem:** Credentials in PHP files
**Solution:** Move to environment variables

**Implementation:**
```php
// config/app.php (refactored)
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'name' => getenv('DB_NAME') ?: 'social_media_analytics',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    'tiktok' => [
        'client_key' => getenv('TIKTOK_CLIENT_KEY'),
        'client_secret' => getenv('TIKTOK_CLIENT_SECRET'),
    ],
];
```

**Plesk Setup:**
- Dashboard → PHP Settings → Environment Variables
- Add: `DB_PASS=K7#mP9$xQw2@nL4&vB8!zR6^tY3*jH5`

### 2. Secrets Management

**Options:**
1. **Plesk Environment Variables** (recommended for current setup)
2. **HashiCorp Vault** (overkill for small project)
3. **AWS Secrets Manager** (if migrating to cloud)
4. **1Password/Bitwarden** (manual but secure)

### 3. Automated Security Scanning

**Tools:**
- **Gitleaks:** Scan git history for secrets
  ```bash
  gitleaks detect --source C:\xampp3\htdocs\socialbit-live
  ```
- **TruffleHog:** Find accidentally committed secrets
- **GitHub Secret Scanning:** Enable in repo settings

### 4. Regular Rotation Schedule

**Recommended:**
- GitHub tokens: Every 90 days
- Database passwords: Every 180 days
- API keys: Every 90 days (or when leaving team members)

**Set Calendar Reminders:**
- Next rotation: 2026-05-07 (3 months)

### 5. Audit Trail

**Track rotations in:**
```markdown
# docs/SECURITY_ROTATIONS.md

## 2026-02-07
- GitHub token rotated (security audit)
- Production DB password rotated
- TikTok client secret rotated

## 2026-05-07 (Scheduled)
- GitHub token rotation (90-day cycle)
```

---

## Summary & Next Steps

### Immediate Actions (Next 30 Minutes)

1. **Rotate GitHub Token** (5-10 min)
   - Generate new token at GitHub
   - Update 4 files (`.auto-claude/.env`, configs, docs)
   - Test with `gh auth status`
   - Revoke old token

2. **Rotate Production DB Password** (15-20 min)
   - Access Plesk
   - Generate strong password
   - Update `config/app.php`
   - Test application

3. **Update .gitignore** (2 min)
   - Add `config/app.php` to `.gitignore`
   - Remove from git tracking: `git rm --cached config/app.php`

### Short-Term Actions (This Week)

4. **Verify Metricool Key** (2 min)
   - Test if example or real token
   - Rotate if real

5. **Rotate TikTok Secret** (when account unblocked)
   - Regenerate in developer portal
   - Update config
   - Test OAuth flow

6. **Create Security Documentation** (10 min)
   - Document rotation schedule
   - Create `docs/SECURITY_ROTATIONS.md`
   - Add to CLAUDE.md security section

### Long-Term Actions (Month 2-3)

7. **Migrate to Environment Variables**
   - Refactor `config/app.php`
   - Set up Plesk environment variables
   - Test thoroughly

8. **Set Up Automated Scanning**
   - Install Gitleaks
   - Add pre-commit hooks
   - Enable GitHub secret scanning

9. **Password Manager Integration**
   - Store all credentials in 1Password/Bitwarden
   - Share securely with team (if applicable)

---

## Risk Assessment Summary

| Credential | Exposure | Impact | Priority | Estimated Time |
|------------|----------|--------|----------|----------------|
| GitHub Token | Untracked files | High | 🔴 Immediate | 5-10 min |
| Production DB Pass | Git history | Critical | 🔴 Immediate | 15-20 min |
| TikTok Secret | Git history | Medium | 🟡 Medium | 10-15 min |
| Metricool Key | Documentation | Low | 🟢 Low | 2 min |

**Total Time Required:** 32-47 minutes

**Break-even:** Rotating credentials now prevents potential:
- Unauthorized database access
- Data breach ($50K+ in damages)
- GitHub repo compromise
- TikTok API abuse

**ROI:** 45 minutes of work = months of security

---

## Appendix: Credential Locations Reference

### GitHub Token: `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`
- `.auto-claude/.env:22` (gitignored ✅)
- `.auto-claude/CONFIG_GUIDE.md:36` (gitignored ✅)
- `docs/CRITICAL_QA_ANALYSIS_REPORT.md:163` (untracked ⚠️)
- `actions.md:54` (untracked ⚠️)

### Production DB Password: `MiNiMiN1L5uv5n!`
- `config/app.php:44` (tracked in git 🔴)
- `.worktrees/001-lets-continue-to-optimise-the-tiktok-import-from-c/config/app.php:44` (worktree copy)

### TikTok Client Secret: `Gj3r1gpO1qP8dct7oVTaCZyDbmUxXPGM`
- `config/app.php:19` (tracked in git 🔴)
- `.worktrees/001-lets-continue-to-optimise-the-tiktok-import-from-c/config/app.php:19` (worktree copy)

### Metricool API Key: `YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB`
- `docs/METRICOOL_QUICK_START.md:27` (untracked, likely example ✅)

---

**Report Generated:** 2026-02-07
**Auditor:** security-auditor (Claude Agent)
**Next Audit:** 2026-05-07 (90 days)
