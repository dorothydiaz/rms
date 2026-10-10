# [Workflow] — The Adversarial Value Stream & Conflict Matrix

**Mantra**: *"High-quality software is not born from polite consensus; it is forged through constructive friction, rigorous debate, and adversarial verification."*

---

## 1. Operational Overview

The Adversarial Value Stream rejects passive, waterfall handoffs and superficial peer reviews. It organizes the 7 personas into a dialectical gauntlet where software architecture, code quality, accessibility, and security are aggressively pressure-tested before entering production.

```mermaid
flowchart TD
    subgraph S0["STAGE 0: Librarian Context & Memory Gate"]
        LIB["[The Librarian]<br>O(1) Symbol Map + AgentMemory Recall"] --> S1
    end

    subgraph S1["STAGE 1: Discovery & Invariant Gate"]
        BA["[Senior BA]<br>Superpowers Brainstorming & FSM"] --> QA_AUD["[Lead QA] & [Auditor]<br>Boundary & Scale Attack"]
        QA_AUD --> G1{"Invariant Matrix Signed?"}
        G1 -- No --> BA
    end

    subgraph S2["STAGE 2: Architectural & Design Duel"]
        G1 -- Yes --> B_DUEL["Track A: Backend Duel<br>[Dev 1] (Pure FSM) vs [Dev 2] (ACID Pragmatism)"]
        G1 -- Yes --> F_DUEL["Track B: Frontend Duel<br>[FE 1] (OKLCH Luxury) vs [FE 2] (WCAG Ergonomics)"]
        B_DUEL & F_DUEL --> CONTRACT["Locked Shared Schema<br>(Zod / TypeSpec)"]
    end

    subgraph S3["STAGE 3: Concrete Implementation (Superpowers TDD)"]
        CONTRACT --> TDD_RED["[Dev 1] Write Failing Tests (RED)"]
        TDD_RED --> TDD_GREEN["[Dev 2] Write Minimal Code (GREEN)"]
        TDD_GREEN --> TDD_REF["Pair Refactoring & Verification"]
    end

    subgraph S4["STAGE 4: Chaos Crucible & Systematic Debugging"]
        TDD_REF --> CHAOS["[Lead QA]<br>Concurrency, Boundary Fuzzing,<br>Network Throttling"]
        CHAOS --> BUGS{"Blocker (P1) Found?"}
        BUGS -- Yes --> SYS_DEBUG["[Superpowers] Systematic Debugging<br>(Reproduce -> Characterize -> Fix & Verify)"]
        SYS_DEBUG --> S3
    end

    subgraph S5["STAGE 5: Auditor's Guillotine & Memory Persistence"]
        BUGS -- No --> AUDIT["[Auditor]<br>Code Review Checklist,<br>Verification Before Completion"]
        AUDIT --> PASS{"Release Candidate Approved?"}
        PASS -- No --> S3
        PASS -- Yes --> MEM_SAVE["[AgentMemory] Commit Lessons Learned<br>(agentmemory:lesson)"]
        MEM_SAVE --> SHIP["Production Deployment"]
    end
```

---

## 2. Gate Entry & Exit Criteria

### Stage 0: The Librarian Context & Memory Gate
- **Actors**: The Librarian (Persona 0).
- **Actions**:
  - Intercepts incoming prompt and inspects active file buffer, cursor scope, and working tree git diff.
  - Queries `agentmemory` (`recall`) for historical architectural lessons and previous bug post-mortems.
  - Resolves pre-matched symbols against `librarian_index.json` in O(1) time.
- **Exit Artifact**: **Librarian Cartography & Memory Header** with exact symbol lines and historical lessons.

### Stage 1: Discovery & The Invariant Gate
- **Actors**: Senior BA, Lead QA, External Auditor.
- **Entry Trigger**: Raw user request, feature ticket, or product requirement.
- **Actions**:
  - Senior BA executes the 10-Step Translation Engine using `superpowers:brainstorming` and `superpowers:writing-plans`.
  - Lead QA probes with extreme boundary values (negative integers, max safe ints, unicode).
  - Auditor inspects data sources of truth and RBAC entitlements.
- **Exit Artifact**: Signed **Data Invariant & Boundary Matrix** and **Failure & Constraint Profile**. No ticket enters development without QA and Auditor sign-off.

