# Drupal 11 Change Request → Reviewable Config Diff (Native-stack, governance-first MVP)

## Submission metadata

- Submission type: `GitHub Issue`
- Team name: `Mike Miles`
- On-site DrupalCon team lead (required for prize eligibility): `@mikemiles86`
- Submission repository: `https://github.com/mikemiles86/acquia-hackathon-changerequest`

## Short summary

This submission delivers `agent_config_diff`, a Drupal 11 custom module that turns constrained natural-language site-building requests into explicit, staged, reviewable configuration diffs. It is intentionally planning-and-review only: no automatic config import/apply.

## What was built

- Admin planning UI:
  - `/admin/config/development/agent-config-diff`
- Deterministic parser + plan builder for supported node-field/display operations
- In-memory staged config builder using active config as baseline
- Review output with:
  - parsed intent
  - supported/unsupported clauses
  - structured operations
  - affected config names
  - before/after summaries
  - diff output
  - risk/confidence notes
  - export artifacts (plan JSON, staged YAML, markdown)
- Run history page:
  - `/admin/config/development/agent-config-diff/history`

## How it works

1. Parse request into deterministic intents.
2. Convert intents to allowlisted operations.
3. Stage changes in non-live, in-memory config data.
4. Render reviewable diff and risk notes.
5. Persist run trace metadata.

## Drupal-in-the-loop

- Access is permission-gated.
- Planner output is constrained to allowlisted Drupal config operations.
- Proposed changes are staged for review (not applied).
- Risk/warning visibility is explicit for unsupported or ambiguous requests.
- Human review remains required before any deployment workflow.

## How to demo

Use these requests:
1. `Add a required Summary field to Article and show it on the default form and view display.`
2. `Add a Department taxonomy field to Article using the Departments vocabulary.`
3. `Change the help text of the Summary field on Article to 'Used in listing pages and teasers.'`
4. `Make the Event location field required.`
5. `Add a boolean field called Featured to Basic page.`

## What the agent did

- Implemented module scaffold and architecture.
- Built deterministic parser, planner, staging, diff, risk, export, and history services.
- Built admin UI and permission-gated routes.
- Added tests and complete hackathon docs/artifacts.

## What the human did

- Defined product scope and governance constraints.
- Performed final project verification and submission routing.

## AX artifact(s) included

- `AGENTS.md`
- `tools/check_submission.py`
- `docs/agent-run-log.md`
- `docs/agent-experience-report.md`
- `docs/benchmark-task-definition.md`

## Supporting documentation

- README: `README.md`
- Run log: `docs/agent-run-log.md`
- AX report: `docs/agent-experience-report.md`
- Architecture: `docs/architecture.md`
- Demo script: `docs/demo-script.md`

## Known limitations

- Deterministic parser intentionally narrow (MVP scope).
- No direct config import/apply automation.
- Diff renderer is pragmatic/simple.
- Node bundle operations only.

## Future work

- Optional external planner provider integration.
- More entity types and operation families.
- Enhanced interactive clarification UX.
- Config import handoff packaging for deployment workflows.

## Submission readiness checklist

- [x] Meaningful agent-work outcome included
- [x] At least one AX artifact included
- [x] README included
- [x] Agent Run Log included
- [x] Agent Experience Report included
- [x] On-site team lead name/GitHub handle filled

## Final submission note

File the final submission in the official hackathon repository as a GitHub issue or PR using this body, then replace all `TODO` values before posting.
