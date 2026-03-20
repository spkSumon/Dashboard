# 📁 SocialBit File Analyzer

**Developer tool for analyzing project structure and generating contextual Claude Code prompts**

## Overview

The File Analyzer is a client-side JavaScript utility that scans the SocialBit codebase, analyzes its structure, and generates contextual prompts for Claude Code. It helps developers quickly understand the project architecture and create targeted prompts for AI-assisted development.

## Features

### 🔍 Project Structure Analysis
- **Controllers** - HTTP request handlers (16 files)
- **Services** - Business logic layer (12 files)
- **Repositories** - Database access layer (9 files)
- **Migrations** - Database schema evolution (20+ migrations)
- **Helpers** - Utility functions
- **Core** - Framework components

### 📊 Codebase Insights
- Platform integration breakdown (TikTok, Instagram, Facebook, Metricool, etc.)
- Architecture pattern detection (3-layer MVC)
- File categorization by function
- Migration history tracking
- Recent features and additions

### 🤖 Claude Code Prompt Generation
Generate contextual prompts for different development scenarios:

1. **General Overview** - Full project structure and key files
2. **Platform Integration** - Platform-specific API analysis
3. **Database Schema** - Repository and migration details
4. **API Services** - API integration overview
5. **Architecture** - 3-layer MVC pattern breakdown

### 💾 Export Capabilities
- JSON export of full analysis
- Copy prompts to clipboard
- Visual HTML display with jungle theme styling

## File Structure

```
public/assets/
├── file-analyzer.js      # Main analyzer class
└── file-analyzer.css     # Jungle theme compatible styling

file-analyzer-demo.html   # Standalone demo page
```

## Usage

### Standalone Demo

Open `file-analyzer-demo.html` in a browser:

```
http://localhost/socialbit-live/file-analyzer-demo.html
```

1. Click **"Run Full Analysis"** to scan the project
2. View analysis results with statistics and insights
3. Select a prompt type from the buttons
4. Click **"Copy to Clipboard"** to use the prompt
5. Optional: Export full analysis as JSON

### Integration into Other Pages

Add to any HTML page:

```html
<!-- Include CSS -->
<link rel="stylesheet" href="public/assets/file-analyzer.css">

<!-- Include JS -->
<script src="public/assets/file-analyzer.js"></script>

<!-- Create container -->
<div id="analysis-container"></div>

<!-- Run analysis -->
<script>
  const analyzer = new FileAnalyzer();
  analyzer.scanProject().then(() => {
    document.getElementById('analysis-container').innerHTML = analyzer.generateHTML();
  });
</script>
```

### Programmatic Usage

```javascript
// Create analyzer instance
const analyzer = new FileAnalyzer();

// Run full scan
await analyzer.scanProject();

// Access analysis data
console.log(analyzer.analysis.summary);
console.log(analyzer.analysis.controllers);
console.log(analyzer.analysis.insights);

// Generate prompts
const generalPrompt = analyzer.generateClaudePrompt('general');
const platformPrompt = analyzer.generateClaudePrompt('platform');
const databasePrompt = analyzer.generateClaudePrompt('database');

// Export JSON
const json = analyzer.exportJSON();

// Generate HTML display
const html = analyzer.generateHTML();
```

## API Reference

### FileAnalyzer Class

#### Constructor
```javascript
const analyzer = new FileAnalyzer();
```

#### Methods

**`async scanProject()`**
- Scans project structure and populates analysis data
- Returns: `Promise<Object>` - Analysis results

**`generateClaudePrompt(focusArea)`**
- Generates contextual prompt for Claude Code
- Parameters:
  - `focusArea` (string): 'general', 'platform', 'database', 'api', or 'architecture'
- Returns: `string` - Markdown formatted prompt

**`generateHTML()`**
- Generates HTML display of analysis results
- Returns: `string` - HTML markup

**`exportJSON()`**
- Exports full analysis as JSON
- Returns: `string` - JSON formatted analysis

#### Properties

**`analysis.summary`**
```javascript
{
  totalFiles: 57,
  controllers: 16,
  services: 12,
  repositories: 9,
  migrations: 20,
  helpers: 3,
  core: 2,
  platforms: { count: 6, list: [...] },
  architecture: '3-Layer MVC',
  techStack: 'Vanilla PHP 8.4+, MySQL, PDO'
}
```

**`analysis.insights`**
```javascript
[
  {
    type: 'success',
    icon: '🏗️',
    title: 'Clean Architecture',
    message: 'Following 3-layer MVC pattern...'
  },
  // ...more insights
]
```

