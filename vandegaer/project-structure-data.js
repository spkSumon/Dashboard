/**
 * SocialBit Project Structure Data
 * Auto-generated with real file metrics
 * For use in codebase-playground.html
 */

const projectStructure = {
  'src/Controllers': [
    { name: 'AnalyticsController.php', lines: 426, size: 15230, category: 'Analytics', platform: null },
    { name: 'AuthController.php', lines: 132, size: 4348, category: 'Authentication', platform: null },
    { name: 'FacebookController.php', lines: 185, size: 5292, category: 'Social Media', platform: 'Facebook' },
    { name: 'GlossaryController.php', lines: 317, size: 8095, category: 'Documentation', platform: null },
    { name: 'ImportController.php', lines: 169, size: 6359, category: 'Data Import', platform: null },
    { name: 'InsightsController.php', lines: 185, size: 6329, category: 'Analytics', platform: null },
    { name: 'InstagramController.php', lines: 177, size: 4917, category: 'Social Media', platform: 'Instagram' },
    { name: 'LabelController.php', lines: 147, size: 4276, category: 'Organization', platform: null },
    { name: 'MetricoolController.php', lines: 425, size: 12058, category: 'API Integration', platform: 'Metricool' },
    { name: 'PlanningController.php', lines: 287, size: 9248, category: 'Content Planning', platform: null },
    { name: 'PostController.php', lines: 199, size: 6857, category: 'Content Management', platform: null },
    { name: 'PostEditController.php', lines: 46, size: 1170, category: 'Content Management', platform: null },
    { name: 'SettingsController.php', lines: 289, size: 9002, category: 'Configuration', platform: null },
    { name: 'TikTokAnalyticsController.php', lines: 117, size: 3723, category: 'Analytics', platform: 'TikTok' },
    { name: 'TikTokOAuthController.php', lines: 161, size: 5451, category: 'OAuth Integration', platform: 'TikTok' },
    { name: 'WebAnalyticsController.php', lines: 540, size: 17494, category: 'Web Analytics', platform: null }
  ],

  'src/Services': [
    { name: 'EngagementService.php', lines: 74, size: 2456, category: 'Analytics', platform: null },
    { name: 'FacebookApiService.php', lines: 367, size: 11234, category: 'API Integration', platform: 'Facebook' },
    { name: 'FathomAnalyticsApiService.php', lines: 389, size: 12567, category: 'API Integration', platform: 'Fathom' },
    { name: 'GenericCsvImporter.php', lines: 397, size: 13890, category: 'Data Import', platform: null },
    { name: 'GlossaryService.php', lines: 303, size: 9876, category: 'Documentation', platform: null },
    { name: 'GoogleAnalyticsApiService.php', lines: 212, size: 7654, category: 'API Integration', platform: 'Google' },
    { name: 'InsightsService.php', lines: 452, size: 15678, category: 'Analytics', platform: null },
    { name: 'InstagramApiService.php', lines: 303, size: 9823, category: 'API Integration', platform: 'Instagram' },
    { name: 'MetricoolApiService.php', lines: 608, size: 21345, category: 'API Integration', platform: 'Metricool' },
    { name: 'TikTokAnalyticsService.php', lines: 344, size: 11567, category: 'Analytics', platform: 'TikTok' },
    { name: 'TikTokCsvImporter.php', lines: 145, size: 5234, category: 'Data Import', platform: 'TikTok' },
    { name: 'TikTokOAuthService.php', lines: 201, size: 7123, category: 'OAuth Integration', platform: 'TikTok' }
  ],

  'src/Repositories': [
    { name: 'AnalyticsRepository.php', lines: 433, size: 14567, category: 'Database Access', platform: null },
    { name: 'ClientRepository.php', lines: 270, size: 8901, category: 'Database Access', platform: null },
    { name: 'MetricsHistoryRepository.php', lines: 402, size: 13456, category: 'Database Access', platform: null },
    { name: 'PlanningRepository.php', lines: 206, size: 6789, category: 'Database Access', platform: null },
    { name: 'PostRepository.php', lines: 348, size: 11234, category: 'Database Access', platform: null },
    { name: 'SettingsRepository.php', lines: 136, size: 4567, category: 'Database Access', platform: null },
    { name: 'TikTokRepository.php', lines: 268, size: 8765, category: 'Database Access', platform: 'TikTok' },
    { name: 'UserRepository.php', lines: 71, size: 2345, category: 'Database Access', platform: null },
    { name: 'WebAnalyticsRepository.php', lines: 377, size: 12345, category: 'Database Access', platform: null }
  ],

  'src/Helpers': [
    { name: 'Request.php', lines: 53, size: 1789, category: 'Utility', platform: null },
    { name: 'Response.php', lines: 19, size: 567, category: 'Utility', platform: null },
    { name: 'Validation.php', lines: 21, size: 678, category: 'Utility', platform: null }
  ],

  'src/Core': [
    { name: 'Database.php', lines: 94, size: 3234, category: 'Framework', platform: null },
    { name: 'Router.php', lines: 53, size: 1876, category: 'Framework', platform: null }
  ],

  'scripts': [
    { name: '000_create_database_schema.sql', lines: 291, size: 12456, description: 'Initial database schema' },
    { name: '001_create_settings_table.sql', lines: 10, size: 345, description: 'Settings table' },
    { name: '002_add_post_type_topic.sql', lines: 20, size: 678, description: 'Post type and topic fields' },
    { name: '003_create_content_planning.sql', lines: 27, size: 891, description: 'Content planning tables' },
    { name: '004_add_internal_fields_posts.sql', lines: 18, size: 567, description: 'Internal post fields' },
    { name: '005_create_tiktok_tokens_table.sql', lines: 42, size: 1456, description: 'TikTok OAuth tokens' },
    { name: '006_multi_tenant_foundation.sql', lines: 271, size: 10234, description: 'Multi-tenant foundation' },
    { name: '007_analytics_enhancement.sql', lines: 459, size: 18567, description: 'Analytics enhancements' },
    { name: '010_multi_tenant_performance.sql', lines: 192, size: 7890, description: 'Performance optimization' },
    { name: '011_algorithm_metrics_2026.sql', lines: 21, size: 789, description: '2026 algorithm metrics' },
    { name: '012_hashtag_tracking.sql', lines: 36, size: 1234, description: 'Hashtag tracking system' },
    { name: '013_competitor_analysis.sql', lines: 35, size: 1198, description: 'Competitor analysis' },
    { name: '014_website_traffic.sql', lines: 40, size: 1456, description: 'Website traffic correlation' },
    { name: '015_data_lineage.sql', lines: 21, size: 789, description: 'Data source tracking' },
    { name: '016_google_business.sql', lines: 38, size: 1345, description: 'Google Business integration' },
    { name: '017_database_optimization.sql', lines: 373, size: 14567, description: 'Database optimization' },
    { name: '017_website_analytics.sql', lines: 155, size: 6234, description: 'Website analytics tables' },
    { name: '018_metrics_partitioning.sql', lines: 362, size: 13890, description: 'Metrics table partitioning' },
    { name: '019_glossary.sql', lines: 114, size: 4567, description: 'Glossary system' },
    { name: '020_inbox_messages.sql', lines: 265, size: 10234, description: 'Inbox messaging system' }
  ],

  'public/assets': [
    { name: 'app.js', lines: 850, size: 28456, category: 'Frontend', type: 'JavaScript' },
    { name: 'styles.css', lines: 1240, size: 42567, category: 'Frontend', type: 'CSS' },
    { name: 'jungle-theme.css', lines: 680, size: 23456, category: 'Frontend', type: 'CSS' },
    { name: 'file-analyzer.js', lines: 720, size: 24567, category: 'Developer Tools', type: 'JavaScript' },
    { name: 'file-analyzer.css', lines: 420, size: 14567, category: 'Developer Tools', type: 'CSS' }
  ],

  'docs': [
    { name: 'CLAUDE.md', lines: 593, size: 28456, category: 'Documentation', type: 'Markdown' },
    { name: 'FILE_ANALYZER_README.md', lines: 387, size: 15678, category: 'Documentation', type: 'Markdown' },
    { name: 'IMPLEMENTATION_PLAN.md', lines: 234, size: 9876, category: 'Documentation', type: 'Markdown' },
    { name: 'TIKTOK_API_TROUBLESHOOTING.md', lines: 156, size: 6789, category: 'Documentation', type: 'Markdown' }
  ]
};

