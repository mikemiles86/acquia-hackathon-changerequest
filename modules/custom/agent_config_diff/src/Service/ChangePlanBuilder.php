<?php

namespace Drupal\agent_config_diff\Service;

use Drupal\agent_config_diff\ValueObject\ChangeIntent;
use Drupal\agent_config_diff\ValueObject\ChangePlan;
use Drupal\agent_config_diff\ValueObject\PlannedOperation;

/**
 * Builds a structured and explicit change plan from parsed intents.
 */
class ChangePlanBuilder {

  /**
   * Constructs the plan builder.
   */
  public function __construct(
    protected SiteTopologyInspector $topologyInspector,
    protected RiskAnalyzer $riskAnalyzer,
  ) {
  }

  /**
   * Builds a complete plan object from parser output.
   */
  public function build(string $originalRequest, string $normalizedRequest, array $intents, array $unsupportedClauses): ChangePlan {
    $operations = [];
    $warnings = [];
    $affectedConfig = [];
    $confidence = 'high';

    foreach ($intents as $intent) {
      if (!$intent instanceof ChangeIntent) {
        continue;
      }

      $intentData = $intent->toArray();
      $warnings = array_merge($warnings, $intentData['warnings']);
      $confidence = $this->reduceConfidence($confidence, (string) $intentData['confidence']);

      $bundle = (string) $intentData['bundle'];
      $field = (string) $intentData['field_machine_name'];

      if (!$this->topologyInspector->resolveBundleMachineName($bundle)) {
        $warnings[] = sprintf('Bundle "%s" does not exist.', $bundle);
        continue;
      }

      switch ($intentData['action']) {
        case 'add_field':
        case 'add_taxonomy_reference_field':
          [$ops, $opWarnings] = $this->buildAddFieldOperations($intentData);
          $operations = array_merge($operations, $ops);
          $warnings = array_merge($warnings, $opWarnings);
          break;

        case 'update_field_required':
          $configName = sprintf('field.field.node.%s.%s', $bundle, $field);
          $operations[] = new PlannedOperation(
            'update_field_instance',
            $configName,
            [
              'set' => ['required' => (bool) $intentData['required']],
            ],
            sprintf('Set %s as %s on %s.', $field, $intentData['required'] ? 'required' : 'optional', $bundle)
          );
          break;

        case 'update_field_help_text':
          $configName = sprintf('field.field.node.%s.%s', $bundle, $field);
          $operations[] = new PlannedOperation(
            'update_field_instance',
            $configName,
            [
              'set' => ['description' => (string) ($intentData['raw_matches']['help_text'] ?? '')],
            ],
            sprintf('Update help text for %s on %s.', $field, $bundle)
          );
          break;
      }
    }

    $operationArrays = array_map(static fn (PlannedOperation $operation) => $operation->toArray(), $operations);
    foreach ($operationArrays as $operation) {
      $affectedConfig[] = $operation['config_name'];
    }

    $risk = $this->riskAnalyzer->analyze($warnings, $unsupportedClauses, $operationArrays);
    $warnings = array_values(array_unique($warnings));

    $summary = sprintf(
      'Generated %d operation(s) affecting %d config object(s).',
      count($operationArrays),
      count(array_unique($affectedConfig))
    );

    return new ChangePlan(
      $originalRequest,
      $normalizedRequest,
      $intents,
      $operationArrays,
      array_values(array_unique($affectedConfig)),
      $warnings,
      $unsupportedClauses,
      $risk['risk_level'],
      $confidence,
      $summary,
    );
  }

