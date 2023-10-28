<?php

namespace Drupal\feeds_tamper\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\feeds\FeedTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tamper delete form.
 *
 * @package Drupal\feeds_tamper\Form
 */
class TamperDeleteForm extends ConfirmFormBase {

  use TamperFormTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var self $form */
    $form = parent::create($container);
    $form->setTamperManager($container->get('plugin.manager.tamper'));
    $form->setTamperMetaManager($container->get('feeds_tamper.feed_type_tamper_manager'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feeds_tamper_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.feeds_feed_type.tamper', [
      'feeds_feed_type' => $this->feedsFeedType->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the Tamper plugin instance %instance from the source %source?', [
      '%source' => $this->plugin->getSetting('source'),
      '%instance' => $this->plugin->getPluginDefinition()['label'],
    ]);
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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tamper_meta = $this->feedTypeTamperManager->getTamperMeta($this->feedsFeedType);
    $uuid = $this->plugin->getSetting('uuid');
    $tampers_config = $tamper_meta->getTampers()->getConfiguration();
    $tamper_meta->removeTamper($uuid);
    $this->feedsFeedType->save();

    $this->messenger()->addStatus($this->t('The Tamper plugin instance %plugin has been deleted from %source.', [
      '%plugin' => $this->plugin->getPluginDefinition()['label'],
      '%source' => $tampers_config[$uuid]['source'],
    ]));
    $form_state->setRedirect('entity.feeds_feed_type.tamper', [
      'feeds_feed_type' => $this->feedsFeedType->id(),
    ]);
  }

}
