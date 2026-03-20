# SocialBit Design System - Metricool-Inspired Light Theme

**Status:** Draft v1.0
**Created:** 2026-02-07
**Designer:** UX/Design Lead (Claude Agent)
**Reference:** Metricool Inbox UI (docs/metriinbox.png)

---

## 🎨 Design Philosophy

**Principles:**
1. **Light & Clean** - Professional SaaS aesthetic with white backgrounds
2. **Subtle Depth** - Gentle shadows and borders, no heavy gradients
3. **Brand Color Accents** - Dynamic brand colors for highlights, not overwhelming
4. **Platform Recognition** - Use authentic platform colors (TikTok pink, Instagram gradient, etc.)
5. **Information Density** - Dense but scannable, like Metricool
6. **No Emojis** - Professional indicators (dots, badges, icons)

---

## 🌈 Color Palette

### Base Colors (Light Theme)

```css
:root {
  /* === BACKGROUNDS === */
  --bg-app: #F8F9FA;           /* Main app background - very light gray */
  --bg-card: #FFFFFF;          /* Card backgrounds - pure white */
  --bg-elevated: #FFFFFF;      /* Modals, dropdowns */
  --bg-hover: #F1F3F5;         /* Hover states */
  --bg-selected: #E9ECEF;      /* Selected/active states */

  /* === TEXT === */
  --text-primary: #212529;     /* Main text - very dark gray (not black) */
  --text-secondary: #6C757D;   /* Secondary text - medium gray */
  --text-tertiary: #ADB5BD;    /* Tertiary text - light gray */
  --text-inverse: #FFFFFF;     /* Text on dark backgrounds */

  /* === BORDERS === */
  --border-light: #E9ECEF;     /* Subtle borders */
  --border-medium: #DEE2E6;    /* Default borders */
  --border-dark: #ADB5BD;      /* Emphasized borders */

  /* === SHADOWS === */
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.10);
  --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.12);

  /* === RADIUS === */
  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  --radius-full: 9999px;

  /* === SPACING === */
  --space-xs: 4px;
  --space-sm: 8px;
  --space-md: 12px;
  --space-lg: 16px;
  --space-xl: 24px;
  --space-2xl: 32px;
}
```

### Brand Colors (Dynamic - from database)

```css
:root {
  /* === BRAND (User-customizable) === */
  --brand-primary: #6d28d9;    /* Primary brand color - default purple */
  --brand-secondary: #22d3ee;  /* Secondary brand color - default cyan */
  --brand-accent: #10b981;     /* Accent brand color - default green */

  /* Brand color variations (auto-generated) */
  --brand-primary-light: color-mix(in srgb, var(--brand-primary) 20%, white);
  --brand-primary-dark: color-mix(in srgb, var(--brand-primary) 80%, black);
  --brand-secondary-light: color-mix(in srgb, var(--brand-secondary) 20%, white);
  --brand-accent-light: color-mix(in srgb, var(--brand-accent) 20%, white);
}
```

### Platform Colors (Fixed - DO NOT change)

```css
:root {
  /* === PLATFORM COLORS === */
  --platform-tiktok: #FE2C55;        /* TikTok pink */
  --platform-instagram: #E4405F;     /* Instagram gradient red (use with gradient) */
  --platform-instagram-gradient: linear-gradient(135deg, #833AB4, #FD1D1D, #F77737);
  --platform-facebook: #1877F2;      /* Facebook blue */
  --platform-youtube: #FF0000;       /* YouTube red */
  --platform-google: #4285F4;        /* Google blue */
  --platform-twitter: #1DA1F2;       /* Twitter blue (legacy) */
}
```

### Semantic Colors (Status & Feedback)

```css
:root {
  /* === SEMANTIC COLORS === */
  --color-success: #10b981;          /* Green - success states */
  --color-warning: #F59E0B;          /* Amber - warnings */
  --color-error: #EF4444;            /* Red - errors */
  --color-info: #3B82F6;             /* Blue - informational */

  /* Light backgrounds for semantic colors */
  --color-success-bg: #D1FAE5;
  --color-warning-bg: #FEF3C7;
  --color-error-bg: #FEE2E2;
  --color-info-bg: #DBEAFE;

  /* Text colors for semantic states */
  --color-success-text: #065F46;
  --color-warning-text: #92400E;
  --color-error-text: #991B1B;
  --color-info-text: #1E40AF;
}
```

---

## 📐 Layout Components

### Navigation Bar (Metricool-style)

