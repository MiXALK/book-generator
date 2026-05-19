# Agent Instructions

This file describes how AI coding agents should work in this repository. It is
tool-agnostic and applies to any assistant that edits or reviews the code.

## Start Here

Before making changes:

1. Read `README.md` for project purpose and operational commands.
2. Read `CONVENTIONS.md` for project conventions and constraints.
3. Inspect the relevant source and tests before deciding on an implementation.
4. Ask for clarification when feature scope, expected behavior, or ownership is unclear.

## Working Rules

- Keep changes minimal and focused on the requested task.
- Preserve existing comments and code style.
- Do not add dependencies, build tools, or configuration files without explicit approval.
- Do not introduce scaffold, demo, placeholder, or unrelated example code.
- Prefer extending existing functionality over duplicating it.
- Update `CONVENTIONS.md` when changing project conventions, structure, dependencies, or architectural decisions.
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
