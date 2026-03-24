<?php

namespace Drupal\agent_config_diff\ValueObject;

/**
 * Represents the complete plan produced for one request.
 */
final class ChangePlan {

  /**
   * Constructs a change plan object.
   */
  public function __construct(
    protected string $originalRequest,
    protected string $normalizedRequest,
    protected array $intents,
    protected array $operations,
    protected array $affectedConfig,
    protected array $warnings,
    protected array $unsupportedClauses,
    protected string $riskLevel,
    protected string $confidence,
    protected string $summary,
  ) {
  }

  /**
   * Converts the plan to an array for rendering/export.
   */
  public function toArray(): array {
    return [
      'original_request' => $this->originalRequest,
      'normalized_request' => $this->normalizedRequest,
      'intents' => array_map(static fn ($intent) => $intent instanceof ChangeIntent ? $intent->toArray() : $intent, $this->intents),
      'operations' => array_map(static fn ($operation) => $operation instanceof PlannedOperation ? $operation->toArray() : $operation, $this->operations),
      'affected_config' => array_values(array_unique($this->affectedConfig)),
      'warnings' => $this->warnings,
      'unsupported_clauses' => $this->unsupportedClauses,
      'risk_level' => $this->riskLevel,
      'confidence' => $this->confidence,
      'summary' => $this->summary,
    ];
  }

}