**`analysis.controllers`**
```javascript
[
  {
    name: 'AnalyticsController.php',
    path: 'src/Controllers/AnalyticsController.php',
    type: 'Controller',
    platform: null,
    category: 'Analytics'
  },
  // ...more controllers
]
```

## Prompt Types

### 1. General Overview
Comprehensive project summary with key files and structure.

**Use when:**
- Starting new work on the project
- Onboarding new developers
- Getting high-level context

**Includes:**
- File count breakdown
- Platform integrations
- Architecture overview
- Key files to review

### 2. Platform Integration
Platform-specific analysis (TikTok, Instagram, Facebook, etc.)

**Use when:**
- Working on specific platform integrations
- Debugging API connections
- Adding new platform support

**Includes:**
- Controller and Service mapping per platform
- API service list
- Platform-specific file paths

### 3. Database Schema
Repository and migration details

**Use when:**
- Creating new migrations
- Modifying database schema
- Understanding data models

**Includes:**
- All repository files
- Recent migration history
- Latest database features

### 4. API Services
API integration overview

**Use when:**
- Adding new API integrations
- Debugging API issues
- OAuth implementation

**Includes:**
- All API service files
- OAuth controllers
- Data import services

### 5. Architecture
Full 3-layer MVC breakdown

**Use when:**
- Understanding code organization
- Following architectural patterns
- Refactoring components

**Includes:**
- Complete layer breakdown
- File categorization
- Core framework components

## Styling

The analyzer uses jungle theme compatible CSS with variables:

```css
--jungle-glow: #39ff14     /* Bright green */
--jungle-accent: #2ecc71   /* Medium green */
--jungle-dark: #0a0e0a     /* Dark background */
--jungle-warning: #f39c12  /* Orange warning */
--jungle-danger: #e74c3c   /* Red error */
```

### Responsive Design
- Mobile-optimized grid layouts
- Touch-friendly controls
- Collapsible sections on small screens

## Integration with Playground

The File Analyzer is designed to integrate with the SocialBit Playground project:

1. **Task #3**: Prompt generation functionality ✅
2. **Task #4**: File analysis capabilities ✅

Can be combined with:
- API connector tools
- Database migration runners
- Code snippet generators
- Claude Code integration

## Future Enhancements

### Planned Features
- [ ] Real-time file system scanning (backend API)
- [ ] Line count and complexity metrics
- [ ] Code quality analysis
- [ ] Dependency graph visualization
- [ ] Git commit history integration
- [ ] Direct Claude Code API integration
- [ ] Custom prompt templates
- [ ] Save/load analysis snapshots

### Backend Integration
Currently client-side with static data. Future backend endpoint:

```php
// GET /api/project/analyze
{
  "controllers": [...],
  "services": [...],
  "repositories": [...],
  "metrics": {
    "totalLines": 15420,
    "avgComplexity": 4.2,
    "testCoverage": 0
  }
}
```

## Development Notes

### Current Limitations
- Static file list (no dynamic scanning)
- Estimated file sizes (not actual)
- No line count metrics
- No code complexity analysis

### Why Static Data?
- Client-side only (no backend required)
- Fast performance
- Works offline
- Easy to integrate

### When to Update
Update `file-analyzer.js` when:
- New controllers/services/repositories added
- New platforms integrated
- New migrations created
- Architecture changes

## Examples

### Example 1: Quick Project Overview
```javascript
const analyzer = new FileAnalyzer();
await analyzer.scanProject();
console.log(analyzer.analysis.summary);
// Output: { totalFiles: 57, platforms: {...}, ... }
```

### Example 2: Generate Database Prompt
```javascript
const analyzer = new FileAnalyzer();
await analyzer.scanProject();
const prompt = analyzer.generateClaudePrompt('database');

// Copy to clipboard or use with Claude Code
navigator.clipboard.writeText(prompt);
```

### Example 3: Export Full Analysis
```javascript
const analyzer = new FileAnalyzer();
await analyzer.scanProject();
const json = analyzer.exportJSON();

// Save to file or send to backend
fetch('/api/save-analysis', {
  method: 'POST',
  body: json,
  headers: { 'Content-Type': 'application/json' }
});
```

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Modern mobile browsers

Requires:
- ES6+ JavaScript
- Async/await support
- Clipboard API (for copy function)

## License

Part of the SocialBit project. Internal developer tool.

---

**Last Updated:** 2026-02-20
**Version:** 1.0.0
**Author:** Claude Code Agent (file-analysis-specialist)
