---
name: devteam
description: "Executes the 5-persona zero-hallucination software development pipeline: Translator -> PM -> UI/UX -> Full Stack Dev -> Auditor. Trigger via /Devteam."
triggers:
  - "/Devteam"
  - "/devteam"
---

# `/Devteam` Orchestrator Skill

When `/Devteam` is invoked, execute the 5-persona pipeline sequentially using `.agents/state.md` as the blackboard. Do not jump to coding until Persona 1 and Persona 2 have approved the specification.

## Pipeline Lifecycle

```text
[User Input via /Devteam]
│
▼
[Phase 1: Translator] ──► Sanitizes intent & freezes scope into prompt_spec.md
│
▼
[Phase 2: PM / BA] ──► Generates PRD.md with Gherkin criteria & schema.md
├─────────────────────────┐
▼                         ▼
[Phase 3: UI/UX Designer] [Phase 4: Full Stack Dev]
(Design Tokens & Layout)  (Doc-grounded Research, Tradeoffs, TDD)
└───────────┬─────────────┘
            ▼
[Phase 5: Auditor Gatekeeper]
├─────────────────────────┐
▼ (Issues Found)          ▼ (Clean Audit)
[Loopback to Dev / UI]    [Final Output / PR Ready]
```

## Execution Protocol

1. **Initialize State:** Clear or reset `.agents/state.md` with the raw user input and set `current_phase: 1_TRANSLATOR`.
2. **Execute Phase 1 (Translator):** Read raw prompt, eliminate ambiguity, produce `prompt_spec.md`. Update `state.md`.
3. **Execute Phase 2 (Project Manager):** Read `prompt_spec.md`, produce `PRD.md` with Gherkin acceptance criteria and `schema.md`. Update `state.md`.
4. **Execute Phase 3 & 4 (UI/UX & Developer in Tandem):**
   - UI/UX generates `design-system.md` with typography, color tokens, and WCAG criteria.
   - Developer performs grounded library research, documents pros/cons, writes failing tests first (TDD), then writes implementation.
5. **Execute Phase 5 (Auditor):** Runs dependency verification, schema validation, and test runner.
   - If `AUDIT_STATUS: REJECTED`: writes diff back to Developer/UI agent for automated remediation.
   - If `AUDIT_STATUS: APPROVED`: summarizes the build and presents the final walkthrough to the user.