```css
.topbar {
  background: #2D1B3D;             /* Dark purple - Metricool signature */
  color: white;
  height: 56px;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: var(--shadow-md);
  position: sticky;
  top: 0;
  z-index: 100;
}

.topbar__logo {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 18px;
  font-weight: 700;
  color: white;
}

.topbar__nav {
  display: flex;
  gap: 8px;
}

.topbar__nav-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: var(--radius-md);
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.2s;
}

.topbar__nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

.topbar__nav-item.is-active {
  background: rgba(255, 255, 255, 0.15);
  color: white;
}

.topbar__user {
  display: flex;
  align-items: center;
  gap: 12px;
}
```

### Sidebar (Optional - Metricool uses top nav)

```css
.sidebar {
  width: 260px;
  background: var(--bg-card);
  border-right: 1px solid var(--border-light);
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  height: 100vh;
  position: sticky;
  top: 56px; /* Below topbar */
}

.sidebar__section {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.sidebar__title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-tertiary);
  padding: 8px 12px;
}
```

### Cards

```css
.card {
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 20px;
  transition: box-shadow 0.2s;
}

.card:hover {
  box-shadow: var(--shadow-md);
}

.card--elevated {
  box-shadow: var(--shadow-md);
}

.card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-light);
}

.card__title {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}
```

---

## 🧩 UI Components

### Buttons

```css
/* Primary Button */
.btn--primary {
  background: var(--brand-primary);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: var(--shadow-sm);
}

.btn--primary:hover {
  background: var(--brand-primary-dark);
  box-shadow: var(--shadow-md);
  transform: translateY(-1px);
}

.btn--primary:active {
  transform: translateY(0);
}

/* Secondary Button */
.btn--secondary {
  background: var(--bg-hover);
  color: var(--text-primary);
  border: 1px solid var(--border-medium);
  padding: 10px 20px;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn--secondary:hover {
  background: var(--bg-selected);
  border-color: var(--border-dark);
}

/* Ghost Button */
.btn--ghost {
  background: transparent;
  color: var(--text-secondary);
  border: none;
  padding: 8px 12px;
  border-radius: var(--radius-md);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn--ghost:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

/* Icon Button */
.btn--icon {
  background: transparent;
  border: none;
  padding: 8px;
  border-radius: var(--radius-md);
  cursor: pointer;
  color: var(--text-secondary);
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn--icon:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}
```

### Form Inputs

```css
.input {
  background: var(--bg-card);
  border: 1px solid var(--border-medium);
  border-radius: var(--radius-md);
  padding: 10px 12px;
  font-size: 14px;
  color: var(--text-primary);
  transition: all 0.2s;
  outline: none;
}

.input:focus {
  border-color: var(--brand-primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-primary) 15%, transparent);
}

.input::placeholder {
  color: var(--text-tertiary);
}

/* Search Input (Metricool style) */
.input--search {
  background: var(--bg-hover);
  border: 1px solid transparent;
  border-radius: var(--radius-full);
  padding-left: 40px; /* Space for icon */
}

.input--search:focus {
  background: var(--bg-card);
  border-color: var(--border-medium);
}
```

### Badges & Pills

```css
/* Badge */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
  background: var(--bg-selected);
  color: var(--text-primary);
  border: 1px solid var(--border-medium);
}

.badge--success {
  background: var(--color-success-bg);
  color: var(--color-success-text);
  border-color: var(--color-success);
}

.badge--warning {
  background: var(--color-warning-bg);
  color: var(--color-warning-text);
  border-color: var(--color-warning);
}

.badge--error {
  background: var(--color-error-bg);
  color: var(--color-error-text);
  border-color: var(--color-error);
}

.badge--brand {
  background: var(--brand-primary-light);
  color: var(--brand-primary);
  border-color: var(--brand-primary);
}

/* Platform Badges */
.badge--platform {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: var(--radius-md);
  font-size: 11px;
  font-weight: 600;
}

.badge--tiktok {
  background: color-mix(in srgb, var(--platform-tiktok) 15%, white);
  color: var(--platform-tiktok);
}

.badge--instagram {
  background: color-mix(in srgb, var(--platform-instagram) 15%, white);
  color: var(--platform-instagram);
}

.badge--facebook {
  background: color-mix(in srgb, var(--platform-facebook) 15%, white);
  color: var(--platform-facebook);
}
```

### Avatar System (Metricool-style)

