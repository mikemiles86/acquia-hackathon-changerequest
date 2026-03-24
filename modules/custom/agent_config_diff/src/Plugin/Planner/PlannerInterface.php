<?php

namespace Drupal\agent_config_diff\Plugin\Planner;

/**
 * Defines a future extension contract for planner providers.
 */
interface PlannerInterface {

  /**
   * Returns a machine name for the planner.
   */
  public function id(): string;

  /**
   * Plans request and returns normalized parser-like output.
   */
  public function parse(string $request, ?string $bundleOverride = NULL): array;

}
