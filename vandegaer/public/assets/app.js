// Check voor TikTok OAuth redirect met token
(function handleTikTokRedirect() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('tiktok') === 'connected' && params.get('token')) {
    localStorage.setItem('token', params.get('token'));
    // Clean URL
    window.history.replaceState({}, '', window.location.pathname + window.location.hash);
  }
})();

const state = {
  token: localStorage.getItem('token') || null,
  earliestPostDate: null // Cache for earliest post date
};

// Base URL voor API calls - detecteer automatisch
const BASE_URL = (() => {
  const path = window.location.pathname;
  // Strip index.html of trailing slash
  const base = path.replace(/\/?(index\.html)?$/, '');
  return base || '';
})();

function apiUrl(endpoint) {
  return BASE_URL + '/' + endpoint;
}

function authHeaders() { return state.token ? { 'Authorization': 'Bearer ' + state.token } : {}; }
function setText(id, msg) { const el = document.getElementById(id); if (el) el.textContent = msg; }

function setAuthBadge() {
  const badge = document.getElementById('authBadge');
  const pill = badge?.closest('.pill');
  if (!badge) return;

  if (state.token) {
    badge.textContent = 'Ingelogd';
    pill?.classList.add('is-on');
  } else {
    badge.textContent = 'Niet ingelogd';
    pill?.classList.remove('is-on');
  }
}

// --------------------
// SPA Router (hash based)
// --------------------
const routesMeta = {
  '/overview': { title: 'Overzicht', subtitle: 'Hoe doet je social media het?' },
  '/posts': { title: 'Je posts', subtitle: 'Alle geplaatste berichten' },
  '/glossary': { title: 'Woordenlijst', subtitle: 'Uitleg van termen en statistieken' },
  '/settings': { title: 'Instellingen', subtitle: 'Koppelingen en personalisatie' },
};

function currentRoute() {
  const h = window.location.hash || '#/overview';
  const r = h.replace('#', '');
  return routesMeta[r] ? r : '/overview';
}

function renderRoute() {
  const route = currentRoute();

  // pages
  document.querySelectorAll('.page').forEach(p => p.classList.remove('is-active'));
  document.querySelectorAll(`.page[data-page="${route}"]`).forEach(p => p.classList.add('is-active'));

  // nav active
  document.querySelectorAll('.nav__item').forEach(a => {
    a.classList.toggle('is-active', a.getAttribute('data-route') === route);
  });

  // title/subtitle
  const meta = routesMeta[route] || routesMeta['/overview'];
  setText('pageTitle', meta.title);
  setText('pageSubtitle', meta.subtitle);
}

window.addEventListener('hashchange', renderRoute);

// --------------------
// Branding (localStorage)
// --------------------
function applyBranding() {
  const saved = JSON.parse(localStorage.getItem('brand') || 'null');
  if (!saved) return;

  if (saved.name) {
    setText('brandName', saved.name);
    setText('brandNamePreview', saved.name);
  } else {
    setText('brandName', 'La Giuditta');
    setText('brandNamePreview', 'La Giuditta');
  }
  if (saved.accent) {
    document.documentElement.style.setProperty('--brand-accent', saved.accent);
    updateColorHex(saved.accent);
  }

  const nameInput = document.getElementById('brandNameInput');
  const accentInput = document.getElementById('brandAccentInput');
  if (nameInput && saved.name) nameInput.value = saved.name;
  if (accentInput && saved.accent) accentInput.value = saved.accent;
}

function updateColorHex(color) {
  const hexDisplay = document.getElementById('colorHex');
  if (hexDisplay) hexDisplay.textContent = color;
}

function saveBranding() {
  const name = document.getElementById('brandNameInput')?.value?.trim() || '';
  const accent = document.getElementById('brandAccentInput')?.value || '';
  const payload = { name, accent };

  localStorage.setItem('brand', JSON.stringify(payload));
  applyBranding();
  setText('brandStatus', '✓ Opgeslagen');
  setTimeout(() => setText('brandStatus', ''), 2000);
}

function resetBranding() {
  localStorage.removeItem('brand');
  const nameInput = document.getElementById('brandNameInput');
  const accentInput = document.getElementById('brandAccentInput');

  if (nameInput) nameInput.value = '';
  if (accentInput) accentInput.value = '#6d28d9';

  setText('brandName', 'La Giuditta');
  setText('brandNamePreview', 'La Giuditta');
  document.documentElement.style.setProperty('--brand-accent', '#6d28d9');
  updateColorHex('#6d28d9');

  setText('brandStatus', '✓ Gereset');
  setTimeout(() => setText('brandStatus', ''), 2000);
}

// Real-time preview
document.getElementById('brandNameInput')?.addEventListener('input', (e) => {
  const name = e.target.value.trim() || 'La Giuditta';
  setText('brandNamePreview', name);
});

document.getElementById('brandAccentInput')?.addEventListener('input', (e) => {
  updateColorHex(e.target.value);
  document.documentElement.style.setProperty('--brand-accent', e.target.value);
});

// Color presets
document.querySelectorAll('.color-preset').forEach(btn => {
  btn.addEventListener('click', () => {
    const color = btn.getAttribute('data-color');
    const accentInput = document.getElementById('brandAccentInput');
    if (accentInput) {
      accentInput.value = color;
      updateColorHex(color);
      document.documentElement.style.setProperty('--brand-accent', color);
    }
  });
});

document.getElementById('saveBrandBtn')?.addEventListener('click', saveBranding);
document.getElementById('resetBrandBtn')?.addEventListener('click', resetBranding);
document.getElementById('brandBtn')?.addEventListener('click', () => { window.location.hash = '#/settings'; });

// --------------------
// Login
// --------------------
document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value;

  const res = await fetch(apiUrl('api/auth/login'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  });
  const data = await res.json();

  if (res.ok) {
    state.token = data.token;
    localStorage.setItem('token', state.token);
    setText('loginStatus', 'Ingelogd als ' + data.user.username);
    setAuthBadge();
  } else {
    setText('loginStatus', data?.error?.message || 'Login mislukt');
  }
});

// --------------------
// Settings
// --------------------
document.getElementById('settingsForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const key = document.getElementById('settingKey').value.trim();
  const value = document.getElementById('settingValue').value;

  const res = await fetch(apiUrl('api/settings'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...authHeaders() },
    body: JSON.stringify({ key, value })
  });
  const data = await res.json();
  if (res.ok) setText('settingsStatus', 'Opgeslagen.');
  else setText('settingsStatus', data?.error?.message || 'Opslaan mislukt');
});

document.getElementById('loadSettingsBtn')?.addEventListener('click', async () => {
  const res = await fetch(apiUrl('api/settings'), { headers: { ...authHeaders() } });
  const data = await res.json();
  const dump = document.getElementById('settingsDump');
  if (res.ok) {
    dump.textContent = JSON.stringify(data, null, 2);
    setText('settingsStatus', 'Geladen.');
  } else {
    dump.textContent = '';
    setText('settingsStatus', data?.error?.message || 'Laden mislukt');
  }
});

// --------------------
// Import CSV
// --------------------
document.getElementById('importForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const file = document.getElementById('csvFile').files[0];
  if (!file) return;

  setText('importStatus', 'Bezig met importeren...');

  const fd = new FormData();
  fd.append('file', file);

  // Use generic CSV import endpoint (auto-detects format)
  const res = await fetch(apiUrl('api/import/csv'), {
    method: 'POST',
    headers: { ...authHeaders() },
    body: fd
  });
  const data = await res.json();

  if (res.ok) {
    const formatLabel = data.format === 'content_tracker' ? 'Content Tracker' : 'TikTok Export';
    setText('importStatus', `✓ Geïmporteerd: ${data.processed} posts, ${data.skipped} overgeslagen (${formatLabel} formaat)`);

    // Refresh posts table after import
    setTimeout(() => loadPosts(), 1000);
  } else {
    setText('importStatus', '✗ ' + (data?.error?.message || 'Import mislukt'));
  }
});

// --------------------
// Chart.js instances
// --------------------
const chartInstances = {};

