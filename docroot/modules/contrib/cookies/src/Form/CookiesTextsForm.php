<?php

namespace Drupal\cookies\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 *
 * @internal
 */
class CookiesTextsForm extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory);

    if (!$alias_manager instanceof AliasManagerInterface) {
      // @codingStandardsIgnoreStart
      // Disabled PHPCS warning because this is just a deprecation fallback.
      $alias_manager = \Drupal::service('path_alias.manager');
      // @codingStandardsIgnoreEnd
    }

    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cookies_text_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cookies.texts'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cookies.texts');

    $form['banner'] = [
      '#type' => 'details',
      '#title' => $this->t('Banner texts'),
      '#open' => TRUE,
    ];
    $form['banner']['bannerText'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Banner details'),
      '#default_value' => $config->get('bannerText'),
      '#required' => TRUE,
    ];

    $form['links'] = [
      '#type' => 'details',
      '#title' => $this->t('Links'),
      '#open' => TRUE,
    ];
    $form['links']['privacyPolicy'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Privacy policy'),
      '#default_value' => $config->get('privacyPolicy'),
      '#description' => $this->t("Link title for privacy policy link."),
    ];
    $form['links']['privacyUri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Privacy uri'),
      '#default_value' => $config->get('privacyUri'),
      '#description' => $this->t("Link path (int./ext.) for privacy policy link, e.g. '/privacy' (int.) or 'https://www.example.com/privacy' (ext.)."),
    ];
    $form['links']['imprint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Imprint'),
      '#default_value' => $config->get('imprint'),
      '#description' => $this->t("Link title for imprint link."),
    ];
    $form['links']['imprintUri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Imprint uri'),
      '#default_value' => $config->get('imprintUri'),
      '#description' => $this->t("Link path (int./ext.) for imprint link, e.g. '/imprint' (int.) or 'https://www.example.com/imprint' (ext.)."),
    ];
    $form['links']['cookieDocs'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie documentation'),
      '#default_value' => $config->get('cookieDocs'),
      '#description' => $this->t("Link text for a cookie documentation page."),
    ];
    $form['links']['cookieDocsUri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie documentation uri'),
      '#default_value' => $config->get('cookieDocsUri'),
      '#description' => $this->t("URL for a cookie documentation (default: '/cookies/documentation') page where explicitly is described what 3rd-party services and cookies are used. This is required, if you use 'Group consent'. The default cookies documentation is also provided as a block, if you want to attach these information to an existing page."),
    ];

    $form['buttons'] = [
      '#type' => 'details',
      '#title' => $this->t('Button texts'),
      '#open' => TRUE,
    ];
    $form['buttons']['denyAll'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Deny all'),
      '#default_value' => $config->get('denyAll'),
      '#description' => $this->t("Button text 'deny all'."),
    ];
    $form['buttons']['acceptAll'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accept all'),
      '#default_value' => $config->get('acceptAll'),
      '#description' => $this->t("Button text 'accept all'."),
    ];
    $form['buttons']['settings'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Settings'),
      '#default_value' => $config->get('settings'),
      '#description' => $this->t("Button text 'Settings'."),
    ];
    $form['buttons']['saveSettings'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Save Settings'),
      '#default_value' => $config->get('saveSettings'),
      '#description' => $this->t("Button text for save button."),
    ];

    $form['dialog'] = [
      '#type' => 'details',
      '#title' => $this->t('Dialog texts'),
      '#open' => TRUE,
    ];
    $form['dialog']['cookieSettings'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog title'),
      '#default_value' => $config->get('cookieSettings'),
      '#required' => TRUE,
    ];
    $form['dialog']['close'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close'),
      '#default_value' => $config->get('close'),
      '#description' => $this->t("Close button (hover text)."),
    ];
    $form['dialog']['allowed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed'),
      '#default_value' => $config->get('allowed'),
      '#description' => $this->t("Switch title (hover text)"),
    ];
    $form['dialog']['denied'] = [
      '#type' => 'textfield',
      '#title' => $this->t('denied'),
      '#default_value' => $config->get('denied'),
      '#description' => $this->t("Switch title (hover text)."),
    ];
    $form['dialog']['requiredCookies'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Required cookies'),
      '#default_value' => $config->get('requiredCookies'),
      '#description' => $this->t("Text for 'required cookies' with grouped consent."),
    ];
    $form['dialog']['readMore'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Read more'),
      '#default_value' => $config->get('readMore'),
      '#description' => $this->t("Read more link text."),
    ];
    $form['dialog']['officialWebsite'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Official website'),
      '#default_value' => $config->get('officialWebsite'),
      '#description' => $this->t("Official website link text."),
    ];
    $form['dialog']['alwaysActive'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Always active'),
      '#default_value' => $config->get('alwaysActive'),
      '#description' => $this->t("Label replaces switch when service is always active."),
    ];

    $form['dialog']['settingsAllServices'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Settings all services'),
      '#default_value' => $config->get('settingsAllServices'),
      '#description' => $this->t("Dialog footer, label for actions."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Validate front page path.
    if (($value = $form_state->getValue('imprintUri')) && !preg_match('/^http(s)?:\/\//', $value) && $value[0] !== '/') {
      $form_state->setErrorByName('imprintUri', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('imprintUri')]));
    }
    if (!preg_match('/^http(s)?:\/\//', $value) && !$this->pathValidator->isValid($form_state->getValue('imprintUri'))) {
      $form_state->setErrorByName('imprintUri', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('imprintUri')]));
    }
    // Get the normal paths of both error pages.
    if (!$form_state->isValueEmpty('imprintUri')) {
      $form_state->setValueForElement($form['links']['imprintUri'], $this->aliasManager->getPathByAlias($form_state->getValue('imprintUri')));
    }

    // Validate privacy uri.
    if (($value = $form_state->getValue('privacyUri')) && !preg_match('/^http(s)?:\/\//', $value) && $value[0] !== '/') {
      $form_state->setErrorByName('privacyUri', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('privacyUri')]));
    }
    if (!preg_match('/^http(s)?:\/\//', $value) && !$this->pathValidator->isValid($form_state->getValue('privacyUri'))) {
      $form_state->setErrorByName('privacyUri', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('privacyUri')]));
    }
    // Get the normal paths of both error pages.
    if (!$form_state->isValueEmpty('privacyUri')) {
      $form_state->setValueForElement($form['links']['privacyUri'], $this->aliasManager->getPathByAlias($form_state->getValue('privacyUri')));
    }

    // Validate front page path.
    if (($value = $form_state->getValue('cookieDocsUri')) && !preg_match('/^http(s)?:\/\//', $value) && $value[0] !== '/') {
      $form_state->setErrorByName('cookieDocsUri', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('cookieDocsUri')]));
    }
    if (!preg_match('/^http(s)?:\/\//', $value) && !$this->pathValidator->isValid($form_state->getValue('cookieDocsUri'))) {
      $form_state->setErrorByName('cookieDocsUri', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('cookieDocsUri')]));
    }
    // Get the normal paths of both error pages.
    if (!$form_state->isValueEmpty('cookieDocsUri')) {
      $form_state->setValueForElement($form['links']['cookieDocsUri'], $this->aliasManager->getPathByAlias($form_state->getValue('cookieDocsUri')));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('cookies.texts')
      ->set('bannerText', $form_state->getValue('bannerText'))
      ->set('privacyPolicy', $form_state->getValue('privacyPolicy'))
      ->set('privacyUri', $form_state->getValue('privacyUri'))
      ->set('imprint', $form_state->getValue('imprint'))
      ->set('imprintUri', $form_state->getValue('imprintUri'))
      ->set('cookieDocs', $form_state->getValue('cookieDocs'))
      ->set('cookieDocsUri', $form_state->getValue('cookieDocsUri'))
      ->set('denyAll', $form_state->getValue('denyAll'))
      ->set('settings', $form_state->getValue('settings'))
      ->set('acceptAll', $form_state->getValue('acceptAll'))
      ->set('saveSettings', $form_state->getValue('saveSettings'))
      ->set('cookieSettings', $form_state->getValue('cookieSettings'))
      ->set('close', $form_state->getValue('close'))
      ->set('allowed', $form_state->getValue('allowed'))
      ->set('denied', $form_state->getValue('denied'))
      ->set('requiredCookies', $form_state->getValue('requiredCookies'))
      ->set('readMore', $form_state->getValue('readMore'))
      ->set('officialWebsite', $form_state->getValue('officialWebsite'))
      ->set('alwaysActive', $form_state->getValue('alwaysActive'))
      ->set('settingsAllServices', $form_state->getValue('settingsAllServices'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
