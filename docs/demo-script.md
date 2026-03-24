# Demo Script (3–5 minutes)

## Goal

Show that the agent does real work, while Drupal governs review and approval.

## Setup

- Enable `agent_config_diff`.
- Ensure node types `Article`, `Event`, and optional `Basic page` exist.
- Ensure taxonomy vocabulary `Departments` exists for taxonomy demo.
- Grant yourself `use agent config diff`.
- Optionally grant `view agent config diff history` for the traceability step.

## Demo Day Quickstart

Use this when you have 2–3 minutes before presenting and want a clean launch.

1. Confirm DDEV is running.
2. Generate a one-time login URL to the demo page.
3. Open the URL and run the first two prompts.

Quick commands:

```bash
ddev start
ddev exec vendor/bin/drush --root=/var/www/html/web cr
ddev exec vendor/bin/drush --root=/var/www/html/web uli --uri=[DDEV URL] /admin/config/development/agent-config-diff
```

First two demo prompts:

- `Add a required Summary field to Article and show it on the default form and view display.`
- `Add a Department taxonomy field to Article using the Departments vocabulary.`

If something looks stale, run another cache rebuild (`drush cr`) and refresh once.

## Flow

### 1) Open admin page

Go to:
- `/admin/config/development/agent-config-diff`

Narration:
- “This tool plans config mutations but never imports them automatically.”

### 2) Example A — Combined request

Input:
- `Add a required Summary field to Article and show it on the default form and view display.`

Click submit.

Show:
- Parsed request + confidence
- Planned operations (`create_field_storage`, `create_field_instance`, display updates)
- Affected config names
- Before/after YAML
- Risk and confidence notes

Narration:
- “This is staged and reviewable; active config is untouched.”

### 3) Example B — Taxonomy reference

Input:
- `Add a Department taxonomy field to Article using the Departments vocabulary.`

Show:
- Vocabulary resolution
- Entity reference settings
- Risk note behavior if vocabulary is missing

### 4) Example C — Existing field update

Input:
- `Change the help text of the Summary field on Article to 'Used in listing pages and teasers.'`

Show:
- `update_field_instance` operation only
- Diff localized to description key

### 5) Example D — Unsupported/ambiguous

Input:
- `Create a media type and rebuild all teasers.`

Show:
- Unsupported clause visibility
- High risk when no operations generated

### 6) Run history

Go to:
- `/admin/config/development/agent-config-diff/history`

Show:
- traceable run records (who/when/request/status)

## Close

Message:
- “Drupal stays in control. The agent prepares proposals; humans review and execute deployment workflows.”