  /**
   * Builds operations for field creation and optional display placement.
   */
  protected function buildAddFieldOperations(array $intentData): array {
    $warnings = [];
    $operations = [];

    $bundle = (string) $intentData['bundle'];
    $fieldName = (string) $intentData['field_machine_name'];
    $fieldLabel = (string) $intentData['field_label'];
    $fieldType = (string) ($intentData['field_type'] ?? 'string');

    $storageConfigName = sprintf('field.storage.node.%s', $fieldName);
    $fieldConfigName = sprintf('field.field.node.%s.%s', $bundle, $fieldName);
    $formDisplayName = sprintf('core.entity_form_display.node.%s.default', $bundle);
    $viewDisplayName = sprintf('core.entity_view_display.node.%s.default', $bundle);

    if ($this->topologyInspector->bundleFieldExists($bundle, $fieldName)) {
      $warnings[] = sprintf('Field %s already exists on bundle %s.', $fieldName, $bundle);
    }

    $storageData = [
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => ['module' => ['node']],
      'id' => sprintf('node.%s', $fieldName),
      'field_name' => $fieldName,
      'entity_type' => 'node',
      'type' => $fieldType,
      'settings' => [],
      'module' => $this->mapFieldTypeModule($fieldType),
      'locked' => FALSE,
      'cardinality' => 1,
      'translatable' => TRUE,
      'persist_with_no_fields' => FALSE,
      'custom_storage' => FALSE,
    ];

    if ($fieldType === 'entity_reference') {
      $vocabulary = (string) ($intentData['vocabulary'] ?? '');
      if ($vocabulary === '') {
        $warnings[] = 'Taxonomy reference field requested without a resolvable vocabulary.';
      }
      $storageData['settings'] = [
        'target_type' => 'taxonomy_term',
      ];
      $storageData['module'] = 'core';
    }

    $operations[] = new PlannedOperation(
      'create_field_storage',
      $storageConfigName,
      ['set' => $storageData],
      sprintf('Create field storage %s (%s).', $fieldName, $fieldType)
    );

    $fieldSettings = [];
    if ($fieldType === 'entity_reference') {
      $vocabulary = (string) ($intentData['vocabulary'] ?? '');
      $fieldSettings = [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => $vocabulary ? [$vocabulary => $vocabulary] : [],
        ],
      ];
    }

    $operations[] = new PlannedOperation(
      'create_field_instance',
      $fieldConfigName,
      [
        'set' => [
          'langcode' => 'en',
          'status' => TRUE,
          'dependencies' => ['config' => [$storageConfigName], 'module' => ['node']],
          'id' => sprintf('node.%s.%s', $bundle, $fieldName),
          'field_name' => $fieldName,
          'entity_type' => 'node',
          'bundle' => $bundle,
          'label' => $fieldLabel,
          'description' => '',
          'required' => (bool) $intentData['required'],
          'translatable' => TRUE,
          'default_value' => [],
          'default_value_callback' => '',
          'settings' => $fieldSettings,
          'field_type' => $fieldType,
        ],
      ],
      sprintf('Create field instance %s on %s.', $fieldName, $bundle)
    );

    if (!empty($intentData['attach_form_display'])) {
      $operations[] = new PlannedOperation(
        'update_form_display',
        $formDisplayName,
        [
          'set' => [
            'content' => [
              $fieldName => [
                'type' => $this->mapFormWidget($fieldType),
                'weight' => 10,
                'region' => 'content',
                'settings' => [],
                'third_party_settings' => [],
              ],
            ],
          ],
        ],
        sprintf('Attach %s to default form display for %s.', $fieldName, $bundle)
      );
    }

    if (!empty($intentData['attach_view_display'])) {
      $operations[] = new PlannedOperation(
        'update_view_display',
        $viewDisplayName,
        [
          'set' => [
            'content' => [
              $fieldName => [
                'type' => $this->mapViewFormatter($fieldType),
                'label' => 'above',
                'settings' => [],
                'third_party_settings' => [],
                'weight' => 10,
                'region' => 'content',
              ],
            ],
          ],
        ],
        sprintf('Attach %s to default view display for %s.', $fieldName, $bundle)
      );
    }

    return [$operations, $warnings];
  }

  /**
   * Reduces confidence to the lowest level seen.
   */
  protected function reduceConfidence(string $left, string $right): string {
    $order = ['low' => 0, 'medium' => 1, 'high' => 2];
    return ($order[$right] ?? 0) < ($order[$left] ?? 0) ? $right : $left;
  }

  /**
   * Maps field type to the providing module name.
   */
  protected function mapFieldTypeModule(string $fieldType): string {
    return match ($fieldType) {
      'text_long' => 'text',
      'list_string' => 'options',
      'boolean' => 'core',
      'entity_reference' => 'core',
      default => 'core',
    };
  }

  /**
   * Maps field type to a default widget.
   */
  protected function mapFormWidget(string $fieldType): string {
    return match ($fieldType) {
      'text_long' => 'text_textarea',
      'boolean' => 'boolean_checkbox',
      'entity_reference' => 'options_select',
      default => 'string_textfield',
    };
  }

  /**
   * Maps field type to a default view formatter.
   */
  protected function mapViewFormatter(string $fieldType): string {
    return match ($fieldType) {
      'text_long' => 'text_default',
      'boolean' => 'boolean',
      'entity_reference' => 'entity_reference_label',
      default => 'string',
    };
  }

}
