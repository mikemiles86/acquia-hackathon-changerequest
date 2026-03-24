<?php

namespace Drupal\agent_config_diff\Service;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Inspects topology for supported node and taxonomy config surfaces.
 */
class SiteTopologyInspector {

  /**
   * Constructs the inspector.
   */
  public function __construct(
    protected EntityTypeBundleInfoInterface $bundleInfo,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EntityDisplayRepositoryInterface $displayRepository,
  ) {
  }

  /**
   * Returns all node bundles keyed by machine name.
   */
  public function getNodeBundles(): array {
    $bundles = $this->bundleInfo->getBundleInfo('node');
    $output = [];
    foreach ($bundles as $machine_name => $info) {
      $output[$machine_name] = (string) ($info['label'] ?? $machine_name);
    }
    return $output;
  }

  /**
   * Resolves a bundle machine name from user-entered label or machine name.
   */
  public function resolveBundleMachineName(string $input): ?string {
    $input = mb_strtolower(trim($input));
    if ($input === '') {
      return NULL;
    }

    foreach ($this->getNodeBundles() as $machine_name => $label) {
      if ($input === mb_strtolower($machine_name) || $input === mb_strtolower($label)) {
        return $machine_name;
      }
    }

    return NULL;
  }

  /**
   * Returns taxonomy vocabularies keyed by machine name.
   */
  public function getTaxonomyVocabularies(): array {
    if (!$this->entityTypeManager->hasDefinition('taxonomy_vocabulary')) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $vocabularies = $storage->loadMultiple();

    $output = [];
    foreach ($vocabularies as $vocabulary) {
      $output[$vocabulary->id()] = $vocabulary->label();
    }

    return $output;
  }

  /**
   * Resolves a vocabulary machine name from user-entered label or machine name.
   */
  public function resolveVocabularyMachineName(string $input): ?string {
    $input = mb_strtolower(trim($input));
    if ($input === '') {
      return NULL;
    }

    foreach ($this->getTaxonomyVocabularies() as $machine_name => $label) {
      if ($input === mb_strtolower($machine_name) || $input === mb_strtolower($label)) {
        return $machine_name;
      }
    }

    return NULL;
  }

  /**
   * Returns field definitions for a node bundle.
   */
  public function getBundleFields(string $bundle): array {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $bundle);
    $output = [];
    foreach ($definitions as $field_name => $definition) {
      $output[$field_name] = [
        'label' => (string) $definition->getLabel(),
        'type' => $definition->getType(),
        'required' => $definition->isRequired(),
        'description' => (string) $definition->getDescription(),
      ];
    }
    return $output;
  }

  /**
   * Returns TRUE if a field exists on the given bundle.
   */
  public function bundleFieldExists(string $bundle, string $fieldName): bool {
    $fields = $this->getBundleFields($bundle);
    return isset($fields[$fieldName]);
  }

  /**
   * Returns FieldConfig data if found.
   */
  public function getFieldConfigData(string $bundle, string $fieldName): ?array {
    $storage = $this->entityTypeManager->getStorage('field_config');
    $id = sprintf('node.%s.%s', $bundle, $fieldName);
    $entity = $storage->load($id);
    return $entity ? $entity->toArray() : NULL;
  }

  /**
   * Returns FieldStorageConfig data if found.
   */
  public function getFieldStorageData(string $fieldName): ?array {
    $storage = $this->entityTypeManager->getStorage('field_storage_config');
    $id = sprintf('node.%s', $fieldName);
    $entity = $storage->load($id);
    return $entity ? $entity->toArray() : NULL;
  }

  /**
   * Returns form/view display component info for defaults.
   */
  public function getDefaultDisplaySummary(string $bundle): array {
    $form = $this->displayRepository->getFormDisplay('node', $bundle, 'default');
    $view = $this->displayRepository->getViewDisplay('node', $bundle, 'default');

    return [
      'form_display_id' => $form->id(),
      'view_display_id' => $view->id(),
      'form_components' => array_keys($form->getComponents()),
      'view_components' => array_keys($view->getComponents()),
    ];
  }

}
