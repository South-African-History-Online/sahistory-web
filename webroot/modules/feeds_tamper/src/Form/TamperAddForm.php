<?php

namespace Drupal\feeds_tamper\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedTypeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form to add a tamper plugin to a feed type.
 */
class TamperAddForm extends TamperFormBase {

  /**
   * The source field on the feed type.
   *
   * @var string
   */
  protected $sourceField;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feeds_tamper_add_form';
  }

  /**
   * Makes sure that the source field exists.
   *
   * @param \Drupal\feeds\FeedTypeInterface $feeds_feed_type
   *   The feed.
   * @param string $source_field
   *   The source field.
   */
  private function assertSourceField(FeedTypeInterface $feeds_feed_type, $source_field) {
    $sources = $feeds_feed_type->getMappingSources();
    if (!isset($sources[$source_field])) {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Prepares the tamper plugin.
   *
   * @param string $tamper_id
   *   The id of the tamper plugin.
   *
   * @return \Drupal\tamper\TamperInterface|null
   *   The tamper plugin instance or null in case the Tamper plugin could not be
   *   instantiated.
   */
  protected function preparePlugin($tamper_id = NULL) {
    if (empty($tamper_id)) {
      return NULL;
    }

    $meta = $this->feedTypeTamperManager->getTamperMeta($this->feedsFeedType);

    try {
      /** @var \Drupal\tamper\TamperInterface $tamper */
      $tamper = $this->tamperManager->createInstance($tamper_id, [
        'source_definition' => $meta->getSourceDefinition(),
      ]);
      return $tamper;
    }
    catch (PluginException $e) {
      $this->messenger()->addError($this->t('The specified plugin is invalid.'));
    }
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
   * @param string $source_field
   *   The source field we are adding the tamper plugin to.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FeedTypeInterface $feeds_feed_type = NULL, $source_field = NULL) {
    $this->assertSourceField($feeds_feed_type, $source_field);

    $this->feedsFeedType = $feeds_feed_type;
    $this->sourceField = $source_field;
    $this->plugin = $this->preparePlugin($form_state->getValue(self::VAR_TAMPER_ID));

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Support non-javascript browsers.
    if (empty($this->plugin) || $this->plugin->getPluginId() !== $form_state->getValue('tamper_id')) {
      $form_state->setRebuild();
      return;
    }

    $config = $this->prepareConfig($this->sourceField, $form_state);
    $tamper_meta = $this->feedTypeTamperManager->getTamperMeta($this->feedsFeedType);
    $tamper_meta->addTamper($config);
    $this->feedsFeedType->save();

    $this->messenger()->addStatus($this->t('Plugin %plugin_label was successfully added to %source.', [
      '%plugin_label' => $this->plugin->getPluginDefinition()['label'],
      '%source' => $this->sourceField,
    ]));
  }

}
