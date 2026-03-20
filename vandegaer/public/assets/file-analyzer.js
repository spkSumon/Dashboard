/**
 * SocialBit File Analyzer
 * Analyzes project structure and generates contextual insights
 * For use in Claude Code playground/development tools
 */

class FileAnalyzer {
  constructor() {
    this.projectRoot = '/xampp3/htdocs/socialbit-live';
    this.analysis = {
      controllers: [],
      services: [],
      repositories: [],
      migrations: [],
      helpers: [],
      core: [],
      summary: {},
      insights: []
    };
  }

  /**
   * Scan project directories and analyze structure
   */
  async scanProject() {
    console.log('🔍 Starting project scan...');

    try {
      // In a real implementation, this would make API calls to a backend endpoint
      // For now, we'll use the data we know exists
      await this.analyzeControllers();
      await this.analyzeServices();
      await this.analyzeRepositories();
      await this.analyzeMigrations();
      await this.analyzeHelpers();
      await this.analyzeCore();
      await this.generateSummary();
      await this.generateInsights();

      console.log('✅ Project scan complete');
      return this.analysis;
    } catch (error) {
      console.error('❌ Project scan failed:', error);
      throw error;
    }
  }

  /**
   * Analyze Controllers
   */
  async analyzeControllers() {
    const controllers = [
      'AnalyticsController.php',
      'AuthController.php',
      'FacebookController.php',
      'GlossaryController.php',
      'ImportController.php',
      'InsightsController.php',
      'InstagramController.php',
      'LabelController.php',
      'MetricoolController.php',
      'PlanningController.php',
      'PostController.php',
      'PostEditController.php',
      'SettingsController.php',
      'TikTokAnalyticsController.php',
      'TikTokOAuthController.php',
      'WebAnalyticsController.php'
    ];

    this.analysis.controllers = controllers.map(file => ({
      name: file,
      path: `src/Controllers/${file}`,
      type: 'Controller',
      platform: this.detectPlatform(file),
      category: this.categorizeController(file),
      estimatedSize: 'Medium' // Would be actual file size in real implementation
    }));
  }

  /**
   * Analyze Services
   */
  async analyzeServices() {
    const services = [
      'EngagementService.php',
      'FacebookApiService.php',
      'FathomAnalyticsApiService.php',
      'GenericCsvImporter.php',
      'GlossaryService.php',
      'GoogleAnalyticsApiService.php',
      'InsightsService.php',
      'InstagramApiService.php',
      'MetricoolApiService.php',
      'TikTokAnalyticsService.php',
      'TikTokCsvImporter.php',
      'TikTokOAuthService.php'
    ];

    this.analysis.services = services.map(file => ({
      name: file,
      path: `src/Services/${file}`,
      type: 'Service',
      platform: this.detectPlatform(file),
      category: this.categorizeService(file),
      estimatedSize: 'Medium'
    }));
  }

  /**
   * Analyze Repositories
   */
  async analyzeRepositories() {
    const repositories = [
      'AnalyticsRepository.php',
      'ClientRepository.php',
      'MetricsHistoryRepository.php',
      'PlanningRepository.php',
      'PostRepository.php',
      'SettingsRepository.php',
      'TikTokRepository.php',
      'UserRepository.php',
      'WebAnalyticsRepository.php'
    ];

    this.analysis.repositories = repositories.map(file => ({
      name: file,
      path: `src/Repositories/${file}`,
      type: 'Repository',
      platform: this.detectPlatform(file),
      category: 'Database Access',
      estimatedSize: 'Medium'
    }));
  }

  /**
   * Analyze Migration Scripts
   */
  async analyzeMigrations() {
    const migrations = [
      { file: '000_create_database_schema.sql', description: 'Initial schema' },
      { file: '001_create_settings_table.sql', description: 'Settings table' },
      { file: '002_add_post_type_topic.sql', description: 'Post type/topic' },
      { file: '003_create_content_planning.sql', description: 'Content planning' },
      { file: '004_add_internal_fields_posts.sql', description: 'Internal fields' },
      { file: '005_create_tiktok_tokens_table.sql', description: 'TikTok tokens' },
      { file: '006_multi_tenant_foundation.sql', description: 'Multi-tenant foundation' },
      { file: '007_analytics_enhancement.sql', description: 'Analytics enhancement' },
      { file: '010_multi_tenant_performance.sql', description: 'Performance optimization' },
      { file: '011_algorithm_metrics_2026.sql', description: '2026 algorithm metrics' },
      { file: '012_hashtag_tracking.sql', description: 'Hashtag tracking' },
      { file: '013_competitor_analysis.sql', description: 'Competitor analysis' },
      { file: '014_website_traffic.sql', description: 'Website traffic' },
      { file: '015_data_lineage.sql', description: 'Data lineage' },
      { file: '016_google_business.sql', description: 'Google Business' },
      { file: '017_database_optimization.sql', description: 'Database optimization' },
      { file: '017_website_analytics.sql', description: 'Website analytics' },
      { file: '018_metrics_partitioning.sql', description: 'Metrics partitioning' },
      { file: '019_glossary.sql', description: 'Glossary system' },
      { file: '020_inbox_messages.sql', description: 'Inbox messages' }
    ];

    this.analysis.migrations = migrations.map(item => ({
      name: item.file,
      path: `scripts/${item.file}`,
      type: 'Migration',
      description: item.description,
      number: parseInt(item.file.split('_')[0])
    }));
  }

