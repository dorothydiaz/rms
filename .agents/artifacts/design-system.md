# Design System & UI Specification: Accelerated Shift Schedule Planner

## 1. Design Tokens (Semantic HSL / Modern Design System)

```css
:root {
  /* Surface & Base */
  --surface-canvas: hsl(210, 40%, 98%);
  --surface-card: hsl(0, 0%, 100%);
  --surface-subtle: hsl(210, 40%, 96%);
  --surface-hover: hsl(210, 40%, 93%);
  --surface-border: hsl(215, 25%, 88%);
  --surface-border-subtle: hsl(215, 20%, 92%);

  /* Typography Colors */
  --on-surface-strong: hsl(222, 47%, 11%); /* #0f172a - 14.1:1 contrast */
  --on-surface-medium: hsl(215, 25%, 27%); /* #334155 - 7.5:1 contrast */
  --on-surface-muted: hsl(215, 16%, 47%);  /* #64748b - 4.6:1 contrast (WCAG AA pass) */

  /* Semantic Brands */
  --primary: hsl(265, 89%, 66%);           /* #8b5cf6 / purple brand */
  --primary-hover: hsl(265, 89%, 58%);
  --primary-subtle: hsl(265, 89%, 96%);
  --primary-border: hsl(265, 70%, 82%);

  /* Shift Theme Tokens (All WCAG AA >= 4.5:1 text-on-surface) */
  /* 1. Opening (O) - Emerald */
  --shift-o-bg: hsl(152, 76%, 96%);
  --shift-o-border: hsl(152, 60%, 75%);
  --shift-o-badge: hsl(153, 60%, 35%);
  --shift-o-text: hsl(155, 80%, 18%);
  --shift-o-sub: hsl(153, 60%, 28%);

  /* 2. Mid Day (MD) - Blue */
  --shift-md-bg: hsl(214, 95%, 96%);
  --shift-md-border: hsl(214, 80%, 80%);
  --shift-md-badge: hsl(221, 83%, 53%);
  --shift-md-text: hsl(224, 76%, 18%);
  --shift-md-sub: hsl(221, 70%, 32%);

  /* 3. Late Day (LD) - Amber */
  --shift-ld-bg: hsl(38, 92%, 95%);
  --shift-ld-border: hsl(38, 80%, 75%);
  --shift-ld-badge: hsl(35, 92%, 40%);
  --shift-ld-text: hsl(30, 85%, 20%);
  --shift-ld-sub: hsl(32, 85%, 28%);

  /* 4. Closing (C) - Purple */
  --shift-c-bg: hsl(270, 75%, 96%);
  --shift-c-border: hsl(270, 60%, 80%);
  --shift-c-badge: hsl(271, 76%, 53%);
  --shift-c-text: hsl(273, 76%, 22%);
  --shift-c-sub: hsl(271, 65%, 34%);

  /* 5. Rest Day (OFF) - Slate Neutral */
  --shift-off-bg: hsl(210, 30%, 95%);
  --shift-off-border: hsl(215, 20%, 80%);
  --shift-off-badge: hsl(215, 20%, 45%);
  --shift-off-text: hsl(215, 25%, 25%);
  --shift-off-sub: hsl(215, 16%, 45%);

  /* Destructive / Remove */
  --destructive: hsl(354, 80%, 54%);
  --destructive-hover: hsl(354, 80%, 45%);
}
```

## 2. Fluid Typography Scale (`clamp()`)
- **Title / Header:** `clamp(1.125rem, 1rem + 0.6vw, 1.35rem)` (Outfit / Poppins Semi-Bold)
- **Cell Heading / Name:** `clamp(0.78rem, 0.72rem + 0.25vw, 0.875rem)` (Semi-Bold 600)
- **Shift Pill / Code:** `clamp(0.68rem, 0.64rem + 0.15vw, 0.75rem)` (Bold 700 / Monospace)
- **Time Range / Subtitle:** `clamp(0.62rem, 0.58rem + 0.12vw, 0.6875rem)` (Medium 500)

## 3. 4px / 8px Grid Dimensioning
- **Cell Padding:** `6px 8px` (compact density for 7-day display without excessive scrolling)
- **Cell Min Height:** `68px` (sufficient to display shift badge, time range, clear trigger, or 2x3 preset button matrix)
- **Preset Pill Height:** `24px` with `4px` gap between buttons
- **Border Radius:** `8px` for cards, `5px` for shift pill buttons

## 4. WCAG 2.1 AA Accessibility Standards
- **Color Contrast:** All shift text tokens maintain > 4.5:1 against their respective tinted backgrounds.
- **Focus Rings:** Explicit outline `2px solid var(--primary)` with `2px offset` on interactive buttons.
- **Keyboard Navigation:** Shift preset buttons are keyboard navigable via `Tab` and triggerable via `Enter`/`Space`.
- **ARIA Attributes:**
  - `role="grid"` on table container
  - `aria-label="Assign [Shift Name] to [Employee Name] on [Date]"` on cell quick-buttons
  - `aria-label="Remove shift for [Employee Name] on [Date]"` on unassign button
