# Auto Claude Configuration Guide

**Last Updated:** 2026-02-06

This directory contains configuration for the Auto Claude UI framework.

---

## 📁 File Overview

### `.env`
Environment variables for Auto Claude features.

**Important Notes:**
- `CLAUDE_CODE_OAUTH_TOKEN` is **intentionally not set here** (security)
- Token is loaded from system environment variable instead
- GitHub token is safe to keep here (scoped, revocable)

### Environment Variable Hierarchy

Claude Code loads environment variables in this order (last wins):

1. **System environment** (highest priority)
2. **`.auto-claude/.env`** (this file)
3. **`~/.claude/settings.json` → `env` section**
4. **Project `.claude_settings.json`**

---

## 🔧 Current Configuration

### ✅ Configured Features

**GitHub Integration:**
```bash
GITHUB_TOKEN=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
GITHUB_REPO=Gbit-bjorn/socialbit-live
```
- Used for: PR creation, issue management, gh CLI
- Scope: `repo` access
- Revocable at: https://github.com/settings/tokens

**Claude Code OAuth:**
```bash
# Loaded from system environment (not stored in file)
CLAUDE_CODE_OAUTH_TOKEN=sk-ant-oat01-...
```

### ❌ Not Configured (Optional)

**Linear Integration:** (Project management)
- `LINEAR_API_KEY`
- `LINEAR_TEAM_ID`
- `LINEAR_PROJECT_ID`

**Memory/Graphiti:** (Semantic search)
- `GRAPHITI_ENABLED`
- `GRAPHITI_EMBEDDER_PROVIDER`
- OpenAI/Ollama/Google AI embeddings

---

## 🚀 Recommended Setup for SocialBit

### Do You Need These?

**Linear Integration:** ❌ **No**
- SocialBit uses GitHub Issues + Project Boards
- Linear adds complexity without benefit

**Memory/Graphiti:** ⚠️ **Maybe Later**
- Useful for large codebases (10K+ files)
- SocialBit is ~50 files currently
- Wait until Month 4-5 when codebase grows

**Ollama Embeddings:** 💡 **Consider**
- Free, local semantic search
- No API costs
- Requires Ollama installation
- Setup: https://ollama.com/

---

## 🔐 Security Best Practices

### Tokens & Secrets

**DO:**
- ✅ Use system environment variables for sensitive tokens
- ✅ Add `.env` to `.gitignore` (already done)
- ✅ Use scoped GitHub tokens (not full access)
- ✅ Rotate tokens every 90 days

**DON'T:**
- ❌ Commit tokens to Git
- ❌ Share tokens in chat/email
- ❌ Use the same token across multiple projects
- ❌ Give tokens more permissions than needed

### Current Token Scopes

**GitHub Token:**
- Scope: `repo` (private repo access)
- Expires: Never (consider setting expiration)
- Can revoke: Yes (GitHub settings)

**Claude Code OAuth:**
- Scope: Claude API access
- Tied to: Your Claude account
- Can revoke: Yes (Claude dashboard)

---

## 🛠️ How to Update Configuration

### Add a New Environment Variable

1. **Edit `.env` file:**
   ```bash
   nano .auto-claude/.env
   ```

2. **Add variable:**
   ```bash
   NEW_VARIABLE=value
   ```

3. **Restart Claude Code session:**
   ```bash
   # Exit current session (Ctrl+D or /exit)
   claude
   ```

### Rotate GitHub Token

1. **Generate new token:** https://github.com/settings/tokens/new
   - Select `repo` scope
   - Set expiration (90 days recommended)

2. **Update `.env`:**
   ```bash
   GITHUB_TOKEN=gho_NEW_TOKEN_HERE
   ```

3. **Revoke old token:** GitHub Settings → Tokens → Revoke

---

## 📖 References

- **Auto Claude UI:** (if applicable - internal tool?)
- **Claude Code Docs:** https://code.claude.com/docs
- **GitHub Token Scopes:** https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/scopes-for-oauth-apps
- **Environment Variables:** https://code.claude.com/docs/en/settings

---

**For Questions:** Ask user (Bjorn) or check Auto Claude UI documentation