function destroyChart(name) {
  if (chartInstances[name]) {
    chartInstances[name].destroy();
    delete chartInstances[name];
  }
}

// --------------------
// Data + charts
// --------------------
async function loadPosts() {
  const platform = document.getElementById('platformFilter')?.value || '';
  const from = document.getElementById('fromDate')?.value || '';
  const to = document.getElementById('toDate')?.value || '';

  try {
    const url = new URL(apiUrl('api/posts'), window.location.origin);
    if (platform) url.searchParams.set('platform', platform);
    if (from) url.searchParams.set('from', from);
    if (to) url.searchParams.set('to', to);
    url.searchParams.set('limit', '200');

    const res = await fetch(url.toString());

    if (!res.ok) {
      console.error('Failed to load posts:', res.status, res.statusText);
      renderTable([]);
      return;
    }

    const data = await res.json();
    const rows = Array.isArray(data) ? data : [];

    renderTable(rows);
    renderKpis(rows);
    renderAllCharts(rows);

    // Load optional features (don't block if they fail)
    loadInsights().catch(e => console.warn('Insights not available:', e));
    loadHashtagRecommendations().catch(e => console.warn('Hashtag recommendations not available:', e));
    loadWebsiteTraffic().catch(e => console.warn('Website traffic not available:', e));

  } catch (error) {
    console.error('Error loading posts:', error);
    renderTable([]);
  }
}

// Load and render insights with actionable recommendations
async function loadInsights() {
  const platform = document.getElementById('platformFilter')?.value || '';
  const from = document.getElementById('fromDate')?.value || '';
  const to = document.getElementById('toDate')?.value || '';

  const url = new URL(apiUrl('api/insights'), window.location.origin);
  if (platform) url.searchParams.set('platform', platform);
  if (from) url.searchParams.set('from', from);
  if (to) url.searchParams.set('to', to);

  const container = document.getElementById('insightsContainer');
  if (!container) return;

  try {
    container.innerHTML = '<div class="loading">Inzichten aan het laden...</div>';

    const res = await fetch(url.toString());
    const data = await res.json();

    if (!data.insights || data.insights.length === 0) {
      container.innerHTML = '<div class="muted">Geen inzichten beschikbaar. Post meer content om aanbevelingen te krijgen.</div>';
      return;
    }

    renderInsights(data.insights);
  } catch (err) {
    console.error('Failed to load insights:', err);
    container.innerHTML = '<div class="muted">Fout bij laden van inzichten.</div>';
  }
}

// Render insights cards with status indicators
function renderInsights(insights) {
  const container = document.getElementById('insightsContainer');
  if (!container) return;

  const statusColors = {
    'excellent': '#10b981',
    'good': '#059669',
    'average': '#f59e0b',
    'below_average': '#f97316',
    'warning': '#ef4444',
    'tip': '#3b82f6',
    'success': '#10b981'
  };

  const html = insights.map(insight => {
    // Highlight numbers in message (e.g., "4×", "35%", "43.669")
    const messageWithHighlights = highlightNumbers(insight.message);

    // Generate action buttons if provided
    let actionsHtml = '';
    if (insight.actions && insight.actions.length > 0) {
      actionsHtml = `
        <div class="insight-card__actions">
          ${insight.actions.map(action => `
            <button class="insight-card__action-btn ${action.style === 'ghost' ? 'insight-card__action-btn--ghost' : ''}"
                    onclick="${action.onclick || ''}"
                    type="button">
              ${action.icon || ''} ${action.label}
            </button>
          `).join('')}
        </div>
      `;
    }

    return `
      <div class="insight-card" style="border-left: 4px solid ${statusColors[insight.status] || '#6b7280'};">
        <div class="insight-card__header">
          <span class="insight-card__icon">${insight.icon}</span>
          <h3 class="insight-card__title">${insight.title}</h3>
        </div>
        <p class="insight-card__message">${messageWithHighlights}</p>
        <p class="insight-card__recommendation">
          <strong>Tip:</strong> ${insight.recommendation}
        </p>
        ${actionsHtml}
      </div>
    `;
  }).join('');

  container.innerHTML = html;
}

/**
 * Highlight numbers in insight messages for better visual hierarchy
 * Converts "4× more engagement" → "4× more engagement" (with styled number)
 */
function highlightNumbers(text) {
  if (!text) return '';

  // Match patterns: "4×", "35%", "43.669", "19:00"
  return text.replace(/(\d+[.,]?\d*[×%]?|\d{1,2}:\d{2})/g, '<strong class="highlight-number">$1</strong>');
}

// Load and render hashtag recommendations
async function loadHashtagRecommendations() {
  const platform = document.getElementById('platformFilter')?.value || '';

  const url = new URL(apiUrl('api/hashtags/recommendations'), window.location.origin);
  if (platform) url.searchParams.set('platform', platform);
  url.searchParams.set('top', '5');
  url.searchParams.set('avoid', '3');

  const container = document.getElementById('hashtagRecommendationsContainer');
  if (!container) return;

  try {
    container.innerHTML = '<div class="loading">Hashtag aanbevelingen laden...</div>';

    const res = await fetch(url.toString());
    const data = await res.json();

    if (!data.recommended || data.recommended.length === 0) {
      container.innerHTML = '<div class="muted">Geen hashtag aanbevelingen beschikbaar. Post meer content met hashtags!</div>';
      return;
    }

    renderHashtagRecommendations(data);
  } catch (err) {
    console.error('Failed to load hashtag recommendations:', err);
    container.innerHTML = '<div class="muted">Fout bij laden van hashtag aanbevelingen.</div>';
  }
}

// Render hashtag recommendations with copy functionality
function renderHashtagRecommendations(data) {
  const container = document.getElementById('hashtagRecommendationsContainer');
  if (!container) return;

  const recommended = data.recommended || [];
  const avoid = data.avoid || [];

  // Build recommended hashtags HTML
  const recommendedHtml = recommended.length > 0 ? `
    <div class="hashtag-section">
      <h3 class="hashtag-section__title">Gebruik deze hashtags:</h3>
      <div class="hashtag-list">
        ${recommended.map(h => `
          <div class="hashtag-item hashtag-item--good">
            <div class="hashtag-item__tag">#${h.hashtag}</div>
            <div class="hashtag-item__stats">
              <span class="muted mini">${formatNumber(h.total_views)} views</span>
              <span class="muted mini">•</span>
              <span class="muted mini">${formatNumber(Math.round(h.avg_views_per_post))} gem./post</span>
            </div>
          </div>
        `).join('')}
      </div>
      <button class="btn btn--primary" id="copyHashtagsBtn" type="button">
        📋 Kopieer hashtags
      </button>
    </div>
  ` : '';

  // Build avoid hashtags HTML
  const avoidHtml = avoid.length > 0 ? `
    <div class="hashtag-section" style="margin-top: 24px;">
      <h3 class="hashtag-section__title">❌ Vermijd deze hashtags:</h3>
      <div class="hashtag-list">
        ${avoid.map(h => `
          <div class="hashtag-item hashtag-item--bad">
            <div class="hashtag-item__tag">#${h.hashtag}</div>
            <div class="hashtag-item__stats">
              <span class="muted mini">${formatNumber(h.total_views)} views (laag)</span>
            </div>
          </div>
        `).join('')}
      </div>
      <p class="muted mini" style="margin-top: 8px;">
        Deze hashtags hebben weinig bereik en kunnen je post minder zichtbaar maken.
      </p>
    </div>
  ` : '';

  container.innerHTML = recommendedHtml + avoidHtml;

  // Add copy button functionality
  const copyBtn = document.getElementById('copyHashtagsBtn');
  if (copyBtn) {
    copyBtn.addEventListener('click', () => {
      const hashtagString = recommended.map(h => '#' + h.hashtag).join(' ');
      navigator.clipboard.writeText(hashtagString).then(() => {
        copyBtn.textContent = 'Gekopieerd!';
        copyBtn.classList.add('btn--success');
        setTimeout(() => {
          copyBtn.textContent = 'Kopieer hashtags';
          copyBtn.classList.remove('btn--success');
        }, 2000);
      }).catch(err => {
        console.error('Failed to copy:', err);
        copyBtn.textContent = 'Kopiëren mislukt';
      });
    });
  }
}

