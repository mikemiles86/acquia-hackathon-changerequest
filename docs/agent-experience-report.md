# Agent Experience Report

## What worked

- The bounded operation contract (`create_field_storage`, `create_field_instance`, `update_field_instance`, `update_form_display`, `update_view_display`) made planning deterministic and reviewable.
- Drupal config naming conventions were strong guardrails for generating explicit, auditable targets.
- A service-oriented architecture (inspector/parser/planner/stager/diff/risk/export) kept responsibilities clear and easy to test.
- The “no auto-apply” requirement was straightforward to enforce with in-memory staging.

## What was confusing

- In a workspace without full Drupal core indexing/runtime, editor diagnostics can over-report unresolved Drupal symbols despite valid PHP syntax.
- Some request phrasing is naturally ambiguous (“summary” vs “text field”), requiring stricter UX clarification patterns than MVP currently provides.
- Display defaults (widget/formatter/weights) are practical but still heuristic in constrained parser mode.

## What the agent could not do

1. **Fully run Drupal kernel/functional test suite in a complete Drupal runtime**
	- what it tried: created kernel and functional tests and ran syntax-level checks
	- where it failed: environment did not include full Drupal test runtime/bootstrap setup for end-to-end execution
	- why it failed: workspace constraints, not module architecture
	- issue class: environment/tooling context gap

2. **Use `xargs` for bulk linting due command policy**
	- what it tried: `find ... | xargs ... php -l`
	- where it failed: command policy denial
	- why it failed: restricted terminal command policy
	- issue class: tool access restriction

## What would make the next run 10× faster

- Provide a prewired Drupal 11 test harness in starter repos for module hackathon entries.
- Publish canonical core examples for field/display config payload fragments by field type.
- Add a reusable Drupal admin diff utility for structured config key-level comparisons.
- Provide starter parser rule packs for common site-building intents (fields, displays, taxonomy refs).
- Include a first-party “submission compliance checklist” in markdown alongside the validator script.

## Where Drupal acted as a useful governor

- **Permissions and admin routes** constrained who could use planning features.
- **Config naming/schema conventions** constrained output into explicit, reviewable objects.
- **Diff/review workflow orientation** kept human approval central.
- **Explicit warnings and risk levels** discouraged silent or unsafe assumptions.
- **Separation of planning from deployment** prevented accidental production mutation.

## Recommended follow-up issue(s)

1. Add first-party Drupal guidance for safe, deterministic NL-to-config planning patterns.
2. Add core docs/examples for default widget/formatter mapping per field type in automated proposal workflows.
3. Create a reusable starter-kit package for Drupal module CI test execution (kernel + functional) for hackathon participants.
