<?php
declare(strict_types=1);

/**
 * Dit is de enige "entry point" voor je backend.
 * Alles komt binnen via deze file, en we dispatchen dan naar de juiste controller.
 */

// Error handling - altijd JSON response
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => [
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
    exit;
});

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use App\Core\Database;
use App\Core\Router;
use App\Helpers\Response;
use App\Middleware\Auth;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;
use App\Repositories\SettingsRepository;
use App\Services\TikTokCsvImporter;
use App\Services\GenericCsvImporter;
use App\Controllers\AuthController;
use App\Controllers\PostController;
use App\Controllers\ImportController;
use App\Controllers\SettingsController;
use App\Repositories\AnalyticsRepository;
use App\Services\EngagementService;
use App\Controllers\AnalyticsController;
use App\Controllers\LabelController;
use App\Repositories\PlanningRepository;
use App\Controllers\PlanningController;
use App\Controllers\PostEditController;
use App\Repositories\TikTokRepository;
use App\Services\TikTokOAuthService;
use App\Services\TikTokAnalyticsService;
use App\Controllers\TikTokOAuthController;
use App\Controllers\TikTokAnalyticsController;
use App\Controllers\InsightsController;
use App\Services\InsightsService;
use App\Controllers\WebAnalyticsController;
use App\Controllers\GlossaryController;
use App\Services\GlossaryService;
use App\Repositories\WebAnalyticsRepository;
use App\Controllers\InstagramController;
use App\Controllers\FacebookController;
use App\Controllers\MetricoolController;
use App\Services\InstagramApiService;
use App\Services\FacebookApiService;
use App\Services\MetricoolApiService;
use App\Repositories\MetricsHistoryRepository;

// Config (auto-detecteert lokaal vs productie)
$config = require __DIR__ . '/../config/app.php';

