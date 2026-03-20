# AI Agent Architecture Best Practices for SocialBit
## Comprehensive Guide & Recommendations (2026-02-07)

**Research Agent:** best-practices-researcher
**Task:** Task #3 - Research and compile official and community best practices for AI agent architecture

---

## Executive Summary

This document provides research-backed best practices for AI agent architecture specifically tailored to the SocialBit project. Based on official Anthropic documentation, community resources, and industry experts, it includes:

1. **Current SocialBit Architecture Analysis**
2. **Official Claude Code Best Practices**
3. **Multi-Agent Workflow Patterns**
4. **Configuration Architecture Recommendations**
5. **Common Pitfalls and How to Avoid Them**
6. **Prioritized Improvement Suggestions**

---

## 1. Current SocialBit Setup Analysis

### Existing Configuration

**Project Structure:**
```
socialbit-live/
├── .claude/
│   └── settings.local.json       # Project-level permissions
├── .claude_settings.json          # Root-level settings (sandbox, permissions)
├── .serena/
│   └── memories/                  # Serena agent memory files (7 files)
├── CLAUDE.md                      # Project instructions (458 lines)
├── actions.md                     # Completed task log
└── docs/
    ├── IMPLEMENTATION_PLAN.md
    ├── migration_*.log
    └── archive/                   # Strategy documents
```

**Global Configuration:**
```
~/.claude/projects/C--xampp3-htdocs-socialbit-live/memory/
└── MEMORY.md                      # Global memory (73 lines)
```

### Current Strengths

✅ **Comprehensive CLAUDE.md** - Well-structured with clear sections
✅ **Action Logging** - actions.md tracks completed work
✅ **Multi-agent capable** - Settings allow multiple agent tools
✅ **Documentation-focused** - Strong docs/ folder hierarchy
✅ **Memory management** - Separate global MEMORY.md

### Current Weaknesses

⚠️ **CLAUDE.md length** (458 lines) - Risk of context degradation
⚠️ **No explicit skills directory** - Skills not organized in separate folder
⚠️ **Serena vs Claude overlap** - Two memory systems (.serena/memories + MEMORY.md)
⚠️ **No compaction strategy** - CLAUDE.md doesn't specify compaction rules
⚠️ **Missing agent definitions** - No explicit AGENTS.md or .claude/agents/

---

## 2. Official Claude Code Best Practices

### 2.1 CLAUDE.md Core Principles

