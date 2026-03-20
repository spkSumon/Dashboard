# Brand Colors API Endpoints

**Created:** 2026-02-07
**Purpose:** Support design-lead Task #6 (brand color integration into new theme)
**Status:** ✅ Fully functional

---

## Overview

The brand colors API provides endpoints to retrieve and update the application's color scheme. These colors are used throughout the UI for consistent theming.

**Default Colors (Metricool-inspired palette):**
- `brand_primary`: `#6d28d9` (Purple - primary brand color)
- `brand_secondary`: `#22d3ee` (Cyan - secondary/accent color)
- `brand_accent`: `#10b981` (Emerald - success/positive indicator)

---

## Endpoints

### 1. Get Brand Colors

**Endpoint:** `GET /api/settings/brand-colors`
**Authentication:** None (public)
**Description:** Retrieve current brand colors or defaults if not configured

**Response (HTTP 200):**
```json
{
  "brand_primary": "#6d28d9",
  "brand_secondary": "#22d3ee",
  "brand_accent": "#10b981"
}
```

**Frontend Example:**
```javascript
async function loadBrandColors() {
  const response = await fetch('/api/settings/brand-colors');
  const colors = await response.json();

  // Apply to CSS variables
  document.documentElement.style.setProperty('--brand-primary', colors.brand_primary);
  document.documentElement.style.setProperty('--brand-secondary', colors.brand_secondary);
  document.documentElement.style.setProperty('--brand-accent', colors.brand_accent);

  return colors;
}

// Call on app initialization
loadBrandColors();
```

---

### 2. Update Brand Colors

**Endpoint:** `POST /api/settings/brand-colors`
**Authentication:** Required (Bearer token in `Authorization` header)
**Description:** Update one or more brand colors

**Request Body (JSON):**
```json
{
  "brand_primary": "#8b5cf6",
  "brand_secondary": "#06b6d4",
  "brand_accent": "#059669"
}
```

**Partial Update (only primary color):**
```json
{
  "brand_primary": "#8b5cf6"
}
```

**Response (HTTP 200):**
```json
{
  "ok": true,
  "updated": ["brand_primary", "brand_secondary", "brand_accent"]
}
```

**Validation Errors (HTTP 400):**
```json
{
  "error": "Invalid hex color format for brand_primary"
}
```

**Authentication Error (HTTP 401):**
```json
{
  "error": {
    "code": null,
    "message": "Geen token"
  }
}
```

**Frontend Example:**
```javascript
async function updateBrandColors(colors) {
  const token = localStorage.getItem('auth_token');

  const response = await fetch('/api/settings/brand-colors', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(colors)
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.error || 'Failed to update brand colors');
  }

  return response.json();
}

// Usage
await updateBrandColors({
  brand_primary: '#8b5cf6',
  brand_secondary: '#06b6d4'
});
```

---

## Validation Rules

**Hex Color Format:**
- Must start with `#`
- Must have exactly 6 hexadecimal digits (0-9, A-F, a-f)
- Examples: `#6d28d9`, `#FFFFFF`, `#000000`

**Valid Keys:**
- `brand_primary`
- `brand_secondary`
- `brand_accent`

Any other keys are ignored.

---

## CSS Integration

**Recommended approach: CSS custom properties**

```css
:root {
  --brand-primary: #6d28d9;
  --brand-secondary: #22d3ee;
  --brand-accent: #10b981;
}

/* Use in components */
.btn-primary {
  background-color: var(--brand-primary);
}

.badge-success {
  color: var(--brand-accent);
}

.link-secondary {
  color: var(--brand-secondary);
}
```

**Apply dynamically from JavaScript:**
```javascript
document.documentElement.style.setProperty('--brand-primary', colors.brand_primary);
```

---

## Database Storage

Brand colors are stored in the `settings` table:

| key | value | updated_at |
|-----|-------|------------|
| brand_primary | #6d28d9 | 2026-02-07 12:00:00 |
| brand_secondary | #22d3ee | 2026-02-07 12:00:00 |
| brand_accent | #10b981 | 2026-02-07 12:00:00 |

**Schema:**
```sql
CREATE TABLE settings (
  `key` VARCHAR(255) PRIMARY KEY,
  `value` TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## Testing

**Test GET endpoint:**
```bash
curl http://localhost/socialbit-live/public/api/settings/brand-colors
```

**Expected response:**
```json
{"brand_primary":"#6d28d9","brand_secondary":"#22d3ee","brand_accent":"#10b981"}
```

**Test POST endpoint (requires auth):**
```bash
curl -X POST http://localhost/socialbit-live/public/api/settings/brand-colors \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"brand_primary":"#8b5cf6"}'
```

**Expected response:**
```json
{"ok":true,"updated":["brand_primary"]}
```

---

## Integration Checklist for design-lead

- [ ] Fetch brand colors on app initialization
- [ ] Apply colors to CSS custom properties
- [ ] Use CSS variables in all theme-dependent components
- [ ] Create UI for admin to update brand colors (optional)
- [ ] Test color contrast for accessibility (WCAG AA)
- [ ] Document brand color usage in design system

---

## File Locations

**Backend:**
- `src/Controllers/SettingsController.php` - `getBrandColors()`, `updateBrandColors()` methods
- `src/Repositories/SettingsRepository.php` - Database access
- `public/index2.php` - Route registration (lines 215-222)

**Frontend:**
- `public/assets/app.js` - Add brand color fetching logic
- `public/assets/styles.css` - Define CSS custom properties

---

## Related Tasks

- **Task #6:** Integrate brand color settings into new theme (design-lead)
- **Task #5:** Redesign UI with Metricool-style light theme (design-lead)

---

## Notes

- GET endpoint is public (no auth) for easy theme loading
- POST endpoint requires authentication to prevent unauthorized changes
- Default colors are Metricool-inspired (purple, cyan, emerald)
- Colors persist in database across sessions
- Validation ensures only valid hex colors are stored

---

**Last Updated:** 2026-02-07
**Author:** backend-lead
**Status:** Ready for design-lead integration
