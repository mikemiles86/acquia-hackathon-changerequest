# DDEV Demo Setup Runbook (Drupal 11)

## Purpose

This runbook documents how to set up and run the hackathon demo for `agent_config_diff` on a Drupal 11 site in DDEV.

It is designed to be reproducible, reviewable, and aligned with the submission safety model.

## Environment assumptions

- DDEV is installed and available as `ddev`.
- Docker is running.
- Project root is this repository.

## Executed setup plan

1. Initialize and start DDEV for a Drupal 11 project with `web` docroot.
2. Provision Drupal 11 codebase using Composer.
3. Wire the custom module from submission source into Drupal docroot.
4. Install Drupal site and enable required modules.
5. Seed demo prerequisites (Event type, Departments vocabulary, Event location field).
6. Rebuild caches and verify demo route availability.

## What was configured

- DDEV config created in `.ddev/config.yaml`.
- Drupal 11 recommended project dependencies installed at repo root (`composer.json`, `vendor/`, `web/`).
- Custom module exposed at:
  - `web/modules/custom/agent_config_diff` (symlink to `modules/custom/agent_config_diff`)
- Drush added for local automation.

## Demo-ready URLs

- Site base URL:
  - `https://acquia-hackathon-changerequest.ddev.site`
- Agent Config Diff admin page:
  - `/admin/config/development/agent-config-diff`
- Run history page:
  - `/admin/config/development/agent-config-diff/history`

## Demo login

Use Drush to generate a one-time login link to the demo page:

- destination: `/admin/config/development/agent-config-diff`

(One-time links expire; generate a fresh link when needed.)

## Seeded prerequisites for demo prompts

The setup created/ensured:

- Node bundle: `event`
- Taxonomy vocabulary: `departments`
- Event field: `field_location` (string, optional)

These support the example prompts in `docs/demo-script.md`.

## Suggested demo sequence

1. Open one-time login link to Agent Config Diff page.
2. Run prompt: add required Summary field to Article + attach displays.
3. Run prompt: add Department taxonomy field to Article using Departments vocabulary.
4. Run prompt: change Summary help text.
5. Run prompt: make Event location required.
6. Show run history page.

## Safety checks

- Module plans changes only; it does not auto-import config.
- Output remains reviewable with explicit risk/warning sections.
- Drupal permissions and admin UI remain the governance boundary.

## Troubleshooting notes

- If DDEV start fails due malformed global SSH compose file, inspect:
  - `~/.ddev/.ssh-auth-compose-full.yaml`
- If `ddev composer` cannot locate root `composer.json`, run Composer through:
  - `ddev exec composer ... --working-dir=/var/www/html`

## Submission alignment

This runbook complements the submission package by providing:

- a reproducible execution plan,
- evidence of real agent-assisted setup work,
- Drupal-in-the-loop governance during demo operation.
