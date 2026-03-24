<?php

namespace Drupal\agent_config_diff\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds staged config arrays from a plan without mutating active config.
 */
class StagedConfigBuilder {

  /**
   * Constructs staged config builder.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Builds staged config output keyed by config object name.
   */
  public function build(array $plan): array {
    $staged = [];

    foreach ($plan['operations'] ?? [] as $operation) {
      $configName = (string) $operation['config_name'];
      $before = $this->configFactory->get($configName)->getRawData();
      $before = is_array($before) ? $before : [];

      $after = $before;
      $set = $operation['payload']['set'] ?? [];
      if (!is_array($set)) {
        $set = [];
      }
      $after = $this->mergeDistinct($after, $set);

      $after = $this->sortRecursive($after);
      $before = $this->sortRecursive($before);

      $status = 'unchanged';
      if (empty($before) && !empty($after)) {
        $status = 'new';
      }
      elseif ($before !== $after) {
        $status = 'changed';
      }

      $staged[$configName] = [
        'status' => $status,
        'before' => $before,
        'after' => $after,
        'old_yaml' => Yaml::dump($before, 8, 2),
        'new_yaml' => Yaml::dump($after, 8, 2),
      ];
    }

    ksort($staged);
    return $staged;
  }

  /**
   * Deep merge that replaces scalar values and recurses for arrays.
   */
  protected function mergeDistinct(array $base, array $override): array {
    foreach ($override as $key => $value) {
      if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
        $base[$key] = $this->mergeDistinct($base[$key], $value);
      }
      else {
        $base[$key] = $value;
      }
    }
    return $base;
  }

  /**
   * Sorts associative arrays recursively for deterministic output.
   */
  protected function sortRecursive(array $data): array {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $data[$key] = $this->sortRecursive($value);
      }
    }

    if (array_keys($data) !== range(0, count($data) - 1)) {
      ksort($data);
    }

    return $data;
  }

}
