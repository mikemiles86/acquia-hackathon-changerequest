<?php

namespace Drupal\agent_config_diff\Form;

use Drupal\agent_config_diff\Service\ChangePlanBuilder;
use Drupal\agent_config_diff\Service\DiffRenderer;
use Drupal\agent_config_diff\Service\ExportArtifactBuilder;
use Drupal\agent_config_diff\Service\RiskAnalyzer;
use Drupal\agent_config_diff\Service\RunHistoryRepository;
use Drupal\agent_config_diff\Service\SiteTopologyInspector;
use Drupal\agent_config_diff\Service\StagedConfigBuilder;
use Drupal\agent_config_diff\Service\SupportedIntentParser;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin form for planning safe, reviewable config diffs.
 */
class ChangeRequestForm extends FormBase {

  /**
   * Constructs form.
   */
  public function __construct(
    protected SiteTopologyInspector $topologyInspector,
    protected SupportedIntentParser $supportedIntentParser,
    protected ChangePlanBuilder $changePlanBuilder,
    protected StagedConfigBuilder $stagedConfigBuilder,
    protected DiffRenderer $diffRenderer,
    protected RiskAnalyzer $riskAnalyzer,
    protected ExportArtifactBuilder $exportArtifactBuilder,
    protected RunHistoryRepository $runHistoryRepository,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('agent_config_diff.site_topology_inspector'),
      $container->get('agent_config_diff.supported_intent_parser'),
      $container->get('agent_config_diff.change_plan_builder'),
      $container->get('agent_config_diff.staged_config_builder'),
      $container->get('agent_config_diff.diff_renderer'),
      $container->get('agent_config_diff.risk_analyzer'),
      $container->get('agent_config_diff.export_artifact_builder'),
      $container->get('agent_config_diff.run_history_repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'agent_config_diff_change_request_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $bundleOptions = ['' => $this->t('- Auto-detect bundle -')];
    foreach ($this->topologyInspector->getNodeBundles() as $machine => $label) {
      $bundleOptions[$machine] = sprintf('%s (%s)', $label, $machine);
    }

    $form['request'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Natural-language change request'),
      '#required' => TRUE,
      '#default_value' => (string) $form_state->getValue('request', ''),
      '#description' => $this->t('Example: Add a required Summary field to Article and show it on the default form and view display.'),
    ];

    $form['target_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Target bundle override (optional)'),
      '#options' => $bundleOptions,
      '#default_value' => (string) $form_state->getValue('target_bundle', ''),
    ];

    $form['planner_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Planner mode'),
      '#options' => [
        'deterministic' => $this->t('Deterministic (MVP)'),
        'null_ai' => $this->t('Null AI planner placeholder'),
      ],
      '#default_value' => (string) $form_state->getValue('planner_mode', 'deterministic'),
      '#description' => $this->t('MVP uses deterministic parser and plan builder. No external AI provider required.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate reviewable config diff'),
      '#button_type' => 'primary',
    ];

    $result = $form_state->get('agent_config_diff_result');
    if (is_array($result)) {
      $form['result'] = $this->buildResultSection($result);
    }