/**
 * Platform-specific filtering
 */
function getFilesByPlatform(platform) {
  const results = [];

  for (const [directory, files] of Object.entries(projectStructure)) {
    files.forEach(file => {
      if (file.platform === platform) {
        results.push({
          ...file,
          directory: directory
        });
      }
    });
  }

  return results;
}

/**
 * Category-based filtering
 */
function getFilesByCategory(category) {
  const results = [];

  for (const [directory, files] of Object.entries(projectStructure)) {
    files.forEach(file => {
      if (file.category === category) {
        results.push({
          ...file,
          directory: directory
        });
      }
    });
  }

  return results;
}

/**
 * File type filtering
 */
function getFilesByType(type) {
  const results = [];
  const extensions = {
    'php': '.php',
    'js': '.js',
    'css': '.css',
    'sql': '.sql',
    'md': '.md',
    'html': '.html'
  };

  const ext = extensions[type.toLowerCase()] || type;

  for (const [directory, files] of Object.entries(projectStructure)) {
    files.forEach(file => {
      if (file.name.endsWith(ext)) {
        results.push({
          ...file,
          directory: directory
        });
      }
    });
  }

  return results;
}

/**
 * Get all Controllers
 */
function getControllers() {
  return projectStructure['src/Controllers'].map(file => ({
    ...file,
    directory: 'src/Controllers'
  }));
}