```css
.avatar {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 16px;
  color: white;
  position: relative;
}

/* Avatar color variations (auto-assign based on hash) */
.avatar--magenta { background: #E91E63; }
.avatar--purple { background: #9C27B0; }
.avatar--indigo { background: #3F51B5; }
.avatar--cyan { background: #00BCD4; }
.avatar--teal { background: #009688; }
.avatar--green { background: #4CAF50; }
.avatar--orange { background: #FF9800; }
.avatar--pink { background: #F06292; }

/* Platform icon badge on avatar */
.avatar__badge {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 18px;
  height: 18px;
  border-radius: var(--radius-full);
  border: 2px solid var(--bg-card);
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar__badge--instagram {
  background: var(--platform-instagram);
}

.avatar__badge--facebook {
  background: var(--platform-facebook);
}

.avatar__badge--google {
  background: var(--platform-google);
}
```

### Tables

```css
.table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.table thead th {
  background: var(--bg-hover);
  color: var(--text-secondary);
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid var(--border-medium);
}

.table tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-light);
  color: var(--text-primary);
  font-size: 14px;
}

.table tbody tr:hover {
  background: var(--bg-hover);
}

.table tbody tr:last-child td {
  border-bottom: none;
}
```

---

## 🎯 Subtle Indicators (NO Emojis)

### Status Indicators

```css
/* Dot Indicator */
.indicator {
  width: 8px;
  height: 8px;
  border-radius: var(--radius-full);
  display: inline-block;
}

.indicator--success { background: var(--color-success); }
.indicator--warning { background: var(--color-warning); }
.indicator--error { background: var(--color-error); }
.indicator--info { background: var(--color-info); }
.indicator--neutral { background: var(--text-tertiary); }

/* Progress Bar */
.progress {
  height: 6px;
  background: var(--bg-selected);
  border-radius: var(--radius-full);
  overflow: hidden;
}

.progress__bar {
  height: 100%;
  background: var(--brand-primary);
  border-radius: var(--radius-full);
  transition: width 0.3s ease;
}

.progress__bar--success { background: var(--color-success); }
.progress__bar--warning { background: var(--color-warning); }

/* Star Rating (like Metricool reviews) */
.rating {
  display: inline-flex;
  gap: 2px;
}

.rating__star {
  color: #FFB400; /* Gold */
  font-size: 16px;
}

.rating__star--empty {
  color: var(--border-medium);
}
```

### Performance Badges (Context Indicators)

```css
/* Performance badges for KPIs */
.perf-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.perf-badge--excellent {
  background: var(--color-success-bg);
  color: var(--color-success-text);
}

.perf-badge--good {
  background: var(--color-info-bg);
  color: var(--color-info-text);
}

.perf-badge--average {
  background: var(--color-warning-bg);
  color: var(--color-warning-text);
}

.perf-badge--below {
  background: var(--color-error-bg);
  color: var(--color-error-text);
}

/* Arrow indicators */
.arrow {
  display: inline-block;
  font-size: 12px;
  font-weight: 700;
}

.arrow--up {
  color: var(--color-success);
}

.arrow--down {
  color: var(--color-error);
}

.arrow--neutral {
  color: var(--text-tertiary);
}
```

---

## 📱 Responsive Breakpoints

```css
/* Mobile First Approach */

/* Extra Small (Phones) */
@media (max-width: 576px) {
  .sidebar { display: none; }
  .topbar { padding: 0 12px; }
  .card { padding: 16px; }
  .grid--2, .grid--3, .grid--4 { grid-template-columns: 1fr; }
}

/* Small (Large Phones, Small Tablets) */
@media (min-width: 577px) and (max-width: 768px) {
  .sidebar { width: 220px; }
  .grid--3, .grid--4 { grid-template-columns: repeat(2, 1fr); }
}

/* Medium (Tablets) */
@media (min-width: 769px) and (max-width: 992px) {
  .sidebar { width: 240px; }
  .grid--4 { grid-template-columns: repeat(2, 1fr); }
}

/* Large (Desktops) */
@media (min-width: 993px) and (max-width: 1200px) {
  .sidebar { width: 260px; }
}

/* Extra Large (Large Desktops) */
@media (min-width: 1201px) {
  .sidebar { width: 280px; }
  .content { max-width: 1400px; }
}
```

---

## 🎨 Brand Color Integration

### How Brand Colors Are Applied

**Database Storage:**
```sql
-- settings table
('brand_primary_color', '#6d28d9')
('brand_secondary_color', '#22d3ee')
('brand_accent_color', '#10b981')
```

**JavaScript Integration:**
```javascript
// Load brand colors from backend
fetch('/api/settings/branding')
  .then(res => res.json())
  .then(data => {
    document.documentElement.style.setProperty('--brand-primary', data.primary_color);
    document.documentElement.style.setProperty('--brand-secondary', data.secondary_color);
    document.documentElement.style.setProperty('--brand-accent', data.accent_color);
  });
```

