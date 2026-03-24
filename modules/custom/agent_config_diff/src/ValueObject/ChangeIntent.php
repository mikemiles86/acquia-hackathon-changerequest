<?php

namespace Drupal\agent_config_diff\ValueObject;

/**
 * Represents a parsed intent from a natural-language request.
 */
final class ChangeIntent {

  /**
   * Constructs a ChangeIntent value object.
   */
  public function __construct(
    protected string $originalRequest,
    protected string $normalizedRequest,
    protected string $bundle,
    protected string $action,
    protected string $fieldLabel,
    protected string $fieldMachineName,
    protected ?string $fieldType,
    protected bool $required,
    protected bool $attachFormDisplay,
    protected bool $attachViewDisplay,
    protected ?string $vocabulary,
    protected array $unsupportedClauses,
    protected array $warnings,
    protected string $confidence,
    protected array $rawMatches = [],
  ) {
  }

  /**
   * Creates an instance from a plain array.
   */
  public static function fromArray(array $data): self {
    return new self(
      (string) ($data['original_request'] ?? ''),
      (string) ($data['normalized_request'] ?? ''),
      (string) ($data['bundle'] ?? ''),
      (string) ($data['action'] ?? 'unknown'),
      (string) ($data['field_label'] ?? ''),
      (string) ($data['field_machine_name'] ?? ''),
      isset($data['field_type']) ? (string) $data['field_type'] : NULL,
      (bool) ($data['required'] ?? FALSE),
      (bool) ($data['attach_form_display'] ?? FALSE),
      (bool) ($data['attach_view_display'] ?? FALSE),
      isset($data['vocabulary']) ? (string) $data['vocabulary'] : NULL,
      array_values($data['unsupported_clauses'] ?? []),
      array_values($data['warnings'] ?? []),
      (string) ($data['confidence'] ?? 'low'),
      $data['raw_matches'] ?? [],
    );
  }

  /**
   * Exports the object as a plain array.
   */
  public function toArray(): array {
    return [
      'original_request' => $this->originalRequest,
      'normalized_request' => $this->normalizedRequest,
      'bundle' => $this->bundle,
      'action' => $this->action,
      'field_label' => $this->fieldLabel,
      'field_machine_name' => $this->fieldMachineName,
      'field_type' => $this->fieldType,
      'required' => $this->required,
      'attach_form_display' => $this->attachFormDisplay,
      'attach_view_display' => $this->attachViewDisplay,
      'vocabulary' => $this->vocabulary,
      'unsupported_clauses' => $this->unsupportedClauses,
      'warnings' => $this->warnings,
      'confidence' => $this->confidence,
      'raw_matches' => $this->rawMatches,
    ];
  }

}