// Fetch earliest post date for "All Time" preset
async function fetchEarliestPostDate() {
  try {
    const response = await fetch(apiUrl('api/posts/meta/earliest-date'), {
      headers: authHeaders()
    });
    if (response.ok) {
      const data = await response.json();
      if (data.success && data.earliest_date) {
        state.earliestPostDate = data.earliest_date;
        console.log('Earliest post date cached:', state.earliestPostDate);
      }
    }
  } catch (err) {
    console.warn('Failed to fetch earliest post date, using fallback 2020-01-01:', err);
  }
}

// Auto-refresh when filters change
document.getElementById('platformFilter')?.addEventListener('change', loadPosts);
document.getElementById('fromDate')?.addEventListener('change', loadPosts);
document.getElementById('toDate')?.addEventListener('change', loadPosts);

// Date preset handler
document.getElementById('datePreset')?.addEventListener('change', (e) => {
  const preset = e.target.value;
  const customRange = document.getElementById('customDateRange');
  const fromInput = document.getElementById('fromDate');
  const toInput = document.getElementById('toDate');

  if (preset === 'custom') {
    customRange.style.display = 'block';
    return; // Don't auto-load, wait for user to set dates
  } else {
    customRange.style.display = 'none';
  }

  const today = new Date();
  const formatDate = (d) => d.toISOString().split('T')[0];

  let fromDate, toDate = formatDate(today);

  switch(preset) {
    case 'last7':
      const last7 = new Date(today);
      last7.setDate(last7.getDate() - 7);
      fromDate = formatDate(last7);
      break;
    case 'last30':
      const last30 = new Date(today);
      last30.setDate(last30.getDate() - 30);
      fromDate = formatDate(last30);
      break;
    case 'last90':
      const last90 = new Date(today);
      last90.setDate(last90.getDate() - 90);
      fromDate = formatDate(last90);
      break;
    case 'thisMonth':
      const thisMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
      fromDate = formatDate(thisMonthStart);
      break;
    case 'lastMonth':
      const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
      fromDate = formatDate(lastMonthStart);
      toDate = formatDate(lastMonthEnd);
      break;
    case 'thisYear':
      const yearStart = new Date(today.getFullYear(), 0, 1);
      fromDate = formatDate(yearStart);
      break;
    case 'allTime':
      // Use cached earliest date or fallback to 2020-01-01
      const allTimeStart = state.earliestPostDate
        ? new Date(state.earliestPostDate)
        : new Date('2020-01-01');
      fromDate = formatDate(allTimeStart);
      break;
  }

  if (fromInput) fromInput.value = fromDate;
  if (toInput) toInput.value = toDate;

  loadPosts();
});

function renderKpis(rows) {
  const sum = (key) => rows.reduce((acc, r) => acc + Number(r[key] ?? 0), 0);
  const avg = (key) => rows.length > 0 ? sum(key) / rows.length : 0;

  const totalViews = sum('views');
  const totalLikes = sum('likes');
  const totalComments = sum('comments');
  const totalShares = sum('shares');
  const totalPosts = rows.length;

  // Engagement rate: (likes / views) * 100
  const engagementRate = totalViews > 0 ? (totalLikes / totalViews * 100) : 0;

  // Primary KPIs
  setText('kpiViews', formatNumber(totalViews));
  setText('kpiLikes', formatNumber(totalLikes));
  setText('kpiEngagement', engagementRate.toFixed(2) + '%');
  setText('kpiPosts', String(totalPosts));

  // Secondary KPIs
  setText('kpiAvgViews', formatNumber(Math.round(avg('views'))));
  setText('kpiAvgLikes', formatNumber(Math.round(avg('likes'))));
  setText('kpiComments', formatNumber(totalComments));
  setText('kpiShares', formatNumber(totalShares));

  // DEMO QUICK FIX: Add plain language context to KPIs
  addKpiContextBadges(engagementRate, avg('views'), totalPosts);
}

/**
 * DEMO QUICK FIX: Add plain language "is this good?" indicators
 * Uses industry benchmarks from MEMORY.md
 */
function addKpiContextBadges(engagementRate, avgViews, postCount) {
  // Industry benchmarks (TikTok: 3.7%, Instagram: 0.48%, mixed: ~1.8%)
  const platform = document.getElementById('platformFilter')?.value || '';
  const benchmarks = {
    'tiktok': { engagement: 3.7, excellent: 5.3, avgViews: 1200 },
    'instagram': { engagement: 0.48, excellent: 3.0, avgViews: 800 },
    'facebook': { engagement: 0.15, excellent: 0.5, avgViews: 300 },
    '': { engagement: 1.8, excellent: 3.5, avgViews: 800 } // Mixed/all platforms
  };

  const benchmark = benchmarks[platform] || benchmarks[''];

  // 1. Engagement Rate Context
  let engagementBadge = '';
  if (engagementRate >= benchmark.excellent) {
    engagementBadge = `<span class="kpi__badge kpi__badge--excellent">Uitstekend! ${(engagementRate / benchmark.engagement).toFixed(1)}× boven gemiddeld</span>`;
  } else if (engagementRate >= benchmark.engagement) {
    engagementBadge = `<span class="kpi__badge kpi__badge--good">Goed - ${(engagementRate / benchmark.engagement).toFixed(1)}× boven gemiddeld</span>`;
  } else if (engagementRate >= benchmark.engagement * 0.7) {
    engagementBadge = `<span class="kpi__badge kpi__badge--average">Gemiddeld voor je sector</span>`;
  } else {
    engagementBadge = `<span class="kpi__badge kpi__badge--below">Tip: Stel meer vragen in je captions</span>`;
  }

  const engagementEl = document.getElementById('kpiEngagementContext');
  if (engagementEl) engagementEl.innerHTML = engagementBadge;

  // 2. Avg Views Context
  let viewsBadge = '';
  if (avgViews >= benchmark.avgViews * 1.5) {
    viewsBadge = `<span class="kpi__badge kpi__badge--excellent">Sterk bereik per post</span>`;
  } else if (avgViews >= benchmark.avgViews * 0.8) {
    viewsBadge = `<span class="kpi__badge kpi__badge--good">Normaal bereik</span>`;
  } else {
    viewsBadge = `<span class="kpi__badge kpi__badge--below">Tip: Post vaker om bereik te vergroten</span>`;
  }

  const viewsEl = document.getElementById('kpiAvgViewsContext');
  if (viewsEl) viewsEl.innerHTML = viewsBadge;

  // 3. Post Frequency Context
  let postsBadge = '';
  if (postCount >= 30) {
    postsBadge = `<span class="kpi__badge kpi__badge--excellent">Goede frequentie - blijf zo doorgaan!</span>`;
  } else if (postCount >= 15) {
    postsBadge = `<span class="kpi__badge kpi__badge--good">Normale frequentie</span>`;
  } else if (postCount >= 5) {
    postsBadge = `<span class="kpi__badge kpi__badge--average">Post vaker voor meer bereik (3-4× per week)</span>`;
  } else {
    postsBadge = `<span class="kpi__badge kpi__badge--below">Te weinig posts - post minimaal 3× per week</span>`;
  }

  const postsEl = document.getElementById('kpiPostsContext');
  if (postsEl) postsEl.innerHTML = postsBadge;
}

function formatNumber(n) {
  try { return new Intl.NumberFormat('nl-BE').format(n); }
  catch { return String(n); }
}

// Store all posts for filtering/searching
let allPostsData = [];