    return $form;
  }

  /**
   * Builds result UI render array.
   */
  protected function buildResultSection(array $result): array {
    $plan = $result['plan'];
    $diff = $result['diff'];
    $staged = $result['staged'];
    $risk = $result['risk'];
    $artifacts = $result['artifacts'];

    $build = [
      '#type' => 'details',
      '#title' => $this->t('Review results'),
      '#open' => TRUE,
    ];

    $build['parsed_request'] = [
      '#type' => 'item',
      '#title' => $this->t('Parsed request'),
      '#markup' => '<pre>' . htmlspecialchars(json_encode([
        'normalized_request' => $plan['normalized_request'],
        'confidence' => $plan['confidence'],
        'summary' => $plan['summary'],
      ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>',
    ];

    $build['supported'] = [
      '#type' => 'item',
      '#title' => $this->t('Supported / unsupported parts'),
      '#markup' => '<pre>' . htmlspecialchars(json_encode([
        'operations_count' => count($plan['operations'] ?? []),
        'unsupported_clauses' => $plan['unsupported_clauses'] ?? [],
      ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>',
    ];

    $build['operations'] = [
      '#type' => 'item',
      '#title' => $this->t('Structured proposed operations'),
      '#markup' => '<pre>' . htmlspecialchars(json_encode($plan['operations'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>',
    ];

    $build['affected_config'] = [
      '#type' => 'item',
      '#title' => $this->t('Affected config names'),
      '#markup' => '<pre>' . htmlspecialchars(json_encode($plan['affected_config'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>',
    ];

    $beforeAfter = [];
    foreach ($staged as $configName => $entry) {
      $beforeAfter[$configName] = [
        'status' => $entry['status'],
        'before' => $entry['before'],
        'after' => $entry['after'],
      ];
    }
    $build['before_after'] = [
      '#type' => 'item',
      '#title' => $this->t('Before / after summaries'),
      '#markup' => '<pre>' . htmlspecialchars(json_encode($beforeAfter, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>',
    ];

    $build['diff'] = [
      '#type' => 'item',
      '#title' => $this->t('Reviewable diff output'),
      '#markup' => '<pre>' . htmlspecialchars(json_encode($diff, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>',
    ];

    $build['risk'] = [
      '#type' => 'item',
      '#title' => $this->t('Risk and confidence notes'),
      '#markup' => '<pre>' . htmlspecialchars(json_encode([
        'risk' => $risk,
        'warnings' => $plan['warnings'] ?? [],
      ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>',
    ];

    $build['export_plan_json'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Export: plan JSON'),
      '#value' => $artifacts['plan_json'],
      '#rows' => 12,
    ];

    $build['export_review_md'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Export: markdown review summary'),
      '#value' => $artifacts['review_markdown'],
      '#rows' => 12,
    ];

    $allYaml = '';
    foreach ($artifacts['staged_yaml'] as $configName => $yaml) {
      $allYaml .= "# " . $configName . "\n" . $yaml . "\n";
    }
    $build['export_yaml'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Export: staged YAML'),
      '#value' => $allYaml,
      '#rows' => 18,
    ];

    $build['governance_note'] = [
      '#type' => 'markup',
      '#markup' => '<p><strong>Safety:</strong> This tool does not import config or write staged changes into active configuration.</p>',
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $request = (string) $form_state->getValue('request', '');
    $bundleOverride = (string) $form_state->getValue('target_bundle', '');
    $plannerMode = (string) $form_state->getValue('planner_mode', 'deterministic');

    if ($plannerMode === 'null_ai') {
      $parsed = [
        'normalized' => trim($request),
        'intents' => [],
        'unsupported_clauses' => ['Null AI planner is a placeholder in MVP. Use deterministic mode.'],
      ];
    }
    else {
      $parsed = $this->supportedIntentParser->parse($request, $bundleOverride ?: NULL);
    }

    $plan = $this->changePlanBuilder->build(
      $request,
      $parsed['normalized'],
      $parsed['intents'],
      $parsed['unsupported_clauses'],
    )->toArray();

    $staged = $this->stagedConfigBuilder->build($plan);
    $diff = $this->diffRenderer->render($staged);
    $risk = $this->riskAnalyzer->analyze($plan['warnings'] ?? [], $plan['unsupported_clauses'] ?? [], $plan['operations'] ?? []);
    $artifacts = $this->exportArtifactBuilder->build($plan, $staged, $diff, $risk);

    $this->runHistoryRepository->save(
      (int) $this->currentUser()->id(),
      $request,
      (string) $plan['normalized_request'],
      $plan,
      $risk,
      'planned',
    );

    $form_state->set('agent_config_diff_result', [
      'plan' => $plan,
      'staged' => $staged,
      'diff' => $diff,
      'risk' => $risk,
      'artifacts' => $artifacts,
    ]);
    $form_state->setRebuild(TRUE);
  }

}
