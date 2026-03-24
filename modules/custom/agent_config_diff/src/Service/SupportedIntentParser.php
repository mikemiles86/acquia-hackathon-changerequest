<?php

namespace Drupal\agent_config_diff\Service;

use Drupal\agent_config_diff\ValueObject\ChangeIntent;

/**
 * Deterministic parser for a constrained set of supported requests.
 */
class SupportedIntentParser {

  /**
   * Constructs parser.
   */
  public function __construct(
    protected RequestNormalizer $normalizer,
    protected SiteTopologyInspector $topologyInspector,
  ) {
  }

  /**
   * Parses one request into deterministic intents.
   */
  public function parse(string $request, ?string $bundleOverride = NULL): array {
    $normalized = $this->normalizer->normalize($request);
    $clauses = preg_split('/\s+and\s+/i', $normalized) ?: [];

    $intents = [];
    $unsupported = [];

    foreach ($clauses as $clause) {
      $parsed = $this->parseClause($clause, $normalized, $bundleOverride);
      if ($parsed instanceof ChangeIntent) {
        $intents[] = $parsed;
      }
      else {
        $unsupported[] = trim($clause);
      }
    }

    if (empty($intents) && $normalized !== '') {
      $unsupported[] = $normalized;
    }

    return [
      'normalized' => $normalized,
      'intents' => $intents,
      'unsupported_clauses' => array_values(array_unique($unsupported)),
    ];
  }

  /**
   * Parses one clause.
   */
  protected function parseClause(string $clause, string $normalizedRequest, ?string $bundleOverride): ?ChangeIntent {
    $clean = trim($clause);
    if ($clean === '') {
      return NULL;
    }

    $warnings = [];
    $rawMatches = [];

    if (preg_match('/^change\s+the\s+help\s+text\s+of\s+the\s+(.+?)\s+field\s+on\s+(.+?)\s+to\s+["\'](.+)["\']$/i', $clean, $match)) {
      $fieldLabel = trim($match[1]);
      $bundleInput = trim($match[2]);
      $helpText = trim($match[3]);
      $bundle = $this->resolveBundle($bundleInput, $bundleOverride);
      if (!$bundle) {
        $warnings[] = sprintf('Bundle "%s" could not be resolved.', $bundleInput);
      }

      $fieldMachine = $this->toFieldMachineName($fieldLabel);
      $rawMatches['help_text'] = $helpText;

      return ChangeIntent::fromArray([
        'original_request' => $clean,
        'normalized_request' => $normalizedRequest,
        'bundle' => $bundle ?? $bundleInput,
        'action' => 'update_field_help_text',
        'field_label' => $fieldLabel,
        'field_machine_name' => $fieldMachine,
        'field_type' => NULL,
        'required' => FALSE,
        'attach_form_display' => FALSE,
        'attach_view_display' => FALSE,
        'unsupported_clauses' => [],
        'warnings' => $warnings,
        'confidence' => $bundle ? 'high' : 'medium',
        'raw_matches' => $rawMatches,
      ]);
    }

    if (preg_match('/^make\s+the\s+(.+?)\s+field\s+(required|optional)(?:\s+on|\s+for)?\s+(.+)$/i', $clean, $match)) {
      $fieldLabel = trim($match[1]);
      $required = mb_strtolower(trim($match[2])) === 'required';
      $bundleInput = trim($match[3]);
      $bundle = $this->resolveBundle($bundleInput, $bundleOverride);
      if (!$bundle) {
        $warnings[] = sprintf('Bundle "%s" could not be resolved.', $bundleInput);
      }

      return ChangeIntent::fromArray([
        'original_request' => $clean,
        'normalized_request' => $normalizedRequest,
        'bundle' => $bundle ?? $bundleInput,
        'action' => 'update_field_required',
        'field_label' => $fieldLabel,
        'field_machine_name' => $this->toFieldMachineName($fieldLabel),
        'field_type' => NULL,
        'required' => $required,
        'attach_form_display' => FALSE,
        'attach_view_display' => FALSE,
        'unsupported_clauses' => [],
        'warnings' => $warnings,
        'confidence' => $bundle ? 'high' : 'medium',
      ]);
    }

    if (preg_match('/^add\s+(?:a\s+|an\s+)?(?:(required|optional)\s+)?(.+?)\s+field\s+to\s+(.+)$/i', $clean, $match)) {
      $required = mb_strtolower((string) ($match[1] ?? '')) === 'required';
      $fieldLabel = trim((string) $match[2]);
      $tail = trim((string) $match[3]);
      $attachForm = (bool) preg_match('/form\s+display/i', $tail);
      $attachView = (bool) preg_match('/view\s+display/i', $tail);

      $vocabulary = NULL;
      if (preg_match('/using\s+the\s+(.+?)\s+vocabulary/i', $tail, $vMatch)) {
        $vocabulary = trim($vMatch[1]);
      }

      $bundleInput = trim(preg_replace('/\s+using\s+the\s+.+?\s+vocabulary/i', '', $tail) ?? $tail);
      $bundleInput = trim(preg_replace('/\s+and\s+show\s+it.+$/i', '', $bundleInput) ?? $bundleInput);
      $bundle = $this->resolveBundle($bundleInput, $bundleOverride);
      if (!$bundle) {
        $warnings[] = sprintf('Bundle "%s" could not be resolved.', $bundleInput);
      }

      $resolvedVocabulary = $vocabulary ? $this->topologyInspector->resolveVocabularyMachineName($vocabulary) : NULL;
      if ($vocabulary && !$resolvedVocabulary) {
        $warnings[] = sprintf('Vocabulary "%s" could not be resolved.', $vocabulary);
      }

      $fieldType = $this->inferFieldType($fieldLabel, $clean, (bool) $vocabulary);
      $action = $fieldType === 'entity_reference' ? 'add_taxonomy_reference_field' : 'add_field';

      return ChangeIntent::fromArray([
        'original_request' => $clean,
        'normalized_request' => $normalizedRequest,
        'bundle' => $bundle ?? $bundleInput,
        'action' => $action,
        'field_label' => $fieldLabel,
        'field_machine_name' => $this->toFieldMachineName($fieldLabel),
        'field_type' => $fieldType,
        'required' => $required,
        'attach_form_display' => $attachForm,
        'attach_view_display' => $attachView,
        'vocabulary' => $resolvedVocabulary ?? $vocabulary,
        'unsupported_clauses' => [],
        'warnings' => $warnings,
        'confidence' => (!$bundle || ($vocabulary && !$resolvedVocabulary)) ? 'medium' : 'high',
      ]);
    }

    if (preg_match('/^add\s+(.+?)\s+to\s+(.+)$/i', $clean, $match)) {
      $fieldLabel = trim($match[1]);
      $tail = trim($match[2]);
      $vocabulary = NULL;
      if (preg_match('/using\s+the\s+(.+?)\s+vocabulary/i', $tail, $vMatch)) {
        $vocabulary = trim($vMatch[1]);
      }

      $bundleInput = trim(preg_replace('/\s+using\s+the\s+.+?\s+vocabulary/i', '', $tail) ?? $tail);
      $bundle = $this->resolveBundle($bundleInput, $bundleOverride);
      if (!$bundle) {
        $warnings[] = sprintf('Bundle "%s" could not be resolved.', $bundleInput);
      }

      if (!$vocabulary && preg_match('/taxonomy/i', $clean)) {
        $warnings[] = 'Taxonomy vocabulary not specified.';
      }

      $resolvedVocabulary = $vocabulary ? $this->topologyInspector->resolveVocabularyMachineName($vocabulary) : NULL;
      $fieldType = $this->inferFieldType($fieldLabel, $clean, (bool) $vocabulary);

      return ChangeIntent::fromArray([
        'original_request' => $clean,
        'normalized_request' => $normalizedRequest,
        'bundle' => $bundle ?? $bundleInput,
        'action' => $fieldType === 'entity_reference' ? 'add_taxonomy_reference_field' : 'add_field',
        'field_label' => $fieldLabel,
        'field_machine_name' => $this->toFieldMachineName($fieldLabel),
        'field_type' => $fieldType,
        'required' => (bool) preg_match('/\brequired\b/i', $clean),
        'attach_form_display' => (bool) preg_match('/form\s+display/i', $clean),
        'attach_view_display' => (bool) preg_match('/view\s+display/i', $clean),
        'vocabulary' => $resolvedVocabulary ?? $vocabulary,
        'unsupported_clauses' => [],
        'warnings' => $warnings,
        'confidence' => $bundle ? 'medium' : 'low',
      ]);
    }

    return NULL;
  }