  /**
   * Analyze Helper files
   */
  async analyzeHelpers() {
    const helpers = [
      'Request.php',
      'Response.php',
      'Validation.php'
    ];

    this.analysis.helpers = helpers.map(file => ({
      name: file,
      path: `src/Helpers/${file}`,
      type: 'Helper',
      category: 'Utility',
      estimatedSize: 'Small'
    }));
  }

  /**
   * Analyze Core files
   */
  async analyzeCore() {
    const core = [
      'Database.php',
      'Router.php'
    ];

    this.analysis.core = core.map(file => ({
      name: file,
      path: `src/Core/${file}`,
      type: 'Core',
      category: 'Framework',
      estimatedSize: 'Medium'
    }));
  }

  /**
   * Generate summary statistics
   */
  async generateSummary() {
    this.analysis.summary = {
      totalFiles: this.analysis.controllers.length +
                  this.analysis.services.length +
                  this.analysis.repositories.length +
                  this.analysis.migrations.length +
                  this.analysis.helpers.length +
                  this.analysis.core.length,
      controllers: this.analysis.controllers.length,
      services: this.analysis.services.length,
      repositories: this.analysis.repositories.length,
      migrations: this.analysis.migrations.length,
      helpers: this.analysis.helpers.length,
      core: this.analysis.core.length,
      platforms: this.getPlatformBreakdown(),
      architecture: '3-Layer MVC (Controller → Service → Repository)',
      techStack: 'Vanilla PHP 8.4+, MySQL, PDO',
      patterns: ['Repository Pattern', 'Service Layer', 'Dependency Injection']
    };
  }

  /**
   * Generate actionable insights
   */
  async generateInsights() {
    const insights = [];

    // Platform coverage
    const platforms = this.getPlatformBreakdown();
    insights.push({
      type: 'info',
      icon: '📊',
      title: 'Multi-Platform Support',
      message: `Integrated with ${platforms.count} platforms: ${platforms.list.join(', ')}`
    });

    // Architecture pattern
    insights.push({
      type: 'success',
      icon: '🏗️',
      title: 'Clean Architecture',
      message: `Following 3-layer MVC pattern with ${this.analysis.controllers.length} Controllers, ${this.analysis.services.length} Services, ${this.analysis.repositories.length} Repositories`
    });

    // Migration status
    const latestMigration = Math.max(...this.analysis.migrations.map(m => m.number));
    insights.push({
      type: 'info',
      icon: '🗄️',
      title: 'Database Evolution',
      message: `${this.analysis.migrations.length} migrations applied (latest: ${latestMigration})`
    });

    // API integrations
    const apiServices = this.analysis.services.filter(s => s.name.includes('ApiService'));
    insights.push({
      type: 'success',
      icon: '🔌',
      title: 'API Integrations',
      message: `${apiServices.length} API service integrations: ${apiServices.map(s => s.platform || s.name.replace('ApiService.php', '')).join(', ')}`
    });

    // Recent features
    const recentMigrations = this.analysis.migrations
      .filter(m => m.number >= 19)
      .map(m => m.description);

    if (recentMigrations.length > 0) {
      insights.push({
        type: 'info',
        icon: '✨',
        title: 'Recent Features',
        message: `Latest additions: ${recentMigrations.join(', ')}`
      });
    }

    this.analysis.insights = insights;
  }

  /**
   * Detect platform from filename
   */
  detectPlatform(filename) {
    const lower = filename.toLowerCase();
    if (lower.includes('tiktok')) return 'TikTok';
    if (lower.includes('instagram')) return 'Instagram';
    if (lower.includes('facebook')) return 'Facebook';
    if (lower.includes('metricool')) return 'Metricool';
    if (lower.includes('fathom')) return 'Fathom Analytics';
    if (lower.includes('google')) return 'Google';
    return null;
  }

