<?php

namespace Drupal\agent_config_diff\Plugin\Planner;

use Drupal\agent_config_diff\Service\SupportedIntentParser;

/**
 * Deterministic planner adapter for the MVP parser.
 */
class DeterministicPlanner implements PlannerInterface {

  /**
   * Constructs planner.
   */
  public function __construct(
    protected SupportedIntentParser $supportedIntentParser,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return 'deterministic';
  }

  /**
   * {@inheritdoc}
   */
  public function parse(string $request, ?string $bundleOverride = NULL): array {
    return $this->supportedIntentParser->parse($request, $bundleOverride);
  }

}