> **Source:** [Best Practices for Claude Code - Claude Code Docs](https://code.claude.com/docs/en/best-practices), [Writing a good CLAUDE.md | HumanLayer Blog](https://www.humanlayer.dev/blog/writing-a-good-claude-md)

#### Keep It Minimal

**Rule:** For each line in CLAUDE.md, ask "Would removing this cause Claude to make mistakes?" If not, cut it.

**Why:** If your CLAUDE.md is too long, Claude ignores half of it because important rules get lost in the noise. Context degradation is the primary failure mode.

**SocialBit Impact:** Current 458 lines is approaching danger zone. Target: <300 lines for core instructions.

#### Use Pointers, Not Copies

**Rule:** Don't include code snippets in CLAUDE.md—they become out-of-date quickly. Use `file:line` references instead.

**Current SocialBit Example (Good):**
```markdown
# Project Structure (shows paths, not code)
```

**Recommendation:** Maintain this pattern. Don't add code examples to CLAUDE.md.

#### Delegate to External Tools

**Rule:** Never send an LLM to do a linter's job. LLMs are expensive and slow compared to traditional linters.

**SocialBit Consideration:** The current "PSR-12 standard" reference is good. Don't add detailed code style rules—let `phpcbf` handle it.

#### What Belongs in CLAUDE.md

**Include:**
- Common bash commands specific to your project
- Core files and utility functions (paths only)
- Code style guidelines (brief - point to external linter)
- Testing instructions
- Repository etiquette
- Developer environment setup
- Unexpected behaviors particular to the project

**Exclude:**
- Detailed code examples (will become stale)
- Language syntax tutorials (Claude already knows PHP)
- Temporary project state (use MEMORY.md instead)

#### Compaction Strategy

**Rule:** Customize compaction behavior in CLAUDE.md with explicit instructions.

**Recommendation for SocialBit:**
```markdown
## Compaction Rules

When compacting conversation history:
- ALWAYS preserve the full list of modified files
- ALWAYS preserve test commands and their results
- ALWAYS preserve migration numbers and execution status
- ALWAYS preserve multi-tenant client_id context
- Summarize research findings but keep URLs/sources
```

---

### 2.2 Memory Management Best Practices

> **Source:** [Manage Claude's memory - Claude Code Docs](https://code.claude.com/docs/en/memory), [Claude Code Best Practices: Memory Management](https://cuong.io/blog/2025/06/15-claude-code-best-practices-memory-management)

#### Memory Hierarchy

**Claude Code Memory Locations (in loading order):**
1. **Global MEMORY.md** (~/.claude/projects/{project}/memory/MEMORY.md)
2. **Project CLAUDE.md** (project-root/CLAUDE.md)
3. **Active conversation context**
4. **Tool results and compacted history**

Files higher in hierarchy take precedence and load first.

#### Memory File Organization

**Best Practices:**

**Keep MEMORY.md under 200 lines** - Lines after 200 get truncated
**Organize by topic** - Use descriptive markdown headings
**One topic per file** - For detailed info, create separate files
**Use modular imports** - Link to detailed docs with @docs/filename.md

**Current SocialBit MEMORY.md:** 73 lines ✅ Well within limit

**Recommendation:** Current structure is good. Consider splitting if it grows beyond 150 lines:
```
memory/
├── MEMORY.md              # Index + critical rules (<200 lines)
├── database-patterns.md   # Database-specific practices
├── api-integration.md     # Platform API quirks
└── analytics-insights.md  # KPI and metrics context
```

#### Memory Update Workflow

**Rule:** When you correct Claude about a specific implementation detail, immediately update the relevant memory file.

**Recommendation for SocialBit:**
- After each actions.md entry, evaluate if MEMORY.md needs updating
- Remove obsolete information ruthlessly
- Archive old insights to docs/archive/ rather than delete

#### For Long-Running Workflows

**Rule:** Use both compaction (keeps active context manageable) and memory (persists important information across compaction boundaries).

**SocialBit Application:**
- Multi-month MVP development = long-running workflow
- Use MEMORY.md for patterns that span weeks (database conventions, API quirks)
- Use actions.md for completed work history
- Use CLAUDE.md for architectural principles

---

### 2.3 Context Management

> **Source:** [Cooking with Claude Code: The Complete Guide](https://www.siddharthbharath.com/claude-code-the-complete-guide/), [Claude Code Best Practices](https://rosmur.github.io/claudecode-best-practices/)

#### The Context Degradation Problem

**Issue:** LLM performance degrades as context fills. Claude may start "forgetting" earlier instructions or making mistakes.

**Common Mistakes:**

1. **"Kitchen Sink" Session**
   - Starting with one task, asking something unrelated, going back to first task
   - **Fix:** Use `/clear` between unrelated tasks

2. **Over-Specified CLAUDE.md**
   - Too many instructions cause important rules to get lost
   - **Fix:** Ruthlessly prune

3. **Code Style Guidelines in CLAUDE.md**
   - Adds irrelevant code snippets that degrade performance
   - **Fix:** Use linters/formatters, keep CLAUDE.md brief

#### Best Practices for SocialBit

**Session Hygiene:**
- `/clear` after completing each migration
- `/clear` between research and implementation tasks
- `/clear` when switching between subsystems (TikTok API → Instagram API)

**Context-Efficient Patterns:**
- Use subagents for isolated research (don't pollute main context)
- Use @docs/filename.md references instead of pasting content
- Keep tool results minimal (read files, don't dump entire file in chat)

---

## 3. Multi-Agent Workflow Patterns

> **Source:** [How we built our multi-agent research system - Anthropic Engineering](https://www.anthropic.com/engineering/multi-agent-research-system), [AI Agent Coordination: 8 Proven Patterns [2026]](https://tacnode.io/post/ai-agent-coordination)

### 3.1 Performance Insights

**Key Finding:** A multi-agent system with Claude Opus 4 as lead and Claude Sonnet 4 subagents outperformed single-agent Claude Opus 4 by 90.2% on research evaluations.

**Why:** Token usage by itself explains 80% of variance. Distributing work across agents with separate context windows adds capacity for parallel reasoning.

**Cost Consideration:**
- Single agent chat: 1× token usage (baseline)
- Single agent: 4× token usage
- Multi-agent system: 15× token usage

**Recommendation:** Use multi-agent systems where task value justifies 15× cost (complex migrations, critical architecture decisions).

### 3.2 Coordination Patterns

#### Pattern 1: Sequential Specialists

**When:** Multi-step workflows where each step depends on previous results.

**How:** Main agent passes context to subagent → subagent completes task → returns to main → main passes relevant context to next subagent.

**SocialBit Example:**
```
Main Agent → Database Agent (design schema)
          → Backend Agent (implement repositories)
          → Testing Agent (verify migrations)
          → Documentation Agent (update docs)
```

**Implementation:**
```markdown
## Workflow: Database Migration

1. Database Agent designs schema (reviews existing tables, proposes changes)
2. Main agent reviews, approves
3. Backend Agent implements repository methods
4. Testing Agent runs smoke tests
5. Documentation Agent updates migration logs
```

#### Pattern 2: Parallel Research

**When:** Multiple independent queries needed simultaneously.

**How:** Lead agent spawns parallel subagents for research, collects results, synthesizes.

**SocialBit Example:**
```
Main Agent spawns simultaneously:
├─ Subagent A: Research TikTok API 2026 capabilities
├─ Subagent B: Research Instagram Graph API changes
├─ Subagent C: Research Metricool API coverage
└─ Subagent D: Research competitor features

Main agent synthesizes findings → Recommendation
```

**Caution:** Avoid dumping extensive subagent output into main conversation. Use context fork for isolation.

#### Pattern 3: Task-Specific Specialists

**When:** Agents focus on specific domains with custom prompts.

**How:** Define agents with specialized knowledge, tools, and constraints.

**SocialBit Recommendation:**
```
.claude/agents/
├── database-architect.md      # Schema design, migrations
├── backend-developer.md       # PHP services, repositories
├── api-integrator.md          # Platform API wrappers
├── research-analyst.md        # Web search, competitor analysis
├── documentation-writer.md    # Update docs, write guides
└── quality-assurance.md       # Testing, verification
```

### 3.3 Tool Design for Multi-Agent

> **Source:** [Effective harnesses for long-running agents - Anthropic Engineering](https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents)

**Rule:** Without tool descriptions, agents go down the wrong path. Each tool needs purpose and clear description.

**Bad Tool Design:**
```json
{
  "name": "run_query",
  "description": "Execute SQL"
}
```

**Good Tool Design:**
```json
{
  "name": "execute_migration",
  "description": "Execute a numbered SQL migration file. Always check if migration was previously run by querying schema_migrations table first. Use ONLY for files in scripts/ directory. Provide full absolute path."
}
```

**Recommendation for SocialBit:**
- Each agent should have minimal tool access (principle of least privilege)
- Code reviewer agent doesn't need Bash, Write, WebFetch
- Research agent doesn't need Edit or Write
- Backend developer doesn't need WebSearch (unless explicitly researching)

---

## 4. Configuration Architecture

> **Source:** [AGENTS.md vs CLAUDE.md: Complete Guide](https://substratia.io/blog/agents-md-vs-claude-md/), [Global Agents Inheritance issue](https://github.com/anthropics/claude-code/issues/5750)

### 4.1 Global vs Project Configuration

#### Expected Hierarchy

**Typical Pattern:**
```
~/.claude/                          # Global configuration
  ├── CLAUDE.md                     # Global instructions (all projects)
  ├── AGENTS.md or agents/*.md      # Global agent definitions
  └── projects/{hash}/memory/       # Project-specific memory

project-root/
  ├── .claude/
  │   ├── settings.local.json       # Project permissions
  │   └── agents/*.md               # Project-specific agents
  └── CLAUDE.md                     # Project instructions (overrides global)
```

**Inheritance:**
- Project-level configuration takes precedence over global
- Global agents available across all projects unless overridden
- Project agents supplement (not replace) global agents

#### Known Issues

⚠️ **Bug:** Sub-agents spawned by Task tool may not inherit configured model from global/local settings ([Issue #5456](https://github.com/anthropics/claude-code/issues/5456))

⚠️ **Inconsistency:** Different AI tools use different global config paths, no standardization yet.

### 4.2 Recommended Configuration Strategy

#### For SocialBit

**Global (~/.claude/):**
- Generic coding best practices (DRY, SOLID)
- Security fundamentals (SQL injection prevention)
- Git commit conventions
- Generic agent definitions (researcher, documenter)

**Project (socialbit-live/CLAUDE.md):**
- SocialBit-specific architecture (vanilla PHP, no framework)
- Database conventions (multi-tenant, client_id everywhere)
- Project-specific constraints (XAMPP, Plesk deployment)
- Platform integration details (TikTok API quirks)

**Project (socialbit-live/.claude/agents/):**
- Specialized agents for SocialBit tasks
- Tools and permissions specific to this codebase

### 4.3 Settings Inheritance Pattern

**Best Practice:**

```json
// Global ~/.claude/settings.json
{
  "permissions": {
    "defaultMode": "acceptEdits",
    "allow": [
      "Read(./**)",
      "Glob(./**)",
      "Grep(./**)"
    ]
  }
}

// Project .claude_settings.json
{
  "permissions": {
    "allow": [
      // Inherits global + adds project-specific
      "Bash(php:*)",
      "Bash(mysql:*)"
    ]
  }
}
```

**SocialBit Current State:**
- Has `.claude_settings.json` with sandbox and permissions ✅
- Has `.claude/settings.local.json` with additional MCP permissions ✅
- Missing `.claude/agents/` directory ⚠️

---

## 5. Skills Organization Best Practices

> **Source:** [Extend Claude with skills - Claude Code Docs](https://code.claude.com/docs/en/skills), [Claude Skills and CLAUDE.md: practical 2026 guide](https://www.gend.co/blog/claude-skills-claude-md-guide)

### 5.1 Skill Structure

**Every skill needs:**
```
~/.claude/skills/{skill-name}/
├── SKILL.md                  # YAML frontmatter + instructions
└── additional-context.md     # (optional) Referenced files
```

**SKILL.md Format:**
```markdown
---
name: database-migration
description: Create and execute database migration scripts for SocialBit multi-tenant architecture
tags: [database, migration, mysql]
---

# Database Migration Skill

## When to Use
- Creating numbered migration files (scripts/XXX_description.sql)
- Adding multi-tenant columns (client_id with foreign key)
- Updating analytics views

## Instructions
1. Check latest migration number: `ls scripts/*.sql | tail -1`
2. Create new file: `scripts/{next_number}_{description}.sql`
3. Include multi-tenant considerations (CASCADE DELETE)
4. Test locally: `mysql -u root social_media_analytics < scripts/{file}`
5. Update docs/migrations_{numbers}_log.md
6. Add entry to actions.md
```

### 5.2 Naming Conventions

**Best Practice:** Use gerund form (verb + -ing) for skill names - clearly describes activity.

**Examples:**
- ✅ `database-migrating`
- ✅ `api-integrating`
- ✅ `code-reviewing`
- ❌ `database` (noun - unclear what it does)
- ❌ `api-integration` (noun phrase - use gerund)

### 5.3 Context Management for Skills

**Key Insight:** Only metadata (name, description) pre-loaded at startup. Claude reads SKILL.md only when skill becomes relevant.

**Implication:** Being concise in SKILL.md still matters—once loaded, every token competes with conversation history.

**Recommendation:** Keep SKILL.md under 200 lines. For complex procedures, link to docs:
```markdown
See detailed procedure: @docs/TIKTOK_API_TROUBLESHOOTING.md
```

### 5.4 Organization-Level Skills

**Feature (Dec 2025):** Admins can deploy skills workspace-wide with automatic updates.

**SocialBit Application:** Not immediately relevant (single developer), but consider for future if onboarding team members.

---

## 6. Common Pitfalls and How to Avoid Them

> **Source:** [Claude Code Subagents: Common Mistakes & Best Practices](https://claudekit.cc/blog/vc-04-subagents-from-basic-to-deep-dive-i-misunderstood), [4 Claude Code Subagent Mistakes That Kill Your Workflow](https://dev.to/alireza_rezvani/4-claude-code-subagent-mistakes-that-kill-your-workflow-and-the-fixes-3n72)

### 6.1 Context Management Pitfalls

#### Pitfall 1: Kitchen Sink Sessions

**Problem:** Mixing unrelated tasks in one session fills context with irrelevant information.

**Example:**
```
User: "Debug TikTok API issue"
[Claude investigates, uses 20K tokens]
User: "Also can you explain how hashtag tracking works?"
[Claude explains, uses 15K tokens]
User: "Back to the API, did you find the issue?"
[Claude's earlier findings buried in context]
```

**Fix:**
- Use `/clear` between unrelated tasks
- Start new conversation for different subsystems
- For SocialBit: Separate sessions for each platform integration

#### Pitfall 2: Over-Specified CLAUDE.md

**Problem:** 500+ line CLAUDE.md with detailed code examples causes Claude to ignore important rules.

**SocialBit Current State:** 458 lines - approaching danger zone

**Fix:**
1. Move code style to linter config
2. Move detailed architecture docs to docs/
3. Keep CLAUDE.md focused on constraints and project-specific quirks
4. Target: <300 lines

**Refactoring Strategy:**
```markdown
# Current CLAUDE.md (458 lines)
- 80 lines: Project structure (keep - useful reference)
- 150 lines: Database architecture (MOVE to docs/DATABASE.md)
- 100 lines: Coding standards (REDUCE - point to PSR-12)
- 80 lines: Platform integrations (MOVE to docs/API_GUIDE.md)
- 48 lines: Development workflow, constraints (KEEP)

# Optimized CLAUDE.md (~250 lines)
- Project overview (50 lines)
- Critical constraints (50 lines)
- Architecture principles (50 lines)
- Development workflow (50 lines)
- Pointers to detailed docs (50 lines)
```

#### Pitfall 3: Code Style in CLAUDE.md

**Problem:** Including PHP code style examples in CLAUDE.md wastes tokens and becomes outdated.

**SocialBit Current Example (Good):**
```markdown
- **PSR-12** standard (use `phpcbf` for auto-formatting if available)
```

**Keep this brief approach.** Don't expand to include examples.

### 6.2 Subagent Architecture Pitfalls

#### Pitfall 1: Blind Subagents

**Problem:** Each subagent only knows its task, has no context about the project.

**Example:**
```
Main: "Subagent, fix this bug in TikTokRepository"
Subagent: [Has no idea about multi-tenant architecture, client_id requirements]
```

**Fix:** Subagents inherit CLAUDE.md but need explicit context for their task.

**Best Practice:**
```markdown
# When delegating to subagent
"Fix TikTokRepository bug. Context: This is a multi-tenant system.
All queries MUST include client_id in WHERE clause. See CLAUDE.md
'Multi-tenant Foundation' section for details."
```

#### Pitfall 2: Excessive Tool Access

**Problem:** Giving agents unnecessary tools wastes tokens and risks misuse.

**Example:**
```yaml
# Bad: Code reviewer with full tool access
agents/code-reviewer.md:
  tools: [Bash, Read, Write, Edit, WebFetch, Grep, Glob]
  # Reviewer doesn't need Write or Bash!

# Good: Minimal tool access
agents/code-reviewer.md:
  tools: [Read, Grep, Glob]
  # Can read and search, can't modify
```

**SocialBit Recommendation:**
```markdown
# Agent tool permissions
database-architect:   [Read, Write, Bash(mysql:*), Glob, Grep]
backend-developer:    [Read, Edit, Bash(php:*), Grep, Glob]
code-reviewer:        [Read, Grep, Glob]
research-analyst:     [WebSearch, WebFetch, Read]
documentation-writer: [Read, Edit(docs/**), Write(docs/**)]
```

#### Pitfall 3: Dumping Subagent Output

**Problem:** Research subagent dumps 50K tokens of results into main conversation.

**Fix:** Use context fork - subagent runs in isolation, returns summarized results.

**SocialBit Application:**
```markdown
# When researching APIs
Main agent: "Subagent, research TikTok API 2026 capabilities"
[Subagent runs in isolation]
Subagent returns: "Summary: TikTok API now supports X, Y, Z.
Full details at: [links]. Recommendation: Use endpoint /v2/..."
```

### 6.3 Verification and Testing Pitfalls

#### Pitfall 1: Trust-Then-Verify Gap

**Problem:** Claude produces plausible-looking code that doesn't handle edge cases.

**Example:**
```php
// Claude generates
public function findByClient(int $clientId): array {
    return $this->db->query("SELECT * FROM posts WHERE client_id = ?", [$clientId]);
}
// Looks good, but: What if $clientId is 0? NULL? Negative?
```

**Fix:** Include tests, expected outputs, or edge cases in request.

**Best Practice for SocialBit:**
```markdown
# When requesting feature
"Implement findByClient method. Requirements:
- Return empty array if clientId invalid (<= 0)
- Handle NULL gracefully
- Test with: clientId=1 (expect 5 posts), clientId=999 (expect empty array)
Include these test cases in your implementation."
```

#### Pitfall 2: Jumping to Implementation

**Problem:** Letting Claude code immediately can solve the wrong problem.

**Fix:** Separate research/planning from implementation.

**Best Practice:**
```markdown
# Multi-phase approach
Phase 1: "Research Instagram Graph API rate limits and pagination"
[Subagent researches, returns findings]

Phase 2: "Design API wrapper architecture based on findings"
[Main agent designs, user reviews]

Phase 3: "Implement InstagramApiService based on approved design"
[Backend agent implements]
```

### 6.4 Over-Engineering Pitfall

**Problem:** Creating complex multi-agent systems when simple control loops suffice.

**Key Insight:** Simple control loops outperform multi-agent systems for straightforward tasks. LLMs are fragile—additional complexity makes debugging exponentially harder.

**When to Use Multi-Agent (15× cost):**
- ✅ Complex migrations requiring research + design + implementation + verification
- ✅ Critical architecture decisions needing multiple perspectives
- ✅ Parallel research across multiple platforms

**When to Use Single Agent:**
- ✅ Bug fixes in existing code
- ✅ Adding single feature to existing service
- ✅ Updating documentation
- ✅ Running migrations

**SocialBit Application:**
- Use multi-agent for: Multi-platform API integration strategy
- Use single agent for: Adding new repository method

---

## 7. Vanilla PHP Best Practices for AI Agents

> **Source:** [Neuron AI - PHP Agentic Framework](https://www.neuron-ai.dev/), [Building Intelligent PHP Applications - Symfony AI Agent](https://dev.to/mattleads/building-intelligent-php-applications-best-practices-for-the-symfony-ai-agent-component-3b76)

### 7.1 Repository Pattern

**Current SocialBit Pattern (Good):**
```php
class PostRepository {
    public function findById(int $id): ?array { }
    public function findByClient(int $clientId): array { }
    public function create(array $data): int { }
    public function update(int $id, array $data): bool { }
}
```

**Best Practices:**
- Keep repositories thin (data access ONLY)
- Business logic goes in Services
- Always use prepared statements
- Type hint everything (int, array, bool, ?string)

**Recommendation for AI Agents:**
```markdown
# In CLAUDE.md
## Repository Pattern Rules
- Repositories ONLY handle database access
- No business logic in repositories
- Every method has type hints
- Use PDO prepared statements ALWAYS
- Return types: single row = ?array, multiple = array, mutations = int|bool
```

### 7.2 Service Layer

**Current SocialBit Pattern (Good):**
```php
class TikTokAnalyticsService {
    public function __construct(
        private PostRepository $postRepo,
        private MetricsRepository $metricsRepo
    ) {}

    public function calculateEngagementRate(int $postId): float {
        // Business logic
    }
}
```

**Best Practices:**
- Services orchestrate multiple repositories
- Services contain business logic
- Services handle validation
- Services are unit-testable (inject repos)

### 7.3 Code Quality Standards

**For AI-Generated PHP:**
- Follow PSR-12 coding style
- Implement SOLID principles
- Use design patterns (Repository, Service, Factory)
- Proper error handling with try/catch
- Prepared statements for SQL security
- Type declarations on all functions

**SocialBit Current State:** ✅ Already following these patterns

**Recommendation:** Add to CLAUDE.md:
```markdown
## Code Quality Non-Negotiables
1. NEVER use raw SQL - always prepared statements
2. ALWAYS type hint parameters and returns
3. ALWAYS validate input in Services before passing to Repositories
4. ALWAYS use try/catch for database operations
5. NEVER put business logic in Controllers or Repositories
```

---

## 8. Prioritized Improvement Suggestions

### 8.1 Immediate (This Week)

#### 1. Optimize CLAUDE.md Length
**Priority:** High
**Impact:** Prevent context degradation
**Effort:** 2-3 hours

**Actions:**
1. Move detailed database schema to `docs/DATABASE_SCHEMA.md`
2. Move API integration details to `docs/API_INTEGRATION.md`
3. Reduce coding standards section (brief PSR-12 reference only)
4. Add compaction rules section
5. Target: Reduce from 458 to ~280 lines

**Before/After:**
```markdown
# Before (150 lines in CLAUDE.md)
### Database Tables
**Multi-tenant Foundation:**
- `clients` - Tenant/customer accounts
  - id, name, domain, created_at, updated_at
  - Cascading deletes to maintain data isolation
- All tables have `client_id` foreign key
... [detailed schema continues]

# After (15 lines in CLAUDE.md)
### Database Architecture
- Multi-tenant with `client_id` everywhere
- CASCADE DELETE for data isolation
- Time-series metrics in `metrics_history`
- Full schema: @docs/DATABASE_SCHEMA.md
- Migration strategy: @docs/DATABASE_EVOLUTION.md
```

#### 2. Create Dedicated Agents Directory
**Priority:** High
**Impact:** Organize multi-agent workflows
**Effort:** 1-2 hours

**Actions:**
```bash
mkdir .claude/agents/
```

Create specialized agents:
```
.claude/agents/
├── database-architect.md
├── backend-developer.md
├── api-integrator.md
├── research-analyst.md
├── documentation-writer.md
└── quality-assurance.md
```

**Template:** See Section 8.3 for agent definitions.

#### 3. Add Compaction Rules to CLAUDE.md
**Priority:** Medium
**Impact:** Preserve critical context across sessions
**Effort:** 15 minutes

**Add Section:**
```markdown
## Compaction Rules

When compacting conversation history:
- ALWAYS preserve migration file paths and execution status
- ALWAYS preserve multi-tenant client_id context
- ALWAYS preserve test commands and results
- ALWAYS preserve list of modified files
- Summarize research findings but keep source URLs
- Remove verbose tool outputs (keep summaries)
```

### 8.2 Short-Term (Next 2 Weeks)

#### 4. Consolidate Memory Systems
**Priority:** Medium
**Impact:** Reduce confusion between Serena and Claude memories
**Effort:** 2-3 hours

**Current Overlap:**
- `.serena/memories/` (7 files) - Serena-specific
- `~/.claude/.../memory/MEMORY.md` - Claude global

**Recommendation:**
- Keep Serena memories for Serena agent
- Use Claude MEMORY.md as single source of truth for Claude Code agents
- Cross-reference when needed: "See also .serena/memories/tech_stack.md"

**Actions:**
1. Review all 7 Serena memory files
2. Identify overlaps with MEMORY.md
3. Consolidate common patterns into MEMORY.md
4. Keep Serena-specific (if any) in .serena/

#### 5. Create Skills Directory
**Priority:** Medium
**Impact:** Reusable workflows across projects
**Effort:** 3-4 hours

**Structure:**
```
~/.claude/skills/
├── database-migrating/
│   └── SKILL.md
├── api-integrating/
│   └── SKILL.md
├── csv-importing/
│   └── SKILL.md
└── code-reviewing/
    └── SKILL.md
```

**Example:** `database-migrating/SKILL.md`
```markdown
---
name: database-migrating
description: Create and execute MySQL migration scripts for multi-tenant PHP applications
tags: [database, migration, mysql, multi-tenant]
---

# Database Migration Skill

## When to Use This Skill
- Creating numbered SQL migration files
- Updating multi-tenant tables (adding client_id)
- Modifying time-series analytics tables

## Instructions

### 1. Check Existing Migrations
```bash
ls scripts/*.sql | sort -V | tail -5
# Identify next number
```

### 2. Create Migration File
File: `scripts/{next_number}_{description}.sql`

Template:
```sql
-- Migration: {number} - {description}
-- Date: {YYYY-MM-DD}

-- Add multi-tenant client_id if new table
ALTER TABLE ... ADD COLUMN client_id INT NOT NULL;
ALTER TABLE ... ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Create indices for performance
CREATE INDEX idx_client_date ON ... (client_id, created_at);
```

### 3. Test Locally
```bash
mysql -u root social_media_analytics < scripts/{file}
# Verify: SHOW TABLES; DESCRIBE {table};
```

### 4. Document
- Update docs/migrations_{range}_log.md
- Add entry to actions.md

### 5. Production Deployment
- Via Plesk phpMyAdmin
- Backup database first
- Run migration
- Verify schema
```

#### 6. Document Context Management Strategy
**Priority:** Low
**Impact:** Team onboarding (future)
**Effort:** 1 hour

**Create:** `docs/AI_CONTEXT_MANAGEMENT.md`

**Contents:**
- When to use `/clear`
- When to create subagents
- When to use single agent
- Cost considerations (15× for multi-agent)
- Session hygiene best practices

### 8.3 Long-Term (Month 2-3)

#### 7. Implement Agent Specialization
**Priority:** Medium
**Impact:** Improved code quality through focused agents
**Effort:** 4-6 hours

**Create Specialized Agents:**

**`database-architect.md`:**
```markdown
---
name: database-architect
description: Design and implement database schemas for SocialBit multi-tenant analytics
tools: [Read, Write, Bash(mysql:*), Glob, Grep]
model: claude-sonnet-4-5
---

# Database Architect Agent

## Responsibilities
- Design database schemas
- Create migration scripts
- Optimize queries and indices
- Ensure multi-tenant data isolation

## Context
- Project: SocialBit (vanilla PHP, no ORM)
- Database: MySQL/MariaDB
- Pattern: Multi-tenant with client_id everywhere

## Constraints
- ALWAYS include client_id in new tables
- ALWAYS use CASCADE DELETE for foreign keys
- ALWAYS create indices on (client_id, date) for time-series
- NEVER use SELECT * in queries
- ALWAYS use prepared statements

## Workflow
1. Review existing schema: `scripts/000_create_database_schema.sql`
2. Check latest migration: `ls scripts/*.sql | tail -1`
3. Design new schema (propose to user)
4. Create migration script
5. Test locally
6. Document in migrations log
```

**`backend-developer.md`:**
```markdown
---
name: backend-developer
description: Implement PHP services and repositories following SocialBit architecture
tools: [Read, Edit, Bash(php:*), Grep, Glob]
model: claude-sonnet-4-5
---

# Backend Developer Agent

## Responsibilities
- Implement Controllers (thin - HTTP handling only)
- Implement Services (business logic)
- Implement Repositories (database access)
- Follow 3-layer MVC pattern

## Architecture Rules
- Controllers: HTTP handling, validation, response formatting
- Services: Business logic, orchestration, multi-repo coordination
- Repositories: Database access ONLY, prepared statements

## Code Standards
- PSR-12 coding style
- Type hints on everything
- Dependency injection in constructors
- Try/catch for database operations

## Multi-Tenant Requirements
- EVERY query includes client_id
- Validate client_id in Services before Repositories
- Use ClientRepository->findById() to verify client exists

## Workflow
1. Read existing code in src/{layer}/
2. Follow established patterns exactly
3. Test with scripts/smoke-test.php
4. Update actions.md when complete
```

**`research-analyst.md`:**
```markdown
---
name: research-analyst
description: Research social media APIs, competitor features, and industry best practices
tools: [WebSearch, WebFetch, Read, Write(docs/research/**)]
model: claude-opus-4-6
---

# Research Analyst Agent

## Responsibilities
- Research 2026 API capabilities (TikTok, Instagram, Facebook)
- Analyze competitor features (Metricool, Hootsuite, Buffer)
- Find industry benchmarks and best practices
- Document findings for implementation teams

## Research Methodology
1. Use WebSearch for current 2026 information
2. Verify multiple sources (official docs + community)
3. Extract actionable insights for developers
4. Document with sources (URLs) for verification

## Output Format
Create research report: `docs/research/{topic}_{date}.md`

Include:
- Executive Summary (3-5 bullet points)
- Detailed Findings (with source URLs)
- Recommendations for SocialBit
- Implementation Considerations
- Next Steps

## Best Practices
- Prioritize official documentation (platform APIs)
- Cross-reference community sources (dev forums, blog posts)
- Note API limitations (rate limits, data freshness)
- Flag breaking changes from previous versions
```

**`documentation-writer.md`:**
```markdown
---
name: documentation-writer
description: Create and maintain SocialBit documentation
tools: [Read, Edit(docs/**), Write(docs/**), Grep, Glob]
model: claude-sonnet-4-5
---

# Documentation Writer Agent

## Responsibilities
- Update project documentation after features/migrations
- Create clear, actionable guides for developers
- Maintain migration logs
- Update actions.md after completed tasks

## Documentation Standards
- Use GitHub-flavored Markdown
- Include code examples with syntax highlighting
- Provide step-by-step instructions
- Link to related docs with relative paths
- Keep docs DRY (Don't Repeat Yourself)

## File Organization
- Strategy docs: docs/archive/YYYY-MM-DD-*/
- Active guides: docs/
- Migration logs: docs/migration_{numbers}_log.md
- Task history: actions.md
- Project context: CLAUDE.md

## Workflow
1. Read related existing docs
2. Identify what needs updating
3. Maintain consistent style/format
4. Cross-link related documents
5. Update table of contents if needed
```

**`code-reviewer.md`:**
```markdown
---
name: code-reviewer
description: Review PHP code for quality, security, and architecture compliance
tools: [Read, Grep, Glob]
model: claude-opus-4-6
---

# Code Reviewer Agent

## Responsibilities
- Review code changes for quality and security
- Verify architecture pattern compliance
- Check for SQL injection vulnerabilities
- Ensure multi-tenant data isolation

## Review Checklist

### Security
- [ ] All SQL uses prepared statements (NO raw SQL)
- [ ] Input validated before database operations
- [ ] client_id verified and included in queries
- [ ] No sensitive data in logs

### Architecture
- [ ] Controllers are thin (no business logic)
- [ ] Business logic in Services
- [ ] Database access ONLY in Repositories
- [ ] Dependency injection used correctly

### Code Quality
- [ ] Type hints on all parameters and returns
- [ ] Try/catch for error handling
- [ ] Meaningful variable names
- [ ] No code duplication
- [ ] PSR-12 compliant

### Multi-Tenant
- [ ] client_id in WHERE clause
- [ ] CASCADE DELETE on foreign keys
- [ ] Data isolation verified

## Output Format
Provide review as:
1. Summary (Approve / Request Changes)
2. Security Issues (if any)
3. Architecture Issues (if any)
4. Code Quality Suggestions
5. Approval or Required Changes
```

**`quality-assurance.md`:**
```markdown
---
name: quality-assurance
description: Test features, verify migrations, ensure data integrity
tools: [Read, Bash(php:*, mysql:*), Grep, Glob]
model: claude-sonnet-4-5
---

# Quality Assurance Agent

## Responsibilities
- Run smoke tests after changes
- Verify database migrations
- Test API integrations
- Validate multi-tenant data isolation

## Testing Workflow

### 1. Smoke Test
```bash
php scripts/smoke-test.php
# Verify: All tests pass
```

### 2. Migration Verification
```bash
mysql -u root -e "USE social_media_analytics; DESCRIBE {new_table};"
# Verify: Columns exist, types correct, indices present
```

### 3. Data Isolation Test
```sql
-- Verify client_id isolation
SELECT COUNT(*) FROM posts WHERE client_id = 1;
SELECT COUNT(*) FROM posts WHERE client_id = 2;
-- Should return different counts (isolated data)
```

### 4. API Integration Test
```bash
php scripts/test_metricool_api.php
# Verify: Authentication works, data retrieved
```

## Test Report Format
Create: `docs/tests/test_{feature}_{date}.md`

Include:
- Test scenarios executed
- Results (pass/fail)
- Issues found
- Recommended fixes
- Re-test after fixes

## Failure Protocol
If tests fail:
1. Document exact error message
2. Identify root cause
3. Notify backend-developer agent
4. Re-test after fix
5. Verify no regressions
```

#### 8. Create Testing Strategy Document
**Priority:** Low
**Impact:** Improve code reliability
**Effort:** 2 hours

**Create:** `docs/TESTING_STRATEGY.md`

**Contents:**
- Manual testing procedures (current approach)
- Smoke test expansion plans
- When to involve QA agent
- PHPUnit introduction roadmap (Month 3)

---

## 9. Comparison with Current Setup

### What's Working Well ✅

1. **Comprehensive CLAUDE.md** - Good structure, clear sections
2. **Action logging** - actions.md provides audit trail
3. **Documentation-first approach** - Strong docs/ folder
4. **Multi-tenant awareness** - Well-documented in CLAUDE.md
5. **Clear architecture** - 3-layer MVC well-explained
6. **Memory management** - MEMORY.md is concise (73 lines)

### What Needs Improvement ⚠️

1. **CLAUDE.md length (458 lines)** → Target: <300 lines
2. **No explicit agent definitions** → Create .claude/agents/
3. **Dual memory systems** → Consolidate Serena + Claude memories
4. **No compaction rules** → Add to CLAUDE.md
5. **No skills directory** → Create reusable skills
6. **No context management guide** → Document when to use /clear

### Alignment with Best Practices

| Best Practice | SocialBit Current State | Recommendation |
|---------------|------------------------|----------------|
| CLAUDE.md < 300 lines | 458 lines ⚠️ | Refactor to ~280 lines |
| Use pointers, not code | Good ✅ | Maintain this |
| Compaction rules | Missing ⚠️ | Add section |
| MEMORY.md < 200 lines | 73 lines ✅ | Good, maintain |
| Dedicated agents/ dir | Missing ⚠️ | Create 6 agents |
| Skills organization | Missing ⚠️ | Create ~/.claude/skills/ |
| Tool minimization | Not specified ⚠️ | Add to agent defs |
| Context hygiene | Not documented ⚠️ | Create guide |

---

## 10. Resources and References

### Official Anthropic Documentation

1. [Best Practices for Claude Code - Claude Code Docs](https://code.claude.com/docs/en/best-practices)
2. [Manage Claude's memory - Claude Code Docs](https://code.claude.com/docs/en/memory)
3. [Extend Claude with skills - Claude Code Docs](https://code.claude.com/docs/en/skills)
4. [Create custom subagents - Claude Code Docs](https://code.claude.com/docs/en/sub-agents)
5. [How we built our multi-agent research system - Anthropic Engineering](https://www.anthropic.com/engineering/multi-agent-research-system)
6. [Effective harnesses for long-running agents - Anthropic Engineering](https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents)
7. [Skill authoring best practices - Claude API Docs](https://platform.claude.com/docs/en/agents-and-tools/agent-skills/best-practices)

### Community Resources

8. [Writing a good CLAUDE.md | HumanLayer Blog](https://www.humanlayer.dev/blog/writing-a-good-claude-md)
9. [The Complete Guide to CLAUDE.md - Builder.io](https://www.builder.io/blog/claude-md-guide)
10. [Creating the Perfect CLAUDE.md for Claude Code - Dometrain](https://dometrain.com/blog/creating-the-perfect-claudemd-for-claude-code/)
11. [CLAUDE.md: Best Practices from Prompt Learning - Arize](https://arize.com/blog/claude-md-best-practices-learned-from-optimizing-claude-code-with-prompt-learning/)
12. [Claude Skills and CLAUDE.md: practical 2026 guide - Gend](https://www.gend.co/blog/claude-skills-claude-md-guide)
13. [Claude Code Best Practices - rosmur.github.io](https://rosmur.github.io/claudecode-best-practices/)
14. [Cooking with Claude Code: The Complete Guide - Sid Bharath](https://www.siddharthbharath.com/claude-code-the-complete-guide/)

### Multi-Agent Patterns

15. [AI Agent Coordination: 8 Proven Patterns [2026] - Tacnode](https://tacnode.io/post/ai-agent-coordination)
16. [Claude Code Subagents: Common Mistakes & Best Practices - ClaudeKit](https://claudekit.cc/blog/vc-04-subagents-from-basic-to-deep-dive-i-misunderstood)
17. [4 Claude Code Subagent Mistakes That Kill Your Workflow - DEV](https://dev.to/alireza_rezvani/4-claude-code-subagent-mistakes-that-kill-your-workflow-and-the-fixes-3n72)
18. [Best practices for Claude Code subagents - PubNub](https://www.pubnub.com/blog/best-practices-for-claude-code-sub-agents/)

### Configuration Architecture

19. [AGENTS.md vs CLAUDE.md: Complete Guide - Substratia](https://substratia.io/blog/agents-md-vs-claude-md/)
20. [Global Agents Inheritance issue - GitHub](https://github.com/anthropics/claude-code/issues/5750)
21. [Sub-agents Model Config issue - GitHub](https://github.com/anthropics/claude-code/issues/5456)

### Memory Management

22. [Claude Memory: Deep Dive - Skywork AI](https://skywork.ai/blog/claude-memory-a-deep-dive-into-anthropics-persistent-context-solution/)
23. [Claude Code's Memory: Working with Large Codebases - Medium](https://medium.com/@tl_99311/claude-codes-memory-working-with-ai-in-large-codebases-a948f66c2d7e)
24. [Claude Code Best Practices: Memory Management - Code Centre](https://cuong.io/blog/2025/06/15-claude-code-best-practices-memory-management)
25. [Stop Repeating Yourself: Give Claude Code a Memory - Product Talk](https://www.producttalk.org/give-claude-code-a-memory/)

### PHP AI Development

26. [Neuron AI - The Agentic Framework for PHP](https://www.neuron-ai.dev/)
27. [Building Intelligent PHP Applications - Symfony AI - DEV](https://dev.to/mattleads/building-intelligent-php-applications-best-practices-for-the-symfony-ai-agent-component-3b76)
28. [Neuron AI GitHub - PHP Agentic Framework](https://github.com/neuron-core/neuron-ai)

### Additional Resources

29. [Awesome Claude Skills - GitHub](https://github.com/ComposioHQ/awesome-claude-skills)
30. [Awesome Agent Skills - GitHub](https://github.com/VoltAgent/awesome-agent-skills)
31. [Claude Code Swarm Orchestration Skill - GitHub Gist](https://gist.github.com/kieranklaassen/4f2aba89594a4aea4ad64d753984b2ea)

---

## Conclusion

This comprehensive guide provides research-backed best practices for optimizing SocialBit's AI agent architecture. Key takeaways:

1. **CLAUDE.md optimization** - Reduce from 458 to ~280 lines by moving detailed content to docs/
2. **Create dedicated agents/** - Define 6 specialized agents with minimal tool access
3. **Add compaction rules** - Preserve critical context across sessions
4. **Memory consolidation** - Clarify relationship between Serena and Claude memories
5. **Skills organization** - Create reusable skills in ~/.claude/skills/
6. **Context management** - Document when to use /clear, subagents, single agent

Implementing these recommendations will:
- Reduce context degradation (primary failure mode)
- Improve multi-agent coordination (90% performance boost potential)
- Maintain code quality through specialized agents
- Enable efficient long-running workflows (critical for 6-month MVP)

**Next Steps:**
1. Review findings with team lead
2. Implement immediate improvements (CLAUDE.md optimization, agents/ directory)
3. Schedule short-term improvements (memory consolidation, skills)
4. Plan long-term agent specialization rollout

---

**Document Version:** 1.0
**Date:** 2026-02-07
**Author:** best-practices-researcher agent
**Review Status:** Pending team-lead approval