// CORS (simpel)
$origin = $config['cors_origin'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// Dependencies
$db = new Database($config['db']);
$secret = $config['secret'] ?? 'change-me';

$postsRepo = new PostRepository($db);
$usersRepo = new UserRepository($db);
$settingsRepo = new SettingsRepository($db);
$tiktokImporter = new TikTokCsvImporter($db);
$genericImporter = new GenericCsvImporter($db);
$analyticsRepo = new AnalyticsRepository($db);
$engagementService = new EngagementService();
$analyticsController = new AnalyticsController($analyticsRepo, $engagementService);
$insightsService = new InsightsService();
$insightsController = new InsightsController($insightsService, $analyticsRepo, $postsRepo);
$glossaryService = new GlossaryService($db);
$glossaryController = new GlossaryController($glossaryService);
$labelController = new LabelController($postsRepo);
$postEditController = new PostEditController($postsRepo);
$planningRepo = new PlanningRepository($db);
$planningController = new PlanningController($planningRepo);

$authController = new AuthController($usersRepo, $secret);
$postController = new PostController($postsRepo);
$importController = new ImportController($tiktokImporter, $genericImporter);
$settingsController = new SettingsController($settingsRepo);

// TikTok OAuth & Analytics
$tiktokRepo = new TikTokRepository($db);
$tiktokOAuthService = new TikTokOAuthService($config['tiktok'], $tiktokRepo);
$tiktokOAuthController = new TikTokOAuthController($tiktokOAuthService, $tiktokRepo, $secret);
$tiktokAnalyticsService = new TikTokAnalyticsService($tiktokOAuthService, $postsRepo, $db);
$tiktokAnalyticsController = new TikTokAnalyticsController($tiktokAnalyticsService);

// Web Analytics (Fathom, Google Analytics)
$webAnalyticsRepo = new WebAnalyticsRepository($db);
$webAnalyticsController = new WebAnalyticsController($settingsRepo, $webAnalyticsRepo);

// Instagram & Facebook API
$instagramService = new InstagramApiService($settingsRepo, $postsRepo);
$instagramController = new InstagramController($instagramService);
$facebookService = new FacebookApiService($settingsRepo, $postsRepo);
$facebookController = new FacebookController($facebookService);

// Metricool API
$metricsHistoryRepo = new MetricsHistoryRepository($db);
$metricoolController = new MetricoolController($settingsRepo, $postsRepo, $metricsHistoryRepo);

// Router setup
$router = new Router();

// Frontend
$router->add('GET', '/', function() {
    readfile(__DIR__ . '/index.html');
    exit;
});

// API routes
$router->add('GET', '/api/health', fn() => Response::json(['status' => 'ok', 'time' => time()]));
$router->add('POST', '/api/auth/login', fn() => $authController->login());

$router->add('GET', '/api/posts', fn() => $postController->list());
$router->add('GET', '/api/posts/{id}', fn($params) => $postController->show((int)$params['id']));
$router->add('GET', '/api/posts/meta/earliest-date', fn() => $postController->earliestDate());
// Analytics (public - read only)
$router->add('GET', '/api/hashtags/top', fn() => $analyticsController->topHashtags());
$router->add('GET', '/api/hashtags/trend', fn() => $analyticsController->hashtagTrend());
$router->add('GET', '/api/hashtags/recommendations', fn() => $analyticsController->hashtagRecommendations());
$router->add('GET', '/api/engagement/overview', fn() => $analyticsController->engagementOverview());
$router->add('GET', '/api/analytics/post-types', fn() => $analyticsController->postTypes());
$router->add('GET', '/api/analytics/topics', fn() => $analyticsController->topics());
$router->add('GET', '/api/tiktok/demographics/summary', fn() => $analyticsController->tiktokDemographicsSummary());

// Insights (public - actionable recommendations)
$router->add('GET', '/api/insights', fn() => $insightsController->getInsights());

// Web Analytics - Fathom (public endpoints for now, auth can be added later)
$router->add('POST', '/api/analytics/fathom/credentials', fn() => $webAnalyticsController->saveFathomCredentials());
$router->add('GET', '/api/analytics/fathom/test', fn() => $webAnalyticsController->testFathomConnection());
$router->add('GET', '/api/analytics/fathom/stats', fn() => $webAnalyticsController->getFathomStats());
$router->add('GET', '/api/analytics/fathom/referrers', fn() => $webAnalyticsController->getFathomReferrers());
$router->add('GET', '/api/analytics/fathom/pages', fn() => $webAnalyticsController->getFathomPages());
$router->add('GET', '/api/analytics/fathom/timeseries', fn() => $webAnalyticsController->getFathomTimeSeries());

// Web Analytics - Data Collection & Correlation
$router->add('POST', '/api/analytics/fathom/collect', fn() => $webAnalyticsController->collectFathomData());
$router->add('GET', '/api/analytics/website/stats', fn() => $webAnalyticsController->getStoredAnalytics());
$router->add('GET', '/api/analytics/website/top-traffic-posts', fn() => $webAnalyticsController->getTopTrafficPosts());

// Glossary (public - help users understand terminology)
$router->add('GET', '/api/glossary/terms', fn() => $glossaryController->index());
$router->add('GET', '/api/glossary/search', fn() => $glossaryController->search());
$router->add('GET', '/api/glossary/categories', fn() => $glossaryController->categories());
$router->add('GET', '/api/glossary/category/:category', function() use ($router, $glossaryController) {
    $params = $router->getParams();
    $glossaryController->byCategory($params['category']);
});
$router->add('GET', '/api/glossary/terms/:term', function() use ($router, $glossaryController) {
    $params = $router->getParams();
    $glossaryController->show($params['term']);
});

// TikTok OAuth routes (no auth required for OAuth flow)
$router->add('GET', '/api/tiktok/authorize', fn() => $tiktokOAuthController->authorize());
$router->add('GET', '/api/tiktok/callback', fn() => $tiktokOAuthController->callback());

// TikTok status & sync (auth required)
$router->add('GET', '/api/tiktok/status', function() use ($tiktokOAuthController, $secret) {
    Auth::requireAuth($secret);
    $tiktokOAuthController->status();
});
$router->add('POST', '/api/tiktok/disconnect', function() use ($tiktokOAuthController, $secret) {
    Auth::requireAuth($secret);
    $tiktokOAuthController->disconnect();
});
$router->add('POST', '/api/tiktok/sync', function() use ($tiktokAnalyticsController, $secret) {
    Auth::requireAuth($secret);
    $tiktokAnalyticsController->sync();
});

// Instagram API routes
$router->add('POST', '/api/instagram/credentials', fn() => $instagramController->saveCredentials(new \App\Helpers\Request()));
$router->add('GET', '/api/instagram/test', fn() => $instagramController->testConnection());
$router->add('GET', '/api/instagram/posts', fn() => $instagramController->fetchPosts(new \App\Helpers\Request()));
$router->add('POST', '/api/instagram/sync', function() use ($instagramController, $secret) {
    Auth::requireAuth($secret);
    $instagramController->fetchAndSave(new \App\Helpers\Request());
});
$router->add('POST', '/api/instagram/refresh-token', function() use ($instagramController, $secret) {
    Auth::requireAuth($secret);
    $instagramController->refreshToken();
});

// Facebook API routes
$router->add('POST', '/api/facebook/credentials', fn() => $facebookController->saveCredentials(new \App\Helpers\Request()));
$router->add('GET', '/api/facebook/test', fn() => $facebookController->testConnection());
$router->add('GET', '/api/facebook/posts', fn() => $facebookController->fetchPosts(new \App\Helpers\Request()));
$router->add('POST', '/api/facebook/sync', function() use ($facebookController, $secret) {
    Auth::requireAuth($secret);
    $facebookController->fetchAndSave(new \App\Helpers\Request());
});
$router->add('GET', '/api/facebook/insights', fn() => $facebookController->getPageInsights(new \App\Helpers\Request()));

// Metricool API routes
$router->add('POST', '/api/metricool/test', fn() => $metricoolController->testConnection());
$router->add('POST', '/api/metricool/save', fn() => $metricoolController->saveCredentials());
$router->add('GET', '/api/metricool/profiles', fn() => $metricoolController->getProfiles());
$router->add('GET', '/api/metricool/status', fn() => $metricoolController->getStatus());
$router->add('POST', '/api/metricool/sync', function() use ($metricoolController, $secret) {
    Auth::requireAuth($secret);
    $metricoolController->syncPosts();
});

// Auth required

// Planning (auth required)
$router->add('GET', '/api/planning', function() use ($planningController, $secret) {
    Auth::requireAuth($secret);
    $planningController->list();
});
$router->add('GET', '/api/planning/{id}', function($params) use ($planningController, $secret) {
    Auth::requireAuth($secret);
    $planningController->show((int)$params['id']);
});
$router->add('POST', '/api/planning', function() use ($planningController, $secret) {
    Auth::requireAuth($secret);
    $planningController->create();
});
$router->add('POST', '/api/planning/{id}', function($params) use ($planningController, $secret) {
    Auth::requireAuth($secret);
    $planningController->update((int)$params['id']);
});
$router->add('POST', '/api/planning/{id}/delete', function($params) use ($planningController, $secret) {
    Auth::requireAuth($secret);
    $planningController->delete((int)$params['id']);
});

$router->add('POST', '/api/posts/{id}/edit', function($params) use ($postEditController, $secret) {
    Auth::requireAuth($secret);
    $postEditController->update((int)$params['id']);
});

$router->add('POST', '/api/posts/{id}/label', function($params) use ($labelController, $secret) {
    Auth::requireAuth($secret);
    $labelController->setLabel((int)$params['id']);
});

$router->add('POST', '/api/import/tiktok', function() use ($importController) {
    $importController->tiktok();
});

$router->add('POST', '/api/import/csv', function() use ($importController) {
    $importController->generic();
});

$router->add('GET', '/api/settings', function() use ($settingsController, $secret) {
    Auth::requireAuth($secret);
    $settingsController->list();
});

$router->add('POST', '/api/settings', function() use ($settingsController, $secret) {
    Auth::requireAuth($secret);
    $settingsController->upsert();
});

// Brand color endpoints for design-lead (Task #6)
$router->add('GET', '/api/settings/brand-colors', function() use ($settingsController) {
    $settingsController->getBrandColors();
});

$router->add('POST', '/api/settings/brand-colors', function() use ($settingsController, $secret) {
    Auth::requireAuth($secret);
    $settingsController->updateBrandColors();
});

try {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

    // Dynamisch base path detecteren op basis van SCRIPT_NAME
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = dirname($scriptName);
    if ($base === '\\' || $base === '/') {
        $base = '';
    }

    // Strip base path if present
    if ($base && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base));
    }

    // Ensure path starts with /
    if (empty($path) || $path[0] !== '/') {
        $path = '/' . $path;
    }

    $router->dispatch($_SERVER['REQUEST_METHOD'], $path);
} catch (Throwable $e) {
    Response::error('Interne fout: ' . $e->getMessage(), 500);
}
