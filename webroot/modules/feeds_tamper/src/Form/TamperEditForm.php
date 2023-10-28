<?php

namespace Drupal\feeds_tamper\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedTypeInterface;

/**
 * Tamper edit form.
 *
 * @package Drupal\feeds_tamper\Form
 */
class TamperEditForm extends TamperFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feeds_tamper_edit_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\feeds\FeedTypeInterface $feeds_feed_type
   *   The feed that we are adding a tamper plugin to.
   * @param string $tamper_uuid
   *   The tamper uuid.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FeedTypeInterface $feeds_feed_type = NULL, $tamper_uuid = NULL) {
    $this->assertTamper($feeds_feed_type, $tamper_uuid);

    $this->feedsFeedType = $feeds_feed_type;
    $tamper_meta = $this->feedTypeTamperManager->getTamperMeta($feeds_feed_type);
    $this->plugin = $tamper_meta->getTamper($tamper_uuid);

    $form = parent::buildForm($form, $form_state);
    $form[self::VAR_TAMPER_ID]['#disabled'] = TRUE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tamper_meta = $this->feedTypeTamperManager->getTamperMeta($this->feedsFeedType);
    $uuid = $this->plugin->getSetting('uuid');
    $tampers_config = $tamper_meta->getTampers()->getConfiguration();

    $config = $this->prepareConfig($tampers_config[$uuid]['source'], $form_state);
    $tamper_meta->setTamperConfig($uuid, $config);
    $this->feedsFeedType->save();

    $this->messenger()->addStatus($this->t('The plugin %plugin_label has been updated.', [
      '%plugin_label' => $this->plugin->getPluginDefinition()['label'],
    ]));
  }

}