  /**
   * Resolves bundle, preferring explicit override.
   */
  protected function resolveBundle(string $bundleInput, ?string $bundleOverride): ?string {
    if (!empty($bundleOverride)) {
      return $this->topologyInspector->resolveBundleMachineName($bundleOverride) ?? $bundleOverride;
    }
    return $this->topologyInspector->resolveBundleMachineName($bundleInput);
  }

  /**
   * Converts a human label to Drupal field machine name.
   */
  protected function toFieldMachineName(string $label): string {
    $base = mb_strtolower($label);
    $base = preg_replace('/[^a-z0-9_]+/', '_', $base) ?? $base;
    $base = trim($base, '_');
    if (!str_starts_with($base, 'field_')) {
      $base = 'field_' . $base;
    }
    return substr($base, 0, 32);
  }

  /**
   * Infers a supported field type from deterministic rules.
   */
  protected function inferFieldType(string $fieldLabel, string $clause, bool $hasVocabulary): string {
    $haystack = mb_strtolower($fieldLabel . ' ' . $clause);

    if ($hasVocabulary || preg_match('/taxonomy|vocabulary|department|topic|category/', $haystack)) {
      return 'entity_reference';
    }
    if (preg_match('/boolean|featured|highlight/', $haystack)) {
      return 'boolean';
    }
    if (preg_match('/list_string|status label|type|level/', $haystack)) {
      return 'list_string';
    }
    if (preg_match('/long text|summary|subtitle|teaser|description/', $haystack)) {
      return 'text_long';
    }
    if (preg_match('/plain text|string|short text/', $haystack)) {
      return 'string';
    }

    return 'string';
  }

}
