<?php

namespace Drupal\Tests\agent_config_diff\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for access control and admin form rendering.
 *
 * @group agent_config_diff
 */
class AgentConfigDiffAccessAndUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_ui',
    'text',
    'options',
    'taxonomy',
    'user',
    'system',
    'views',
    'agent_config_diff',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests route requires custom permission and renders expected sections.
   */
  public function testAccessAndFormSubmission(): void {
    $this->drupalGet('/admin/config/development/agent-config-diff');
    $this->assertSession()->statusCodeEquals(403);

    $user = $this->drupalCreateUser(['use agent config diff']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/config/development/agent-config-diff');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Natural-language change request');

    $edit = [
      'request' => 'Unclear request that should not parse into supported operations',
      'planner_mode' => 'deterministic',
    ];
    $this->submitForm($edit, 'Generate reviewable config diff');

    $this->assertSession()->pageTextContains('Review results');
    $this->assertSession()->pageTextContains('Supported / unsupported parts');
    $this->assertSession()->pageTextContains('Risk and confidence notes');
    $this->assertSession()->pageTextContains('Safety:');
  }

}
