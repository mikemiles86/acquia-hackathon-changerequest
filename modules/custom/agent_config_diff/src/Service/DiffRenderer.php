<?php

namespace Drupal\agent_config_diff\Service;

/**
 * Renders structured and line-wise diffs for staged config.
 */
class DiffRenderer {

  /**
   * Builds diff details for each staged config object.
   */
  public function render(array $staged): array {
    $output = [];
    foreach ($staged as $configName => $entry) {
      $output[$configName] = [
        'status' => $entry['status'],
        'key_changes' => $this->collectKeyChanges($entry['before'], $entry['after']),
        'line_diff' => $this->lineDiff((string) $entry['old_yaml'], (string) $entry['new_yaml']),
      ];
    }
    return $output;
  }

  /**
   * Returns simple key-level before/after changes.
   */
  protected function collectKeyChanges(array $before, array $after, string $prefix = ''): array {
    $changes = [];
    $keys = array_unique(array_merge(array_keys($before), array_keys($after)));

    foreach ($keys as $key) {
      $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
      $hasBefore = array_key_exists($key, $before);
      $hasAfter = array_key_exists($key, $after);

      if ($hasBefore && !$hasAfter) {
        $changes[] = ['path' => $path, 'type' => 'removed'];
        continue;
      }
      if (!$hasBefore && $hasAfter) {
        $changes[] = ['path' => $path, 'type' => 'added'];
        continue;
      }

      if (is_array($before[$key]) && is_array($after[$key])) {
        $changes = array_merge($changes, $this->collectKeyChanges($before[$key], $after[$key], $path));
        continue;
      }

      if ($before[$key] !== $after[$key]) {
        $changes[] = ['path' => $path, 'type' => 'changed'];
      }
    }

    return $changes;
  }

  /**
   * Creates a simple line-by-line diff text.
   */
  protected function lineDiff(string $oldYaml, string $newYaml): string {
    $oldLines = preg_split('/\R/', $oldYaml) ?: [];
    $newLines = preg_split('/\R/', $newYaml) ?: [];

    $removed = array_values(array_diff($oldLines, $newLines));
    $added = array_values(array_diff($newLines, $oldLines));

    $out = [];
    foreach ($removed as $line) {
      $out[] = '- ' . $line;
    }
    foreach ($added as $line) {
      $out[] = '+ ' . $line;
    }

    return implode("\n", $out);
  }

}
