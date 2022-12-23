<?php

namespace Drupal\commerce_file\Plugin\Commerce\LicenseType;

use Drupal\commerce_file\DownloadLoggerInterface;
use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity\BundleFieldDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The file license type.
 *
 * @CommerceLicenseType(
 *   id = "commerce_file",
 *   label = @Translation("File"),
 * )
 */
class File extends LicenseTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The file download logger.
   *
   * @var \Drupal\commerce_file\DownloadLoggerInterface
   */
  protected $downloadLogger;

  /**
   * Constructs a new File object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_file\DownloadLoggerInterface $download_logger
   *   The download logger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DownloadLoggerInterface $download_logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->downloadLogger = $download_logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_file.download_logger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'file_download_limit' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $checkbox_parents = array_merge($form['#parents'], ['limit']);
    $checkbox_path = array_shift($checkbox_parents);
    $checkbox_path .= '[' . implode('][', $checkbox_parents) . ']';
    $form['limit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit the number of times a user can download a licensed file'),
      '#description' => $this->t('The value specified here overrides the globally configured download limit (Enter 0 for no limit).'),
      '#default_value' => !empty($this->configuration['file_download_limit']),
    ];
    $form['file_download_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Download limit'),
      '#title_display' => 'invisible',
      '#default_value' => $this->configuration['file_download_limit'] ?? 100,
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="' . $checkbox_path . '"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $this->configuration = [];

    if (!empty($values['limit'])) {
      $this->configuration['file_download_limit'] = $values['file_download_limit'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    return $this->t('License for file');
  }

  /**
   * {@inheritdoc}
   */
  public function grantLicense(LicenseInterface $license) {
    // Clear the download log in order to reset download limits.
    $this->downloadLogger->clear($license);
  }

  /**
   * {@inheritdoc}
   */
  public function revokeLicense(LicenseInterface $license) {}

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['file_download_limit'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Download limit'))
      ->setRequired(FALSE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_file_download_limit',
      ]);

    return $fields;
  }

}
