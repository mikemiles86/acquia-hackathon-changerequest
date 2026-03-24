# Drupal 11 Reviewable Config Diff

## What we built

We built `agent_config_diff`, a Drupal 11 custom module that converts constrained natural-language site-building requests into **reviewable, staged config proposals**.

The module focuses on common content-model operations for node bundles (field add/update + default form/view display attachment), then outputs explicit planned operations, affected config names, before/after YAML, diff summary, and risk notes.

The core design is governance-first: this is a planning/review tool, not an auto-apply engine.

## Problem addressed

Drupal teams often receive requests in plain English (“make this field required”, “add a Department taxonomy field”). Translating those requests into exact Drupal config objects is repetitive and error-prone. This module makes that translation reviewable and deterministic while preserving Drupal governance and human approval.

## What the agent did

- Scaffolded the Drupal module and DI service architecture.
- Implemented deterministic request parsing and plan generation.
- Implemented staged config building without active-config mutation.
- Implemented diff/risk/export pipeline.
- Implemented admin UI and run history page.
- Added kernel + functional tests.
- Drafted full hackathon documentation package.

## What the human did

- Defined the product scope, constraints, and acceptance criteria.
- Reviewed safety and governance posture.
- Owns final verification in a real Drupal environment and final submission ownership.

## Drupal-in-the-loop

Drupal remains the governor at every step:

- **Permissions**: access is gated by custom permissions.
- **Schema/config conventions**: planner targets only allowlisted Drupal config names.
- **Diff-first workflow**: output is staged and reviewable before any deployment action.
- **Validation and traceability**: warnings/risk levels are explicit; run history is stored.
- **No bypasses**: no auto config import, no hidden direct state mutation, no arbitrary command execution from request text.

## AX artifact(s) shipped

- `AGENTS.md` governance contract.
- Submission validator script: `tools/check_submission.py`.
- Agent run log: `docs/agent-run-log.md`.
- Agent Experience Report: `docs/agent-experience-report.md`.
- Architecture and demo docs for next principals.

## How to run / demo

1. Put module at `modules/custom/agent_config_diff`.
2. Enable module (with core dependencies listed in `agent_config_diff.info.yml`).
3. Grant permission `use agent config diff`.
4. Visit `/admin/config/development/agent-config-diff`.
5. Try request examples:
   - `Add a required Summary field to Article and show it on the default form and view display.`
   - `Add a Department taxonomy field to Article using the Departments vocabulary.`
   - `Change the help text of the Summary field on Article to 'Used in listing pages and teasers.'`

For a guided demo narrative, see `docs/demo-script.md`.

## Validation

What was run in this workspace:

- PHP syntax lint for module PHP files (passed).
- Submission structure validator (added in `tools/check_submission.py`) against this repository.

What was not fully run here:

- Full Drupal kernel/functional execution in a complete Drupal CI/runtime environment.

To validate submission structure locally:

- `python3 tools/check_submission.py .`

## Limits / known issues

- Parser scope is intentionally narrow (MVP deterministic contract).
- Diff renderer is pragmatic (not full semantic YAML diff tooling).
- Node bundle operations only in MVP.
- No automatic config import/apply (by design).

## Links

- architecture: `docs/architecture.md`
- demo script: `docs/demo-script.md`
- DDEV setup runbook: `docs/ddev-demo-setup.md`
- run log: `docs/agent-run-log.md`
- agent experience report: `docs/agent-experience-report.md`
- benchmark task definition: `docs/benchmark-task-definition.md`
- submission draft: `docs/submission-draft.md`
