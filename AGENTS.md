# AGENTS.md — Governance Contract for Agent Config Diff

## Purpose

`agent_config_diff` is a planning-and-review layer. It is not an auto-apply engine.

## Allowed actions

The system may:
1. Read active Drupal config and entity topology for supported surfaces.
2. Parse natural-language requests using deterministic allowlisted rules.
3. Produce structured operations for supported field/display operations.
4. Build staged (non-active) before/after config representations.
5. Render diffs, risk notes, and export artifacts.
6. Persist run metadata for traceability.

## Disallowed actions

The system must not:
1. Import config automatically.
2. Write proposed config into active storage as execution side effects.
3. Execute arbitrary code or shell commands from user text.
4. Perform unsupported mutations silently.
5. Pretend unsupported clauses are supported.

## Supported operation contract (MVP)

Operations are explicit and bounded:
- `create_field_storage`
- `create_field_instance`
- `update_field_instance`
- `update_form_display`
- `update_view_display`

Targets are bounded:
- Node bundles only.
- Config names:
  - `field.storage.node.FIELD_NAME`
  - `field.field.node.BUNDLE.FIELD_NAME`
  - `core.entity_form_display.node.BUNDLE.default`
  - `core.entity_view_display.node.BUNDLE.default`

## Safe extension points

Future providers can be attached through planner contract files in:
- `src/Plugin/Planner/PlannerInterface.php`
- `src/Plugin/Planner/DeterministicPlanner.php`
- `src/Plugin/Planner/NullAiPlanner.php`

Rules for extensions:
- Must return explicit operations and uncertainty metadata.
- Must keep review gate and no-auto-apply behavior.
- Must preserve risk analysis and unsupported clause visibility.

## Review expectations

Human reviewer should verify:
1. Intent accuracy.
2. Field type selection.
3. Target bundle/vocabulary resolution.
4. Display placement choices.
5. Any medium/high risk note.
6. Final import/deploy workflow outside this module.

## Security guardrails

- Access controlled by custom permission.
- Strict deterministic parser for MVP.
- No raw instruction execution.
- No auto-deployment.
