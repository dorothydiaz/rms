# Design System & UI/UX Specification: Purchase Order Redesign

## 1. Semantic Design Tokens (OKLCH / HSL Aligned with RFQ Module)
- `--po-primary`: `#9333ea` (Purple-600 primary brand accent) / `--po-primary-dark`: `#7e22ce`
- `--po-teal`: `#0d9488` (Fulfillment & positive settlement accent)
- `--po-surface`: `#ffffff` (Light) / `#1e293b` (Dark `[data-theme="dark"]`)
- `--po-on-surface`: `#0f172a` (Light) / `#f8fafc` (Dark)
- `--po-border`: `#cbd5e1` / `--po-border-subtle`: `#e2e8f0`
- `--po-danger`: `#dc2626` / `--po-warning`: `#d97706` / `--po-success`: `#059669`

## 2. Stage-Theme Lifecycle Bar Tokens
- **Stage 1 (`stage-theme-draft`):** Slate/Purple neutral drafting palette (`#64748b` / `#9333ea`).
- **Stage 2 (`stage-theme-approval`):** Amber review palette (`#d97706` / `#fef3c7`).
- **Stage 3 (`stage-theme-issued`):** Blue/Indigo active dispatch palette (`#2563eb` / `#dbeafe`).
- **Stage 4 (`stage-theme-approved` / `stage-theme-fulfilled` / `stage-theme-refused`):** Emerald (`#059669`) for Issued/Fulfilled or Crimson (`#dc2626`) for Refused/Cancelled.

## 3. Dimensional Grid & Ergonomics (WCAG 2.1 AA / 2.2 AAA)
- **Top Lifecycle Bar (`#poLifecycleStepperWrap`):** Flex row with Left Action Buttons (`gap: 8px`) and Right 4-Step Chevron Pipeline (`#poLifecycleProgressBar`), followed by `.po-stage-helper-ribbon`.
- **Top Banner Card (`.po-top-banner-card`):** 100% full width, non-wrapping identity strip (`.po-identity-ribbon-strip`) + 2-row responsive operational grid (`.po-operational-grid`).
- **70/30 Split Body (`.po-builder-split-body`):** CSS Grid `minmax(0, 7fr) minmax(340px, 3fr)` on desktop (`gap: 14px`), collapsing gracefully below `1200px`.
- **Tabular Figures:** All currency (`₱`), quantities, and variance percentages enforce `font-variant-numeric: tabular-nums`.
- **Custom UOM Dropdown:** Uses `window.UomRegistry` (`30%` monospace code pill / `70%` unit name with `?` conversion formula tooltip).
