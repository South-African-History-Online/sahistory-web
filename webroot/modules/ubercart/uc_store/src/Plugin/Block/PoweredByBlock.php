<?php

namespace Drupal\uc_store\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a block to identify Ubercart as the store software on a site.
 *
 * @Block(
 *   id = "powered_by_ubercart",
 *   admin_label = @Translation("Powered by Ubercart")
 * )
 */
class PoweredByBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'label_display' => 0,
      'cache' => array(
        'max_age' => Cache::PERMANENT,
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $configuration = $this->configuration;

    $form['message'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Footer message for store pages'),
      '#options' => array_merge(
        array(0 => $this->t('Randomly select a message from the list below.')),
        $this->options()
      ),
      '#default_value' => isset($configuration['message']) ? $configuration['message'] : '',
      '#weight' => 10,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['message'] = $form_state->getValue('message');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $id = $this->configuration['message'];

    // Figure out what page is being viewed.
    $path = \Drupal::routeMatch()->getRouteName();

    $messages = $this->options();

    if ($id == 0) {
      // Calculate which message to show based on a hash of the path and the
      // site's private key. The message initially chosen for each page on a
      // specific site will thus be pseudo-random, yet we will consistently
      // display the same message on any given page on that site.
      $private_key = \Drupal::service('private_key')->get();
      $id = (hexdec(substr(md5($path . $private_key), 0, 2)) % count($messages)) + 1;
    }

    return array('#markup' => $messages[$id]);
  }

  /**
   * Returns the default message options.
   */
  protected function options() {
    $url = array(':url' => Url::fromUri('http://www.ubercart.org/')->toString());
    return array(
      1 => $this->t('<a href=":url">Powered by Ubercart</a>', $url),
      2 => $this->t('<a href=":url">Drupal e-commerce</a> provided by Ubercart.', $url),
      3 => $this->t('Supported by Ubercart, an <a href=":url">open source e-commerce suite</a>.', $url),
      4 => $this->t('Powered by Ubercart, the <a href=":url">free shopping cart software</a>.', $url),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredCacheContexts() {
    return array('url');
  }

}