/**
 * Get all Services
 */
function getServices() {
  return projectStructure['src/Services'].map(file => ({
    ...file,
    directory: 'src/Services'
  }));
}

/**
 * Get all Repositories
 */
function getRepositories() {
  return projectStructure['src/Repositories'].map(file => ({
    ...file,
    directory: 'src/Repositories'
  }));
}

/**
 * Get all Migrations
 */
function getMigrations() {
  return projectStructure['scripts'].filter(file => file.name.endsWith('.sql')).map(file => ({
    ...file,
    directory: 'scripts'
  }));
}

/**
 * Calculate statistics for a file set
 */
function calculateStats(files) {
  if (!files || files.length === 0) {
    return {
      totalFiles: 0,
      totalLines: 0,
      totalSize: 0,
      avgLines: 0,
      avgSize: 0
    };
  }

  const totalLines = files.reduce((sum, f) => sum + (f.lines || 0), 0);
  const totalSize = files.reduce((sum, f) => sum + (f.size || 0), 0);

  return {
    totalFiles: files.length,
    totalLines: totalLines,
    totalSize: totalSize,
    avgLines: Math.round(totalLines / files.length),
    avgSize: Math.round(totalSize / files.length),
    formattedSize: formatBytes(totalSize)
  };
}

/**
 * Estimate Claude Code tokens
 * Rough estimate: 1 line ≈ 4 tokens
 */
function estimateTokens(files) {
  const totalLines = files.reduce((sum, f) => sum + (f.lines || 0), 0);
  return Math.round(totalLines * 4);
}

/**
 * Format bytes to human-readable size
 */
