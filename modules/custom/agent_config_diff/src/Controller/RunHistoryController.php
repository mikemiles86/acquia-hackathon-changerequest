<?php

namespace Drupal\agent_config_diff\Controller;

use Drupal\agent_config_diff\Service\RunHistoryRepository;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lists previous planning runs for traceability.
 */
class RunHistoryController extends ControllerBase {

  /**
   * Constructs controller.
   */
  public function __construct(
    protected RunHistoryRepository $runHistoryRepository,
    protected DateFormatterInterface $dateFormatter,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('agent_config_diff.run_history_repository'),
      $container->get('date.formatter'),
    );
  }

  /**
   * Builds run history page.
   */
  public function history(): array {
    $rows = [];
    foreach ($this->runHistoryRepository->latest() as $record) {
      $rows[] = [
        'data' => [
          (int) $record->id,
          (int) $record->uid,
          $this->dateFormatter->format((int) $record->created, 'short'),
          (string) $record->status,
          mb_strimwidth((string) $record->raw_request, 0, 100, '…'),
        ],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        $this->t('Run ID'),
        $this->t('UID'),
        $this->t('Created'),
        $this->t('Status'),
        $this->t('Raw request'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No runs yet.'),
    ];
  }

}