  /**
   * Categorize controller by function
   */
  categorizeController(filename) {
    if (filename.includes('Auth')) return 'Authentication';
    if (filename.includes('Analytics') || filename.includes('Insights')) return 'Analytics';
    if (filename.includes('Post')) return 'Content Management';
    if (filename.includes('Import')) return 'Data Import';
    if (filename.includes('Settings')) return 'Configuration';
    if (filename.includes('Planning')) return 'Content Planning';
    if (filename.includes('Label')) return 'Organization';
    if (filename.includes('OAuth')) return 'OAuth Integration';
    if (filename.includes('Glossary')) return 'Documentation';
    if (filename.includes('WebAnalytics')) return 'Web Analytics';
    return 'General';
  }

  /**
   * Categorize service by function
   */
  categorizeService(filename) {
    if (filename.includes('ApiService')) return 'API Integration';
    if (filename.includes('Importer')) return 'Data Import';
    if (filename.includes('Engagement')) return 'Analytics';
    if (filename.includes('OAuth')) return 'Authentication';
    if (filename.includes('Insights')) return 'Analytics';
    if (filename.includes('Glossary')) return 'Documentation';
    return 'Business Logic';
  }

  /**
   * Get platform breakdown
   */
  getPlatformBreakdown() {
    const platforms = new Set();

    [...this.analysis.controllers, ...this.analysis.services].forEach(item => {
      if (item.platform) platforms.add(item.platform);
    });

    return {
      count: platforms.size,
      list: Array.from(platforms).sort()
    };
  }

  /**
   * Generate Claude Code prompt based on analysis
   */
  generateClaudePrompt(focusArea = 'general') {
    const prompts = {
      general: this.generateGeneralPrompt(),
      platform: this.generatePlatformPrompt(),
      database: this.generateDatabasePrompt(),
      api: this.generateApiPrompt(),
      architecture: this.generateArchitecturePrompt()
    };

    return prompts[focusArea] || prompts.general;
  }

  generateGeneralPrompt() {
    return `# SocialBit Codebase Overview

## Project Structure
- **Controllers**: ${this.analysis.controllers.length} files (HTTP request handling)
- **Services**: ${this.analysis.services.length} files (business logic)
- **Repositories**: ${this.analysis.repositories.length} files (database access)
- **Migrations**: ${this.analysis.migrations.length} database migrations

## Platform Integrations
${this.getPlatformBreakdown().list.map(p => `- ${p}`).join('\n')}

## Architecture
${this.analysis.summary.architecture}
Tech Stack: ${this.analysis.summary.techStack}

## Key Files to Review
${this.getKeyFiles()}

What would you like me to help with?`;
  }

  generatePlatformPrompt() {
    const platforms = this.getPlatformBreakdown().list;
    return `# Platform Integration Analysis

## Connected Platforms (${platforms.length})
${platforms.map(p => {
  const controller = this.analysis.controllers.find(c => c.platform === p);
  const service = this.analysis.services.find(s => s.platform === p);
  return `\n### ${p}
- Controller: ${controller ? controller.path : 'N/A'}
- Service: ${service ? service.path : 'N/A'}`;
}).join('\n')}

## API Services
${this.analysis.services.filter(s => s.category === 'API Integration').map(s => `- ${s.path}`).join('\n')}

What platform integration would you like to work on?`;
  }

  generateDatabasePrompt() {
    return `# Database Architecture

## Repositories (${this.analysis.repositories.length})
${this.analysis.repositories.map(r => `- ${r.name}: ${r.path}`).join('\n')}

## Recent Migrations
${this.analysis.migrations.slice(-5).map(m => `- Migration ${m.number}: ${m.description}`).join('\n')}

## Latest Features
${this.analysis.migrations.filter(m => m.number >= 19).map(m => `- ${m.description} (${m.name})`).join('\n')}

What database operation would you like to perform?`;
  }

  generateApiPrompt() {
    const apiServices = this.analysis.services.filter(s => s.category === 'API Integration');
    return `# API Integration Overview

## API Services (${apiServices.length})
${apiServices.map(s => `- **${s.platform || 'Generic'}**: ${s.path}`).join('\n')}

## OAuth Controllers
${this.analysis.controllers.filter(c => c.category === 'OAuth Integration').map(c => `- ${c.path}`).join('\n')}

## Data Import Services
${this.analysis.services.filter(s => s.category === 'Data Import').map(s => `- ${s.path}`).join('\n')}

Which API integration needs work?`;
  }

