<?php

namespace Drupal\agent_config_diff\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;

/**
 * Persists and retrieves lightweight run history records.
 */
class RunHistoryRepository {

  /**
   * Constructs repository.
   */
  public function __construct(
    protected Connection $connection,
    protected TimeInterface $time,
  ) {
  }

  /**
   * Saves a run row and returns new ID.
   */
  public function save(int $uid, string $rawRequest, string $normalizedRequest, array $plan, array $risk, string $status = 'planned'): int {
    $id = $this->connection->insert('agent_config_diff_run')
      ->fields([
        'uid' => $uid,
        'created' => $this->time->getRequestTime(),
        'raw_request' => $rawRequest,
        'normalized_request' => $normalizedRequest,
        'plan_json' => json_encode($plan, JSON_UNESCAPED_SLASHES),
        'risk_json' => json_encode($risk, JSON_UNESCAPED_SLASHES),
        'status' => $status,
      ])
      ->execute();

    return (int) $id;
  }

  /**
   * Returns latest run rows.
   */
  public function latest(int $limit = 20): array {
    $query = $this->connection->select('agent_config_diff_run', 'r')
      ->fields('r', ['id', 'uid', 'created', 'raw_request', 'normalized_request', 'status'])
      ->orderBy('id', 'DESC')
      ->range(0, $limit);

    return $query->execute()->fetchAllAssoc('id');
  }

  /**
   * Loads one run row with full payloads.
   */
  public function load(int $id): ?array {
    $record = $this->connection->select('agent_config_diff_run', 'r')
      ->fields('r')
      ->condition('id', $id)
      ->range(0, 1)
      ->execute()
      ->fetchAssoc();

    if (!$record) {
      return NULL;
    }

    $record['plan'] = json_decode((string) $record['plan_json'], TRUE) ?: [];
    $record['risk'] = json_decode((string) $record['risk_json'], TRUE) ?: [];

    return $record;
  }

}