function renderTable(rows) {
  const tbody = document.querySelector('#postsTable tbody');
  if (!tbody) return;

  // Store all posts globally for filtering
  allPostsData = rows;

  // Apply filters and search
  const filtered = filterAndSearchPosts(rows);

  tbody.innerHTML = '';

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--muted);">Geen posts gevonden met deze filters</td></tr>';
    updatePostsCount(0);
    return;
  }

  filtered.forEach(r => {
    const tr = document.createElement('tr');
    const engagementRate = r.views > 0 ? ((r.likes / r.views) * 100).toFixed(2) : '0.00';

    tr.innerHTML = `
      <td>${r.id}</td>
      <td><span class="platform-badge platform-badge--${r.platform}">${r.platform}</span></td>
      <td class="caption-cell">${escapeHtml((r.caption || '').substring(0, 80))}${(r.caption || '').length > 80 ? '...' : ''}</td>
      <td>${r.posted_date || ''}</td>
      <td>${formatNumber(r.views ?? 0)}</td>
      <td>${formatNumber(r.likes ?? 0)}</td>
      <td>${engagementRate}%</td>
      <td>
        <button class="btn btn--ghost btn--small" onclick="viewPostDetail(${r.id})" type="button">Details</button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  updatePostsCount(filtered.length);
}

function updatePostsCount(count) {
  const countEl = document.getElementById('postsCount');
  if (countEl) {
    countEl.textContent = `${count} ${count === 1 ? 'post' : 'posts'}`;
  }
}

function filterAndSearchPosts(rows) {
  const searchTerm = (document.getElementById('postSearchInput')?.value || '').toLowerCase();
  const platformFilter = document.getElementById('postPlatformFilter')?.value || '';
  const sortBy = document.getElementById('postSortBy')?.value || 'date_desc';

  let filtered = [...rows];

  // Apply platform filter
  if (platformFilter) {
    filtered = filtered.filter(p => p.platform === platformFilter);
  }

  // Apply search filter
  if (searchTerm) {
    filtered = filtered.filter(p => {
      const caption = (p.caption || '').toLowerCase();
      const hashtags = (p.hashtags || '').toLowerCase();
      const topic = (p.topic || '').toLowerCase();
      const internalCaption = (p.internal_caption || '').toLowerCase();

      return caption.includes(searchTerm) ||
             hashtags.includes(searchTerm) ||
             topic.includes(searchTerm) ||
             internalCaption.includes(searchTerm);
    });
  }

  // Apply sorting
  filtered.sort((a, b) => {
    switch(sortBy) {
      case 'date_desc':
        return new Date(b.posted_date || 0) - new Date(a.posted_date || 0);
      case 'date_asc':
        return new Date(a.posted_date || 0) - new Date(b.posted_date || 0);
      case 'views_desc':
        return (b.views ?? 0) - (a.views ?? 0);
      case 'likes_desc':
        return (b.likes ?? 0) - (a.likes ?? 0);
      case 'engagement_desc':
        const engA = a.views > 0 ? (a.likes / a.views) : 0;
        const engB = b.views > 0 ? (b.likes / b.views) : 0;
        return engB - engA;
      default:
        return 0;
    }
  });

  return filtered;
}

// Add event listeners for search and filters (will be initialized on DOMContentLoaded)
function initPostsPageFilters() {
  document.getElementById('postSearchInput')?.addEventListener('input', () => {
    renderTable(allPostsData);
  });

  document.getElementById('postPlatformFilter')?.addEventListener('change', () => {
    renderTable(allPostsData);
  });

  document.getElementById('postSortBy')?.addEventListener('change', () => {
    renderTable(allPostsData);
  });
}


// --------------------
// Chart.js default config
// --------------------
const chartDefaults = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: { color: 'rgba(255,255,255,0.85)' }
    },
    tooltip: {
      backgroundColor: 'rgba(0,0,0,0.8)',
      titleColor: 'rgba(255,255,255,0.95)',
      bodyColor: 'rgba(255,255,255,0.85)',
      borderColor: 'rgba(255,255,255,0.2)',
      borderWidth: 1
    }
  },
  scales: {}
};

// Chart colors
const brandColors = {
  primary: '#6d28d9',
  secondary: '#22d3ee',
  accent: '#10b981',
  warning: '#f59e0b',
  danger: '#ef4444',
  tiktok: '#fe2c55',
  instagram: '#E4405F',
  facebook: '#1877F2'
};

// --------------------
// Render all charts
// --------------------
async function renderAllCharts(rows) {
  if (!rows || rows.length === 0) {
    console.warn('No data to render charts');
    return;
  }

  await renderPlatformDistChart(rows);
  await renderTimelineChart(rows);
  await renderTopPostsChart(rows);
  await renderHashtagsChart(rows);
  await renderPostTypesChart(rows);
  await renderEngagementRateChart(rows);
}

// --------------------
// 1. Platform Distribution (Doughnut)
// --------------------
async function renderPlatformDistChart(rows) {
  destroyChart('platformDist');

  const canvas = document.getElementById('chartPlatformDist');
  if (!canvas) return;

  // Count posts per platform
  const platformCounts = {};
  rows.forEach(r => {
    const p = r.platform || 'unknown';
    platformCounts[p] = (platformCounts[p] || 0) + 1;
  });

  const labels = Object.keys(platformCounts);
  const data = Object.values(platformCounts);
  const colors = labels.map(p => brandColors[p] || brandColors.accent);

  chartInstances.platformDist = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
      datasets: [{
        data: data,
        backgroundColor: colors,
        borderColor: 'rgba(0,0,0,0.5)',
        borderWidth: 2
      }]
    },
    options: {
      ...chartDefaults,
      plugins: {
        ...chartDefaults.plugins,
        legend: { position: 'bottom' }
      }
    }
  });
}

// --------------------
// 2. Timeline (Line Chart)
// --------------------
async function renderTimelineChart(rows) {
  destroyChart('timeline');

  const canvas = document.getElementById('chartTimeline');
  if (!canvas) return;

  // Sort by date
  const sorted = rows
    .filter(r => r.posted_date)
    .sort((a, b) => new Date(a.posted_date) - new Date(b.posted_date));

  if (sorted.length === 0) {
    return;
  }

  const labels = sorted.map(r => r.posted_date);
  const viewsData = sorted.map(r => Number(r.views ?? 0));
  const likesData = sorted.map(r => Number(r.likes ?? 0));

  chartInstances.timeline = new Chart(canvas, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Views',
          data: viewsData,
          borderColor: brandColors.secondary,
          backgroundColor: brandColors.secondary + '20',
          tension: 0.3,
          fill: true
        },
        {
          label: 'Likes',
          data: likesData,
          borderColor: brandColors.accent,
          backgroundColor: brandColors.accent + '20',
          tension: 0.3,
          fill: true
        }
      ]
    },
    options: {
      ...chartDefaults,
      scales: {
        y: {
          beginAtZero: true,
          ticks: { color: 'rgba(255,255,255,0.7)' },
          grid: { color: 'rgba(255,255,255,0.1)' }
        },
        x: {
          ticks: {
            color: 'rgba(255,255,255,0.7)',
            maxRotation: 45,
            minRotation: 45
          },
          grid: { color: 'rgba(255,255,255,0.05)' }
        }
      }
    }
  });
}

// --------------------
// 3. Top 10 Posts (Horizontal Bar)
// --------------------
async function renderTopPostsChart(rows) {
  destroyChart('topPosts');

  const canvas = document.getElementById('chartTopPosts');
  if (!canvas) return;

  // Get top 10 by views
  const top10 = rows
    .sort((a, b) => Number(b.views ?? 0) - Number(a.views ?? 0))
    .slice(0, 10);

  const labels = top10.map((r, i) => `Post ${r.id || i+1}`);
  const data = top10.map(r => Number(r.views ?? 0));

  chartInstances.topPosts = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Views',
        data: data,
        backgroundColor: brandColors.primary + 'cc',
        borderColor: brandColors.primary,
        borderWidth: 1
      }]
    },
    options: {
      ...chartDefaults,
      indexAxis: 'y',
      scales: {
        x: {
          beginAtZero: true,
          ticks: { color: 'rgba(255,255,255,0.7)' },
          grid: { color: 'rgba(255,255,255,0.1)' }
        },
        y: {
          ticks: { color: 'rgba(255,255,255,0.7)' },
          grid: { color: 'rgba(255,255,255,0.05)' }
        }
      }
    }
  });
}

// --------------------
// 4. Top Hashtags (Bar Chart)
// --------------------
async function renderHashtagsChart(rows) {
  destroyChart('hashtags');

  const canvas = document.getElementById('chartHashtags');
  if (!canvas) return;

  // Fetch hashtag data from API
  try {
    const url = new URL(apiUrl('api/hashtags/top'), window.location.origin);
    url.searchParams.set('limit', '10');

    const res = await fetch(url.toString());
    const data = await res.json();

    if (!Array.isArray(data) || data.length === 0) {
      return;
    }

    const labels = data.map(h => '#' + (h.hashtag || 'unknown'));
    const chartData = data.map(h => Number(h.avg_views ?? 0));

    chartInstances.hashtags = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Avg Views',
          data: chartData,
          backgroundColor: brandColors.accent + 'cc',
          borderColor: brandColors.accent,
          borderWidth: 1
        }]
      },
      options: {
        ...chartDefaults,
        scales: {
          y: {
            beginAtZero: true,
            ticks: { color: 'rgba(255,255,255,0.7)' },
            grid: { color: 'rgba(255,255,255,0.1)' }
          },
          x: {
            ticks: { color: 'rgba(255,255,255,0.7)' },
            grid: { color: 'rgba(255,255,255,0.05)' }
          }
        }
      }
    });
  } catch (err) {
    console.error('Failed to load hashtag data:', err);
  }
}

// --------------------
// 5. Post Types Distribution (Pie Chart)
// --------------------
async function renderPostTypesChart(rows) {
  destroyChart('postTypes');

  const canvas = document.getElementById('chartPostTypes');
  if (!canvas) return;

  // Count posts per type
  const typeCounts = {};
  rows.forEach(r => {
    const type = r.post_type || 'unknown';
    typeCounts[type] = (typeCounts[type] || 0) + 1;
  });

  const labels = Object.keys(typeCounts);
  const data = Object.values(typeCounts);

  // Generate colors
  const colors = [
    brandColors.primary,
    brandColors.secondary,
    brandColors.accent,
    brandColors.warning,
    brandColors.danger
  ];

  chartInstances.postTypes = new Chart(canvas, {
    type: 'pie',
    data: {
      labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
      datasets: [{
        data: data,
        backgroundColor: colors,
        borderColor: 'rgba(0,0,0,0.5)',
        borderWidth: 2
      }]
    },
    options: {
      ...chartDefaults,
      plugins: {
        ...chartDefaults.plugins,
        legend: { position: 'bottom' }
      }
    }
  });
}

// --------------------
// 6. Engagement Rate Over Time (Line Chart)
// --------------------
async function renderEngagementRateChart(rows) {
  destroyChart('engagementRate');

  const canvas = document.getElementById('chartEngagementRate');
  if (!canvas) return;

  // Sort by date and calculate engagement rate per post
  const sorted = rows
    .filter(r => r.posted_date && r.views > 0)
    .sort((a, b) => new Date(a.posted_date) - new Date(b.posted_date));

  if (sorted.length === 0) {
    return;
  }

  const labels = sorted.map(r => r.posted_date);
  const engagementData = sorted.map(r => {
    const views = Number(r.views ?? 0);
    const likes = Number(r.likes ?? 0);
    return views > 0 ? (likes / views * 100) : 0;
  });

  chartInstances.engagementRate = new Chart(canvas, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Engagement Rate (%)',
        data: engagementData,
        borderColor: brandColors.warning,
        backgroundColor: brandColors.warning + '20',
        tension: 0.3,
        fill: true
      }]
    },
    options: {
      ...chartDefaults,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            color: 'rgba(255,255,255,0.7)',
            callback: function(value) {
              return value.toFixed(1) + '%';
            }
          },
          grid: { color: 'rgba(255,255,255,0.1)' }
        },
        x: {
          ticks: {
            color: 'rgba(255,255,255,0.7)',
            maxRotation: 45,
            minRotation: 45
          },
          grid: { color: 'rgba(255,255,255,0.05)' }
        }
      }
    }
  });
}

function escapeHtml(s) {
  return String(s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

// --------------------
// Analytics (API-first) - ARCHIVED (removed from UI)
// --------------------
/* ARCHIVED - Analytics page removed from navigation
async function loadAnalytics() {
  const platform = document.getElementById('platformFilter')?.value || '';
  const from = document.getElementById('fromDate')?.value || '';
  const to = document.getElementById('toDate')?.value || '';

  const wants = {
    hashtags: document.getElementById('anHashtags')?.checked,
    engagement: document.getElementById('anEngagement')?.checked,
    types: document.getElementById('anTypes')?.checked,
    topics: document.getElementById('anTopics')?.checked,
    demo: document.getElementById('anDemo')?.checked,
  };

  const out = {};
  function buildUrl(path) {
    const u = new URL(apiUrl(path), window.location.origin);
    if (platform) u.searchParams.set('platform', platform);
    if (from) u.searchParams.set('from', from);
    if (to) u.searchParams.set('to', to);
    return u;
  }

  if (wants.hashtags) {
    const u = buildUrl('api/hashtags/top');
    u.searchParams.set('limit', '25');
    out.top_hashtags = await (await fetch(u)).json();
  }
  if (wants.engagement) {
    const u = buildUrl('api/engagement/overview');
    u.searchParams.set('limit', '200');
    out.engagement_overview = await (await fetch(u)).json();
  }
  if (wants.types) {
    const u = buildUrl('api/analytics/post-types');
    out.post_types = await (await fetch(u)).json();
  }
  if (wants.topics) {
    const u = buildUrl('api/analytics/topics');
    out.topics = await (await fetch(u)).json();
  }
  if (wants.demo) {
    const u = buildUrl('api/tiktok/demographics/summary');
    out.tiktok_demographics_summary = await (await fetch(u)).json();
  }

  const dump = document.getElementById('analyticsDump');
  if (dump) dump.textContent = JSON.stringify(out, null, 2);
}

async function loadHashtagTrend() {
  const hashtag = document.getElementById('trendHashtag')?.value?.trim() || '';
  if (!hashtag) return;

  const platform = document.getElementById('platformFilter')?.value || '';
  const from = document.getElementById('fromDate')?.value || '';
  const to = document.getElementById('toDate')?.value || '';

  const u = new URL(apiUrl('api/hashtags/trend'), window.location.origin);
  u.searchParams.set('hashtag', hashtag.replace(/^#/, ''));
  if (platform) u.searchParams.set('platform', platform);
  if (from) u.searchParams.set('from', from);
  if (to) u.searchParams.set('to', to);

  const data = await (await fetch(u)).json();
  const dump = document.getElementById('analyticsDump');
  if (dump) dump.textContent = JSON.stringify({ hashtag_trend: data }, null, 2);
}

document.getElementById('loadAnalyticsBtn')?.addEventListener('click', loadAnalytics);
document.getElementById('loadTrendBtn')?.addEventListener('click', loadHashtagTrend);
*/

// --------------------
// Planning CRUD (intern) - ARCHIVED (removed from UI)
// --------------------
/* ARCHIVED - Planning page removed from navigation
function toDatetimeLocalValue(mysqlOrIso) {
  if (!mysqlOrIso) return '';
  const s = mysqlOrIso.replace(' ', 'T');
  return s.length >= 16 ? s.substring(0,16) : s;
}

async function planningLoad() {
  const res = await fetch(apiUrl('api/planning') + '?limit=200', { headers: authHeaders() });
  const data = await res.json();
  const dump = document.getElementById('planningDump');
  if (dump) dump.textContent = JSON.stringify(data, null, 2);
  setText('planningStatusText', res.ok ? 'Geladen.' : (data?.error?.message || 'Fout'));
}

function planningClearForm() {
  document.getElementById('planningId').value = '';
  document.getElementById('planningPlatform').value = '';
  document.getElementById('planningStatus').value = 'draft';
  document.getElementById('planningWhen').value = '';
  document.getElementById('planningType').value = '';
  document.getElementById('planningTitle').value = '';
  document.getElementById('planningTopic').value = '';
  document.getElementById('planningHashtags').value = '';
  document.getElementById('planningPostId').value = '';
  document.getElementById('planningNotes').value = '';
  setText('planningStatusText', 'Form leeggemaakt.');
}

document.getElementById('planningLoadBtn')?.addEventListener('click', planningLoad);
document.getElementById('planningClearBtn')?.addEventListener('click', planningClearForm);

document.getElementById('planningForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const id = document.getElementById('planningId').value.trim();
  const payload = {
    platform: document.getElementById('planningPlatform').value || null,
    status: document.getElementById('planningStatus').value || 'draft',
    scheduled_at: document.getElementById('planningWhen').value
      ? document.getElementById('planningWhen').value.replace('T', ' ') + ':00'
      : null,
    content_type: document.getElementById('planningType').value || null,
    title: document.getElementById('planningTitle').value || null,
    topic: document.getElementById('planningTopic').value || null,
    hashtags: document.getElementById('planningHashtags').value || null,
    post_id: document.getElementById('planningPostId').value
      ? Number(document.getElementById('planningPostId').value)
      : null,
    notes: document.getElementById('planningNotes').value || null,
    caption: null
  };

  const url = id ? apiUrl(`api/planning/${id}`) : apiUrl('api/planning');
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...authHeaders() },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if (res.ok) {
    setText('planningStatusText', id ? 'Geüpdatet.' : ('Aangemaakt (id=' + data.id + ').'));
    if (!id && data.id) document.getElementById('planningId').value = data.id;
    await planningLoad();
  } else {
    setText('planningStatusText', data?.error?.message || 'Opslaan mislukt');
  }
});
*/

// --------------------
// Post editor (intern)
// --------------------
let loadedPostId = null;

document.getElementById('postLoadForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const id = Number(document.getElementById('editPostId').value);
  if (!id) return;

  const res = await fetch(apiUrl('api/posts/' + id));
  const data = await res.json();

  const dump = document.getElementById('postDump');
  if (dump) dump.textContent = JSON.stringify(data, null, 2);

  if (!res.ok) {
    setText('postEditStatus', data?.error?.message || 'Niet gevonden');
    return;
  }

  loadedPostId = id;

  document.getElementById('editPostType').value = data.post_type || '';
  document.getElementById('editTopic').value = data.topic || '';
  document.getElementById('editHashtags').value = data.hashtags || '';
  document.getElementById('editInternalCaption').value = data.internal_caption || '';
  document.getElementById('editInternalNotes').value = data.internal_notes || '';

  setText('postEditStatus', 'Post geladen.');
});

document.getElementById('postEditForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!loadedPostId) return;

  const labelPayload = {
    post_type: document.getElementById('editPostType').value || null,
    topic: document.getElementById('editTopic').value || null
  };

  const res1 = await fetch(apiUrl('api/posts/' + loadedPostId + '/label'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...authHeaders() },
    body: JSON.stringify(labelPayload)
  });
  const data1 = await res1.json();
  if (!res1.ok) {
    setText('postEditStatus', data1?.error?.message || 'Label opslaan mislukt');
    return;
  }

  const editPayload = {
    internal_caption: document.getElementById('editInternalCaption').value || null,
    internal_notes: document.getElementById('editInternalNotes').value || null,
    hashtags: document.getElementById('editHashtags').value || null
  };

  const res2 = await fetch(apiUrl('api/posts/' + loadedPostId + '/edit'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...authHeaders() },
    body: JSON.stringify(editPayload)
  });
  const data2 = await res2.json();
  if (!res2.ok) {
    setText('postEditStatus', data2?.error?.message || 'Opslaan mislukt');
    return;
  }

  setText('postEditStatus', 'Opgeslagen (intern).');
});

// --------------------
// Date helpers
// --------------------
function setDefaultDateRange() {
  const today = new Date();
  const last30 = new Date(today);
  last30.setDate(last30.getDate() - 30);

  const formatDate = (d) => d.toISOString().split('T')[0];

  const fromInput = document.getElementById('fromDate');
  const toInput = document.getElementById('toDate');
  const presetSelect = document.getElementById('datePreset');

  // DISABLED: Don't set default dates - show ALL posts by default
  // Users can manually select a date range if needed

  // Leave date inputs EMPTY so no filter is applied
  // if (presetSelect) presetSelect.value = 'last90';
  // if (fromInput && !fromInput.value) {
  //   fromInput.value = formatDate(last30);
  // }
  // if (toInput && !toInput.value) {
  //   toInput.value = formatDate(today);
  // }
}

// --------------------
// Boot
// --------------------
window.addEventListener('DOMContentLoaded', () => {
  applyBranding();
  setAuthBadge();
  fetchEarliestPostDate(); // Fetch earliest date for "All Time" preset
  setDefaultDateRange();
  renderRoute();
  loadPosts();
  initPostsPageFilters();
  initGlossaryFilters();

  // Load glossary if on that page
  if (currentRoute() === '/glossary') {
    loadGlossaryTerms();
  }
});

// --------------------
// Post Detail Modal (Task #15)
// --------------------
async function viewPostDetail(postId) {
  const modal = document.getElementById('postDetailModal');
  const body = document.getElementById('postDetailBody');

  if (!modal || !body) return;

  // Show modal with loading state
  modal.classList.add('is-open');
  body.innerHTML = '<div class="loading" style="text-align: center; padding: 60px;">Loading post details...</div>';

  try {
    // Fetch post details
    const res = await fetch(apiUrl('api/posts/' + postId));
    const post = await res.json();

    if (!res.ok) {
      body.innerHTML = `<div style="text-align: center; padding: 60px; color: var(--muted);">Post niet gevonden</div>`;
      return;
    }

    // Calculate engagement metrics
    const engagementRate = post.views > 0 ? ((post.likes / post.views) * 100).toFixed(2) : '0.00';
    const totalEngagement = (post.likes || 0) + (post.comments || 0) + (post.shares || 0);

    // Render post details
    body.innerHTML = `
      <div class="post-detail-grid">
        <!-- Left Column: Metrics -->
        <div class="post-detail-section">
          <h3 class="post-detail-section__title">Performance Metrics</h3>

          <div class="post-detail-field">
            <span class="post-detail-field__label">Views</span>
            <div class="post-detail-field__value post-detail-field__value--large">${formatNumber(post.views || 0)}</div>
          </div>

          <div class="post-detail-field">
            <span class="post-detail-field__label">Likes</span>
            <div class="post-detail-field__value">${formatNumber(post.likes || 0)}</div>
          </div>

          <div class="post-detail-field">
            <span class="post-detail-field__label">Comments</span>
            <div class="post-detail-field__value">${formatNumber(post.comments || 0)}</div>
          </div>

          <div class="post-detail-field">
            <span class="post-detail-field__label">Shares</span>
            <div class="post-detail-field__value">${formatNumber(post.shares || 0)}</div>
          </div>

          <div class="post-detail-field">
            <span class="post-detail-field__label">Total Engagement</span>
            <div class="post-detail-field__value">${formatNumber(totalEngagement)}</div>
          </div>

          <div class="post-detail-field">
            <span class="post-detail-field__label">Engagement Rate</span>
            <div class="post-detail-field__value post-detail-field__value--large">${engagementRate}%</div>
          </div>
        </div>

        <!-- Right Column: Post Info -->
        <div class="post-detail-section">
          <h3 class="post-detail-section__title">Post Information</h3>

          <div class="post-detail-field">
            <span class="post-detail-field__label">Platform</span>
            <div class="post-detail-field__value">
              <span class="platform-badge platform-badge--${post.platform}">${post.platform}</span>
            </div>
          </div>

          <div class="post-detail-field">
            <span class="post-detail-field__label">Posted Date</span>
            <div class="post-detail-field__value">${post.posted_date || 'N/A'}</div>
          </div>

          <div class="post-detail-field">
            <span class="post-detail-field__label">Post Type</span>
            <div class="post-detail-field__value">${post.post_type || 'N/A'}</div>
          </div>

          <div class="post-detail-field">
            <span class="post-detail-field__label">Topic</span>
            <div class="post-detail-field__value">${post.topic || 'N/A'}</div>
          </div>

          <div class="post-detail-field">
            <span class="post-detail-field__label">Hashtags</span>
            <div class="post-detail-field__value">${post.hashtags || 'N/A'}</div>
          </div>

          ${post.platform_post_id ? `
          <div class="post-detail-field">
            <span class="post-detail-field__label">Platform Post ID</span>
            <div class="post-detail-field__value" style="font-size: 12px; font-family: monospace;">${post.platform_post_id}</div>
          </div>
          ` : ''}
        </div>
      </div>

      <!-- Full Caption -->
      ${post.caption ? `
      <div class="post-detail-section" style="margin-top: 24px;">
        <h3 class="post-detail-section__title">Caption</h3>
        <div class="post-detail-caption">${escapeHtml(post.caption)}</div>
      </div>
      ` : ''}

      <!-- Internal Notes -->
      ${post.internal_notes || post.internal_caption ? `
      <div class="post-detail-section" style="margin-top: 24px;">
        <h3 class="post-detail-section__title">Internal Notes</h3>
        ${post.internal_caption ? `<p><strong>Internal Caption:</strong> ${escapeHtml(post.internal_caption)}</p>` : ''}
        ${post.internal_notes ? `<p><strong>Notes:</strong> ${escapeHtml(post.internal_notes)}</p>` : ''}
      </div>
      ` : ''}

      <!-- Action Buttons -->
      <div class="row wrap" style="margin-top: 24px; gap: 12px;">
        <button class="btn btn--ghost" onclick="closePostDetail()" type="button">Sluiten</button>
        <button class="btn" onclick="editPost(${post.id})" type="button">Bewerk Post</button>
      </div>
    `;

    // Apply term linking to post detail modal (if term-linking.js is loaded)
    if (typeof linkTermsInPostDetail === 'function') {
      setTimeout(() => linkTermsInPostDetail(), 100);
    }

  } catch (err) {
    console.error('Failed to load post details:', err);
    body.innerHTML = `<div style="text-align: center; padding: 60px; color: var(--muted);">Error loading post details</div>`;
  }
}

function closePostDetail() {
  const modal = document.getElementById('postDetailModal');
  if (modal) {
    modal.classList.remove('is-open');
  }
}

function editPost(postId) {
  closePostDetail();
  window.location.hash = '#/posts';
  setTimeout(() => {
    const editInput = document.getElementById('editPostId');
    if (editInput) {
      editInput.value = postId;
      editInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }, 300);
}

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closePostDetail();
  }
});

// --------------------
// Glossary Page (Task #19)
// --------------------
let allGlossaryTerms = [];

async function loadGlossaryTerms() {
  const grid = document.getElementById('glossaryTermsGrid');
  if (!grid) return;

  try {
    grid.innerHTML = '<div class="loading" style="text-align: center; padding: 60px;">Termen aan het laden...</div>';

    const res = await fetch(apiUrl('api/glossary'));
    const terms = await res.json();

    if (!res.ok || !Array.isArray(terms)) {
      grid.innerHTML = '<div style="text-align: center; padding: 60px; color: var(--muted);">Fout bij laden van termen</div>';
      return;
    }

    allGlossaryTerms = terms;
    renderGlossaryTerms(terms);

  } catch (err) {
    console.error('Failed to load glossary terms:', err);
    grid.innerHTML = '<div style="text-align: center; padding: 60px; color: var(--muted);">Fout bij laden van termen</div>';
  }
}

function renderGlossaryTerms(terms) {
  const grid = document.getElementById('glossaryTermsGrid');
  if (!grid) return;

  // Apply filters
  const filtered = filterGlossaryTerms(terms);

  grid.innerHTML = '';

  if (filtered.length === 0) {
    grid.innerHTML = '<div style="text-align: center; padding: 60px; color: var(--muted);">Geen termen gevonden met deze filters</div>';
    updateGlossaryCount(0);
    return;
  }

  filtered.forEach(term => {
    const card = document.createElement('div');
    card.className = 'glossary-card';
    card.setAttribute('data-term-id', term.id);
    card.onclick = () => viewTermDetail(term.id);

    // Category badge color
    const categoryColors = {
      'algorithm': '#6d28d9',
      'metrics': '#10b981',
      'platforms': '#3b82f6',
      'general': '#f59e0b'
    };
    const categoryColor = categoryColors[term.category] || '#6b7280';

    card.innerHTML = `
      <div class="glossary-card__header">
        <h3 class="glossary-card__term">${escapeHtml(term.term)}</h3>
        <span class="glossary-card__category" style="background: ${categoryColor}20; color: ${categoryColor}; border-color: ${categoryColor}40;">
          ${escapeHtml(term.category || 'general')}
        </span>
      </div>
      <p class="glossary-card__definition">${escapeHtml(term.definition.substring(0, 150))}${term.definition.length > 150 ? '...' : ''}</p>
      ${term.platform ? `<div class="glossary-card__platform"><span class="platform-badge platform-badge--${term.platform}">${term.platform}</span></div>` : ''}
    `;

    grid.appendChild(card);
  });

  updateGlossaryCount(filtered.length);
}

function filterGlossaryTerms(terms) {
  const searchTerm = (document.getElementById('glossarySearchInput')?.value || '').toLowerCase();
  const category = document.getElementById('glossaryCategoryFilter')?.value || '';

  let filtered = [...terms];

  // Apply category filter
  if (category) {
    filtered = filtered.filter(t => t.category === category);
  }

  // Apply search filter
  if (searchTerm) {
    filtered = filtered.filter(t => {
      const term = (t.term || '').toLowerCase();
      const definition = (t.definition || '').toLowerCase();
      const example = (t.example || '').toLowerCase();

      return term.includes(searchTerm) ||
             definition.includes(searchTerm) ||
             example.includes(searchTerm);
    });
  }

  // Sort alphabetically
  filtered.sort((a, b) => (a.term || '').localeCompare(b.term || ''));

  return filtered;
}

function updateGlossaryCount(count) {
  const countEl = document.getElementById('glossaryCount');
  if (countEl) {
    countEl.textContent = `${count} ${count === 1 ? 'term' : 'termen'}`;
  }
}

// Add event listeners for glossary filters
function initGlossaryFilters() {
  document.getElementById('glossarySearchInput')?.addEventListener('input', () => {
    renderGlossaryTerms(allGlossaryTerms);
  });

  document.getElementById('glossaryCategoryFilter')?.addEventListener('change', () => {
    renderGlossaryTerms(allGlossaryTerms);
  });
}

// View term detail in modal
async function viewTermDetail(termId) {
  const modal = document.getElementById('postDetailModal');
  const body = document.getElementById('postDetailBody');
  const title = document.getElementById('postDetailTitle');

  if (!modal || !body || !title) return;

  // Show modal with loading state
  modal.classList.add('is-open');
  title.textContent = 'Term Details';
  body.innerHTML = '<div class="loading" style="text-align: center; padding: 60px;">Loading term...</div>';

  try {
    const res = await fetch(apiUrl('api/glossary/' + termId));
    const term = await res.json();

    if (!res.ok) {
      body.innerHTML = `<div style="text-align: center; padding: 60px; color: var(--muted);">Term niet gevonden</div>`;
      return;
    }

    title.textContent = term.term;

    // Category badge color
    const categoryColors = {
      'algorithm': '#6d28d9',
      'metrics': '#10b981',
      'platforms': '#3b82f6',
      'general': '#f59e0b'
    };
    const categoryColor = categoryColors[term.category] || '#6b7280';

    body.innerHTML = `
      <div class="post-detail-section">
        <div class="post-detail-field">
          <span class="post-detail-field__label">Categorie</span>
          <div class="post-detail-field__value">
            <span class="glossary-card__category" style="background: ${categoryColor}20; color: ${categoryColor}; border-color: ${categoryColor}40; padding: 6px 12px; border-radius: 6px; display: inline-block;">
              ${escapeHtml(term.category || 'general')}
            </span>
          </div>
        </div>

        ${term.platform ? `
        <div class="post-detail-field">
          <span class="post-detail-field__label">Platform</span>
          <div class="post-detail-field__value">
            <span class="platform-badge platform-badge--${term.platform}">${term.platform}</span>
          </div>
        </div>
        ` : ''}

        <div class="post-detail-field" style="margin-top: 24px;">
          <span class="post-detail-field__label">Definitie</span>
          <div class="post-detail-field__value" style="font-size: 16px; line-height: 1.6; color: var(--text);">
            ${escapeHtml(term.definition)}
          </div>
        </div>

        ${term.example ? `
        <div class="post-detail-field" style="margin-top: 24px;">
          <span class="post-detail-field__label">Voorbeeld</span>
          <div class="post-detail-caption" style="margin-top: 8px;">
            ${escapeHtml(term.example)}
          </div>
        </div>
        ` : ''}

        ${term.related_terms ? `
        <div class="post-detail-field" style="margin-top: 24px;">
          <span class="post-detail-field__label">Gerelateerde termen</span>
          <div class="post-detail-field__value" style="font-size: 14px;">
            ${escapeHtml(term.related_terms)}
          </div>
        </div>
        ` : ''}
      </div>

      <div class="row wrap" style="margin-top: 24px; gap: 12px;">
        <button class="btn btn--ghost" onclick="closePostDetail()" type="button">Sluiten</button>
      </div>
    `;

    // Apply term linking to term detail modal (if term-linking.js is loaded)
    if (typeof linkTermsInTermDetail === 'function') {
      setTimeout(() => linkTermsInTermDetail(), 100);
    }

  } catch (err) {
    console.error('Failed to load term details:', err);
    body.innerHTML = `<div style="text-align: center; padding: 60px; color: var(--muted);">Error loading term</div>`;
  }
}

// Load glossary terms when navigating to glossary page
window.addEventListener('hashchange', () => {
  if (currentRoute() === '/glossary') {
    loadGlossaryTerms();
  }
});

// --------------------
// Website Traffic (Fathom Analytics) - Task #10
// --------------------

async function loadWebsiteTraffic() {
  const from = document.getElementById('fromDate')?.value || '';
  const to = document.getElementById('toDate')?.value || '';

  try {
    // Build query params
    const params = new URLSearchParams();
    if (from) params.set('date_from', from);
    if (to) params.set('date_to', to);

    // Load stats, referrers, and timeseries in parallel
    const [stats, referrers, timeseries] = await Promise.all([
      fetch(apiUrl(`api/analytics/fathom/stats?${params}`)).then(r => r.json()),
      fetch(apiUrl(`api/analytics/fathom/referrers?${params}&limit=10`)).then(r => r.json()),
      fetch(apiUrl(`api/analytics/fathom/timeseries?${params}`)).then(r => r.json())
    ]);

    // Render KPIs
    renderWebsiteKPIs(stats);

    // Render referrers list
    renderTopReferrers(referrers);

    // Render traffic chart
    renderWebsiteTrafficChart(timeseries);

  } catch (err) {
    console.error('Failed to load website traffic:', err);
    // Show friendly error state
    document.getElementById('websiteVisits').textContent = 'N/A';
    document.getElementById('websiteUniques').textContent = 'N/A';
    document.getElementById('websiteAvgDuration').textContent = 'N/A';
    document.getElementById('websiteBounceRate').textContent = 'N/A';
    
    const container = document.getElementById('topReferrersContainer');
    if (container) {
      container.innerHTML = '<div class="muted" style="padding: 20px; text-align: center;">Fathom data niet beschikbaar. Controleer API instellingen.</div>';
    }
  }
}

function renderWebsiteKPIs(stats) {
  if (!stats) return;

  // Total visits
  const visitsEl = document.getElementById('websiteVisits');
  if (visitsEl) {
    visitsEl.textContent = formatNumber(stats.visits || 0);
  }

  // Unique visitors
  const uniquesEl = document.getElementById('websiteUniques');
  if (uniquesEl) {
    uniquesEl.textContent = formatNumber(stats.uniques || 0);
  }

  // Average duration (convert seconds to minutes:seconds)
  const durationEl = document.getElementById('websiteAvgDuration');
  if (durationEl) {
    const seconds = stats.avg_duration || 0;
    const minutes = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    durationEl.textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;
  }

  // Bounce rate
  const bounceEl = document.getElementById('websiteBounceRate');
  if (bounceEl) {
    bounceEl.textContent = `${(stats.bounce_rate || 0).toFixed(1)}%`;
  }
}

function renderTopReferrers(referrers) {
  const container = document.getElementById('topReferrersContainer');
  if (!container) return;

  if (!Array.isArray(referrers) || referrers.length === 0) {
    container.innerHTML = '<div class="muted" style="padding: 20px; text-align: center;">Geen verwijzingen gevonden</div>';
    return;
  }

  // Filter social media referrers
  const socialReferrers = referrers.filter(ref => {
    const referrer = (ref.referrer || '').toLowerCase();
    return referrer.includes('instagram') ||
           referrer.includes('tiktok') ||
           referrer.includes('facebook') ||
           referrer.includes('t.co') ||
           referrer.includes('twitter') ||
           referrer.includes('youtube');
  });

  if (socialReferrers.length === 0) {
    container.innerHTML = '<div class="muted" style="padding: 20px; text-align: center;">Geen social media verkeer in deze periode</div>';
    return;
  }

  container.innerHTML = socialReferrers.map(ref => {
    const platform = detectPlatformFromReferrer(ref.referrer);
    const percentage = ref.visits > 0 ? ((ref.visits / referrers.reduce((sum, r) => sum + r.visits, 0)) * 100).toFixed(1) : 0;

    return `
      <div class="referrer-item">
        <div class="referrer-item__header">
          <span class="platform-badge platform-badge--${platform}">${platform}</span>
          <span class="referrer-item__percentage">${percentage}%</span>
        </div>
        <div class="referrer-item__stats">
          <span class="muted mini">${formatNumber(ref.visits)} bezoeken</span>
          <span class="muted mini">•</span>
          <span class="muted mini">${formatNumber(ref.uniques)} uniek</span>
        </div>
      </div>
    `;
  }).join('');
}

function detectPlatformFromReferrer(referrer) {
  const ref = (referrer || '').toLowerCase();
  if (ref.includes('instagram')) return 'instagram';
  if (ref.includes('tiktok')) return 'tiktok';
  if (ref.includes('facebook')) return 'facebook';
  if (ref.includes('t.co') || ref.includes('twitter')) return 'twitter';
  if (ref.includes('youtube')) return 'youtube';
  return 'other';
}

function renderWebsiteTrafficChart(timeseries) {
  destroyChart('websiteTraffic');

  const canvas = document.getElementById('websiteTrafficChart');
  if (!canvas) return;

  if (!Array.isArray(timeseries) || timeseries.length === 0) {
    return;
  }

  const labels = timeseries.map(d => d.date);
  const visits = timeseries.map(d => d.visits || 0);
  const uniques = timeseries.map(d => d.uniques || 0);

  chartInstances.websiteTraffic = new Chart(canvas, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Bezoeken',
          data: visits,
          borderColor: '#6d28d9',
          backgroundColor: 'rgba(109, 40, 217, 0.1)',
          tension: 0.3,
          fill: true
        },
        {
          label: 'Unieke Bezoekers',
          data: uniques,
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          tension: 0.3,
          fill: true
        }
      ]
    },
    options: {
      ...chartDefaults,
      scales: {
        y: {
          beginAtZero: true,
          ticks: { color: 'rgba(255,255,255,0.7)' },
          grid: { color: 'rgba(255,255,255,0.1)' }
        },
        x: {
          ticks: {
            color: 'rgba(255,255,255,0.7)',
            maxRotation: 45,
            minRotation: 45
          },
          grid: { color: 'rgba(255,255,255,0.05)' }
        }
      }
    }
  });
}
