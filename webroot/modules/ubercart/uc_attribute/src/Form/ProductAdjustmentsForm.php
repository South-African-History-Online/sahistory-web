<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Associates option combinations with a product variant's SKU.
 */
class ProductAdjustmentsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_product_adjustments_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $nid = $node->id();

    // Populate table and such.
    $model = $node->model->value;
    $query = db_select('uc_product_attributes', 'pa');
    $query->leftJoin('uc_attributes', 'a', 'pa.aid = a.aid');
    $query->leftJoin('uc_attribute_options', 'ao', 'a.aid = ao.aid');
    $query->leftJoin('uc_product_options', 'po', 'ao.oid = po.oid AND po.nid = :po_nid', array(':po_nid' => $nid));
    $result = $query->fields('pa', array('nid', 'aid', 'ordering', 'display'))
      ->fields('a', array('name', 'ordering', 'aid'))
      ->fields('ao', array('aid'))
      ->condition('pa.nid', $nid)
      ->having('COUNT(po.oid) > 0')
      ->groupBy('ao.aid')
      ->groupBy('pa.aid')
      ->groupBy('pa.display')
      ->groupBy('a.name')
      ->groupBy('pa.ordering')
      ->groupBy('a.ordering')
      ->groupBy('pa.nid')
      ->groupBy('a.aid')
      ->addTag('uc_product_adjustments_form')
      ->execute();

    $i = 1;
    $attribute_names = '';

    $query = db_select('uc_product_options', "po$i")
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(20);

    $attribute_ids = [];
    foreach ($result as $prod_attr) {
      if ($i > 1) {
        $query->join('uc_product_options', "po$i");
      }
      $query->leftJoin('uc_attribute_options', "ao$i", "po$i.oid = ao$i.oid AND po$i.nid = :nid", array(':nid' => $nid));
      $query->addField("ao$i", 'aid', "aid$i");
      $query->addField("ao$i", 'name', "name$i");
      $query->addField("ao$i", 'oid', "oid$i");
      $query->addField("po$i", 'ordering', "ordering$i");

      $query->condition("ao$i.aid", $prod_attr->aid)
        ->orderBy("po$i.ordering")
        ->orderBy("ao$i.name");

      ++$i;
      $attribute_names .= '<th>' . SafeMarkup::checkPlain($prod_attr->name) . '</th>';
      $attribute_ids[] = $prod_attr->aid;
    }
    $num_prod_attr = count($attribute_ids);

    if ($num_prod_attr) {
      // Get previous values
      $old_vals = db_query("SELECT * FROM {uc_product_adjustments} WHERE nid = :nid", [':nid' => $nid])->fetchAll();

      $result = $query->execute();

      $form['original'] = array(
        '#markup' => '<p><b>' . $this->t('Default product SKU: @sku', ['@sku' => $model]) . '</b></p>',
      );
      $form['default'] = array(
        '#type' => 'value',
        '#value' => $model,
      );
      $form['table'] = array(
        '#prefix' => '<table class="combinations">',
        '#suffix' => '</table>',
      );
      $form['table']['head'] = array(
        '#markup' => '<thead><tr>' . $attribute_names . '<th>' . $this->t('Alternate SKU') . '</th></tr></thead>',
        '#weight' => 0,
      );
      $form['table']['body'] = array(
        '#prefix' => '<tbody>',
        '#suffix' => '</tbody>',
        '#weight' => 1,
        '#tree' => TRUE,
      );

      $i = 0;
      while ($combo = $result->fetchObject()) {
        $cells = '';
        $row_title = '';
        $comb_array = [];
        for ($j = 1; $j <= $num_prod_attr; ++$j) {
          $cells .= '<td>' . SafeMarkup::checkPlain($combo->{'name' . $j}) . '</td>';
          $row_title .= SafeMarkup::checkPlain($combo->{'name' . $j}) . ', ';
          $comb_array[$combo->{'aid' . $j}] = $combo->{'oid' . $j};
        }
        ksort($comb_array);
        $row_title = substr($row_title, 0, strlen($row_title) - 2);
        $default_model = $model;
        foreach ($old_vals as $ov) {
          if ($ov->combination == serialize($comb_array)) {
            $default_model = $ov->model;
            break;
          }
        }

        $form['table']['body'][$i] = array(
          '#prefix' => '<tr title="' . $row_title . '">',
          '#suffix' => '</tr>',
        );
        $form['table']['body'][$i]['combo'] = array(
          '#markup' => $cells,
        );
        $form['table']['body'][$i]['combo_array'] = array(
          '#type' => 'value',
          '#value' => serialize($comb_array),
        );
        $form['table']['body'][$i]['model'] = array(
          '#type' => 'textfield',
          '#default_value' => $default_model,
          '#prefix' => '<td>',
          '#suffix' => '</td>',
        );
        ++$i;
      }

      $form['nid'] = array(
        '#type' => 'hidden',
        '#value' => $nid,
      );
      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      );
    }
    else {
      $form['error'] = array(
        '#markup' => '<div><br />' . $this->t('This product does not have any attributes that can be used for SKU adjustments.') . '</div>',
      );
    }

    $form['pager'] = array(
      '#type' => 'pager',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('body') as $value) {
      if (!empty($value['model']) && $value['model'] != $form_state->getValue('default')) {
        db_merge('uc_product_adjustments')
          ->key(array(
            'nid' => $form_state->getValue('nid'),
            'combination' => $value['combo_array'],
          ))
          ->fields(array(
            'model' => $value['model'],
          ))
          ->execute();
      }
      else {
        db_delete('uc_product_adjustments')
          ->condition('nid', $form_state->getValue('nid'))
          ->condition('combination', $value['combo_array'])
          ->execute();
      }
    }
    drupal_set_message($this->t('Product adjustments have been saved.'));
  }

}
