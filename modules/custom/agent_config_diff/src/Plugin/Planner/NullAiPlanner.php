<?php

namespace Drupal\agent_config_diff\Plugin\Planner;

/**
 * Placeholder planner that records unsupported mode in a safe way.
 */
class NullAiPlanner implements PlannerInterface {

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return 'null_ai';
  }

  /**
   * {@inheritdoc}
   */
  public function parse(string $request, ?string $bundleOverride = NULL): array {
    return [
      'normalized' => trim($request),
      'intents' => [],
      'unsupported_clauses' => [
        'Null AI planner is a placeholder. Use deterministic mode for MVP behavior.',
      ],
    ];
  }

}
