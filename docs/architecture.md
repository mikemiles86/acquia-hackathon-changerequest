# Architecture — Agent Config Diff (Drupal 11)

## Design intent

Build a native-stack planning engine that maps constrained natural language to explicit Drupal config proposals.

## Service boundaries

- `SiteTopologyInspector`
  - Reads bundle, field, vocabulary, and display topology from Drupal APIs.
- `RequestNormalizer`
  - Canonicalizes request text.
- `SupportedIntentParser`
  - Deterministic clause parser for MVP operation set.
- `ChangePlanBuilder`
  - Produces explicit operations + affected config + warnings.
- `StagedConfigBuilder`
  - Builds in-memory staged before/after arrays (no active writes).
- `DiffRenderer`
  - Produces key-level and line-level review diffs.
- `RiskAnalyzer`
  - Severity scoring and risk notes.
- `ExportArtifactBuilder`
  - Emits JSON/YAML/Markdown outputs.
- `RunHistoryRepository`
  - Persists lightweight run traces.

## Parsing model

Deterministic rules + heuristics:
- Clause splitting by `and`.
- Pattern matching for:
  - add field
  - required/optional updates
  - help text updates
  - display placement
  - taxonomy vocabulary references
- Explicit unsupported clause capture.

No unconstrained open-ended generation in MVP.

## Config staging model

Input:
- Active config for targeted object names.

Process:
1. For each operation, read active object raw data.
2. Merge allowlisted keys into staged copy.
3. Keep deterministic ordering via recursive sort.
4. Emit old/new YAML and structured diff.

Output:
- `staged[config_name] = { status, before, after, old_yaml, new_yaml }`

Guarantee:
- No import; no active config mutation.

## Risk model

Severity:
- `low`: clean supported run.
- `medium`: warnings/uncertainty.
- `high`: unsupported clauses or no executable operations.

Typical risk triggers:
- bundle missing
- vocabulary missing
- unsupported request clauses
- field collision

## Persistence model

Table: `agent_config_diff_run`

Columns:
- run id
- uid
- created
- raw request
- normalized request
- plan JSON
- risk JSON
- status

Used for demo traceability and governance evidence.

## Extension strategy

Planner contract in `src/Plugin/Planner` allows optional future providers:
- deterministic adapter (current)
- null AI placeholder
- future external planner provider (optional)

Guardrails for future planners:
- must return explicit operations
- must provide confidence/risk metadata
- must preserve no-auto-apply governance
