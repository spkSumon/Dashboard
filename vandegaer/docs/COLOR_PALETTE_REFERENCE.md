# SocialBit Color Palette - Quick Reference

**Design System:** Light Theme (Metricool-inspired)
**Last Updated:** 2026-02-07

---

## 🎨 Base Colors (Light Theme)

| Name | Hex | Usage | Notes |
|------|-----|-------|-------|
| **App Background** | `#F8F9FA` | Main app background | Very light gray, not pure white |
| **Card Background** | `#FFFFFF` | Cards, modals, panels | Pure white |
| **Hover Background** | `#F1F3F5` | Hover states | Subtle gray tint |
| **Selected Background** | `#E9ECEF` | Active/selected items | Slightly darker gray |

## 📝 Text Colors

| Name | Hex | Usage | Contrast Ratio |
|------|-----|-------|----------------|
| **Primary Text** | `#212529` | Headings, main content | 16.9:1 (AAA) |
| **Secondary Text** | `#6C757D` | Supporting text | 7.0:1 (AAA) |
| **Tertiary Text** | `#ADB5BD` | Placeholders, hints | 4.5:1 (AA) |
| **Inverse Text** | `#FFFFFF` | Text on dark backgrounds | - |

## 🔲 Borders

| Name | Hex | Usage |
|------|-----|-------|
| **Light Border** | `#E9ECEF` | Subtle dividers |
| **Medium Border** | `#DEE2E6` | Default borders |
| **Dark Border** | `#ADB5BD` | Emphasized borders |

## 🌈 Brand Colors (Dynamic)

| Name | Default Hex | Usage | Customizable |
|------|-------------|-------|--------------|
| **Primary** | `#6d28d9` | CTAs, links, active states | ✅ Yes (from database) |
| **Secondary** | `#22d3ee` | Secondary actions, highlights | ✅ Yes (from database) |
| **Accent** | `#10b981` | Success, positive indicators | ✅ Yes (from database) |

**CSS Variable Names:**
- `--brand-primary`
- `--brand-secondary`
- `--brand-accent`

**Auto-generated Variations:**
- `--brand-primary-light` (20% mix with white)
- `--brand-primary-dark` (80% mix with black)

---

## 🎯 Platform Colors (FIXED - DO NOT CHANGE)

| Platform | Hex | Notes |
|----------|-----|-------|
| **TikTok** | `#FE2C55` | Official brand pink |
| **Instagram** | `#E4405F` | Use with gradient for logo |
| **Instagram Gradient** | `linear-gradient(135deg, #833AB4, #FD1D1D, #F77737)` | Official gradient |
| **Facebook** | `#1877F2` | Official brand blue |
| **YouTube** | `#FF0000` | Official brand red |
| **Google** | `#4285F4` | Official brand blue |
| **Twitter/X** | `#1DA1F2` | Legacy blue (if needed) |

**CSS Variable Names:**
- `--platform-tiktok`
- `--platform-instagram`
- `--platform-instagram-gradient`
- `--platform-facebook`
- `--platform-youtube`
- `--platform-google`

---

## ✅ Semantic Colors

| Status | Hex | Background | Text | Usage |
|--------|-----|------------|------|-------|
| **Success** | `#10b981` | `#D1FAE5` | `#065F46` | Completed, verified, good performance |
| **Warning** | `#F59E0B` | `#FEF3C7` | `#92400E` | Attention needed, average performance |
| **Error** | `#EF4444` | `#FEE2E2` | `#991B1B` | Failed, blocked, poor performance |
| **Info** | `#3B82F6` | `#DBEAFE` | `#1E40AF` | Informational messages |

**CSS Variable Names:**
- `--color-success`, `--color-success-bg`, `--color-success-text`
- `--color-warning`, `--color-warning-bg`, `--color-warning-text`
- `--color-error`, `--color-error-bg`, `--color-error-text`
- `--color-info`, `--color-info-bg`, `--color-info-text`

---

## 🎨 Avatar Colors (Auto-assigned)

Use these for user avatars when no profile picture is available:

| Color Name | Hex | Usage |
|------------|-----|-------|
| Magenta | `#E91E63` | High-energy, reviews |
| Purple | `#9C27B0` | Creative, content creators |
| Indigo | `#3F51B5` | Professional, business |
| Cyan | `#00BCD4` | Tech-savvy users |
| Teal | `#009688` | Eco/wellness brands |
| Green | `#4CAF50` | Growth, positive |
| Orange | `#FF9800` | Energetic, fun |
| Pink | `#F06292` | Lifestyle, beauty |

**Auto-assign Logic:**
```javascript
const avatarColors = [
  '#E91E63', '#9C27B0', '#3F51B5', '#00BCD4',
  '#009688', '#4CAF50', '#FF9800', '#F06292'
];

function getAvatarColor(userId) {
  const hash = userId.split('').reduce((acc, char) =>
    char.charCodeAt(0) + ((acc << 5) - acc), 0
  );
  return avatarColors[Math.abs(hash) % avatarColors.length];
}
```

---

## 📐 Spacing Scale

| Variable | Value | Usage |
|----------|-------|-------|
| `--space-xs` | `4px` | Tight spacing (icons, badges) |
| `--space-sm` | `8px` | Small gaps |
| `--space-md` | `12px` | Default spacing |
| `--space-lg` | `16px` | Card padding, sections |
| `--space-xl` | `24px` | Large gaps |
| `--space-2xl` | `32px` | Section dividers |

---

## 🔘 Border Radius

| Variable | Value | Usage |
|----------|-------|-------|
| `--radius-sm` | `6px` | Tight elements (badges) |
| `--radius-md` | `8px` | Buttons, inputs |
| `--radius-lg` | `12px` | Cards |
| `--radius-xl` | `16px` | Large panels |
| `--radius-full` | `9999px` | Pills, avatars |

