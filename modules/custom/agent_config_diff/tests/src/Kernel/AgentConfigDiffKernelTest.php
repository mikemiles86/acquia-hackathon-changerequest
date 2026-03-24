<?php

namespace Drupal\Tests\agent_config_diff\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Kernel tests for parser, plan builder, and staged config generation.
 *
 * @group agent_config_diff
 */
class AgentConfigDiffKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'node',
    'taxonomy',
    'agent_config_diff',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installConfig(['node', 'field', 'text', 'options', 'taxonomy', 'agent_config_diff']);
    $this->installSchema('agent_config_diff', ['agent_config_diff_run']);

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    NodeType::create(['type' => 'event', 'name' => 'Event'])->save();
    Vocabulary::create(['vid' => 'departments', 'name' => 'Departments'])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_location',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => TRUE,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_location',
      'entity_type' => 'node',
      'bundle' => 'event',
      'label' => 'Location',
      'required' => FALSE,
    ])->save();
  }

  /**
   * Tests add required summary flow with display placement.
   */
  public function testAddRequiredSummaryFieldPlan(): void {
    $parser = $this->container->get('agent_config_diff.supported_intent_parser');
    $planner = $this->container->get('agent_config_diff.change_plan_builder');
    $stager = $this->container->get('agent_config_diff.staged_config_builder');

    $request = 'Add a required Summary field to Article and show it on the default form and view display.';
    $parsed = $parser->parse($request);
    $plan = $planner->build($request, $parsed['normalized'], $parsed['intents'], $parsed['unsupported_clauses'])->toArray();

    $this->assertNotEmpty($plan['operations']);
    $this->assertContains('field.storage.node.field_summary', $plan['affected_config']);
    $this->assertContains('field.field.node.article.field_summary', $plan['affected_config']);

    $staged = $stager->build($plan);
    $this->assertArrayHasKey('field.storage.node.field_summary', $staged);
    $this->assertEquals('new', $staged['field.storage.node.field_summary']['status']);
  }

  /**
   * Tests taxonomy field creation intent with vocabulary resolution.
   */
  public function testAddTaxonomyReferenceFieldPlan(): void {
    $parser = $this->container->get('agent_config_diff.supported_intent_parser');
    $planner = $this->container->get('agent_config_diff.change_plan_builder');

    $request = 'Add a Department taxonomy field to Article using the Departments vocabulary.';
    $parsed = $parser->parse($request);
    $plan = $planner->build($request, $parsed['normalized'], $parsed['intents'], $parsed['unsupported_clauses'])->toArray();

    $this->assertNotEmpty($plan['operations']);
    $operationTypes = array_column($plan['operations'], 'type');
    $this->assertContains('create_field_storage', $operationTypes);
    $this->assertContains('create_field_instance', $operationTypes);
  }

  /**
   * Tests help text update intent for existing field.
   */
  public function testChangeHelpTextPlan(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_summary',
      'entity_type' => 'node',
      'type' => 'text_long',
      'cardinality' => 1,
      'translatable' => TRUE,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_summary',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Summary',
      'required' => FALSE,
      'description' => 'Old help text.',
    ])->save();

    $parser = $this->container->get('agent_config_diff.supported_intent_parser');
    $planner = $this->container->get('agent_config_diff.change_plan_builder');
    $stager = $this->container->get('agent_config_diff.staged_config_builder');

    $request = "Change the help text of the Summary field on Article to 'Used in listing pages and teasers.'";
    $parsed = $parser->parse($request);
    $plan = $planner->build($request, $parsed['normalized'], $parsed['intents'], $parsed['unsupported_clauses'])->toArray();

    $this->assertNotEmpty($plan['operations']);
    $staged = $stager->build($plan);

    $this->assertArrayHasKey('field.field.node.article.field_summary', $staged);
    $this->assertEquals(
      'Used in listing pages and teasers.',
      $staged['field.field.node.article.field_summary']['after']['description']
    );
  }

  /**
   * Tests missing vocabulary and missing bundle warnings.
   */
  public function testMissingReferencesProduceWarnings(): void {
    $parser = $this->container->get('agent_config_diff.supported_intent_parser');
    $planner = $this->container->get('agent_config_diff.change_plan_builder');

    $request = 'Add a Department taxonomy field to NotARealBundle using the Missing vocab vocabulary.';
    $parsed = $parser->parse($request);
    $plan = $planner->build($request, $parsed['normalized'], $parsed['intents'], $parsed['unsupported_clauses'])->toArray();

    $this->assertNotEmpty($plan['warnings']);
    $this->assertEquals('high', $plan['risk_level']);
  }

}