  generateArchitecturePrompt() {
    return `# Architecture Overview

## 3-Layer MVC Pattern
\`\`\`
Controller → Service → Repository
\`\`\`

### Controllers (${this.analysis.controllers.length})
Handle HTTP requests, validation, response formatting
${this.analysis.controllers.map(c => `- ${c.name} [${c.category}]`).join('\n')}

### Services (${this.analysis.services.length})
Business logic, orchestration
${this.analysis.services.map(s => `- ${s.name} [${s.category}]`).join('\n')}

### Repositories (${this.analysis.repositories.length})
Database access layer (PDO)
${this.analysis.repositories.map(r => `- ${r.name}`).join('\n')}

### Core Framework
${this.analysis.core.map(c => `- ${c.name}`).join('\n')}

### Helpers
${this.analysis.helpers.map(h => `- ${h.name}`).join('\n')}

What architectural task would you like to perform?`;
  }

  getKeyFiles() {
    return `### Core Controllers
- ${this.analysis.controllers.find(c => c.name === 'AnalyticsController.php')?.path}
- ${this.analysis.controllers.find(c => c.name === 'PostController.php')?.path}

### Key Services
- ${this.analysis.services.find(s => s.name === 'MetricoolApiService.php')?.path}
- ${this.analysis.services.find(s => s.name === 'InsightsService.php')?.path}

### Important Repositories
- ${this.analysis.repositories.find(r => r.name === 'PostRepository.php')?.path}
- ${this.analysis.repositories.find(r => r.name === 'AnalyticsRepository.php')?.path}`;
  }

  /**
   * Export analysis as JSON
   */
  exportJSON() {
    return JSON.stringify(this.analysis, null, 2);
  }

  /**
   * Generate HTML display for analysis results
   */
  generateHTML() {
    return `
      <div class="file-analysis-results">
        <div class="analysis-header">
          <h3>📁 Project Structure Analysis</h3>
          <div class="analysis-stats">
            <div class="stat-item">
              <div class="stat-value">${this.analysis.summary.totalFiles}</div>
              <div class="stat-label">Total Files</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">${this.analysis.summary.controllers}</div>
              <div class="stat-label">Controllers</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">${this.analysis.summary.services}</div>
              <div class="stat-label">Services</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">${this.analysis.summary.repositories}</div>
              <div class="stat-label">Repositories</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">${this.analysis.summary.migrations}</div>
              <div class="stat-label">Migrations</div>
            </div>
          </div>
        </div>

        <div class="insights-section">
          <h4>💡 Insights</h4>
          ${this.analysis.insights.map(insight => `
            <div class="insight-card insight-${insight.type}">
              <div class="insight-icon">${insight.icon}</div>
              <div class="insight-content">
                <div class="insight-title">${insight.title}</div>
                <div class="insight-message">${insight.message}</div>
              </div>
            </div>
          `).join('')}
        </div>

        <div class="file-breakdown">
          <h4>📂 File Breakdown by Category</h4>

          <div class="category-section">
            <h5>Controllers (${this.analysis.controllers.length})</h5>
            <div class="file-grid">
              ${this.analysis.controllers.map(f => `
                <div class="file-item" data-platform="${f.platform || 'core'}">
                  <span class="file-icon">📄</span>
                  <span class="file-name">${f.name}</span>
                  <span class="file-category">${f.category}</span>
                </div>
              `).join('')}
            </div>
          </div>

          <div class="category-section">
            <h5>Services (${this.analysis.services.length})</h5>
            <div class="file-grid">
              ${this.analysis.services.map(f => `
                <div class="file-item" data-platform="${f.platform || 'core'}">
                  <span class="file-icon">⚙️</span>
                  <span class="file-name">${f.name}</span>
                  <span class="file-category">${f.category}</span>
                </div>
              `).join('')}
            </div>
          </div>

          <div class="category-section">
            <h5>Repositories (${this.analysis.repositories.length})</h5>
            <div class="file-grid">
              ${this.analysis.repositories.map(f => `
                <div class="file-item">
                  <span class="file-icon">🗄️</span>
                  <span class="file-name">${f.name}</span>
                </div>
              `).join('')}
            </div>
          </div>

          <div class="category-section">
            <h5>Database Migrations (${this.analysis.migrations.length})</h5>
            <div class="migration-list">
              ${this.analysis.migrations.slice(-10).reverse().map(m => `
                <div class="migration-item">
                  <span class="migration-number">${String(m.number).padStart(3, '0')}</span>
                  <span class="migration-desc">${m.description}</span>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      </div>
    `;
  }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = FileAnalyzer;
}
