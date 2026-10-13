---
name: ux-skill
description: Use when designing user interfaces, auditing UI/UX layouts, choosing color palettes, typography, or components, eliminating AI slop, running design linting, or synthesizing design systems.
---

# ux-skill: The Design Intelligence Engine

A deterministic design-intelligence engine that eliminates generic "AI slop" and generates professional, brand-true UI/UX systems. Powered by the `uxskill` Python package and data manifests, it operates 100% offline without external LLMs.

---

## 1. Quick CLI Reference

`uxskill` is installed locally via Python/pip and available on PATH (`uxskill` or `ux`):

```bash
# Verify inventory & manifest counts
uxskill stats

# Run 5-parallel-search recommendation
uxskill recommend --industry="enterprise-erp" --tone="balanced" --stack="blade"

# Run 7-axis deterministic synthesizer
uxskill synthesize --brief "Restaurant Management ERP warehouse and inventory module"

# Run deterministic anti-AI-slop linter (153 rules)
uxskill lint resources/views/

# Generate portable DESIGN.md specification
uxskill design-md

# Auto-iterating evolve loop (lint -> polish -> re-lint)
uxskill evolve
```

---

## 2. Core Architecture: The 7-Axis Synthesizer

Rather than picking static templates from a catalogue, `uxskill` maps briefs along 7 deterministic axes:

1. **Warmth**: Cool (tech/corporate) ↔ Warm (human/hospitality)
2. **Contrast**: Quiet (low-contrast, subtle) ↔ Loud (high-contrast, bold accents)
3. **Density**: Compact (data-dense enterprise tables) ↔ Airy (spacious consumer landing)
4. **Geometry**: Sharp (0-2px radius, industrial) ↔ Organic (12-24px rounded corners)
5. **Formality**: Technical/Corporate ↔ Playful/Approachable
6. **Motion**: Functional/Snappy (100-150ms) ↔ Expressive/Fluid (300-450ms spring)
7. **Type Personality**: Utilitarian/Mono ↔ Editorial/Display Serif ↔ Geometric Sans

### Axis Interaction Invariants:
- **Dense + Corporate**: Spacing ≤ 8px, border-radius ≤ 4px (Bloomberg/Enterprise School).
- **Airy + Corporate**: Spacing ≥ 16px, border-radius 8-12px (Luxury/Executive).
- **Industrial ERP**: Strict high density, high data scannability, tactile borders, clear focus rings, zero gratuitous animations.

---

## 3. Data Manifests Inventory (`data/*.json`)

The skill ships with 12 structured queryable JSON databases inside `.agents/skills/ux-skill/data/`:

| Manifest | Count | Purpose |
|---|---|---|
| `styles.json` | 84 | Curated UI styles with when-to-use, skip rules, and design tokens |
| `palettes.json` | 176 | WCAG contrast-verified light and dark mode palettes |
| `type-pairs.json` | 70 | Display × Body × Monospace typographic pairings |
| `components.json` | 148 | Component anatomies, responsive states, tokens, and motion specs |
| `industries.json` | 184 | Industry-specific design rules and audience ergonomics |
| `chart-types.json` | 35 | Chart visualizations with encoding and data suitability guidelines |
| `tech-stacks.json` | 25 | Framework-specific best practices (Blade, Tailwind, React, etc.) |
| `ux-guidelines.json` | 112 | Classical UX laws (Fitts, Hick, Miller, Jakob, Peak-End rule) |
| `motion-presets.json` | 57 | Stack-ready transition and micro-interaction timing curves |
| `anti-patterns.json` | 153 | Deterministic anti-AI-slop rules checked by the linter |
| `landing-patterns.json` | 40 | Conversion and landing layout architectures |
| `brands/*.json` | 160 | Real-world brand design specifications (Stripe, Linear, Apple, etc.) |

---

## 4. Anti-AI-Slop Guardrails (The 153 Rules)

Never generate generic AI design tropes:

1. **Forbidden Color Tropes**:
   - ❌ Soft purple-to-blue linear gradients on hero sections or cards.
   - ❌ Neon glowing borders without context.
   - ❌ Low-contrast gray text on dark gray panels (`#777` on `#222`).
2. **Forbidden Layout Tropes**:
   - ❌ Centered hero with 3 identical-width feature cards beneath.
   - ❌ Bouncing chevron scroll arrows.
   - ❌ Full viewport width (`100vw`) causing horizontal scrollbars on Windows.
3. **Forbidden Typography Tropes**:
   - ❌ Unstyled Inter / Roboto for display titles without intentional tracking/leading.
   - ❌ Generic dummy copy ("Lorem Ipsum", "John Doe", "Jane Smith"). Use real domain nomenclature.
4. **Mandatory UI Excellence**:
   - ✅ Distinct visual hierarchy: H1 (24-32px), H2 (18-20px), Body (13-14px), Caption (11-12px).
   - ✅ 4px / 8px dimensional spacing grid.
   - ✅ High-density responsive data tables wrapped in `overflow-x: auto`.
   - ✅ Clear keyboard focus rings (`:focus-visible`) and WCAG 2.1 AA 4.5:1 contrast.
   - ✅ Micro-interactions under 150ms for snappy enterprise UX.

---

## 5. Workflow Guide: How to Apply

When designing, updating, or reviewing any frontend interface:

1. **Capture Intent & Requirements**:
   - Run `uxskill recommend --industry=<industry> --tone=<tone>` or query `data/` directly.
2. **Consult Component Anatomies**:
   - Check `data/components.json` for proper state handling (hover, focus, active, disabled, loading, empty, error).
3. **Enforce Cohesive Design Tokens**:
   - Apply semantic color tokens (`--primary`, `--surface`, `--on-surface`, `--destructive`, `--border`).
   - Use CSS custom properties for spacing and elevation.
4. **Verify with the Linter**:
   - Run `uxskill lint <target-dir>` to catch anti-patterns before finalizing code.
   - Address any `critical` or `high` findings immediately.
