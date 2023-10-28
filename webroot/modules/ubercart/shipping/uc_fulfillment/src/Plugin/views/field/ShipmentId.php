<?php

namespace Drupal\uc_fulfillment\Plugin\views\field;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler: simple renderer that links to the shipment page.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_fulfillment_shipment_id")
 */
class ShipmentId extends FieldPluginBase {

  /**
   * Overrides init function to provide generic option to link to shipment.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    if (!empty($this->options['link_to_shipment'])) {
      $this->additional_fields['order_id'] = array('table' => $this->table_alias, 'field' => 'order_id');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_shipment'] = array('default' => FALSE);
    return $options;
  }

  /**
   * Overrides views_handler::options_form().
   *
   * Provides link to shipment administration page.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['link_to_shipment'] = array(
      '#title' => t('Link this field to the shipment page'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_shipment']),
    );
  }

  /**
   * Renders whatever the data is as a link to the order.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  public function render_link($data, $values) {
    if (!empty($this->options['link_to_shipment'])) {
      $this->options['alter']['make_link'] = FALSE;

      if (\Drupal::currentUser()->hasPermission('fulfill orders')) {
        $path = 'admin/store/orders/' . $this->get_value($values, 'order_id') . '/shipments/' . $values->{$this->field_alias};
      }
      else {
        $path = FALSE;
      }

      if ($path && $data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = $path;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->render_link(SafeMarkup::checkPlain($values->{$this->field_alias}), $values);
  }

}
