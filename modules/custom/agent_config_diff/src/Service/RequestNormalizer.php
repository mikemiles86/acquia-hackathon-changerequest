<?php

namespace Drupal\agent_config_diff\Service;

/**
 * Normalizes natural-language requests before parsing.
 */
class RequestNormalizer {

  /**
   * Normalizes request string to deterministic parser input.
   */
  public function normalize(string $request): string {
    $normalized = trim($request);
    $normalized = preg_replace('/\s+/', ' ', $normalized ?? '');
    $normalized = str_replace(['“', '”', '’'], ['"', '"', "'"], $normalized);
    return $normalized ?? '';
  }

}
