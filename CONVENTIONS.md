# Project Conventions

`CONVENTIONS.md` serves the same purpose as `project-specs.md`: it is the
authoritative source for project conventions, folder structure, technology
choices, and design decisions.

## Current State

The project does not yet define an application stack, package manager, runtime,
source layout, test framework, or build process. Do not assume one without an
explicit project decision.

## General Rules

- Ask for clarification when requirements or feature scope are unclear.
- Keep diffs minimal and focused on the requested change.
- Do not add scaffold, demo, placeholder, or unrelated example code.
- Do not introduce dependencies or change build/configuration files without
  explicit approval.
- Follow existing naming, indentation, file organization, linting, and formatting
  conventions once they exist.
- Preserve existing comments. Add new comments only for non-obvious logic.
- Prefer shared helpers or existing patterns over duplicated functionality.
- Ensure generated code compiles cleanly and passes available validation checks.

## Documentation Rules

- Update this file when adding or changing conventions, dependencies, folder
  structure, tooling, or architectural decisions.
- Keep documentation concise and specific.
- Prefer durable rules over tool-specific instructions.
- Keep AI-agent workflow guidance in `AGENTS.md`.

## Repository Structure

```text
.
├── AGENTS.md
├── CONVENTIONS.md
├── README.md
└── .gitignore
```

Update this tree when the project gains source code, tests, scripts, or
additional documentation.

## Validation

No validation commands are defined yet. When tooling is introduced, add the
canonical commands to `README.md` and keep this section focused on validation
expectations.
