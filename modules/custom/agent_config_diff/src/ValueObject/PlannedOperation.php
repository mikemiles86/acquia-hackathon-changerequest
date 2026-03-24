<?php

namespace Drupal\agent_config_diff\ValueObject;

/**
 * Represents one explicit operation in a generated change plan.
 */
final class PlannedOperation {

  /**
   * Constructs an operation object.
   */
  public function __construct(
    protected string $type,
    protected string $configName,
    protected array $payload,
    protected string $summary,
  ) {
  }

  /**
   * Exports this operation to an array.
   */
  public function toArray(): array {
    return [
      'type' => $this->type,
      'config_name' => $this->configName,
      'payload' => $this->payload,
      'summary' => $this->summary,
    ];
  }

}
