<?php

namespace Drupal\better_social_sharing_buttons\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\better_social_sharing_buttons\Form
 */
class BetterSocialSharingButtonsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'better_social_sharing_buttons_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'better_social_sharing_buttons.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('better_social_sharing_buttons.settings');

    $social_services = [
      'facebook' => $this->t('Facebook'),
      'twitter' => $this->t('Twitter'),
      'whatsapp' => $this->t('WhatsApp'),
      'facebook_messenger' => $this->t('Facebook Messenger'),
      'email' => $this->t('Email'),
      'pinterest' => $this->t('Pinterest'),
      'linkedin' => $this->t('Linkedin'),
      'digg' => $this->t('Digg'),
      'stumbleupon' => $this->t('StumbleUpon'),
      'slashdot' => $this->t('Slashdot'),
      'tumblr' => $this->t('Tumblr'),
      'reddit' => $this->t('Reddit'),
      'evernote' => $this->t('Evernote'),
      'print' => $this->t('Print'),
      'copy' => $this->t('Copy current page url to clipboard'),
    ];

    $form['services'] = [
      '#title' => $this->t('Select which social sharing buttons you would like to use'),
      '#type' => 'checkboxes',
      '#description' => $this->t('Check the services for which you would want social sharing buttons to appear'),
      '#options' => $social_services,
      '#default_value' => $config->get('services'),
    ];

    $form['iconset'] = [
      '#type' => 'radios',
      '#title' => $this->t('Which iconset do you want to use?'),
      '#default_value' => $config->get('iconset') ?: 'social-icons--square',
      '#required' => TRUE,
      '#options' => [
        'social-icons--square' => $this->t('Colored square icons (you can adjust the border radius)<br>
        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 455.73 455.73"><path d="M0 0v455.73h242.704V279.691h-59.33v-71.864h59.33v-60.353c0-43.893 35.582-79.475 79.475-79.475h62.025v64.622h-44.382c-13.947 0-25.254 11.307-25.254 25.254v49.953h68.521l-9.47 71.864h-59.051V455.73H455.73V0H0z" fill="#3a559f"/></svg>
        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128"><path fill="#55ACEE" d="M0 0h128v128H0z"/><path fill="#FFF" d="M99.84 41.77a29.377 29.377 0 0 1-8.446 2.315 14.751 14.751 0 0 0 6.465-8.136 29.445 29.445 0 0 1-9.338 3.569 14.686 14.686 0 0 0-10.734-4.644c-8.122 0-14.706 6.584-14.706 14.705 0 1.153.13 2.276.38 3.352-12.222-.614-23.058-6.468-30.31-15.366a14.635 14.635 0 0 0-1.992 7.394 14.7 14.7 0 0 0 6.542 12.24 14.643 14.643 0 0 1-6.66-1.84c-.002.062-.002.123-.002.186 0 7.125 5.07 13.068 11.797 14.42a14.734 14.734 0 0 1-6.642.252c1.872 5.842 7.303 10.094 13.738 10.213a29.506 29.506 0 0 1-18.264 6.295 29.91 29.91 0 0 1-3.508-.205 41.62 41.62 0 0 0 22.543 6.606c27.05 0 41.842-22.408 41.842-41.842 0-.637-.015-1.271-.043-1.902a29.865 29.865 0 0 0 7.338-7.612z"/></svg>
        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128"><path fill="#25D366" d="M0 0h128v128H0z"/><g fill="#FFF"><path d="M28.16 100.013l5.064-18.495a35.622 35.622 0 0 1-4.765-17.842c.008-19.679 16.018-35.689 35.69-35.689 9.548.004 18.51 3.721 25.247 10.468 6.739 6.745 10.448 15.712 10.444 25.25-.008 19.677-16.02 35.689-35.69 35.689 0 0 .001 0 0 0h-.015a35.652 35.652 0 0 1-17.055-4.345l-18.92 4.964zm19.796-11.425l1.084.643a29.622 29.622 0 0 0 15.098 4.135h.012c16.35 0 29.658-13.307 29.664-29.665.003-7.926-3.08-15.379-8.68-20.986-5.6-5.607-13.049-8.696-20.972-8.7-16.363 0-29.67 13.307-29.677 29.663a29.583 29.583 0 0 0 4.535 15.786l.706 1.123-2.996 10.946 11.226-2.945z"/><path fill-rule="evenodd" clip-rule="evenodd" d="M82.13 72.19c-.223-.372-.816-.594-1.708-1.042-.893-.446-5.277-2.603-6.094-2.9-.817-.298-1.412-.447-2.007.445-.594.894-2.303 2.903-2.823 3.497-.52.595-1.041.67-1.933.224-.891-.446-3.764-1.389-7.17-4.427-2.652-2.364-4.442-5.285-4.961-6.177-.52-.893-.056-1.375.39-1.82.402-.4.892-1.042 1.338-1.562.445-.52.594-.894.892-1.489.297-.594.148-1.116-.075-1.562-.223-.446-2.006-4.836-2.75-6.621-.723-1.738-1.459-1.503-2.005-1.531a36.387 36.387 0 0 0-1.71-.032c-.594 0-1.56.224-2.378 1.117-.818.892-3.121 3.05-3.121 7.439 0 4.39 3.195 8.63 3.641 9.225.446.595 6.288 9.603 15.235 13.465a51.182 51.182 0 0 0 5.084 1.88c2.135.678 4.08.582 5.616.353 1.714-.257 5.276-2.157 6.02-4.24.742-2.084.742-3.87.52-4.242z"/></g></svg>
        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128"><path fill="#0084FF" d="M0 0h128v128H0z"/><path d="M64 17.531c-25.405 0-46 19.259-46 43.015 0 13.515 6.665 25.574 17.089 33.46v16.462l15.698-8.707a49 49 0 0 0 13.213 1.8c25.405 0 46-19.258 46-43.015 0-23.756-20.595-43.015-46-43.015zm4.845 57.683L56.947 62.855 34.035 75.524l25.12-26.657 11.898 12.359 22.91-12.67-25.118 26.658z" fill="#FFF"/></svg>
        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><g fill="none" fill-rule="evenodd"><path fill="#f60" stroke-width=".994" d="M0 0h60v60H0z"/><path d="M30 32.885l17.308-15H12.692zm-4.675-1.66L30 35.06l4.602-3.837 12.706 10.891H12.692zm-13.787 9.737V19.038L24.231 30zm36.924 0V19.038L35.769 30z" fill="#fff"/></g></svg>
        ...
        '),
        'social-icons--no-color' => $this->t('Icons without color or background (you can use svg fill property in css to alter the icon color)<br/>
        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 455.73 455.73"><path d="M186.78 421.73V245.693h-59.33v-71.864h59.33v-60.353C186.78 69.582 222.362 34 266.255 34h62.025v64.622h-44.382c-13.947 0-25.254 11.307-25.254 25.254v49.953h68.521l-9.47 71.864h-59.051V421.73z"/></svg>
        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128"><path d="M99.84 41.77a29.377 29.377 0 0 1-8.446 2.315 14.751 14.751 0 0 0 6.465-8.136 29.445 29.445 0 0 1-9.338 3.569 14.686 14.686 0 0 0-10.734-4.644c-8.122 0-14.706 6.584-14.706 14.705 0 1.153.13 2.276.38 3.352-12.222-.614-23.058-6.468-30.31-15.366a14.635 14.635 0 0 0-1.992 7.394 14.7 14.7 0 0 0 6.542 12.24 14.643 14.643 0 0 1-6.66-1.84c-.002.062-.002.123-.002.186 0 7.125 5.07 13.068 11.797 14.42a14.734 14.734 0 0 1-6.642.252c1.872 5.842 7.303 10.094 13.738 10.213a29.506 29.506 0 0 1-18.264 6.295 29.91 29.91 0 0 1-3.508-.205 41.62 41.62 0 0 0 22.543 6.606c27.05 0 41.842-22.408 41.842-41.842 0-.637-.015-1.271-.043-1.902a29.865 29.865 0 0 0 7.338-7.612z"/></svg>
        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128"><path d="M28.16 100.013l5.064-18.495a35.622 35.622 0 0 1-4.765-17.842c.008-19.679 16.018-35.689 35.69-35.689 9.548.004 18.51 3.721 25.247 10.468 6.739 6.745 10.448 15.712 10.444 25.25-.008 19.677-16.02 35.689-35.69 35.689 0 0 .001 0 0 0h-.015a35.652 35.652 0 0 1-17.055-4.345l-18.92 4.964zm19.796-11.425l1.084.643a29.622 29.622 0 0 0 15.098 4.135h.012c16.35 0 29.658-13.307 29.664-29.665.003-7.926-3.08-15.379-8.68-20.986-5.6-5.607-13.049-8.696-20.972-8.7-16.363 0-29.67 13.307-29.677 29.663a29.583 29.583 0 0 0 4.535 15.786l.706 1.123-2.996 10.946 11.226-2.945z"/><path d="M82.13 72.19c-.223-.372-.816-.594-1.708-1.042-.893-.446-5.277-2.603-6.094-2.9-.817-.298-1.412-.447-2.007.445-.594.894-2.303 2.903-2.823 3.497-.52.595-1.041.67-1.933.224-.891-.446-3.764-1.389-7.17-4.427-2.652-2.364-4.442-5.285-4.961-6.177-.52-.893-.056-1.375.39-1.82.402-.4.892-1.042 1.338-1.562.445-.52.594-.894.892-1.489.297-.594.148-1.116-.075-1.562-.223-.446-2.006-4.836-2.75-6.621-.723-1.738-1.459-1.503-2.005-1.531a36.387 36.387 0 0 0-1.71-.032c-.594 0-1.56.224-2.378 1.117-.818.892-3.121 3.05-3.121 7.439 0 4.39 3.195 8.63 3.641 9.225.446.595 6.288 9.603 15.235 13.465a51.182 51.182 0 0 0 5.084 1.88c2.135.678 4.08.582 5.616.353 1.714-.257 5.276-2.157 6.02-4.24.742-2.084.742-3.87.52-4.242z" clip-rule="evenodd" fill-rule="evenodd"/></svg>
        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128"><path d="M64 17.531c-25.405 0-46 19.259-46 43.015 0 13.515 6.665 25.574 17.089 33.46v16.462l15.698-8.707a49 49 0 0 0 13.213 1.8c25.405 0 46-19.258 46-43.015 0-23.756-20.595-43.015-46-43.015zm4.845 57.683L56.947 62.855 34.035 75.524l25.12-26.657 11.898 12.359 22.91-12.67-25.118 26.658z"/></svg>
        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><path d="M30 32.885l17.308-15H12.692zm-4.675-1.66L30 35.06l4.602-3.837 12.706 10.891H12.692zm-13.787 9.737V19.038L24.231 30zm36.924 0V19.038L35.769 30z"/></svg>
        ...
        '),
      ],
    ];

    $form['facebook_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook App ID'),
      '#default_value' => $config->get('facebook_app_id') ?: '',
      '#description' => $this->t('If you want to share to FB messenger, a Facebook App ID is required'),
      '#states'        => [
        'visible'      => [
          ':input[name="services[facebook_messenger]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="services[facebook_messenger]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['print_css'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Print css file'),
      '#default_value' => $config->get('print_css') ?: '',
      '#description' => $this->t('Enter absolute path to your print css file. When set, the print version will display on screen.'),
      '#states'        => [
        'visible'      => [
          ':input[name="services[print]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon size'),
      '#default_value' => $config->get('width') ?: '20px',
      '#description' => $this->t('Set the size of the icons in pixels, e.g., 32px. Height and width will be set to this size.'),
      '#required' => TRUE,
    ];

    $form['radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border radius'),
      '#default_value' => $config->get('radius') ?: '3px',
      '#description' => $this->t('Set the border radius of each icon (=rounded corners). Set to 0px to have square icons or 100% to convert the square icons into circular icons.'),
      '#required' => TRUE,
    ];

    $form['node_field'] = [
      '#title' => $this->t('Enable Better Social Sharing Buttons display field for nodes'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('node_field') ?: FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('better_social_sharing_buttons.settings');
    $config->set('services', $form_state->getValue('services'));
    $config->set('width', $form_state->getValue('width'));
    $config->set('radius', $form_state->getValue('radius'));
    $config->set('facebook_app_id', $form_state->getValue('facebook_app_id'));
    $config->set('print_css', $form_state->getValue('print_css'));
    $config->set('iconset', $form_state->getValue('iconset'));
    $config->set('node_field', $form_state->getValue('node_field'));
    $config->save();
    $this->messenger()->addMessage($this->t("Configuration saved!"));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('services')['facebook_messenger']) {
      $appid = $form_state->getValue('facebook_app_id');
      if (!is_numeric($appid)) {
        $form_state->setErrorByName('facebook_app_id', $this->t('Facebook App ID should be numeric'));
      }
    }

  }

}
