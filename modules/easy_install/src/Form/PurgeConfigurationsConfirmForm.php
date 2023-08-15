<?php

namespace Drupal\easy_install\Form;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigDependencyDeleteFormTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Config\InstallStorage;

/**
 * Builds a confirmation form to uninstall selected modules.
 */
class PurgeConfigurationsConfirmForm extends ConfirmFormBase {
  use ConfigDependencyDeleteFormTrait;

  /**
   * The module installer service.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The expirable key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueExpirable;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An array of modules to uninstall.
   *
   * @var array
   */
  protected $modules = [];

  /**
   * Constructs a ModulesUninstallConfirmForm object.
   *
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ModuleInstallerInterface $module_installer, KeyValueStoreExpirableInterface $key_value_expirable, ConfigManagerInterface $config_manager, EntityTypeManagerInterface $entity_manager, FileSystemInterface $file_system) {
    $this->moduleInstaller = $module_installer;
    $this->keyValueExpirable = $key_value_expirable;
    $this->configManager = $config_manager;
    $this->entityTypeManager = $entity_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_installer'),
      $container->get('keyvalue.expirable')->get('easy_install_purgeconfigs'),
      $container->get('config.manager'),
      $container->get('entity_type.manager'),
       $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Confirm Purge Configurations');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Purge Configurations');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('easy_install.purge_configurations');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Would you like to continue with purge the above?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'purge_configuration_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the list of modules from the key value store.
    $account = $this->currentUser()->id();
    $this->modules = $this->keyValueExpirable->get($account);
    // Prevent this page from showing when the module list is empty.
    if (empty($this->modules['install'])) {
      $this->messenger()->addError($this->t('The selected modules could not be Purged, either due to a website problem or due to the uninstall confirmation form timing out. Please try again.'));
      return $this->redirect('easy_install.purge_configurations');
    }

    $form['text']['#markup'] = '<p>' . $this->t('Select the following configurations, selected configs will be completely deleted from your site!') . '</p>';
    $form['modules'] = [
      '#theme' => 'item_list',
      '#items' => $this->modules['install'],
    ];
    foreach ($this->modules['install'] as $module => $module_name) {
      $install_dir = drupal_get_path('module', $module) . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
      $optional_dir = drupal_get_path('module', $module) . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
      if(file_exists($install_dir)) { 
          $install_details =  $this->fileSystem->scanDirectory($install_dir, "/\.(yml)$/");
      }
      if (!empty($install_details)) {
        $form['modules_config'][$module] = [
          '#type' => 'details',
          '#title' => $this->t('@name', ['@name' => $module]),
          '#description' => $this->t('We found that @description module have configurations with it, if you like to delete it Please select the checkbox', ['@description' => $module]),
          '#weight' => 0,
          '#validated' => TRUE,
          '#open' => TRUE,
        ];
        if(file_exists($install_dir)) { 
            $install_details =  $this->fileSystem->scanDirectory($install_dir, "/\.(yml)$/");
        }    
        $ins_options = [];
        foreach ($install_details as $config_value) {
          $ins_options[$config_value->name] = $config_value->name;
        }
        if (!empty($ins_options)) {
          $form['modules_config'][$module]['configs'] = [
            '#type' => 'checkboxes',
            '#label' => $config_value->name,
            '#title' => 'Select the configurations to be deleted',
            '#options' => $ins_options,
            '#validated' => TRUE,
          ];
        }
        if(file_exists($optional_dir)) {
          $optional_details =  $this->fileSystem->scanDirectory($optional_dir, "/\.(yml)$/");
          $opt_options = [];
          foreach ($optional_details as $config_value) {
            $opt_options[$config_value->name] = $config_value->name;
          }
        }
        if (!empty($opt_options)) {
          $form['modules_config'][$module]['opt_details'] = [
            '#type' => 'details',
            '#title' => "Optional Configurations",
            '#weight' => 0,
            '#validated' => TRUE,
            '#open' => TRUE,
          ];
          $form['modules_config'][$module]['opt_details']['opt_configs'] = [
            '#type' => 'checkboxes',
            '#label' => $config_value->name,
            '#options' => $opt_options,
            '#validated' => TRUE,
          ];
        }
      }
    }
    $label = 'Delete all the listed configurations except optional';
    if (empty($opt_options)) {
      $label = 'Delete all the listed configurations';
    }
    if (!empty($ins_options)) {
      $form['ins_all_configs'] = [
        '#type' => 'checkbox',
        '#label' => $label,
        '#title' => $label,
        '#validated' => TRUE,
      ];
    }
    if (!empty($opt_options)) {
      $form['opt_all_configs'] = [
        '#type' => 'checkbox',
        '#label' => 'Delete all the listed Optional configurations',
        '#title' => 'Delete all the listed Optional configurations',
        '#validated' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the current user and to get the config that are selected.
    $account = $this->currentUser()->id();
    $ins_configs = $form_state->getValue('configs') ? $form_state->getValue('configs') : [];
    if ($form_state->getValue('ins_all_configs') != 0) {
      foreach ($ins_configs as $key => $value) {
        \Drupal::configFactory()->getEditable($key)->delete();
      }
    }
    else {
      foreach ($ins_configs as $key => $values) {
        if ($values !== 0) {
          \Drupal::configFactory()->getEditable($key)->delete();
        }
      }
    }
    // Get the user selected configs in optional folder and delete.
    $opt_configs = $form_state->getValue('opt_configs') ? $form_state->getValue('opt_configs') : [];
    if ($form_state->getValue('opt_all_configs') != 0) {
      foreach ($opt_configs as $key => $value) {
        \Drupal::configFactory()->getEditable($key)->delete();
      }
    }
    else {
      foreach ($opt_configs as $key => $values) {
        if ($values !== 0) {
          \Drupal::configFactory()->getEditable($key)->delete();
        }
      }
    }
    // Delete the keyvalue of current user.
    $this->keyValueExpirable->delete($account);

    $this->messenger()->addMessage($this->t('The selected configurations have
      been deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
