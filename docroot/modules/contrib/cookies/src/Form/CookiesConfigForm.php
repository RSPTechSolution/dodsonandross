<?php

namespace Drupal\cookies\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form to configure the COOKiES module settings.
 */
class CookiesConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cookies.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cookies_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cookies.config');

    $form['cookie'] = [
      '#type' => 'details',
      '#title' => $this->t('Cookie'),
      '#open' => TRUE,
    ];
    $form['cookie']['cookie_intro'] = [
      '#markup' => $this->t("<p>The user's decisions about which services to use and which cookies can be installed are stored in a (required) cookie. These settings only refer to this single cookie.</p>"),
    ];
    $form['cookie']['cookie_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('Enter the name of the cookie where the configuration is saved what servies are allowed or denied.'),
      '#maxlength' => 64,
      '#size' => 60,
      '#default_value' => $config->get('cookie_name'),
    ];
    $form['cookie']['cookie_expires'] = [
      '#type' => 'number',
      '#title' => $this->t('Expiration'),
      '#description' => $this->t('Number of days after that the cookie expires.'),
      '#default_value' => $config->get('cookie_expires'),
    ];
    $form['cookie']['cookie_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#description' => $this->t('(optional, default: empty) Enter the cookie domain in shape of this "my-project.de". No "http" prefix, no slashes.'),
      '#maxlength' => 64,
      '#size' => 60,
      '#default_value' => $config->get('cookie_domain'),
    ];
    $form['cookie']['cookie_secure'] = [
      '#type' => 'select',
      '#title' => $this->t('Secure mode'),
      '#description' => $this->t('(optional, default: false) Cookie is only sent if secure protocol (https) is used.'),
      '#options' => ['0' => 'false', '1' => 'true'],
      '#size' => 1,
      '#default_value' => $config->get('cookie_secure'),
    ];
    $form['cookie']['cookie_same_site'] = [
      '#type' => 'select',
      '#title' => $this->t('SameSite'),
      '#description' => $this->t('(default: Lax) Handle cross-site requests.'),
      '#options' => ['None' => 'None', 'Lax' => 'Lax', 'Strict' => 'Strict'],
      '#size' => 1,
      '#default_value' => $config->get('cookie_same_site'),
    ];
    $form['cookie']['cookie_help'] = [
      '#markup' => $this->t('<p>A documentation of these different options <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie"  target="_blank">you can find here</a>.</p>'),
    ];

    $form['interface'] = [
      '#type' => 'details',
      '#title' => $this->t('Dialog'),
      '#open' => TRUE,
    ];
    $form['interface']['open_settings_hash'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL hash'),
      '#description' => $this->t('Enter the url hash value to open the settings dialog.'),
      '#maxlength' => 64,
      '#size' => 60,
      '#default_value' => $config->get('open_settings_hash'),
    ];
    $form['interface']['show_deny_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display "Deny All" button'),
      '#description' => $this->t('If you do not display this button, user is forced to disable services with settings dialog. (Real name depends on translation).'),
      '#default_value' => $config->get('show_deny_all'),
    ];
    $form['interface']['deny_all_on_layer_close'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deny all services on close layer by X-button.'),
      '#description' => $this->t('In some countries (e.g. Italy) cookie banners must have a close button that behaves equal to deny all cookies. (Has no effect, if cookiesjsr cookie is already set.)'),
      '#default_value' => $config->get('deny_all_on_layer_close'),
    ];
    $form['interface']['settings_as_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display "Settings" as Link'),
      '#description' => $this->t('Open settings dialog with a link (less prominent as  below the banner text.'),
      '#default_value' => $config->get('settings_as_link'),
    ];
    $form['interface']['group_consent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Group consent'),
      '#description' => $this->t('(default: unchecked) The user can only en-/disable entire groups not individual services. Services are not shown in detail.'),
      '#default_value' => $config->get('group_consent'),
    ];
    $form['interface']['cookie_docs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display "cookie documentation" links.'),
      '#description' => $this->t('Display links to cookie documentation (provided you have a documentation page) where explicitly is described what 3rd-party services and cookies are used. This is required, if you use "group consent". Link and link text are provided by translation.'),
      '#default_value' => $config->get('cookie_docs'),
    ];

    $form['library'] = [
      '#type' => 'details',
      '#title' => $this->t('Library'),
      '#open' => FALSE,
    ];
    $form['library']['lib_load_from_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load Cookies JSR from CDN.'),
      '#description' => $this->t('(Default: checked) Load required library from external resource (JSDelivr). The alternative (if you uncheck) is to <a href="https://github.com/jfeltkamp/cookiesjsr" target="_blank" rel="noreferrer">download the library</a> and place it in the library folder, so library file can be reached e.g. under path: <em>{docroot}/libraries/cookiesjsr/dist/cookiesjsr.min.js</em>.'),
      '#default_value' => $config->get('lib_load_from_cdn'),
    ];
    $form['library']['lib_scroll_limit'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 1200,
      '#title' => $this->t('Scroll limit (px)'),
      '#description' => $this->t('(default: 250px) Open COOKiES UI when user scrolls down more then X pixels (X is the scroll limit value). A value >= 1 avoids a page speed issue because the library loads independent from (after) page load.'),
      '#default_value' => $config->get('lib_scroll_limit') ?: 0,
    ];

    $form['styling'] = [
      '#type' => 'details',
      '#title' => $this->t('Styling'),
      '#open' => !(bool) $config->get('use_default_styles'),
    ];
    $form['styling']['styling_intro'] = [
      '#markup' => $this->t('<p>Cookies JSR offers a standard styling that is loaded via CDN. This styling can certainly be overridden for the purpose of customizing the layout; however, it is better to completely remove the original style sheet and rebuild it. Cookies JSR contains <a href="https://github.com/jfeltkamp/cookiesjsr/tree/master/styles" target="_blank">the original SCSS files</a> that can be transferred to the theme.</p>'),
    ];
    $form['styling']['use_default_styles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use original Cookies JSR layout.'),
      '#description' => $this->t('(default: checked) If you uncheck this field the Cookies JSR UI will be loaded without stylesheet.'),
      '#default_value' => $config->get('use_default_styles'),
    ];

    $form['callback'] = [
      '#type' => 'details',
      '#title' => $this->t('Callback options'),
      '#open' => (bool) $config->get('store_auth_user_consent'),
      '#description' => $this->t('The callback is invoked immediately after a user sets/updates his consents. You can use <b>hook_cookies_user_consent($consent)</b> to make backend adjustments (but keep in mind that site should remain cacheable).')
    ];
    $form['callback']['store_auth_user_consent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Store consent for authenticated users'),
      '#description' => $this->t('If "use callback" enabled, consents are stored for authenticated users.'),
      '#default_value' => $config->get('store_auth_user_consent') ?? 1
    ];

    if($config->get('use_callback')) {
      // @todo to be removed before 1.1.x
      $form['callback']['use_callback'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use callback'),
        '#description' => $this->t('Enable callback sent from cookies settings widget when consents have changed.<br/><b>WARNING:</b> This option is deprecated and will be removed in next minor release 1.1.x. Disable this feature when you moved code to the hook.'),
        '#default_value' => $config->get('use_callback'),
      ];
      $form['callback']['callback_method'] = [
        '#type' => 'select',
        '#title' => $this->t('Callback method'),
        '#description' => $this->t('<b>WARNING:</b> This option is deprecated and will be removed in next minor release 1.1.x. (default: POST) Select method for the callback.'),
        '#options' => ['post' => 'POST', 'get' => 'GET'],
        '#size' => 1,
        '#default_value' => $config->get('callback_method') ?? 'post',
        '#disabled' => true
      ];
      $form['callback']['callback_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Callback URL'),
        '#description' => $this->t('<b>WARNING:</b> This option is deprecated and will be removed in next minor release 1.1.x. If you still use this feature config changes can be made in your config/sync/cookies.config.yml. As alternative use hook_cookies_user_consent() to manage your adjustments. (default: /cookies/consent/callback.json) Enter the callback url with trailing slash.'),
        '#default_value' => $config->get('callback_url') ?? '',
        '#disabled' => true
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Validate cookie name.
    if (($value = $form_state->getValue('cookie_name')) && preg_match('/^[a-zA-Z0-9_]{3,64}$/', $value) !== 1) {
      $form_state->setErrorByName('cookie_name', $this->t("The cookie name has invalid characters. Permitted are numbers, letters and underscores."));
    }

    // Validate cookie domain pattern.
    $pattern = '/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/';
    if (($value = $form_state->getValue('cookie_domain')) && preg_match($pattern, $value) !== 1) {
      $form_state->setErrorByName('cookie_domain', $this->t("The cookie domain seems to be invalid."));
    }

    // Validate hash value may not contain the #-char.
    if (($value = $form_state->getValue('open_settings_hash')) && !(strpos($value, '#') === FALSE)) {
      $form_state->setErrorByName('open_settings_hash', $this->t("The hash value may not contain the #-char."));
    }

    // Validate hash value has only valid chars.
    if (($value = $form_state->getValue('open_settings_hash')) && preg_match('/^[a-zA-Z0-9_\-]{3,64}$/', $value) !== 1) {
      $form_state->setErrorByName('open_settings_hash', $this->t("The hash value is invalid. Permitted are between 3 and 64 characters of (0-9 a-z A-Z _ -)."));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if ($form_state->getValue('use_callback') === 0) {
      // @todo to be removed before 1.1.x
      $this->config('cookies.config')
        ->clear('use_callback')
        ->clear('callback_method')
        ->clear('callback_url');
    }

    $this->config('cookies.config')
      ->set('cookie_name', $form_state->getValue('cookie_name'))
      ->set('cookie_expires', $form_state->getValue('cookie_expires'))
      ->set('cookie_domain', $form_state->getValue('cookie_domain'))
      ->set('cookie_secure', $form_state->getValue('cookie_secure'))
      ->set('cookie_same_site', $form_state->getValue('cookie_same_site'))
      ->set('open_settings_hash', $form_state->getValue('open_settings_hash'))
      ->set('show_deny_all', $form_state->getValue('show_deny_all'))
      ->set('deny_all_on_layer_close', $form_state->getValue('deny_all_on_layer_close'))
      ->set('settings_as_link', $form_state->getValue('settings_as_link'))
      ->set('group_consent', $form_state->getValue('group_consent'))
      ->set('cookie_docs', $form_state->getValue('cookie_docs'))
      ->set('lib_load_from_cdn', $form_state->getValue('lib_load_from_cdn'))
      ->set('lib_scroll_limit', $form_state->getValue('lib_scroll_limit'))
      ->set('use_default_styles', $form_state->getValue('use_default_styles'))
      ->set('store_auth_user_consent', $form_state->getValue('store_auth_user_consent'))
      ->save();
  }

}