**Usage Guidelines:**
- **Primary Color:** Main CTAs, active states, links
- **Secondary Color:** Secondary CTAs, highlights
- **Accent Color:** Success states, positive indicators, graphs
- **DO NOT use brand colors for:** Platform badges, semantic colors (error/warning/info)

---

## 📊 Typography

```css
:root {
  --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  --font-mono: "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, monospace;
}

body {
  font-family: var(--font-family);
  font-size: 14px;
  line-height: 1.5;
  color: var(--text-primary);
}

/* Headings */
h1, .h1 {
  font-size: 24px;
  font-weight: 700;
  line-height: 1.3;
  color: var(--text-primary);
  margin: 0 0 16px 0;
}

h2, .h2 {
  font-size: 18px;
  font-weight: 600;
  line-height: 1.4;
  color: var(--text-primary);
  margin: 0 0 12px 0;
}

h3, .h3 {
  font-size: 16px;
  font-weight: 600;
  line-height: 1.4;
  color: var(--text-primary);
  margin: 0 0 8px 0;
}

/* Text Styles */
.text-primary { color: var(--text-primary); }
.text-secondary { color: var(--text-secondary); }
.text-tertiary { color: var(--text-tertiary); }

.text-sm { font-size: 12px; }
.text-md { font-size: 14px; }
.text-lg { font-size: 16px; }
.text-xl { font-size: 18px; }

.font-normal { font-weight: 400; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.font-bold { font-weight: 700; }

/* Monospace (for codes, metrics) */
.mono {
  font-family: var(--font-mono);
  font-size: 13px;
  letter-spacing: -0.5px;
}
```

---

## 🎬 Animation & Transitions

```css
:root {
  --transition-fast: 150ms ease;
  --transition-base: 200ms ease;
  --transition-slow: 300ms ease;
  --transition-bounce: 300ms cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

/* Default transitions */
* {
  transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
  transition-duration: var(--transition-base);
}

/* Hover lift effect */
.hover-lift {
  transition: transform var(--transition-base), box-shadow var(--transition-base);
}

.hover-lift:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

/* Fade in animation */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in {
  animation: fadeIn var(--transition-slow);
}
```

---

## ✅ Implementation Checklist

### Phase 1: Core Light Theme (Week 1)
- [ ] Convert background to light (`#F8F9FA`)
- [ ] Update text colors to dark (`#212529`)
- [ ] Replace dark cards with white cards + subtle shadows
- [ ] Update borders to light gray (`#E9ECEF`)
- [ ] Fix navigation bar (dark purple `#2D1B3D`)

### Phase 2: Component Updates (Week 2)
- [ ] Update all buttons (primary, secondary, ghost)
- [ ] Update form inputs with light theme
- [ ] Update badges and pills
- [ ] Replace emoji indicators with dots/badges
- [ ] Update tables with light theme

### Phase 3: Brand Integration (Week 3)
- [ ] Test brand color picker with light theme
- [ ] Ensure brand colors work with light backgrounds
- [ ] Update chart colors for light theme
- [ ] Test contrast ratios (WCAG AA compliance)

### Phase 4: Platform Colors (Week 4)
- [ ] Add platform color variables
- [ ] Update platform badges
- [ ] Create avatar system with platform icons
- [ ] Test all platform color combinations

---

## 🔧 Migration Notes

**Critical Changes:**
1. **Background:** Dark (`#0b1020`) → Light (`#F8F9FA`)
2. **Text:** Light (`rgba(255,255,255,.92)`) → Dark (`#212529`)
3. **Cards:** Dark glass effect → White with shadows
4. **Borders:** Transparent/dark → Light gray
5. **Navigation:** Transparent → Dark purple (`#2D1B3D`)

**Brand Color Compatibility:**
- Ensure brand colors have sufficient contrast on white
- Lighten brand colors if needed (auto via `color-mix()`)
- Test with accessibility tools

**Testing Checklist:**
- [ ] Test all pages in light theme
- [ ] Verify text readability (contrast ratio ≥ 4.5:1)
- [ ] Test brand color picker
- [ ] Verify platform colors look correct
- [ ] Mobile responsive testing
- [ ] Dark mode toggle (future enhancement)

---

## 📚 Resources

- **Metricool Reference:** `docs/metriinbox.png`
- **Current Styles:** `public/assets/styles.css`
- **Color Contrast Checker:** https://webaim.org/resources/contrastchecker/
- **Material Design:** https://m3.material.io/styles/color/system/overview
- **Tailwind Colors:** https://tailwindcss.com/docs/customizing-colors

---

**Next Steps:**
1. Get approval from team-lead on design system
2. Create light theme CSS file (`styles-light.css`)
3. Implement brand color integration
4. Test with real data
5. Deploy to staging for user feedback
