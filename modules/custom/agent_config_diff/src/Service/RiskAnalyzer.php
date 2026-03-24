<?php

namespace Drupal\agent_config_diff\Service;

/**
 * Builds risk notes and risk levels for one generated plan.
 */
class RiskAnalyzer {

  /**
   * Calculates risk summary.
   */
  public function analyze(array $warnings, array $unsupportedClauses, array $operations): array {
    $notes = [];
    $severity = 'low';

    foreach ($warnings as $warning) {
      $notes[] = [
        'severity' => 'medium',
        'message' => $warning,
      ];
    }

    foreach ($unsupportedClauses as $clause) {
      $notes[] = [
        'severity' => 'high',
        'message' => sprintf('Unsupported clause: "%s"', $clause),
      ];
    }

    if (empty($operations)) {
      $notes[] = [
        'severity' => 'high',
        'message' => 'No supported operations could be produced from this request.',
      ];
    }

    foreach ($notes as $note) {
      if ($note['severity'] === 'high') {
        $severity = 'high';
        break;
      }
      if ($note['severity'] === 'medium' && $severity !== 'high') {
        $severity = 'medium';
      }
    }

    return [
      'risk_level' => $severity,
      'notes' => $notes,
    ];
  }

}
