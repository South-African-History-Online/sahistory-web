<?php

namespace Drupal\basic_cart\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\basic_cart\Utility;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides a 'Basic Cart Count' block.
 *
 * @Block(
 *   id = "basic_cart_countblock",
 *   admin_label = @Translation("Basic Cart Count Block")
 * )
 */
class CountBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();
    $render = array(
      '#theme' => 'basic_cart_count_block',
      '#cartcount' => Utility::cartCount(),
      '#cache' => array('max-age' => 0),
    );
    if ($config['float']) {
      $render['#float'] = Html::escape($config['float']);
    }
    if ($config['size']) {
      $render['#size'] = Html::escape($config['size']);
      $render['#size_class'] = "-" . str_replace("x", "-", Html::escape($config['size']));
      $css = str_replace("x", "", Html::escape($config['size']));
    }
    if ($config['top']) {
      $render['#top'] = Html::escape($config['top']) . 'px';
    }
    if ($config['bottom']) {
      $render['#bottom'] = Html::escape($config['bottom']) . 'px';
    }
    if ($config['left']) {
      $render['#left'] = Html::escape($config['left']) . 'px';
    }
    if ($config['right']) {
      $render['#right'] = Html::escape($config['right']) . 'px';
    }
    if (!$css) {
      $css = "4839";
    }
    $render['#attached']['library'][] = 'basic_cart/' . $css;

    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['size'] = array(
      '#type' => 'select',
      '#options' => [
        '32x26' => '32x26',
        '48x39' => '48x39',
        '128x105' => '128x105',
        '64x52' => '64x52',
      ],
      '#title' => $this->t('Cart Icon Size'),
      '#description' => $this->t('Cart icon size'),
      '#default_value' => isset($config['size']) ? $config['size'] : '48x29',
    );

    $form['float'] = array(
      '#type' => 'select',
      '#options' => ['none' => 'none', 'right' => 'right', 'left' => 'left'],
      '#title' => $this->t('Float'),
      '#description' => $this->t('Cart icon floated to right or left'),
      '#default_value' => isset($config['float']) ? $config['float'] : '',
    );
    $form['top'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Top'),
      '#description' => $this->t('Top positon value in pixel'),
      '#default_value' => isset($config['top']) ? $config['top'] : '',
      '#size' => 3,
    );
    $form['bottom'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Bottom'),
      '#description' => $this->t('Bottom positon value in pixel'),
      '#default_value' => isset($config['bottom']) ? $config['bottom'] : '',
      '#size' => 3,
    );
    $form['left'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Left'),
      '#description' => $this->t('Left positon value in pixel'),
      '#default_value' => isset($config['left']) ? $config['left'] : '',
      '#size' => 3,
    );
    $form['right'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Right'),
      '#description' => $this->t('Right positon value in pixel'),
      '#default_value' => isset($config['right']) ? $config['right'] : '',
      '#size' => 3,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values                        = $form_state->getValues();
    $this->configuration['float']  = $values['float'];
    $this->configuration['top']    = $values['top'];
    $this->configuration['bottom'] = $values['bottom'];
    $this->configuration['left']   = $values['left'];
    $this->configuration['right']  = $values['right'];
    $this->configuration['size']   = $values['size'];
  }

}
