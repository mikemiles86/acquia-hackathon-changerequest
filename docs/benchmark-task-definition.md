# Benchmark task definition

## Task name
Deterministic NL request to staged Drupal config diff

## Goal
Measure whether an agent can convert constrained natural-language Drupal site-building requests into explicit, safe, reviewable config proposals without mutating active state.

## Starting context
- Drupal 11 site with node bundles and common core modules enabled.
- Custom module target: `agent_config_diff`.
- Request examples include add field, update required/help text, and display placement.

## Allowed tools
- Workspace file read/write/edit tools
- Search/grep tools
- Terminal for lint/test commands
- Drupal core APIs already available in project

## Constraints / guardrails
- No auto config import/apply.
- No arbitrary command execution from user request.
- No unsupported mutation should be silently accepted.
- Keep operations explicit and allowlisted.

## Success criteria
- Parsed request produces explicit operation list.
- Staged before/after config is generated for affected objects.
- Risk/confidence and unsupported clauses are visible.
- Output is reviewable and exportable.
- README/run log/AX report included for submission package.

## Failure conditions
- Writes directly to active config as side effect.
- Hides unsupported clauses or uncertainty.
- No reviewable diff output.
- Missing required submission artifacts.

## Evidence to collect
- Planned operation JSON
- Before/after YAML
- Diff summary
- Risk notes
- Run log with commands/tool calls

## Scoring notes
Prioritize Drupal governance, safety, and reviewer clarity over breadth of operation support.
