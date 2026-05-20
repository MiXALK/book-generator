# Agent Instructions

This file describes how AI coding agents should work in this repository. It is
tool-agnostic and applies to any assistant that edits or reviews the code.

## Start Here

Before making changes:

1. Read `README.md` for project purpose and operational commands.
2. Read `CONVENTIONS.md` for project conventions and constraints.
3. Read `ROADMAP.md` when the task affects product scope, architecture, or delivery stages.
4. Inspect the relevant source and tests before deciding on an implementation.

## Core Behavior

### 1. Think Before Coding

**Don't assume. Don't hide confusion. Surface tradeoffs.**

Before implementing:

- State your assumptions explicitly. If uncertain, ask.
- If multiple interpretations exist, present them - don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- If something is unclear, stop. Name what's confusing. Ask.

### 2. Simplicity First

**Minimum code that solves the problem. Nothing speculative.**

- No features beyond what was asked.
- No abstractions for single-use code.
- No "flexibility" or "configurability" that wasn't requested.
- No error handling for impossible scenarios.
- If you write 200 lines and it could be 50, rewrite it.

Ask yourself: "Would a senior engineer say this is overcomplicated?" If yes,
simplify.

### 3. Surgical Changes

**Touch only what you must. Clean up only your own mess.**

When editing existing code:

- Don't "improve" adjacent code, comments, or formatting.
- Don't refactor things that aren't broken.
- Match existing style, even if you'd do it differently.
- If you notice unrelated dead code, mention it - don't delete it.

When your changes create orphans:

- Remove imports/variables/functions that YOUR changes made unused.
- Don't remove pre-existing dead code unless asked.

The test: Every changed line should trace directly to the user's request.

### 4. Goal-Driven Execution

**Define success criteria. Loop until verified.**

Transform tasks into verifiable goals:

- "Add validation" -> "Write tests for invalid inputs, then make them pass"
- "Fix the bug" -> "Write a test that reproduces it, then make it pass"
- "Refactor X" -> "Ensure tests pass before and after"

For multi-step tasks, state a brief plan:

```text
1. [Step] -> verify: [check]
2. [Step] -> verify: [check]
3. [Step] -> verify: [check]
```

Strong success criteria let you loop independently. Weak criteria ("make it
work") require constant clarification.

These guidelines are working if: fewer unnecessary changes in diffs, fewer
rewrites due to overcomplication, and clarifying questions come before
implementation rather than after mistakes.

## Working Rules

- Do not add dependencies, build tools, or configuration files without explicit approval.
- Prefer extending existing functionality over duplicating it.
- Update `CONVENTIONS.md` when changing project conventions, structure, dependencies, or architectural decisions.
- Update `ROADMAP.md` when changing planned product stages, MVP scope, or delivery order.
- Never commit secrets, credentials, local environment files, or generated private artifacts.

## Validation

Run the narrowest useful checks after editing. If no commands are defined yet,
state that validation could not be run because project tooling has not been
introduced.

When adding tooling later, document the canonical commands in `README.md`.

## Git Safety

- Do not create commits unless explicitly requested.
- Do not rewrite history or run destructive git commands unless explicitly approved.
- Treat uncommitted changes as user work unless you created them in the current task.

