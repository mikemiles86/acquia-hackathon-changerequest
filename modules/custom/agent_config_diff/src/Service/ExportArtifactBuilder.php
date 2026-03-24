<?php

namespace Drupal\agent_config_diff\Service;

/**
 * Builds export-ready artifacts from generated plan and staged config.
 */
class ExportArtifactBuilder {

  /**
   * Returns JSON/YAML/Markdown artifacts for copy or download.
   */
  public function build(array $plan, array $staged, array $diff, array $risk): array {
    $yamlByConfig = [];
    foreach ($staged as $configName => $entry) {
      $yamlByConfig[$configName] = $entry['new_yaml'];
    }

    $markdown = [];
    $markdown[] = '# Agent Config Diff Review Summary';
    $markdown[] = '';
    $markdown[] = '## Request';
    $markdown[] = $plan['original_request'] ?? '';
    $markdown[] = '';
    $markdown[] = '## Plan Summary';
    $markdown[] = '- ' . ($plan['summary'] ?? 'No summary available.');
    $markdown[] = '- Confidence: ' . ($plan['confidence'] ?? 'low');
    $markdown[] = '- Risk level: ' . ($risk['risk_level'] ?? ($plan['risk_level'] ?? 'high'));
    $markdown[] = '';
    $markdown[] = '## Affected Config';
    foreach ($plan['affected_config'] ?? [] as $configName) {
      $markdown[] = '- `' . $configName . '`';
    }

    if (!empty($plan['warnings'])) {
      $markdown[] = '';
      $markdown[] = '## Warnings';
      foreach ($plan['warnings'] as $warning) {
        $markdown[] = '- ' . $warning;
      }
    }

    if (!empty($plan['unsupported_clauses'])) {
      $markdown[] = '';
      $markdown[] = '## Unsupported clauses';
      foreach ($plan['unsupported_clauses'] as $clause) {
        $markdown[] = '- ' . $clause;
      }
    }

    return [
      'plan_json' => json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
      'risk_json' => json_encode($risk, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
      'staged_yaml' => $yamlByConfig,
      'diff_json' => json_encode($diff, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
      'review_markdown' => implode("\n", $markdown),
    ];
  }

}