### Stage 2: Architectural & Design Duel
- **Track A (Backend Clash)**:
  - Backend Dev 1 (Logic Master) proposes pure Hexagonal domain model and FSM statecharts.
  - Backend Dev 2 (Pragmatic Builder) challenges operational complexity, proposing single-instance PostgreSQL transactions with `SKIP LOCKED`.
  - *Compromise*: Clean architecture domain logic coupled with boring, rock-solid Postgres primitives.
- **Track B (Frontend Duel)**:
  - Frontend Dev 1 (Modern UI) drafts custom tokens, OKLCH colors, spring motion, and spatial depth.
  - Frontend Dev 2 (Modern UX) audits against Fitts's Law, WCAG 2.2 AAA contrast, keyboard navigation, and mobile GPU compositing budgets.
- **Exit Artifact**: Immutable shared API Contract Schema (Zod / TypeSpec) locked between backend and frontend.

### Stage 3: Concrete Implementation (Superpowers TDD Iron Law)
- **The Iron Rule**: NO production code without a failing test first.
  - **RED**: Backend Dev 1 writes automated tests and verifies they fail for the expected reason.
  - **GREEN**: Backend Dev 2 writes the minimal production code, queries, and migrations to turn the tests green.
  - **REFACTOR**: Code is optimized for clarity, speed, and maintainability without altering public behavior.
- **Frontend Implementation**:
  - Frontend Dev 1 & 2 pair-program: Frontend Dev 2 structures semantic HTML, ARIA landmarks, and focus rings; Frontend Dev 1 layers in micro-borders, inset shadows, and physics-based springs.
- **Exit Artifact**: Fully integrated, runnable feature code with verified green test suite and zero `TODO` comments.

### Stage 4: The Chaos Crucible & Systematic Debugging
- **Actors**: Lead QA.
- **Actions**:
  - Launches 50–100 simultaneous concurrent mutation requests to detect race conditions and double-debits.
  - Throttles network to Slow 3G with 2% packet loss and measures INP / LCP / CLS.
  - Executes keyboard-only navigation end-to-end and tests 200% system font scaling.
- **Systematic Debugging Protocol**: When defects arise, follow `superpowers:systematic-debugging` (Reproduce -> Characterize -> Hypothesize -> Fix & Verify). Shotgun fixes and speculative edits are barred.
- **Exit Artifact**: Clean verification report. If a P1 blocker is logged, feature work stops immediately until resolved.

### Stage 5: The Auditor's Guillotine & Memory Persistence
- **Actors**: External Systems Auditor.
- **Actions**:
  - Conducts line-by-line inspection of all modified files using `superpowers:requesting-code-review` and `receiving-code-review` checklists.
  - Enforces `superpowers:verification-before-completion`: Evidence of test suite execution is mandatory.
  - Checks for parameter pollution, unvalidated DTO perimeters, infinite money glitches (`balance - (-amount)`), database-level unique indexes, and fake interactive HTML elements.
  - **Persistent Memory Commitment**: Stumps verified domain lessons and invariant post-mortems into `agentmemory:lesson`.
- **Exit Artifact**: Production sign-off and persisted architectural memory record.

---

## 3. Conflict Resolution Matrix (The Hierarchy of Values)

When technical or design debates stall, the team strictly enforces this 5-tier resolution hierarchy:

| Priority | Value Domain | Deciding Personas | Loses To / Yields |
| :---: | :--- | :--- | :--- |
| **1** | **Data Integrity & Security** | `[Auditor]` & `[Backend Dev 1]` | Wins over speed, convenience, and feature velocity |
| **2** | **System Stability & Operational Simplicity** | `[Backend Dev 2]` & `[Lead QA]` | Wins over theoretical microservice or distributed complexity |
| **3** | **Usability & Deep Accessibility** | `[Frontend Dev 2]` | Wins over aesthetic flair and decorative animations |
| **4** | **Visual Distinction & Brand Luxury** | `[Frontend Dev 1]` | Wins over generic bootstrap/framework templates |
| **5** | **Theoretical Architectural Purity** | `[Backend Dev 1]` yields | Yields to pragmatic maintenance and operational MTTR |