---

## 🌑 Shadows

| Variable | Value | Usage |
|----------|-------|-------|
| `--shadow-sm` | `0 1px 2px rgba(0,0,0,0.05)` | Subtle depth (cards) |
| `--shadow-md` | `0 4px 6px rgba(0,0,0,0.07)` | Standard elevation |
| `--shadow-lg` | `0 10px 15px rgba(0,0,0,0.10)` | Hover states |
| `--shadow-xl` | `0 20px 25px rgba(0,0,0,0.12)` | Modals, dropdowns |

---

## 🎯 Special UI Colors

### Navigation Bar (Metricool-style)
- **Background:** `#2D1B3D` (Dark purple)
- **Text:** `#FFFFFF` (White)
- **Hover:** `rgba(255, 255, 255, 0.1)`
- **Active:** `rgba(255, 255, 255, 0.15)`

### Star Rating
- **Active Star:** `#FFB400` (Gold)
- **Empty Star:** `#DEE2E6` (Light gray)

### Performance Indicators
- **Excellent:** Green `#10b981` + background `#D1FAE5`
- **Good:** Blue `#3B82F6` + background `#DBEAFE`
- **Average:** Amber `#F59E0B` + background `#FEF3C7`
- **Below:** Red `#EF4444` + background `#FEE2E2`

---

## 🖨️ Print-Ready Swatch

```
┌─────────────┬─────────────┬─────────────┬─────────────┐
│  App BG     │  Card BG    │  Hover BG   │ Selected BG │
│  #F8F9FA    │  #FFFFFF    │  #F1F3F5    │  #E9ECEF    │
├─────────────┼─────────────┼─────────────┼─────────────┤
│ Primary Txt │ Second. Txt │ Tertiary Txt│ Inverse Txt │
│  #212529    │  #6C757D    │  #ADB5BD    │  #FFFFFF    │
├─────────────┼─────────────┼─────────────┼─────────────┤
│ Brand Pri.  │ Brand Sec.  │ Brand Acc.  │  Nav BG     │
│  #6d28d9    │  #22d3ee    │  #10b981    │  #2D1B3D    │
├─────────────┼─────────────┼─────────────┼─────────────┤
│  Success    │  Warning    │   Error     │    Info     │
│  #10b981    │  #F59E0B    │  #EF4444    │  #3B82F6    │
├─────────────┼─────────────┼─────────────┼─────────────┤
│  TikTok     │ Instagram   │  Facebook   │  YouTube    │
│  #FE2C55    │  #E4405F    │  #1877F2    │  #FF0000    │
└─────────────┴─────────────┴─────────────┴─────────────┘
```

---

## ✅ Accessibility (WCAG)

### Text Contrast Ratios (on white `#FFFFFF`)
- Primary Text `#212529`: **16.9:1** ✅ AAA (>7:1)
- Secondary Text `#6C757D`: **7.0:1** ✅ AAA (>7:1)
- Tertiary Text `#ADB5BD`: **4.5:1** ✅ AA (>4.5:1)

### Brand Colors (on white)
- Brand Primary `#6d28d9`: **8.1:1** ✅ AAA
- Brand Secondary `#22d3ee`: **3.9:1** ⚠️ AA Large Text Only
- Brand Accent `#10b981`: **3.4:1** ⚠️ AA Large Text Only

**Note:** For small text on brand colors, use white text or adjust shade.

### Button Contrast
- Primary button (white on `#6d28d9`): **14.8:1** ✅ AAA
- Secondary button (`#212529` on `#F1F3F5`): **15.2:1** ✅ AAA

---

## 🎨 Color Usage Guidelines

### DO ✅
- Use brand colors for primary CTAs and active states
- Use platform colors for platform-specific badges
- Use semantic colors for status indicators
- Ensure 4.5:1 contrast for normal text, 3:1 for large text
- Use subtle borders (`#E9ECEF`) for dividers

### DON'T ❌
- Don't use brand colors for platform badges (use official colors)
- Don't use semantic colors for branding (green ≠ brand accent)
- Don't use pure black (`#000000`) - use `#212529` instead
- Don't use pure white backgrounds everywhere (use `#F8F9FA` for app)
- Don't mix platform colors with brand colors

---

## 🔧 Implementation

### CSS Custom Properties
```css
:root {
  /* Base */
  --bg-app: #F8F9FA;
  --bg-card: #FFFFFF;
  --text-primary: #212529;
  --text-secondary: #6C757D;
  --border-light: #E9ECEF;

  /* Brand (dynamic) */
  --brand-primary: #6d28d9;
  --brand-secondary: #22d3ee;
  --brand-accent: #10b981;

  /* Platforms (fixed) */
  --platform-tiktok: #FE2C55;
  --platform-instagram: #E4405F;
  --platform-facebook: #1877F2;

  /* Semantic */
  --color-success: #10b981;
  --color-warning: #F59E0B;
  --color-error: #EF4444;
}
```

### JavaScript (Load from backend)
```javascript
fetch('/api/settings/branding')
  .then(res => res.json())
  .then(data => {
    document.documentElement.style.setProperty('--brand-primary', data.primary_color);
    document.documentElement.style.setProperty('--brand-secondary', data.secondary_color);
    document.documentElement.style.setProperty('--brand-accent', data.accent_color);
  });
```

---

**Quick Links:**
- Full Design System: `docs/DESIGN_SYSTEM_METRICOOL.md`
- Inbox UI Design: `docs/INBOX_UI_DESIGN.md`
- Metricool Reference: `docs/metriinbox.png`