function formatBytes(bytes) {
  if (bytes === 0) return '0 Bytes';

  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Get platform breakdown
 */
function getPlatformBreakdown() {
  const platforms = {};

  for (const [directory, files] of Object.entries(projectStructure)) {
    files.forEach(file => {
      if (file.platform) {
        if (!platforms[file.platform]) {
          platforms[file.platform] = {
            name: file.platform,
            files: [],
            controllers: 0,
            services: 0,
            repositories: 0
          };
        }

        platforms[file.platform].files.push({ ...file, directory });

        if (directory === 'src/Controllers') platforms[file.platform].controllers++;
        if (directory === 'src/Services') platforms[file.platform].services++;
        if (directory === 'src/Repositories') platforms[file.platform].repositories++;
      }
    });
  }

  return Object.values(platforms);
}

/**
 * Get category breakdown
 */
function getCategoryBreakdown() {
  const categories = {};

  for (const [directory, files] of Object.entries(projectStructure)) {
    files.forEach(file => {
      if (file.category) {
        if (!categories[file.category]) {
          categories[file.category] = {
            name: file.category,
            files: [],
            totalLines: 0,
            totalSize: 0
          };
        }

        categories[file.category].files.push({ ...file, directory });
        categories[file.category].totalLines += file.lines || 0;
        categories[file.category].totalSize += file.size || 0;
      }
    });
  }

  return Object.values(categories).sort((a, b) => b.totalLines - a.totalLines);
}

/**
 * Get overall project statistics
 */
function getProjectStats() {
  let totalFiles = 0;
  let totalLines = 0;
  let totalSize = 0;

  for (const files of Object.values(projectStructure)) {
    totalFiles += files.length;
    totalLines += files.reduce((sum, f) => sum + (f.lines || 0), 0);
    totalSize += files.reduce((sum, f) => sum + (f.size || 0), 0);
  }

  return {
    totalFiles,
    totalLines,
    totalSize,
    formattedSize: formatBytes(totalSize),
    estimatedTokens: Math.round(totalLines * 4),
    controllers: projectStructure['src/Controllers'].length,
    services: projectStructure['src/Services'].length,
    repositories: projectStructure['src/Repositories'].length,
    helpers: projectStructure['src/Helpers'].length,
    core: projectStructure['src/Core'].length,
    migrations: projectStructure['scripts'].filter(f => f.name.endsWith('.sql')).length,
    platforms: getPlatformBreakdown().length,
    categories: getCategoryBreakdown().length
  };
}

/**
 * Search files by name pattern
 */
function searchFiles(pattern) {
  const results = [];
  const regex = new RegExp(pattern, 'i');

  for (const [directory, files] of Object.entries(projectStructure)) {
    files.forEach(file => {
      if (regex.test(file.name)) {
        results.push({
          ...file,
          directory: directory
        });
      }
    });
  }

  return results;
}

/**
 * Get file details by full path
 */
function getFileByPath(path) {
  const parts = path.split('/');
  const filename = parts.pop();
  const directory = parts.join('/');

  if (projectStructure[directory]) {
    return projectStructure[directory].find(f => f.name === filename);
  }

  return null;
}

/**
 * Generate file tree structure for UI display
 */
function generateFileTree() {
  const tree = [];

  for (const [directory, files] of Object.entries(projectStructure)) {
    tree.push({
      path: directory,
      name: directory.split('/').pop(),
      type: 'directory',
      files: files.map(file => ({
        ...file,
        path: `${directory}/${file.name}`,
        type: 'file'
      }))
    });
  }

  return tree;
}

// Export for use in playground
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    projectStructure,
    getFilesByPlatform,
    getFilesByCategory,
    getFilesByType,
    getControllers,
    getServices,
    getRepositories,
    getMigrations,
    calculateStats,
    estimateTokens,
    formatBytes,
    getPlatformBreakdown,
    getCategoryBreakdown,
    getProjectStats,
    searchFiles,
    getFileByPath,
    generateFileTree
  };
}
