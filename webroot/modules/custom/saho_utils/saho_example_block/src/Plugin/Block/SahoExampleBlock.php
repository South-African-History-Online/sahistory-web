<?php

namespace Drupal\saho_example_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an example block for SAHO.
 *
 * @Block(
 *   id = "saho_example_block",
 *   admin_label = @Translation("SAHO Example Block"),
 *   category = @Translation("All custom")
 * )
 */
class SahoExampleBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // We're referencing a theme hook 'saho_example_block' that we defined
    // in hook_theme() within saho_example_block.module. This will link to our
    // saho-example-block.html.twig template by default.
    $build = [
      '#theme' => 'saho_example_block',
    // Overridden by preprocess if needed.
      '#my_variable' => 'Hello from build() method!',
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // If you want config fields for this block, define them here.
    // For example, a field for a custom text:
    $config = $this->getConfiguration();

    $form['custom_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Text'),
      '#default_value' => $config['custom_text'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save the block configuration.
    $this->setConfigurationValue('custom_text', $form_state->getValue('custom_text'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Return 0 to ensure it doesn't cache, or set as needed.
    return 0;
  }

}
